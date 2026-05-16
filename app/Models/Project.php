<?php

declare(strict_types=1);

namespace App\Models;

class Project
{
    public const STATUSES = ['active', 'on_hold', 'completed'];

    public function __construct(
        public readonly ?int $id,
        public readonly string $name,
        public readonly ?string $description,
        public readonly int $ownerId,
        public readonly string $status = 'active',
        public readonly ?string $createdAt = null,
        public readonly ?string $updatedAt = null,
    ) {
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'description' => $this->description,
            'status' => $this->status,
            'owner_id' => $this->ownerId,
            'created_at' => $this->createdAt,
            'updated_at' => $this->updatedAt,
        ];
    }

    /** @param array<string, mixed> $row */
    public static function fromArray(array $row): self
    {
        return new self(
            isset($row['id']) ? (int) $row['id'] : null,
            (string) ($row['name'] ?? ''),
            $row['description'] ?? null,
            (int) ($row['owner_id'] ?? 0),
            (string) ($row['status'] ?? 'active'),
            $row['created_at'] ?? null,
            $row['updated_at'] ?? null,
        );
    }
}
