<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Request;
use App\Core\Response;
use App\Models\Category;
use App\Repositories\CategoryRepository;
use App\Services\CategoryService;

class CategoryController extends Controller
{
    public function index(Request $request): Response
    {
        if (!$this->authService()->isAuthenticated()) {
            return $this->redirect('/login');
        }

        return Response::html($this->view('categories.index', [
            'title' => 'Kategorie',
        ]));
    }

    public function apiIndex(Request $request): Response
    {
        if ($response = $this->requireAuthJson()) {
            return $response;
        }

        $user = $this->currentUser();
        $categories = $this->categoryService()->listForUser($user['role'] === 'admin');

        return $this->json([
            'categories' => array_map(static fn (Category $c): array => $c->toArray(), $categories),
        ]);
    }

    public function apiStore(Request $request): Response
    {
        if ($response = $this->requireAuthJson()) {
            return $response;
        }

        $user = $this->currentUser();
        $result = $this->categoryService()->create($request->json(), $user['role'] === 'admin');

        if (isset($result['error'])) {
            return $this->json(['message' => $result['error']], $this->errorStatus($result['error']));
        }

        return $this->json([
            'message' => 'Kategoria utworzona.',
            'category' => $result['category']->toArray(),
        ], 201);
    }

    public function apiUpdate(Request $request): Response
    {
        if ($response = $this->requireAuthJson()) {
            return $response;
        }

        $id = $this->parseCategoryId($request);
        if ($id === null) {
            return $this->json(['message' => 'Nieprawidłowy identyfikator kategorii.'], 400);
        }

        $user = $this->currentUser();
        $result = $this->categoryService()->update($id, $request->json(), $user['role'] === 'admin');

        if (isset($result['error'])) {
            return $this->json(['message' => $result['error']], $this->errorStatus($result['error']));
        }

        return $this->json([
            'message' => 'Kategoria zaktualizowana.',
            'category' => $result['category']->toArray(),
        ]);
    }

    public function apiDestroy(Request $request): Response
    {
        if ($response = $this->requireAuthJson()) {
            return $response;
        }

        $id = $this->parseCategoryId($request);
        if ($id === null) {
            return $this->json(['message' => 'Nieprawidłowy identyfikator kategorii.'], 400);
        }

        $user = $this->currentUser();
        $result = $this->categoryService()->delete($id, $user['role'] === 'admin');

        if (!$result['success']) {
            return $this->json(
                ['message' => $result['error'] ?? 'Błąd usuwania.'],
                $this->errorStatus($result['error'] ?? ''),
            );
        }

        return $this->json(['message' => 'Kategoria usunięta.']);
    }

    private function categoryService(): CategoryService
    {
        return new CategoryService(new CategoryRepository($this->db()));
    }

    private function parseCategoryId(Request $request): ?int
    {
        $id = $request->route('id');
        if ($id === null || !ctype_digit((string) $id)) {
            return null;
        }

        return (int) $id;
    }

    private function errorStatus(string $message): int
    {
        if (str_contains($message, 'nie istnieje')) {
            return 404;
        }
        if (str_contains($message, 'uprawnień')) {
            return 403;
        }

        return 400;
    }
}
