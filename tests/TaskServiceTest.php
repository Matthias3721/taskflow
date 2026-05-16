<?php

declare(strict_types=1);

namespace Tests;

use App\Models\Task;
use App\Repositories\ProjectRepository;
use App\Repositories\TaskRepository;
use App\Repositories\UserRepository;
use App\Services\TaskService;
use PHPUnit\Framework\TestCase;

class TaskServiceTest extends TestCase
{
    public function testCreateRejectsEmptyTitle(): void
    {
        $tasks = $this->createMock(TaskRepository::class);
        $projects = $this->createMock(ProjectRepository::class);
        $users = $this->createMock(UserRepository::class);

        $tasks->expects($this->never())->method('create');

        $service = new TaskService($tasks, $projects, $users);
        $result = $service->create(['title' => '  ', 'project_id' => 1], 1, false);

        $this->assertSame('Tytuł zadania jest wymagany.', $result['error']);
    }

    public function testCreateRejectsMissingProject(): void
    {
        $tasks = $this->createMock(TaskRepository::class);
        $projects = $this->createMock(ProjectRepository::class);
        $users = $this->createMock(UserRepository::class);

        $projects->method('exists')->with(99)->willReturn(false);
        $tasks->expects($this->never())->method('create');

        $service = new TaskService($tasks, $projects, $users);
        $result = $service->create(['title' => 'Test', 'project_id' => 99], 1, false);

        $this->assertSame('Projekt nie istnieje.', $result['error']);
    }

    public function testListForAdminReturnsAllTasks(): void
    {
        $task = new Task(1, 'Zadanie', null, 'todo', 'medium', 1, null);

        $tasks = $this->createMock(TaskRepository::class);
        $tasks->method('findAll')->willReturn([$task]);

        $service = new TaskService(
            $tasks,
            $this->createMock(ProjectRepository::class),
            $this->createMock(UserRepository::class),
        );

        $this->assertCount(1, $service->listForUser(1, true));
    }
}
