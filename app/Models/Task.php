<?php

declare(strict_types=1);

namespace App\Models;

class Task
{
    public const STATUSES = ['todo', 'in_progress', 'done'];
    public const PRIORITIES = ['low', 'medium', 'high'];

    public function __construct(
        public readonly ?int $id,
        public readonly string $title,
        public readonly ?string $description,
        public readonly string $status,
        public readonly string $priority,
        public readonly int $projectId,
        public readonly ?int $assigneeId,
        public readonly ?string $dueDate = null,
        public readonly ?int $categoryId = null,
        public readonly ?string $createdAt = null,
        public readonly ?string $updatedAt = null,
    ) {
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'description' => $this->description,
            'status' => $this->status,
            'priority' => $this->priority,
            'due_date' => $this->dueDate,
            'project_id' => $this->projectId,
            'assignee_id' => $this->assigneeId,
            'created_at' => $this->createdAt,
            'updated_at' => $this->updatedAt,
        ];
    }

    /** @param array<string, mixed> $row */
    public static function fromArray(array $row): self
    {
        return new self(
            isset($row['id']) ? (int) $row['id'] : null,
            (string) ($row['title'] ?? ''),
            $row['description'] ?? null,
            (string) ($row['status'] ?? 'todo'),
            (string) ($row['priority'] ?? 'medium'),
            (int) ($row['project_id'] ?? 0),
            isset($row['assignee_id']) ? (int) $row['assignee_id'] : null,
            $row['due_date'] ?? null,
            isset($row['category_id']) ? (int) $row['category_id'] : null,
            $row['created_at'] ?? null,
            $row['updated_at'] ?? null,
        );
    }
}
