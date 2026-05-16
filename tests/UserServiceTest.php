<?php

declare(strict_types=1);

namespace Tests;

use App\Models\User;
use App\Repositories\UserRepository;
use App\Services\UserService;
use PHPUnit\Framework\TestCase;

class UserServiceTest extends TestCase
{
    public function testListAllReturnsUsersFromRepository(): void
    {
        $user = new User(1, 'test@example.com', 'Test User', 'user');

        $repository = $this->createMock(UserRepository::class);
        $repository->method('findAll')->willReturn([$user]);

        $service = new UserService($repository);
        $result = $service->listAll();

        $this->assertCount(1, $result);
        $this->assertSame('test@example.com', $result[0]->email);
    }

    public function testGetReturnsNullWhenUserNotFound(): void
    {
        $repository = $this->createMock(UserRepository::class);
        $repository->method('findById')->with(99)->willReturn(null);

        $service = new UserService($repository);
        $this->assertNull($service->get(99));
    }
}
