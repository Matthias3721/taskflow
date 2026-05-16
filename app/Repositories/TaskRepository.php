<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\Task;
use PDO;

class TaskRepository
{
    public function __construct(private readonly PDO $db)
    {
    }

    public function findById(int $id): ?Task
    {
        $stmt = $this->db->prepare('SELECT * FROM tasks WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();
        return $row ? Task::fromArray($row) : null;
    }

    /** @return list<Task> */
    public function findByProject(int $projectId): array
    {
        $stmt = $this->db->prepare('SELECT * FROM tasks WHERE project_id = :project_id ORDER BY id');
        $stmt->execute(['project_id' => $projectId]);
        $rows = $stmt->fetchAll();
        return array_map(static fn (array $row): Task => Task::fromArray($row), $rows);
    }
}
