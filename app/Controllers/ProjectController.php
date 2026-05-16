<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Request;
use App\Core\Response;
use App\Models\Project;

class ProjectController extends Controller
{
    public function index(Request $request): Response
    {
        if (!$this->authService()->isAuthenticated()) {
            return $this->redirect('/login');
        }

        return Response::html($this->view('projects.index', [
            'title' => 'Projekty',
        ]));
    }

    public function show(Request $request): Response
    {
        return Response::html($this->view('projects.show', [
            'title' => 'Szczegóły projektu',
            'projectId' => $request->query('id'),
        ]));
    }

    public function apiIndex(Request $request): Response
    {
        if ($response = $this->requireAuthJson()) {
            return $response;
        }

        $user = $this->currentUser();
        $projects = $this->projectService()->listForUser(
            $user['id'],
            $user['role'] === 'admin',
        );

        return $this->json([
            'projects' => $this->serializeProjects($projects),
        ]);
    }

    public function apiStore(Request $request): Response
    {
        if ($response = $this->requireAuthJson()) {
            return $response;
        }

        $user = $this->currentUser();
        $body = $request->json();
        $result = $this->projectService()->create($body, $user['id']);

        if (isset($result['error'])) {
            return $this->json(['message' => $result['error']], 400);
        }

        return $this->json([
            'message' => 'Projekt utworzony.',
            'project' => $result['project']->toArray(),
        ], 201);
    }

    public function apiShow(Request $request): Response
    {
        if ($response = $this->requireAuthJson()) {
            return $response;
        }

        $id = $this->parseProjectId($request);
        if ($id === null) {
            return $this->json(['message' => 'Nieprawidłowy identyfikator projektu.'], 400);
        }

        $project = $this->projectService()->get($id);
        if ($project === null) {
            return $this->json(['message' => 'Projekt nie istnieje.'], 404);
        }

        $user = $this->currentUser();
        if (!$this->canView($project, $user)) {
            return $this->json(['message' => 'Brak dostępu do projektu.'], 403);
        }

        return $this->json(['project' => $project->toArray()]);
    }

    public function apiUpdate(Request $request): Response
    {
        if ($response = $this->requireAuthJson()) {
            return $response;
        }

        $id = $this->parseProjectId($request);
        if ($id === null) {
            return $this->json(['message' => 'Nieprawidłowy identyfikator projektu.'], 400);
        }

        $user = $this->currentUser();
        $result = $this->projectService()->update(
            $id,
            $request->json(),
            $user['id'],
            $user['role'] === 'admin',
        );

        if (isset($result['error'])) {
            $status = str_contains($result['error'], 'uprawnień') ? 403
                : (str_contains($result['error'], 'nie istnieje') ? 404 : 400);
            return $this->json(['message' => $result['error']], $status);
        }

        return $this->json([
            'message' => 'Projekt zaktualizowany.',
            'project' => $result['project']->toArray(),
        ]);
    }

    public function apiDestroy(Request $request): Response
    {
        if ($response = $this->requireAuthJson()) {
            return $response;
        }

        $id = $this->parseProjectId($request);
        if ($id === null) {
            return $this->json(['message' => 'Nieprawidłowy identyfikator projektu.'], 400);
        }

        $user = $this->currentUser();
        $result = $this->projectService()->delete(
            $id,
            $user['id'],
            $user['role'] === 'admin',
        );

        if (!$result['success']) {
            $status = isset($result['error']) && str_contains($result['error'], 'uprawnień') ? 403
                : (isset($result['error']) && str_contains($result['error'], 'nie istnieje') ? 404 : 400);
            return $this->json(['message' => $result['error'] ?? 'Błąd usuwania.'], $status);
        }

        return $this->json(['message' => 'Projekt usunięty.']);
    }

    private function parseProjectId(Request $request): ?int
    {
        $id = $request->route('id');
        if ($id === null || !ctype_digit((string) $id)) {
            return null;
        }

        return (int) $id;
    }

    /** @param array{id: int, role: string} $user */
    private function canView(Project $project, array $user): bool
    {
        if ($user['role'] === 'admin') {
            return true;
        }

        return $project->ownerId === $user['id'];
    }

    /** @param list<Project> $projects */
    private function serializeProjects(array $projects): array
    {
        return array_map(static fn (Project $p): array => $p->toArray(), $projects);
    }
}
