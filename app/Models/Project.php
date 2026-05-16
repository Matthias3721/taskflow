<?php

declare(strict_types=1);

namespace App\Models;

class Project
{
    public function __construct(
        public readonly ?int $id,
        public readonly string $name,
        public readonly ?string $description,
        public readonly int $ownerId,
        public readonly ?string $createdAt = null,
    ) {
    }

    /** @param array<string, mixed> $row */
    public static function fromArray(array $row): self
    {
        return new self(
            isset($row['id']) ? (int) $row['id'] : null,
            (string) ($row['name'] ?? ''),
            $row['description'] ?? null,
            (int) ($row['owner_id'] ?? 0),
            $row['created_at'] ?? null,
        );
    }
}
