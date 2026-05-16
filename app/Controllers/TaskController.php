<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Request;
use App\Core\Response;
use App\Models\Task;

class TaskController extends Controller
{
    public function index(Request $request): Response
    {
        if (!$this->authService()->isAuthenticated()) {
            return $this->redirect('/login');
        }

        return Response::html($this->view('tasks.index', [
            'title' => 'Zadania',
        ]));
    }

    public function apiIndex(Request $request): Response
    {
        if ($response = $this->requireAuthJson()) {
            return $response;
        }

        $user = $this->currentUser();
        $tasks = $this->taskService()->listForUser(
            $user['id'],
            $user['role'] === 'admin',
        );

        return $this->json([
            'tasks' => $this->serializeTasks($tasks),
        ]);
    }

    public function apiStore(Request $request): Response
    {
        if ($response = $this->requireAuthJson()) {
            return $response;
        }

        $user = $this->currentUser();
        $result = $this->taskService()->create(
            $request->json(),
            $user['id'],
            $user['role'] === 'admin',
        );

        if (isset($result['error'])) {
            $status = $this->errorStatus($result['error'], 400);
            return $this->json(['message' => $result['error']], $status);
        }

        return $this->json([
            'message' => 'Zadanie utworzone.',
            'task' => $result['task']->toArray(),
        ], 201);
    }

    public function apiShow(Request $request): Response
    {
        if ($response = $this->requireAuthJson()) {
            return $response;
        }

        $id = $this->parseTaskId($request);
        if ($id === null) {
            return $this->json(['message' => 'Nieprawidłowy identyfikator zadania.'], 400);
        }

        $task = $this->taskService()->get($id);
        if ($task === null) {
            return $this->json(['message' => 'Zadanie nie istnieje.'], 404);
        }

        $user = $this->currentUser();
        if (!$this->taskService()->canAccess($task, $user['id'], $user['role'] === 'admin')) {
            return $this->json(['message' => 'Brak dostępu do zadania.'], 403);
        }

        return $this->json(['task' => $task->toArray()]);
    }

    public function apiUpdate(Request $request): Response
    {
        if ($response = $this->requireAuthJson()) {
            return $response;
        }

        $id = $this->parseTaskId($request);
        if ($id === null) {
            return $this->json(['message' => 'Nieprawidłowy identyfikator zadania.'], 400);
        }

        $user = $this->currentUser();
        $result = $this->taskService()->update(
            $id,
            $request->json(),
            $user['id'],
            $user['role'] === 'admin',
        );

        if (isset($result['error'])) {
            return $this->json(['message' => $result['error']], $this->errorStatus($result['error'], 400));
        }

        return $this->json([
            'message' => 'Zadanie zaktualizowane.',
            'task' => $result['task']->toArray(),
        ]);
    }

    public function apiDestroy(Request $request): Response
    {
        if ($response = $this->requireAuthJson()) {
            return $response;
        }

        $id = $this->parseTaskId($request);
        if ($id === null) {
            return $this->json(['message' => 'Nieprawidłowy identyfikator zadania.'], 400);
        }

        $user = $this->currentUser();
        $result = $this->taskService()->delete(
            $id,
            $user['id'],
            $user['role'] === 'admin',
        );

        if (!$result['success']) {
            return $this->json(
                ['message' => $result['error'] ?? 'Błąd usuwania.'],
                $this->errorStatus($result['error'] ?? '', 400),
            );
        }

        return $this->json(['message' => 'Zadanie usunięte.']);
    }

    private function parseTaskId(Request $request): ?int
    {
        $id = $request->route('id');
        if ($id === null || !ctype_digit((string) $id)) {
            return null;
        }

        return (int) $id;
    }

    private function errorStatus(string $message, int $default): int
    {
        if (str_contains($message, 'nie istnieje')) {
            return 404;
        }
        if (str_contains($message, 'uprawnień') || str_contains($message, 'dostępu')) {
            return 403;
        }

        return $default;
    }

    /** @param list<Task> $tasks */
    private function serializeTasks(array $tasks): array
    {
        return array_map(static fn (Task $t): array => $t->toArray(), $tasks);
    }
}
