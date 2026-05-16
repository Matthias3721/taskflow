<?php

declare(strict_types=1);

namespace App\Repositories;

use PDO;

class DashboardRepository
{
    public function __construct(private readonly PDO $db)
    {
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function findProjectsProgress(bool $isAdmin, int $userId): array
    {
        if ($isAdmin) {
            $stmt = $this->db->query(
                'SELECT project_id, project_name, owner_name, total_tasks, done_tasks, progress_percent
                 FROM view_project_progress
                 ORDER BY project_name',
            );
        } else {
            $stmt = $this->db->prepare(
                'SELECT v.project_id, v.project_name, v.owner_name, v.total_tasks, v.done_tasks, v.progress_percent
                 FROM view_project_progress v
                 INNER JOIN projects p ON p.id = v.project_id
                 WHERE p.owner_id = :user_id
                    OR EXISTS (
                        SELECT 1 FROM project_members pm
                        WHERE pm.project_id = p.id AND pm.user_id = :user_id
                    )
                 ORDER BY v.project_name',
            );
            $stmt->execute(['user_id' => $userId]);
        }

        $rows = $stmt->fetchAll();

        return $rows !== false ? $rows : [];
    }
}
