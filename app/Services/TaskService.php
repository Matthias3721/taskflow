<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Authorization;
use App\Models\Project;
use App\Models\Task;
use App\Repositories\CategoryRepository;
use App\Repositories\ProjectRepository;
use App\Repositories\TaskRepository;
use App\Repositories\UserRepository;

class TaskService
{
    public function __construct(
        private readonly TaskRepository $tasks,
        private readonly ProjectRepository $projects,
        private readonly UserRepository $users,
        private readonly CategoryRepository $categories,
    ) {
    }

    /** @return list<Task> */
    public function listForUser(int $userId, string $role): array
    {
        if (Authorization::isAdmin($role)) {
            return $this->tasks->findAll();
        }

        if (Authorization::isProjectManager($role)) {
            return $this->tasks->findByProjectOwner($userId);
        }

        return $this->tasks->findAssignedToUser($userId);
    }

    public function get(int $id): ?Task
    {
        return $this->tasks->findById($id);
    }

    public function canAccess(Task $task, int $userId, string $role): bool
    {
        if (Authorization::isAdmin($role)) {
            return true;
        }

        if (Authorization::isUser($role)) {
            return $task->assigneeId === $userId;
        }

        $project = $this->projects->findById($task->projectId);

        return $project !== null
            && Authorization::canManageTasksInProject($role, $userId, $project->ownerId);
    }

    /**
     * @return array{can_edit: bool, can_edit_limited: bool, can_delete: bool}
     */
    public function taskPermissions(Task $task, int $userId, string $role): array
    {
        $project = $this->projects->findById($task->projectId);
        $ownerId = $project?->ownerId ?? 0;

        $fullManage = $project !== null
            && Authorization::canManageTasksInProject($role, $userId, $ownerId);
        $limitedEdit = Authorization::canEditAssignedTask($role, $userId, $task->assigneeId);
        $canDelete = $project !== null
            && Authorization::canDeleteTask($role, $userId, $ownerId);

        return [
            'can_edit' => $fullManage || $limitedEdit,
            'can_edit_limited' => $limitedEdit && !$fullManage,
            'can_delete' => $canDelete,
        ];
    }

    /**
     * @param array<string, mixed> $data
     * @return array{task?: Task, error?: string}
     */
    public function create(array $data, int $userId, string $role): array
    {
        $validated = $this->validatePayload($data);
        if (isset($validated['error'])) {
            return $validated;
        }

        $payload = $validated['data'];
        $projectId = (int) $payload['project_id'];

        if (!$this->projects->exists($projectId)) {
            return ['error' => 'Projekt nie istnieje.'];
        }

        if (!$this->canCreateTask($projectId, $userId, $role)) {
            return ['error' => 'Brak uprawnień do tworzenia zadania w tym projekcie.'];
        }

        $assigneeError = $this->validateAssignee($payload['assignee_id']);
        if ($assigneeError !== null) {
            return ['error' => $assigneeError];
        }

        $categoryError = $this->validateCategory($payload['category_id']);
        if ($categoryError !== null) {
            return ['error' => $categoryError];
        }

        $task = $this->tasks->create(
            $payload['title'],
            $payload['description'],
            $payload['status'],
            $payload['priority'],
            $payload['due_date'],
            $projectId,
            $payload['assignee_id'],
            $payload['category_id'],
        );

        return ['task' => $task];
    }

    /**
     * @param array<string, mixed> $data
     * @return array{task?: Task, error?: string}
     */
    public function update(int $id, array $data, int $userId, string $role): array
    {
        $task = $this->tasks->findById($id);
        if ($task === null) {
            return ['error' => 'Zadanie nie istnieje.'];
        }

        $project = $this->projects->findById($task->projectId);
        if ($project === null) {
            return ['error' => 'Projekt nie istnieje.'];
        }

        if ($this->canFullEditTask($project, $userId, $role)) {
            $merged = array_merge($task->toArray(), $data);
            $validated = $this->validatePayload($merged);
            if (isset($validated['error'])) {
                return $validated;
            }

            $payload = $validated['data'];

            if (!$this->projects->userHasAccess($payload['project_id'], $userId, Authorization::isAdmin($role))) {
                return ['error' => 'Brak dostępu do projektu.'];
            }

            return $this->persistUpdate($id, $payload);
        }

        if (Authorization::canEditAssignedTask($role, $userId, $task->assigneeId)) {
            return $this->updateLimited($task, $data);
        }

        return ['error' => 'Brak uprawnień do edycji zadania.'];
    }

    /** @return array{success: bool, error?: string} */
    public function delete(int $id, int $userId, string $role): array
    {
        $task = $this->tasks->findById($id);
        if ($task === null) {
            return ['success' => false, 'error' => 'Zadanie nie istnieje.'];
        }

        $project = $this->projects->findById($task->projectId);
        if ($project === null) {
            return ['success' => false, 'error' => 'Projekt nie istnieje.'];
        }

        if (!Authorization::canDeleteTask($role, $userId, $project->ownerId)) {
            return ['success' => false, 'error' => 'Brak uprawnień do usunięcia zadania.'];
        }

        if (!$this->tasks->delete($id)) {
            return ['success' => false, 'error' => 'Nie udało się usunąć zadania.'];
        }

        return ['success' => true];
    }

    private function canCreateTask(int $projectId, int $userId, string $role): bool
    {
        if (Authorization::isAdmin($role)) {
            return true;
        }

        $project = $this->projects->findById($projectId);
        if ($project === null) {
            return false;
        }

        return Authorization::canManageTasksInProject($role, $userId, $project->ownerId);
    }

    private function canFullEditTask(Project $project, int $userId, string $role): bool
    {
        return Authorization::canManageTasksInProject($role, $userId, $project->ownerId);
    }

    /**
     * @param array<string, mixed> $data
     * @return array{task?: Task, error?: string}
     */
    private function updateLimited(Task $task, array $data): array
    {
        $status = $this->normalizeStatus((string) ($data['status'] ?? $task->status));
        if ($status === null) {
            return ['error' => 'Nieprawidłowy status zadania.'];
        }

        $description = array_key_exists('description', $data)
            ? $this->normalizeDescription($data['description'])
            : $task->description;

        $payload = [
            'title' => $task->title,
            'description' => $description,
            'status' => $status,
            'priority' => $task->priority,
            'due_date' => $task->dueDate,
            'project_id' => $task->projectId,
            'assignee_id' => $task->assigneeId,
            'category_id' => $task->categoryId,
        ];

        return $this->persistUpdate((int) $task->id, $payload);
    }

    /**
     * @param array<string, mixed> $payload
     * @return array{task?: Task, error?: string}
     */
    private function persistUpdate(int $id, array $payload): array
    {
        $assigneeError = $this->validateAssignee($payload['assignee_id']);
        if ($assigneeError !== null) {
            return ['error' => $assigneeError];
        }

        $categoryError = $this->validateCategory($payload['category_id']);
        if ($categoryError !== null) {
            return ['error' => $categoryError];
        }

        $updated = $this->tasks->update(
            $id,
            $payload['title'],
            $payload['description'],
            $payload['status'],
            $payload['priority'],
            $payload['due_date'],
            (int) $payload['project_id'],
            $payload['assignee_id'],
            $payload['category_id'],
        );

        if ($updated === null) {
            return ['error' => 'Nie udało się zaktualizować zadania.'];
        }

        return ['task' => $updated];
    }

    /**
     * @param array<string, mixed> $data
     * @return array{data?: array<string, mixed>, error?: string}
     */
    private function validatePayload(array $data): array
    {
        $title = trim((string) ($data['title'] ?? ''));
        if ($title === '') {
            return ['error' => 'Tytuł zadania jest wymagany.'];
        }

        $status = $this->normalizeStatus((string) ($data['status'] ?? 'todo'));
        if ($status === null) {
            return ['error' => 'Nieprawidłowy status zadania.'];
        }

        $priority = $this->normalizePriority((string) ($data['priority'] ?? 'medium'));
        if ($priority === null) {
            return ['error' => 'Nieprawidłowy priorytet zadania.'];
        }

        $projectId = $data['project_id'] ?? null;
        if ($projectId === null || $projectId === '' || !is_numeric($projectId)) {
            return ['error' => 'Projekt jest wymagany.'];
        }

        $assigneeId = $this->normalizeAssigneeId($data['assignee_id'] ?? null);
        if ($assigneeId === false) {
            return ['error' => 'Nieprawidłowy identyfikator osoby przypisanej.'];
        }

        $dueDate = $this->normalizeDueDate($data['due_date'] ?? null);
        if ($dueDate === false) {
            return ['error' => 'Nieprawidłowa data terminu.'];
        }

        $description = $this->normalizeDescription($data['description'] ?? null);

        $categoryId = $this->normalizeCategoryId($data['category_id'] ?? null);
        if ($categoryId === false) {
            return ['error' => 'Nieprawidłowy identyfikator kategorii.'];
        }

        return [
            'data' => [
                'title' => $title,
                'description' => $description,
                'status' => $status,
                'priority' => $priority,
                'due_date' => $dueDate,
                'project_id' => (int) $projectId,
                'assignee_id' => $assigneeId,
                'category_id' => $categoryId,
            ],
        ];
    }

    private function validateCategory(?int $categoryId): ?string
    {
        if ($categoryId === null) {
            return null;
        }

        if (!$this->categories->exists($categoryId)) {
            return 'Wybrana kategoria nie istnieje.';
        }

        return null;
    }

    private function validateAssignee(?int $assigneeId): ?string
    {
        if ($assigneeId === null) {
            return null;
        }

        if ($this->users->findById($assigneeId) === null) {
            return 'Wybrany użytkownik nie istnieje.';
        }

        return null;
    }

    private function normalizeStatus(string $status): ?string
    {
        $status = strtolower(trim($status));

        return in_array($status, Task::STATUSES, true) ? $status : null;
    }

    private function normalizePriority(string $priority): ?string
    {
        $priority = strtolower(trim($priority));

        return in_array($priority, Task::PRIORITIES, true) ? $priority : null;
    }

    private function normalizeDescription(mixed $description): ?string
    {
        if ($description === null || $description === '') {
            return null;
        }

        return trim((string) $description);
    }

    private function normalizeDueDate(mixed $dueDate): string|null|false
    {
        if ($dueDate === null || $dueDate === '') {
            return null;
        }

        $dueDate = trim((string) $dueDate);
        $date = \DateTimeImmutable::createFromFormat('Y-m-d', $dueDate);

        if ($date === false || $date->format('Y-m-d') !== $dueDate) {
            return false;
        }

        return $dueDate;
    }

    /** @return int|null|false */
    private function normalizeAssigneeId(mixed $assigneeId): int|null|false
    {
        if ($assigneeId === null || $assigneeId === '') {
            return null;
        }

        if (!is_numeric($assigneeId)) {
            return false;
        }

        return (int) $assigneeId;
    }

    /** @return int|null|false */
    private function normalizeCategoryId(mixed $categoryId): int|null|false
    {
        if ($categoryId === null || $categoryId === '') {
            return null;
        }

        if (!is_numeric($categoryId)) {
            return false;
        }

        return (int) $categoryId;
    }
}
