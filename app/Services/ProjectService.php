<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Project;
use App\Repositories\ProjectRepository;

class ProjectService
{
    public function __construct(private readonly ProjectRepository $projects)
    {
    }

    /** @return list<Project> */
    public function listForUser(int $userId, bool $isAdmin = false): array
    {
        return $isAdmin
            ? $this->projects->findAll()
            : $this->projects->findByOwner($userId);
    }

    public function get(int $id): ?Project
    {
        return $this->projects->findById($id);
    }
}
