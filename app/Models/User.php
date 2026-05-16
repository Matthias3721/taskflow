<?php

declare(strict_types=1);

namespace App\Models;

class User
{
    public function __construct(
        public readonly ?int $id,
        public readonly string $email,
        public readonly string $name,
        public readonly string $role = 'user',
        public readonly ?string $passwordHash = null,
        public readonly ?string $createdAt = null,
    ) {
    }

    public function isAdmin(): bool
    {
        return $this->role === 'admin';
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
        );
    }
}
