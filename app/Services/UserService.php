<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\User;
use App\Repositories\UserRepository;

class UserService
{
    public function __construct(private readonly UserRepository $users)
    {
    }

    /** @return list<User> */
    public function listAll(): array
    {
        return $this->users->findAll();
    }

    public function get(int $id): ?User
    {
        return $this->users->findById($id);
    }
}
