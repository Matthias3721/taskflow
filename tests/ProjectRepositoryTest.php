<?php

declare(strict_types=1);

namespace Tests;

use App\Repositories\ProjectRepository;
use PDO;
use PHPUnit\Framework\TestCase;

class ProjectRepositoryTest extends TestCase
{
    private PDO $db;

    private ProjectRepository $repository;

    protected function setUp(): void
    {
        $this->db = new PDO('sqlite::memory:');
        $this->db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $this->db->exec(
            'CREATE TABLE projects (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                name TEXT NOT NULL,
                description TEXT,
                status TEXT NOT NULL,
                owner_id INTEGER NOT NULL,
                created_at TEXT DEFAULT CURRENT_TIMESTAMP,
                updated_at TEXT
            )',
        );

        $this->db->exec(
            'CREATE TABLE project_members (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                project_id INTEGER NOT NULL,
                user_id INTEGER NOT NULL,
                joined_at TEXT DEFAULT CURRENT_TIMESTAMP,
                UNIQUE (project_id, user_id)
            )',
        );

        $this->repository = new ProjectRepository($this->db);
    }

    public function testCreateInsertsProjectAndOwnerMembership(): void
    {
        $project = $this->repository->create('Projekt testowy', 'Opis', 'active', 42);

        $this->assertSame('Projekt testowy', $project->name);
        $this->assertSame(42, $project->ownerId);
        $this->assertNotNull($project->id);

        $stmt = $this->db->prepare(
            'SELECT COUNT(*) FROM project_members WHERE project_id = :project_id AND user_id = :user_id',
        );
        $stmt->execute([
            'project_id' => $project->id,
            'user_id' => 42,
        ]);

        $this->assertSame(1, (int) $stmt->fetchColumn());
    }

    public function testAddMemberIfNotExistsDoesNotDuplicate(): void
    {
        $project = $this->repository->create('Drugi', null, 'active', 7);

        $this->repository->addMemberIfNotExists($project->id, 7);
        $this->repository->addMemberIfNotExists($project->id, 7);

        $stmt = $this->db->prepare(
            'SELECT COUNT(*) FROM project_members WHERE project_id = :project_id AND user_id = :user_id',
        );
        $stmt->execute([
            'project_id' => $project->id,
            'user_id' => 7,
        ]);

        $this->assertSame(1, (int) $stmt->fetchColumn());
    }

    public function testCreateRollsBackProjectWhenMemberInsertFails(): void
    {
        $this->db->exec('DROP TABLE project_members');

        $this->expectException(\PDOException::class);

        try {
            $this->repository->create('Rollback', null, 'active', 1);
        } finally {
            $count = (int) $this->db->query('SELECT COUNT(*) FROM projects')->fetchColumn();
            $this->assertSame(0, $count);
        }
    }
}
