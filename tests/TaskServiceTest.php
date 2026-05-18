<?php

declare(strict_types=1);

namespace Tests;

use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use App\Repositories\CategoryRepository;
use App\Repositories\ProjectRepository;
use App\Repositories\TaskRepository;
use App\Repositories\UserRepository;
use App\Services\TaskService;
use PHPUnit\Framework\TestCase;

class TaskServiceTest extends TestCase
{
    private function makeService(
        ?TaskRepository $tasks = null,
        ?ProjectRepository $projects = null,
        ?UserRepository $users = null,
        ?CategoryRepository $categories = null,
    ): TaskService {
        return new TaskService(
            $tasks ?? $this->createMock(TaskRepository::class),
            $projects ?? $this->createMock(ProjectRepository::class),
            $users ?? $this->createMock(UserRepository::class),
            $categories ?? $this->createMock(CategoryRepository::class),
        );
    }

    public function testCreateRejectsEmptyTitle(): void
    {
        $tasks = $this->createMock(TaskRepository::class);
        $projects = $this->createMock(ProjectRepository::class);

        $tasks->expects($this->never())->method('create');

        $service = $this->makeService($tasks, $projects);
        $result = $service->create(['title' => '  ', 'project_id' => 1], 1, 'admin');

        $this->assertSame('Tytuł zadania jest wymagany.', $result['error']);
    }

    public function testCreateRejectsMissingProject(): void
    {
        $tasks = $this->createMock(TaskRepository::class);
        $projects = $this->createMock(ProjectRepository::class);

        $projects->method('exists')->with(99)->willReturn(false);
        $tasks->expects($this->never())->method('create');

        $service = $this->makeService($tasks, $projects);
        $result = $service->create(['title' => 'Test', 'project_id' => 99], 1, 'admin');

        $this->assertSame('Projekt nie istnieje.', $result['error']);
    }

    public function testListForAdminReturnsAllTasks(): void
    {
        $task = new Task(1, 'Zadanie', null, 'todo', 'medium', 1, null);

        $tasks = $this->createMock(TaskRepository::class);
        $tasks->method('findAll')->willReturn([$task]);

        $service = $this->makeService($tasks);

        $this->assertCount(1, $service->listForUser(1, 'admin'));
    }

    public function testUserCannotCreateTask(): void
    {
        $projects = $this->createMock(ProjectRepository::class);
        $projects->method('exists')->with(1)->willReturn(true);
        $projects->method('findById')->with(1)->willReturn(new Project(1, 'P', null, 2, 'active'));

        $tasks = $this->createMock(TaskRepository::class);
        $tasks->expects($this->never())->method('create');

        $service = $this->makeService($tasks, $projects);
        $result = $service->create(['title' => 'Test', 'project_id' => 1], 5, 'user');

        $this->assertSame('Brak uprawnień do tworzenia zadania w tym projekcie.', $result['error']);
    }

    public function testAssignedUserCanUpdateStatusAndDescriptionOnly(): void
    {
        $task = new Task(1, 'Zadanie', 'Stary opis', 'todo', 'medium', 1, 5);
        $project = new Project(1, 'P', null, 2, 'active');

        $tasks = $this->createMock(TaskRepository::class);
        $tasks->method('findById')->with(1)->willReturn($task);
        $tasks->expects($this->once())
            ->method('update')
            ->with(1, 'Zadanie', 'Nowy opis', 'in_progress', 'medium', null, 1, 5, null)
            ->willReturn($task);

        $projects = $this->createMock(ProjectRepository::class);
        $projects->method('findById')->with(1)->willReturn($project);

        $users = $this->createMock(UserRepository::class);
        $users->method('findById')->with(5)->willReturn(new User(5, 'u@test', 'User'));

        $service = $this->makeService($tasks, $projects, $users);
        $result = $service->update(1, [
            'status' => 'in_progress',
            'description' => 'Nowy opis',
            'title' => 'Zmiana tytułu',
        ], 5, 'user');

        $this->assertArrayHasKey('task', $result);
    }

    public function testDeleteDeniesRegularUser(): void
    {
        $task = new Task(1, 'Zadanie', null, 'todo', 'medium', 1, 5);
        $project = new Project(1, 'P', null, 2, 'active');

        $tasks = $this->createMock(TaskRepository::class);
        $tasks->method('findById')->with(1)->willReturn($task);
        $tasks->expects($this->never())->method('delete');

        $projects = $this->createMock(ProjectRepository::class);
        $projects->method('findById')->with(1)->willReturn($project);

        $service = $this->makeService($tasks, $projects);
        $result = $service->delete(1, 5, 'user');

        $this->assertFalse($result['success']);
        $this->assertSame('Brak uprawnień do usunięcia zadania.', $result['error']);
    }

    public function testUpdateDeniesUserNotAssignedToTask(): void
    {
        $task = new Task(1, 'Zadanie', null, 'todo', 'medium', 1, 2);
        $project = new Project(1, 'P', null, 3, 'active');

        $tasks = $this->createMock(TaskRepository::class);
        $tasks->method('findById')->with(1)->willReturn($task);
        $tasks->expects($this->never())->method('update');

        $projects = $this->createMock(ProjectRepository::class);
        $projects->method('findById')->with(1)->willReturn($project);

        $service = $this->makeService($tasks, $projects);
        $result = $service->update(1, ['status' => 'done'], 5, 'user');

        $this->assertSame('Brak uprawnień do edycji zadania.', $result['error']);
    }
}
