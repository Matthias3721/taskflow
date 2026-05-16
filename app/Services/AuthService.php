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
        $this->session->set('user_role', $user->role);
        $this->session->set('user_name', $user->name);

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
}
