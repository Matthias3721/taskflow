<?php

declare(strict_types=1);

namespace App\Core;

use App\Repositories\ProjectRepository;
use App\Repositories\TaskRepository;
use App\Repositories\UserRepository;
use App\Services\AuthService;
use App\Services\ProjectService;
use App\Services\TaskService;
use PDO;

abstract class Controller
{
    protected array $config;
    private ?Session $session = null;

    public function __construct()
    {
        $this->config = require dirname(__DIR__, 2) . '/config/config.php';
    }

    protected function session(): Session
    {
        if ($this->session === null) {
            $this->session = new Session($this->config);
            $this->session->start();
        }

        return $this->session;
    }

    protected function db(): PDO
    {
        return Database::getConnection($this->config);
    }

    protected function authService(): AuthService
    {
        return new AuthService(
            new UserRepository($this->db()),
            $this->session(),
        );
    }

    protected function projectService(): ProjectService
    {
        return new ProjectService(new ProjectRepository($this->db()));
    }

    protected function taskService(): TaskService
    {
        return new TaskService(
            new TaskRepository($this->db()),
            new ProjectRepository($this->db()),
            new UserRepository($this->db()),
        );
    }

    protected function requireAuthJson(): ?Response
    {
        if (!$this->authService()->isAuthenticated()) {
            return $this->json(['message' => 'Wymagane logowanie.'], 401);
        }

        return null;
    }

    /** @return array{id: int, name: string, email: string, role: string}|null */
    protected function currentUser(): ?array
    {
        return $this->authService()->currentUser();
    }

    protected function view(string $name, array $data = []): string
    {
        $viewFile = dirname(__DIR__, 2) . '/views/' . str_replace('.', '/', $name) . '.php';
        if (!is_readable($viewFile)) {
            throw new \RuntimeException("Widok nie istnieje: {$name}");
        }

        $data['currentUser'] = $data['currentUser'] ?? $this->currentUser();

        extract($data, EXTR_SKIP);
        $config = $this->config;

        ob_start();
        include $viewFile;
        $content = (string) ob_get_clean();

        $layout = dirname(__DIR__, 2) . '/views/layouts/main.php';
        if (!is_readable($layout)) {
            return $content;
        }

        ob_start();
        include $layout;
        return (string) ob_get_clean();
    }

    protected function json(array $data, int $status = 200): Response
    {
        return Response::json($data, $status);
    }

    protected function redirect(string $path): Response
    {
        return Response::redirect($path);
    }
}
