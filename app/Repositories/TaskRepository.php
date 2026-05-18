<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\Task;
use PDO;

class TaskRepository
{
    private const TASK_SELECT = '
        SELECT t.*, c.name AS category_name, c.color AS category_color
        FROM tasks t
        LEFT JOIN categories c ON c.id = t.category_id
    ';

    public function __construct(private readonly PDO $db)
    {
    }

    public function findById(int $id): ?Task
    {
        $stmt = $this->db->prepare(self::TASK_SELECT . ' WHERE t.id = :id LIMIT 1');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();

        return $row ? Task::fromArray($row) : null;
    }

    /** @return list<Task> */
    public function findAll(): array
    {
        $stmt = $this->db->query(
            self::TASK_SELECT . ' ORDER BY t.created_at DESC, t.id DESC',
        );
        $rows = $stmt->fetchAll();

        return array_map(static fn (array $row): Task => Task::fromArray($row), $rows);
    }

    /** @return list<Task> */
    public function findByProjectOwner(int $ownerId): array
    {
        $stmt = $this->db->prepare(
            self::TASK_SELECT . '
             INNER JOIN projects p ON p.id = t.project_id
             WHERE p.owner_id = :owner_id
             ORDER BY t.created_at DESC, t.id DESC',
        );
        $stmt->execute(['owner_id' => $ownerId]);
        $rows = $stmt->fetchAll();

        return array_map(static fn (array $row): Task => Task::fromArray($row), $rows);
    }

    /** @return list<Task> */
    public function findAssignedToUser(int $userId): array
    {
        $stmt = $this->db->prepare(
            self::TASK_SELECT . '
             WHERE t.assignee_id = :user_id
             ORDER BY t.created_at DESC, t.id DESC',
        );
        $stmt->execute(['user_id' => $userId]);
        $rows = $stmt->fetchAll();

        return array_map(static fn (array $row): Task => Task::fromArray($row), $rows);
    }

    /** @return list<Task> */
    public function findAccessibleByUser(int $userId): array
    {
        $stmt = $this->db->prepare(
            self::TASK_SELECT . '
             INNER JOIN projects p ON p.id = t.project_id
             WHERE p.owner_id = :user_id
                OR EXISTS (
                    SELECT 1 FROM project_members pm
                    WHERE pm.project_id = p.id AND pm.user_id = :user_id
                )
             ORDER BY t.created_at DESC, t.id DESC',
        );
        $stmt->execute(['user_id' => $userId]);
        $rows = $stmt->fetchAll();

        return array_map(static fn (array $row): Task => Task::fromArray($row), $rows);
    }

    /** @return list<Task> */
    public function findByProject(int $projectId): array
    {
        $stmt = $this->db->prepare(
            self::TASK_SELECT . ' WHERE t.project_id = :project_id ORDER BY t.created_at DESC, t.id DESC',
        );
        $stmt->execute(['project_id' => $projectId]);
        $rows = $stmt->fetchAll();

        return array_map(static fn (array $row): Task => Task::fromArray($row), $rows);
    }

    public function create(
        string $title,
        ?string $description,
        string $status,
        string $priority,
        ?string $dueDate,
        int $projectId,
        ?int $assigneeId,
        ?int $categoryId,
    ): Task {
        $stmt = $this->db->prepare(
            'INSERT INTO tasks (title, description, status, priority, due_date, project_id, assignee_id, category_id)
             VALUES (:title, :description, :status, :priority, :due_date, :project_id, :assignee_id, :category_id)
             RETURNING *',
        );
        $stmt->execute([
            'title' => $title,
            'description' => $description,
            'status' => $status,
            'priority' => $priority,
            'due_date' => $dueDate,
            'project_id' => $projectId,
            'assignee_id' => $assigneeId,
            'category_id' => $categoryId,
        ]);
        $row = $stmt->fetch();
        if ($row === false) {
            throw new \RuntimeException('Nie udało się utworzyć zadania.');
        }

        return $this->findById((int) $row['id']) ?? Task::fromArray($row);
    }

    public function update(
        int $id,
        string $title,
        ?string $description,
        string $status,
        string $priority,
        ?string $dueDate,
        int $projectId,
        ?int $assigneeId,
        ?int $categoryId,
    ): ?Task {
        $stmt = $this->db->prepare(
            'UPDATE tasks
             SET title = :title,
                 description = :description,
                 status = :status,
                 priority = :priority,
                 due_date = :due_date,
                 project_id = :project_id,
                 assignee_id = :assignee_id,
                 category_id = :category_id,
                 updated_at = CURRENT_TIMESTAMP
             WHERE id = :id
             RETURNING *',
        );
        $stmt->execute([
            'id' => $id,
            'title' => $title,
            'description' => $description,
            'status' => $status,
            'priority' => $priority,
            'due_date' => $dueDate,
            'project_id' => $projectId,
            'assignee_id' => $assigneeId,
            'category_id' => $categoryId,
        ]);
        $row = $stmt->fetch();

        return $row ? ($this->findById($id) ?? Task::fromArray($row)) : null;
    }

    public function delete(int $id): bool
    {
        $stmt = $this->db->prepare('DELETE FROM tasks WHERE id = :id');
        $stmt->execute(['id' => $id]);

        return $stmt->rowCount() > 0;
    }
}
