<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\Project;
use PDO;

class ProjectRepository
{
    public function __construct(private readonly PDO $db)
    {
    }

    public function findById(int $id): ?Project
    {
        $stmt = $this->db->prepare('SELECT * FROM projects WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();
        return $row ? Project::fromArray($row) : null;
    }

    /** @return list<Project> */
    public function findByOwner(int $ownerId): array
    {
        $stmt = $this->db->prepare('SELECT * FROM projects WHERE owner_id = :owner_id ORDER BY id');
        $stmt->execute(['owner_id' => $ownerId]);
        $rows = $stmt->fetchAll();
        return array_map(static fn (array $row): Project => Project::fromArray($row), $rows);
    }

    /** @return list<Project> */
    public function findAll(): array
    {
        $stmt = $this->db->query('SELECT * FROM projects ORDER BY id');
        $rows = $stmt->fetchAll();
        return array_map(static fn (array $row): Project => Project::fromArray($row), $rows);
    }
}
