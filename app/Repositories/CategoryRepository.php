<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\Category;
use PDO;

class CategoryRepository
{
    public function __construct(private readonly PDO $db)
    {
    }

    public function findById(int $id): ?Category
    {
        $stmt = $this->db->prepare('SELECT * FROM categories WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();

        return $row ? Category::fromArray($row) : null;
    }

    public function exists(int $id): bool
    {
        $stmt = $this->db->prepare('SELECT 1 FROM categories WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $id]);

        return (bool) $stmt->fetchColumn();
    }

    /** @return list<Category> */
    public function findAll(): array
    {
        $stmt = $this->db->query('SELECT * FROM categories ORDER BY name');
        $rows = $stmt->fetchAll();

        return array_map(static fn (array $row): Category => Category::fromArray($row), $rows);
    }

    public function create(string $name, string $color): Category
    {
        $stmt = $this->db->prepare(
            'INSERT INTO categories (name, color) VALUES (:name, :color) RETURNING *',
        );
        $stmt->execute(['name' => $name, 'color' => $color]);
        $row = $stmt->fetch();
        if ($row === false) {
            throw new \RuntimeException('Nie udało się utworzyć kategorii.');
        }

        return Category::fromArray($row);
    }

    public function update(int $id, string $name, string $color): ?Category
    {
        $stmt = $this->db->prepare(
            'UPDATE categories SET name = :name, color = :color WHERE id = :id RETURNING *',
        );
        $stmt->execute(['id' => $id, 'name' => $name, 'color' => $color]);
        $row = $stmt->fetch();

        return $row ? Category::fromArray($row) : null;
    }

    public function delete(int $id): bool
    {
        $stmt = $this->db->prepare('DELETE FROM categories WHERE id = :id');
        $stmt->execute(['id' => $id]);

        return $stmt->rowCount() > 0;
    }
}
