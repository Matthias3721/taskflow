<?php

declare(strict_types=1);

namespace App\Core;

class App
{
    private Router $router;
    private Session $session;
    private array $config;

    public function __construct()
    {
        $this->config = require dirname(__DIR__, 2) . '/config/config.php';
        $this->session = new Session($this->config);
        $this->router = new Router();
        $this->registerRoutes();
    }

    public function run(): void
    {
        $this->session->start();

        if ($this->config['app']['debug']) {
            error_reporting(E_ALL);
            ini_set('display_errors', '1');
        } else {
            error_reporting(0);
            ini_set('display_errors', '0');
        }

        try {
            Database::getConnection($this->config);
        } catch (\PDOException $e) {
            if ($this->config['app']['debug']) {
                Response::html(
                    '<h1>Błąd bazy danych</h1><pre>' . htmlspecialchars($e->getMessage()) . '</pre>',
                    500,
                )->send();
            } else {
                Response::html($this->errorPage(500), 500)->send();
            }
            return;
        }

        $request = Request::capture();

        try {
            $response = $this->router->dispatch($request);
            $response->send();
        } catch (\Throwable $e) {
            if ($this->config['app']['debug']) {
                Response::html(
                    '<h1>Błąd aplikacji</h1><pre>' . htmlspecialchars($e->getMessage()) . '</pre>',
                    500,
                )->send();
            } else {
                Response::html($this->errorPage(500), 500)->send();
            }
        }
    }

    private function registerRoutes(): void
    {
        $this->router->get('/', \App\Controllers\DashboardController::class . '@index');
        $this->router->get('/dashboard', \App\Controllers\DashboardController::class . '@index');
        $this->router->get('/login', \App\Controllers\AuthController::class . '@showLogin');
        $this->router->get('/register', \App\Controllers\AuthController::class . '@showRegister');
        $this->router->get('/projects', \App\Controllers\ProjectController::class . '@index');
        $this->router->get('/projects/show', \App\Controllers\ProjectController::class . '@show');
        $this->router->get('/tasks', \App\Controllers\TaskController::class . '@index');
        $this->router->get('/users', \App\Controllers\UserController::class . '@index');

        $this->router->post('/api/login', \App\Controllers\AuthController::class . '@login');
        $this->router->post('/api/logout', \App\Controllers\AuthController::class . '@logout');
        $this->router->get('/api/me', \App\Controllers\AuthController::class . '@me');
        $this->router->get('/api/dashboard', \App\Controllers\DashboardController::class . '@apiDashboard');

        $this->router->get('/api/projects', \App\Controllers\ProjectController::class . '@apiIndex');
        $this->router->post('/api/projects', \App\Controllers\ProjectController::class . '@apiStore');
        $this->router->get('/api/projects/{id}', \App\Controllers\ProjectController::class . '@apiShow');
        $this->router->put('/api/projects/{id}', \App\Controllers\ProjectController::class . '@apiUpdate');
        $this->router->delete('/api/projects/{id}', \App\Controllers\ProjectController::class . '@apiDestroy');

        $this->router->get('/api/users/options', \App\Controllers\UserController::class . '@apiOptions');
        $this->router->get('/api/users', \App\Controllers\UserController::class . '@apiIndex');
        $this->router->put('/api/users/{id}/role', \App\Controllers\UserController::class . '@apiUpdateRole');
        $this->router->put('/api/users/{id}/status', \App\Controllers\UserController::class . '@apiUpdateStatus');

        $this->router->get('/api/tasks', \App\Controllers\TaskController::class . '@apiIndex');
        $this->router->post('/api/tasks', \App\Controllers\TaskController::class . '@apiStore');
        $this->router->get('/api/tasks/{id}', \App\Controllers\TaskController::class . '@apiShow');
        $this->router->put('/api/tasks/{id}', \App\Controllers\TaskController::class . '@apiUpdate');
        $this->router->delete('/api/tasks/{id}', \App\Controllers\TaskController::class . '@apiDestroy');
    }

    private function errorPage(int $code): string
    {
        $path = dirname(__DIR__, 2) . "/views/errors/{$code}.php";
        if (!is_readable($path)) {
            return "<h1>{$code}</h1>";
        }
        ob_start();
        include $path;
        return (string) ob_get_clean();
    }
}
