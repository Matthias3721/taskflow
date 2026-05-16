<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\User;
use PDO;

class UserRepository
{
    public function __construct(private readonly PDO $db)
    {
    }

    public function findById(int $id): ?User
    {
        $stmt = $this->db->prepare('SELECT * FROM users WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();
        return $row ? User::fromArray($row) : null;
    }

    public function findByEmail(string $email): ?User
    {
        $stmt = $this->db->prepare('SELECT * FROM users WHERE email = :email LIMIT 1');
        $stmt->execute(['email' => $email]);
        $row = $stmt->fetch();
        return $row ? User::fromArray($row) : null;
    }

    /** @return list<User> */
    public function findAll(): array
    {
        $stmt = $this->db->query('SELECT * FROM users ORDER BY id');
        $rows = $stmt->fetchAll();
        return array_map(static fn (array $row): User => User::fromArray($row), $rows);
    }
}
