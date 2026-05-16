<?php

declare(strict_types=1);

namespace App\Models;

class Task
{
    public function __construct(
        public readonly ?int $id,
        public readonly string $title,
        public readonly ?string $description,
        public readonly string $status,
        public readonly int $projectId,
        public readonly ?int $assigneeId,
        public readonly ?int $categoryId,
        public readonly ?string $dueDate = null,
        public readonly ?string $createdAt = null,
    ) {
    }

    /** @param array<string, mixed> $row */
    public static function fromArray(array $row): self
    {
        return new self(
            isset($row['id']) ? (int) $row['id'] : null,
            (string) ($row['title'] ?? ''),
            $row['description'] ?? null,
            (string) ($row['status'] ?? 'todo'),
            (int) ($row['project_id'] ?? 0),
            isset($row['assignee_id']) ? (int) $row['assignee_id'] : null,
            isset($row['category_id']) ? (int) $row['category_id'] : null,
            $row['due_date'] ?? null,
            $row['created_at'] ?? null,
        );
    }
}
