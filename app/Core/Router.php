<?php

declare(strict_types=1);

namespace App\Core;

class Router
{
    /** @var array<string, array<string, array{controller: class-string, action: string}>> */
    private array $routes = [];

    public function get(string $path, string $handler): void
    {
        $this->add('GET', $path, $handler);
    }

    public function post(string $path, string $handler): void
    {
        $this->add('POST', $path, $handler);
    }

    private function add(string $method, string $path, string $handler): void
    {
        [$controller, $action] = explode('@', $handler, 2);
        $this->routes[$method][$this->normalize($path)] = [
            'controller' => $controller,
            'action' => $action,
        ];
    }

    public function dispatch(Request $request): Response
    {
        $method = $request->method();
        $uri = $this->normalize($request->uri());

        $route = $this->routes[$method][$uri] ?? null;

        if ($route === null) {
            if (str_starts_with($uri, '/api')) {
                return Response::json(['message' => 'Nie znaleziono endpointu.'], 404);
            }

            return Response::html(
                $this->renderErrorView(404),
                404,
            );
        }

        $controllerClass = $route['controller'];
        if (!class_exists($controllerClass)) {
            return Response::html(
                $this->renderErrorView(500, 'Brak kontrolera.'),
                500,
            );
        }

        $controller = new $controllerClass();
        $action = $route['action'];

        if (!method_exists($controller, $action)) {
            return Response::html(
                $this->renderErrorView(500, 'Brak metody kontrolera.'),
                500,
            );
        }

        $result = $controller->$action($request);

        if ($result instanceof Response) {
            return $result;
        }

        if (is_string($result)) {
            return Response::html($result);
        }

        return Response::html('');
    }

    private function normalize(string $path): string
    {
        $path = '/' . trim($path, '/');
        return $path === '/' ? '/' : rtrim($path, '/');
    }

    private function renderErrorView(int $code, string $message = ''): string
    {
        $viewPath = dirname(__DIR__, 2) . "/views/errors/{$code}.php";
        if (!is_readable($viewPath)) {
            return "<h1>{$code}</h1><p>{$message}</p>";
        }
        ob_start();
        $errorMessage = $message;
        include $viewPath;
        return (string) ob_get_clean();
    }
}
