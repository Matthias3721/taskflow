<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Authorization;
use App\Models\Project;
use App\Repositories\ProjectRepository;

class ProjectService
{
    public function __construct(private readonly ProjectRepository $projects)
    {
    }

    /** @return list<Project> */
    public function listForUser(int $userId, string $role): array
    {
        if (Authorization::isAdmin($role)) {
            return $this->projects->findAll();
        }

        if (Authorization::isProjectManager($role)) {
            return $this->projects->findByOwner($userId);
        }

        return $this->projects->findAccessibleForUser($userId);
    }

    public function get(int $id): ?Project
    {
        return $this->projects->findById($id);
    }

    public function canManage(Project $project, int $userId, string $role): bool
    {
        return Authorization::canManageProject($role, $userId, $project->ownerId);
    }

    public function canView(Project $project, int $userId, string $role): bool
    {
        if (Authorization::isAdmin($role)) {
            return true;
        }

        return $this->projects->userHasAccess($project->id, $userId, false);
    }

    /**
     * @return array{can_edit: bool, can_delete: bool}
     */
    public function projectPermissions(Project $project, int $userId, string $role): array
    {
        $canManage = $this->canManage($project, $userId, $role);

        return [
            'can_edit' => $canManage,
            'can_delete' => $canManage,
        ];
    }

    /**
     * @param array{name?: string, description?: string|null, status?: string} $data
     * @return array{project?: Project, error?: string}
     */
    public function create(array $data, int $ownerId): array
    {
        $name = trim((string) ($data['name'] ?? ''));
        if ($name === '') {
            return ['error' => 'Nazwa projektu jest wymagana.'];
        }

        $status = $this->normalizeStatus((string) ($data['status'] ?? 'active'));
        if ($status === null) {
            return ['error' => 'Nieprawidłowy status projektu.'];
        }

        $description = $this->normalizeDescription($data['description'] ?? null);

        $project = $this->projects->create($name, $description, $status, $ownerId);

        return ['project' => $project];
    }

    /**
     * @param array{name?: string, description?: string|null, status?: string} $data
     * @return array{project?: Project, error?: string}
     */
    public function update(int $id, array $data, int $userId, string $role): array
    {
        $project = $this->projects->findById($id);
        if ($project === null) {
            return ['error' => 'Projekt nie istnieje.'];
        }

        if (!$this->canManage($project, $userId, $role)) {
            return ['error' => 'Brak uprawnień do edycji projektu.'];
        }

        $name = trim((string) ($data['name'] ?? $project->name));
        if ($name === '') {
            return ['error' => 'Nazwa projektu jest wymagana.'];
        }

        $status = $this->normalizeStatus((string) ($data['status'] ?? $project->status));
        if ($status === null) {
            return ['error' => 'Nieprawidłowy status projektu.'];
        }

        $description = array_key_exists('description', $data)
            ? $this->normalizeDescription($data['description'])
            : $project->description;

        $updated = $this->projects->update($id, $name, $description, $status);
        if ($updated === null) {
            return ['error' => 'Nie udało się zaktualizować projektu.'];
        }

        return ['project' => $updated];
    }

    /** @return array{success: bool, error?: string} */
    public function delete(int $id, int $userId, string $role): array
    {
        $project = $this->projects->findById($id);
        if ($project === null) {
            return ['success' => false, 'error' => 'Projekt nie istnieje.'];
        }

        if (!$this->canManage($project, $userId, $role)) {
            return ['success' => false, 'error' => 'Brak uprawnień do usunięcia projektu.'];
        }

        if (!$this->projects->delete($id)) {
            return ['success' => false, 'error' => 'Nie udało się usunąć projektu.'];
        }

        return ['success' => true];
    }

    private function normalizeStatus(string $status): ?string
    {
        $status = strtolower(trim($status));

        return in_array($status, Project::STATUSES, true) ? $status : null;
    }

    private function normalizeDescription(mixed $description): ?string
    {
        if ($description === null || $description === '') {
            return null;
        }

        return trim((string) $description);
    }
}
