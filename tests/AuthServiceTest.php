<?php

declare(strict_types=1);

namespace Tests;

use App\Core\Session;
use App\Models\User;
use App\Repositories\UserRepository;
use App\Services\AuthService;
use PHPUnit\Framework\TestCase;

class AuthServiceTest extends TestCase
{
    /** @param array<string, mixed> $config */
    private function sessionMock(array &$bag, array $config = ['session' => ['name' => 'test_session']]): Session
    {
        $session = $this->createMock(Session::class);
        $session->method('set')->willReturnCallback(static function (string $key, mixed $value) use (&$bag): void {
            $bag[$key] = $value;
        });
        $session->method('get')->willReturnCallback(static function (string $key, mixed $default = null) use (&$bag): mixed {
            return $bag[$key] ?? $default;
        });
        $session->method('has')->willReturnCallback(static function (string $key) use (&$bag): bool {
            return isset($bag[$key]);
        });
        $session->method('destroy')->willReturnCallback(static function () use (&$bag): void {
            $bag = [];
        });

        return $session;
    }

    public function testAttemptFailsForUnknownEmail(): void
    {
        $bag = [];
        $users = $this->createMock(UserRepository::class);
        $users->method('findByEmail')->with('unknown@test')->willReturn(null);

        $service = new AuthService($users, $this->sessionMock($bag));

        $this->assertFalse($service->attempt('unknown@test', 'password'));
        $this->assertFalse($service->isAuthenticated());
    }

    public function testAttemptFailsForInvalidPassword(): void
    {
        $bag = [];
        $hash = password_hash('correct', PASSWORD_DEFAULT);
        $user = new User(1, 'user@test', 'User', 'user', $hash);

        $users = $this->createMock(UserRepository::class);
        $users->method('findByEmail')->willReturn($user);

        $service = new AuthService($users, $this->sessionMock($bag));

        $this->assertFalse($service->attempt('user@test', 'wrong'));
        $this->assertFalse($service->isAuthenticated());
    }

    public function testAttemptSucceedsAndStoresSession(): void
    {
        $bag = [];
        $hash = password_hash('secret123', PASSWORD_DEFAULT);
        $user = new User(2, 'admin@test', 'Admin', 'admin', $hash);

        $users = $this->createMock(UserRepository::class);
        $users->method('findByEmail')->with('admin@test')->willReturn($user);

        $session = $this->sessionMock($bag);
        $session->expects($this->exactly(4))->method('set');

        $service = new AuthService($users, $session);

        $this->assertTrue($service->attempt('admin@test', 'secret123'));
        $this->assertTrue($service->isAuthenticated());
        $this->assertSame([
            'id' => 2,
            'name' => 'Admin',
            'email' => 'admin@test',
            'role' => 'admin',
        ], $service->currentUser());
    }

    public function testCurrentUserReturnsNullWhenNotAuthenticated(): void
    {
        $bag = [];
        $users = $this->createMock(UserRepository::class);
        $service = new AuthService($users, $this->sessionMock($bag));

        $this->assertNull($service->currentUser());
    }

    public function testLogoutClearsSession(): void
    {
        $bag = ['user_id' => 1, 'user_name' => 'A', 'user_email' => 'a@test', 'user_role' => 'user'];
        $users = $this->createMock(UserRepository::class);
        $session = $this->sessionMock($bag);
        $session->expects($this->once())->method('destroy');

        $service = new AuthService($users, $session);
        $service->logout();

        $this->assertFalse($service->isAuthenticated());
    }
}
