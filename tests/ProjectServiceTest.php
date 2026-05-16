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
        $project = new Project(1, 'Demo', 'Opis', 2);

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
}
