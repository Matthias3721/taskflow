<?php

declare(strict_types=1);

namespace Tests;

use App\Models\Project;
use App\Repositories\ProjectRepository;
use App\Services\ProjectService;
use PHPUnit\Framework\TestCase;

class ProjectServiceTest extends TestCase
{
    public function testListForUserReturnsOwnerProjects(): void
    {
        $project = new Project(1, 'Demo', 'Opis', 2, 'active');

        $repository = $this->createMock(ProjectRepository::class);
        $repository->method('findByOwner')->with(2)->willReturn([$project]);

        $service = new ProjectService($repository);
        $result = $service->listForUser(2, false);

        $this->assertCount(1, $result);
        $this->assertSame('Demo', $result[0]->name);
    }

    public function testListForAdminReturnsAllProjects(): void
    {
        $repository = $this->createMock(ProjectRepository::class);
        $repository->method('findAll')->willReturn([]);

        $service = new ProjectService($repository);
        $result = $service->listForUser(1, true);

        $this->assertIsArray($result);
    }

    public function testCreateRejectsEmptyName(): void
    {
        $repository = $this->createMock(ProjectRepository::class);
        $repository->expects($this->never())->method('create');

        $service = new ProjectService($repository);
        $result = $service->create(['name' => '  '], 1);

        $this->assertArrayHasKey('error', $result);
        $this->assertSame('Nazwa projektu jest wymagana.', $result['error']);
    }

    public function testCreateSetsOwnerFromArgument(): void
    {
        $project = new Project(1, 'Nowy', null, 5, 'active');

        $repository = $this->createMock(ProjectRepository::class);
        $repository->expects($this->once())
            ->method('create')
            ->with('Nowy', null, 'active', 5)
            ->willReturn($project);

        $service = new ProjectService($repository);
        $result = $service->create(['name' => 'Nowy'], 5);

        $this->assertSame($project, $result['project']);
    }
}
