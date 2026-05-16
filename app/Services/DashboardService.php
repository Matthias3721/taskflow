<?php

declare(strict_types=1);

namespace App\Services;

use App\Repositories\DashboardRepository;

class DashboardService
{
    public function __construct(private readonly DashboardRepository $dashboard)
    {
    }

    /**
     * @return array{
     *     total_projects: int,
     *     total_tasks: int,
     *     done_tasks: int,
     *     projects_progress: list<array<string, mixed>>
     * }
     */
    public function getStats(int $userId, bool $isAdmin): array
    {
        $rows = $this->dashboard->findProjectsProgress($isAdmin, $userId);

        $projectsProgress = array_map(static function (array $row): array {
            return [
                'project_id' => (int) $row['project_id'],
                'project_name' => (string) $row['project_name'],
                'owner_name' => (string) $row['owner_name'],
                'total_tasks' => (int) $row['total_tasks'],
                'done_tasks' => (int) $row['done_tasks'],
                'progress_percent' => (float) $row['progress_percent'],
            ];
        }, $rows);

        $totalTasks = 0;
        $doneTasks = 0;
        foreach ($projectsProgress as $project) {
            $totalTasks += $project['total_tasks'];
            $doneTasks += $project['done_tasks'];
        }

        return [
            'total_projects' => count($projectsProgress),
            'total_tasks' => $totalTasks,
            'done_tasks' => $doneTasks,
            'projects_progress' => $projectsProgress,
        ];
    }
}
