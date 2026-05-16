<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\User;
use PDO;

class UserRepository
{
    private const USER_SELECT = '
        SELECT u.*, r.name AS role_name
        FROM users u
        INNER JOIN roles r ON r.id = u.role_id
    ';

    public function __construct(private readonly PDO $db)
    {
    }

    public function findById(int $id): ?User
    {
        $stmt = $this->db->prepare(self::USER_SELECT . ' WHERE u.id = :id LIMIT 1');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();
        return $row ? User::fromArray($row) : null;
    }

    public function findByEmail(string $email): ?User
    {
        $stmt = $this->db->prepare(
            self::USER_SELECT . ' WHERE u.email = :email AND u.is_active = TRUE LIMIT 1',
        );
        $stmt->execute(['email' => $email]);
        $row = $stmt->fetch();
        return $row ? User::fromArray($row) : null;
    }

    /** @return list<User> */
    public function findAll(): array
    {
        $stmt = $this->db->query(self::USER_SELECT . ' ORDER BY u.id');
        $rows = $stmt->fetchAll();
        return array_map(static fn (array $row): User => User::fromArray($row), $rows);
    }

    /** @return list<array{id: int, name: string}> */
    public function findOptions(): array
    {
        $stmt = $this->db->query(
            'SELECT id, name FROM users WHERE is_active = TRUE ORDER BY name',
        );
        $rows = $stmt->fetchAll();

        return array_map(static fn (array $row): array => [
            'id' => (int) $row['id'],
            'name' => (string) $row['name'],
        ], $rows);
    }
}
