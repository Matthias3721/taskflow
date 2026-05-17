<?php

declare(strict_types=1);

namespace App\Models;

class User
{
    public const ROLES = ['admin', 'project_manager', 'user'];

    public function __construct(
        public readonly ?int $id,
        public readonly string $email,
        public readonly string $name,
        public readonly string $role = 'user',
        public readonly ?string $passwordHash = null,
        public readonly ?string $createdAt = null,
        public readonly bool $isActive = true,
    ) {
    }

    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'role' => $this->role,
            'is_active' => $this->isActive,
            'created_at' => $this->createdAt,
        ];
    }

    /** @param array<string, mixed> $row */
    public static function fromArray(array $row): self
    {
        return new self(
            isset($row['id']) ? (int) $row['id'] : null,
            (string) ($row['email'] ?? ''),
            (string) ($row['name'] ?? ''),
            (string) ($row['role_name'] ?? $row['role'] ?? 'user'),
            $row['password_hash'] ?? $row['password'] ?? null,
            $row['created_at'] ?? null,
            (bool) ($row['is_active'] ?? true),
        );
    }
}
