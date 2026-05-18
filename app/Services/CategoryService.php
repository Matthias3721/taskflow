<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Category;
use App\Repositories\CategoryRepository;
use PDOException;

class CategoryService
{
    public function __construct(private readonly CategoryRepository $categories)
    {
    }

    /** @return list<Category> */
    public function listForUser(bool $isAdmin): array
    {
        return $this->categories->findAll();
    }

    public function get(int $id): ?Category
    {
        return $this->categories->findById($id);
    }

    /**
     * @param array{name?: string, color?: string|null} $data
     * @return array{category?: Category, error?: string}
     */
    public function create(array $data, bool $isAdmin): array
    {
        if (!$isAdmin) {
            return ['error' => 'Brak uprawnień do tworzenia kategorii.'];
        }

        $validated = $this->validatePayload($data);
        if (isset($validated['error'])) {
            return $validated;
        }

        try {
            $category = $this->categories->create(
                $validated['data']['name'],
                $validated['data']['color'],
            );
        } catch (PDOException $e) {
            if (str_contains($e->getMessage(), 'unique') || str_contains($e->getMessage(), 'duplicate')) {
                return ['error' => 'Kategoria o tej nazwie już istnieje.'];
            }
            throw $e;
        }

        return ['category' => $category];
    }

    /**
     * @param array{name?: string, color?: string|null} $data
     * @return array{category?: Category, error?: string}
     */
    public function update(int $id, array $data, bool $isAdmin): array
    {
        if (!$isAdmin) {
            return ['error' => 'Brak uprawnień do edycji kategorii.'];
        }

        if ($this->categories->findById($id) === null) {
            return ['error' => 'Kategoria nie istnieje.'];
        }

        $validated = $this->validatePayload($data);
        if (isset($validated['error'])) {
            return $validated;
        }

        try {
            $category = $this->categories->update(
                $id,
                $validated['data']['name'],
                $validated['data']['color'],
            );
        } catch (PDOException $e) {
            if (str_contains($e->getMessage(), 'unique') || str_contains($e->getMessage(), 'duplicate')) {
                return ['error' => 'Kategoria o tej nazwie już istnieje.'];
            }
            throw $e;
        }

        if ($category === null) {
            return ['error' => 'Nie udało się zaktualizować kategorii.'];
        }

        return ['category' => $category];
    }

    /** @return array{success: bool, error?: string} */
    public function delete(int $id, bool $isAdmin): array
    {
        if (!$isAdmin) {
            return ['error' => 'Brak uprawnień do usunięcia kategorii.', 'success' => false];
        }

        if ($this->categories->findById($id) === null) {
            return ['success' => false, 'error' => 'Kategoria nie istnieje.'];
        }

        if (!$this->categories->delete($id)) {
            return ['success' => false, 'error' => 'Nie udało się usunąć kategorii.'];
        }

        return ['success' => true];
    }

    /**
     * @param array<string, mixed> $data
     * @return array{data?: array{name: string, color: string}, error?: string}
     */
    private function validatePayload(array $data): array
    {
        $name = trim((string) ($data['name'] ?? ''));
        if ($name === '') {
            return ['error' => 'Nazwa kategorii jest wymagana.'];
        }

        $color = $this->normalizeColor($data['color'] ?? '#3b82f6');
        if ($color === null) {
            return ['error' => 'Nieprawidłowy kolor (oczekiwano formatu #RRGGBB).'];
        }

        return ['data' => ['name' => $name, 'color' => $color]];
    }

    private function normalizeColor(mixed $color): ?string
    {
        $color = trim((string) $color);
        if ($color === '') {
            return '#3b82f6';
        }

        if (!preg_match('/^#[0-9A-Fa-f]{6}$/', $color)) {
            return null;
        }

        return strtolower($color);
    }
}
