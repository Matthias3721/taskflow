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
        $result = $service->listForUser(2, 'project_manager');

        $this->assertCount(1, $result);
        $this->assertSame('Demo', $result[0]->name);
    }

    public function testListForAdminReturnsAllProjects(): void
    {
        $repository = $this->createMock(ProjectRepository::class);
        $repository->method('findAll')->willReturn([]);

        $service = new ProjectService($repository);
        $result = $service->listForUser(1, 'admin');

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

    public function testProjectPermissionsForProjectManagerOwner(): void
    {
        $project = new Project(1, 'Demo', null, 2, 'active');
        $repository = $this->createMock(ProjectRepository::class);
        $service = new ProjectService($repository);

        $perms = $service->projectPermissions($project, 2, 'project_manager');

        $this->assertTrue($perms['can_edit']);
        $this->assertTrue($perms['can_delete']);
    }

    public function testProjectPermissionsDenyRegularUser(): void
    {
        $project = new Project(1, 'Demo', null, 2, 'active');
        $repository = $this->createMock(ProjectRepository::class);
        $service = new ProjectService($repository);

        $perms = $service->projectPermissions($project, 5, 'user');

        $this->assertFalse($perms['can_edit']);
        $this->assertFalse($perms['can_delete']);
    }

    public function testUpdateDeniesProjectManagerWhoIsNotOwner(): void
    {
        $project = new Project(1, 'Demo', null, 2, 'active');
        $repository = $this->createMock(ProjectRepository::class);
        $repository->method('findById')->with(1)->willReturn($project);
        $repository->expects($this->never())->method('update');

        $service = new ProjectService($repository);
        $result = $service->update(1, ['name' => 'Zmiana'], 9, 'project_manager');

        $this->assertSame('Brak uprawnień do edycji projektu.', $result['error']);
    }

    public function testDeleteDeniesRegularUser(): void
    {
        $project = new Project(1, 'Demo', null, 2, 'active');
        $repository = $this->createMock(ProjectRepository::class);
        $repository->method('findById')->with(1)->willReturn($project);
        $repository->expects($this->never())->method('delete');

        $service = new ProjectService($repository);
        $result = $service->delete(1, 5, 'user');

        $this->assertFalse($result['success']);
        $this->assertSame('Brak uprawnień do usunięcia projektu.', $result['error']);
    }

    public function testUpdateRejectsInvalidStatus(): void
    {
        $project = new Project(1, 'Demo', null, 1, 'active');
        $repository = $this->createMock(ProjectRepository::class);
        $repository->method('findById')->with(1)->willReturn($project);

        $service = new ProjectService($repository);
        $result = $service->update(1, ['status' => 'invalid'], 1, 'admin');

        $this->assertSame('Nieprawidłowy status projektu.', $result['error']);
    }
}
