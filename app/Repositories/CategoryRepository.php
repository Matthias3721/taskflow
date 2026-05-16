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

    /** @return list<Category> */
    public function findAll(): array
    {
        $stmt = $this->db->query('SELECT * FROM categories ORDER BY name');
        $rows = $stmt->fetchAll();
        return array_map(static fn (array $row): Category => Category::fromArray($row), $rows);
    }
}
