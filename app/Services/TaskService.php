<?php

declare(strict_types=1);

namespace App\Services;

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
    public function listForUser(int $userId, bool $isAdmin): array
    {
        return $isAdmin
            ? $this->tasks->findAll()
            : $this->tasks->findAccessibleByUser($userId);
    }

    public function get(int $id): ?Task
    {
        return $this->tasks->findById($id);
    }

    public function canAccess(Task $task, int $userId, bool $isAdmin): bool
    {
        return $this->projects->userHasAccess($task->projectId, $userId, $isAdmin);
    }

    /**
     * @param array<string, mixed> $data
     * @return array{task?: Task, error?: string}
     */
    public function create(array $data, int $userId, bool $isAdmin): array
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

        if (!$this->projects->userHasAccess($projectId, $userId, $isAdmin)) {
            return ['error' => 'Brak dostępu do projektu.'];
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
    public function update(int $id, array $data, int $userId, bool $isAdmin): array
    {
        $task = $this->tasks->findById($id);
        if ($task === null) {
            return ['error' => 'Zadanie nie istnieje.'];
        }

        if (!$this->canAccess($task, $userId, $isAdmin)) {
            return ['error' => 'Brak uprawnień do edycji zadania.'];
        }

        $merged = array_merge($task->toArray(), $data);
        $validated = $this->validatePayload($merged);
        if (isset($validated['error'])) {
            return $validated;
        }

        $payload = $validated['data'];
        $projectId = (int) $payload['project_id'];

        if (!$this->projects->exists($projectId)) {
            return ['error' => 'Projekt nie istnieje.'];
        }

        if (!$this->projects->userHasAccess($projectId, $userId, $isAdmin)) {
            return ['error' => 'Brak dostępu do projektu.'];
        }

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
            $projectId,
            $payload['assignee_id'],
            $payload['category_id'],
        );

        if ($updated === null) {
            return ['error' => 'Nie udało się zaktualizować zadania.'];
        }

        return ['task' => $updated];
    }

    /** @return array{success: bool, error?: string} */
    public function delete(int $id, int $userId, bool $isAdmin): array
    {
        $task = $this->tasks->findById($id);
        if ($task === null) {
            return ['success' => false, 'error' => 'Zadanie nie istnieje.'];
        }

        if (!$this->canAccess($task, $userId, $isAdmin)) {
            return ['success' => false, 'error' => 'Brak uprawnień do usunięcia zadania.'];
        }

        if (!$this->tasks->delete($id)) {
            return ['success' => false, 'error' => 'Nie udało się usunąć zadania.'];
        }

        return ['success' => true];
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
