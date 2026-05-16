<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Session;
use App\Repositories\UserRepository;

class AuthService
{
    public function __construct(
        private readonly UserRepository $users,
        private readonly Session $session,
    ) {
    }

    public function attempt(string $email, string $password): bool
    {
        $user = $this->users->findByEmail($email);
        if ($user === null || $user->passwordHash === null) {
            return false;
        }
        if (!password_verify($password, $user->passwordHash)) {
            return false;
        }

        $this->session->set('user_id', $user->id);
        $this->session->set('user_name', $user->name);
        $this->session->set('user_email', $user->email);
        $this->session->set('user_role', $user->role);

        return true;
    }

    public function logout(): void
    {
        $this->session->destroy();
    }

    public function isAuthenticated(): bool
    {
        return $this->session->has('user_id');
    }

    /** @return array{id: int, name: string, email: string, role: string}|null */
    public function currentUser(): ?array
    {
        if (!$this->isAuthenticated()) {
            return null;
        }

        return [
            'id' => (int) $this->session->get('user_id'),
            'name' => (string) $this->session->get('user_name'),
            'email' => (string) $this->session->get('user_email'),
            'role' => (string) $this->session->get('user_role'),
        ];
    }
}
