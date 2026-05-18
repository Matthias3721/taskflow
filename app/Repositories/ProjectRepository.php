<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\Project;
use PDO;

class ProjectRepository
{
    public function __construct(private readonly PDO $db)
    {
    }

    public function findById(int $id): ?Project
    {
        $stmt = $this->db->prepare('SELECT * FROM projects WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();
        return $row ? Project::fromArray($row) : null;
    }

    public function exists(int $id): bool
    {
        $stmt = $this->db->prepare('SELECT 1 FROM projects WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $id]);

        return (bool) $stmt->fetchColumn();
    }

    public function userHasAccess(int $projectId, int $userId, bool $isAdmin): bool
    {
        if ($isAdmin) {
            return $this->exists($projectId);
        }

        $stmt = $this->db->prepare(
            'SELECT 1 FROM projects p
             WHERE p.id = :project_id
               AND (
                   p.owner_id = :user_id
                   OR EXISTS (
                       SELECT 1 FROM project_members pm
                       WHERE pm.project_id = p.id AND pm.user_id = :user_id
                   )
               )
             LIMIT 1',
        );
        $stmt->execute([
            'project_id' => $projectId,
            'user_id' => $userId,
        ]);

        return (bool) $stmt->fetchColumn();
    }

    /** @return list<Project> */
    public function findAccessibleForUser(int $userId): array
    {
        $stmt = $this->db->prepare(
            'SELECT DISTINCT p.*
             FROM projects p
             LEFT JOIN project_members pm ON pm.project_id = p.id AND pm.user_id = :user_id
             WHERE p.owner_id = :user_id OR pm.user_id IS NOT NULL
             ORDER BY p.created_at DESC, p.id DESC',
        );
        $stmt->execute(['user_id' => $userId]);
        $rows = $stmt->fetchAll();

        return array_map(static fn (array $row): Project => Project::fromArray($row), $rows);
    }

    /** @return list<Project> */
    public function findByOwner(int $ownerId): array
    {
        $stmt = $this->db->prepare(
            'SELECT * FROM projects WHERE owner_id = :owner_id ORDER BY created_at DESC, id DESC',
        );
        $stmt->execute(['owner_id' => $ownerId]);
        $rows = $stmt->fetchAll();
        return array_map(static fn (array $row): Project => Project::fromArray($row), $rows);
    }

    /** @return list<Project> */
    public function findAll(): array
    {
        $stmt = $this->db->query(
            'SELECT * FROM projects ORDER BY created_at DESC, id DESC',
        );
        $rows = $stmt->fetchAll();
        return array_map(static fn (array $row): Project => Project::fromArray($row), $rows);
    }

    public function create(string $name, ?string $description, string $status, int $ownerId): Project
    {
        $stmt = $this->db->prepare(
            'INSERT INTO projects (name, description, status, owner_id)
             VALUES (:name, :description, :status, :owner_id)
             RETURNING *',
        );
        $stmt->execute([
            'name' => $name,
            'description' => $description,
            'status' => $status,
            'owner_id' => $ownerId,
        ]);
        $row = $stmt->fetch();
        if ($row === false) {
            throw new \RuntimeException('Nie udało się utworzyć projektu.');
        }

        return Project::fromArray($row);
    }

    public function update(int $id, string $name, ?string $description, string $status): ?Project
    {
        $stmt = $this->db->prepare(
            'UPDATE projects
             SET name = :name, description = :description, status = :status, updated_at = CURRENT_TIMESTAMP
             WHERE id = :id
             RETURNING *',
        );
        $stmt->execute([
            'id' => $id,
            'name' => $name,
            'description' => $description,
            'status' => $status,
        ]);
        $row = $stmt->fetch();

        return $row ? Project::fromArray($row) : null;
    }

    public function delete(int $id): bool
    {
        $stmt = $this->db->prepare('DELETE FROM projects WHERE id = :id');
        $stmt->execute(['id' => $id]);

        return $stmt->rowCount() > 0;
    }
}
