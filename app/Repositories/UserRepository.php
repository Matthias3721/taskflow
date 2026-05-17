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

    public function findRoleIdByName(string $roleName): ?int
    {
        $stmt = $this->db->prepare('SELECT id FROM roles WHERE name = :name LIMIT 1');
        $stmt->execute(['name' => $roleName]);
        $id = $stmt->fetchColumn();

        return $id !== false ? (int) $id : null;
    }

    public function updateRole(int $userId, int $roleId): ?User
    {
        $stmt = $this->db->prepare(
            'UPDATE users SET role_id = :role_id, updated_at = CURRENT_TIMESTAMP WHERE id = :id RETURNING id',
        );
        $stmt->execute(['id' => $userId, 'role_id' => $roleId]);
        if ($stmt->fetchColumn() === false) {
            return null;
        }

        return $this->findById($userId);
    }

    public function updateStatus(int $userId, bool $isActive): ?User
    {
        $stmt = $this->db->prepare(
            'UPDATE users
             SET is_active = :is_active, updated_at = CURRENT_TIMESTAMP
             WHERE id = :id
             RETURNING id',
        );
        $stmt->bindValue(':id', $userId, PDO::PARAM_INT);
        $stmt->bindValue(':is_active', $isActive, PDO::PARAM_BOOL);
        $stmt->execute();

        if ($stmt->fetchColumn() === false) {
            return null;
        }

        return $this->findById($userId);
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
