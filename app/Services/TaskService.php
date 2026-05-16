<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Task;
use App\Repositories\TaskRepository;

class TaskService
{
    public function __construct(private readonly TaskRepository $tasks)
    {
    }

    /** @return list<Task> */
    public function listByProject(int $projectId): array
    {
        return $this->tasks->findByProject($projectId);
    }

    public function get(int $id): ?Task
    {
        return $this->tasks->findById($id);
    }
}
