<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Request;
use App\Core\Response;
use App\Middleware\RoleMiddleware;
use App\Models\User;
use App\Repositories\UserRepository;
use App\Services\UserService;

class UserController extends Controller
{
    public function index(Request $request): Response
    {
        if (!$this->authService()->isAuthenticated()) {
            return $this->redirect('/login');
        }

        $middleware = new RoleMiddleware($this->session(), ['admin']);

        return $middleware->handle($request, function () {
            return Response::html($this->view('users.index', [
                'title' => 'Użytkownicy',
            ]));
        });
    }

    public function apiOptions(Request $request): Response
    {
        if ($response = $this->requireAuthJson()) {
            return $response;
        }

        return $this->json([
            'users' => (new UserRepository($this->db()))->findOptions(),
        ]);
    }

    public function apiIndex(Request $request): Response
    {
        if ($response = $this->requireAdminJson()) {
            return $response;
        }

        $users = $this->userService()->listAll();

        return $this->json([
            'users' => array_map(static fn (User $u): array => $u->toArray(), $users),
        ]);
    }

    public function apiUpdateRole(Request $request): Response
    {
        if ($response = $this->requireAdminJson()) {
            return $response;
        }

        $id = $this->parseUserId($request);
        if ($id === null) {
            return $this->json(['message' => 'Nieprawidłowy identyfikator użytkownika.'], 400);
        }

        $body = $request->json();
        $role = (string) ($body['role'] ?? '');

        $actor = $this->currentUser();
        $result = $this->userService()->changeRole($id, $role, $actor['id']);

        if (isset($result['error'])) {
            return $this->json(['message' => $result['error']], $this->errorStatus($result['error']));
        }

        return $this->json([
            'message' => 'Rola została zaktualizowana.',
            'user' => $result['user']->toArray(),
        ]);
    }

    public function apiUpdateStatus(Request $request): Response
    {
        if ($response = $this->requireAdminJson()) {
            return $response;
        }

        $id = $this->parseUserId($request);
        if ($id === null) {
            return $this->json(['message' => 'Nieprawidłowy identyfikator użytkownika.'], 400);
        }

        $body = $request->json();
        if (!array_key_exists('is_active', $body)) {
            return $this->json(['message' => 'Pole is_active jest wymagane.'], 400);
        }

        $isActive = $this->parseIsActive($body['is_active']);
        if ($isActive === null) {
            return $this->json(['message' => 'Nieprawidłowa wartość is_active (oczekiwano true lub false).'], 400);
        }

        $actor = $this->currentUser();
        if ($actor === null) {
            return $this->json(['message' => 'Brak aktywnej sesji.'], 401);
        }

        $result = $this->userService()->changeStatus($id, $isActive, $actor['id']);

        if (isset($result['error'])) {
            return $this->json(['message' => $result['error']], $this->errorStatus($result['error']));
        }

        return $this->json([
            'message' => $isActive ? 'Konto aktywowane.' : 'Konto dezaktywowane.',
            'user' => $result['user']->toArray(),
        ]);
    }

    private function requireAdminJson(): ?Response
    {
        if ($response = $this->requireAuthJson()) {
            return $response;
        }

        $user = $this->currentUser();
        if (($user['role'] ?? '') !== 'admin') {
            return $this->json(['message' => 'Brak uprawnień.'], 403);
        }

        return null;
    }

    private function userService(): UserService
    {
        return new UserService(new UserRepository($this->db()));
    }

    private function parseUserId(Request $request): ?int
    {
        $id = $request->route('id');
        if ($id === null || !ctype_digit((string) $id)) {
            return null;
        }

        return (int) $id;
    }

    private function parseIsActive(mixed $value): ?bool
    {
        if (is_bool($value)) {
            return $value;
        }

        if ($value === 1 || $value === '1' || $value === 'true') {
            return true;
        }

        if ($value === 0 || $value === '0' || $value === 'false') {
            return false;
        }

        return null;
    }

    private function errorStatus(string $message): int
    {
        if (str_contains($message, 'nie istnieje')) {
            return 404;
        }
        if (str_contains($message, 'Nie możesz')) {
            return 403;
        }

        return 400;
    }
}
