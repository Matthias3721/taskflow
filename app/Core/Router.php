<?php

declare(strict_types=1);

namespace App\Core;

class Router
{
    /** @var array<string, list<array{pattern: string, controller: class-string, action: string}>> */
    private array $routes = [];

    public function get(string $path, string $handler): void
    {
        $this->add('GET', $path, $handler);
    }

    public function post(string $path, string $handler): void
    {
        $this->add('POST', $path, $handler);
    }

    public function put(string $path, string $handler): void
    {
        $this->add('PUT', $path, $handler);
    }

    public function delete(string $path, string $handler): void
    {
        $this->add('DELETE', $path, $handler);
    }

    private function add(string $method, string $path, string $handler): void
    {
        [$controller, $action] = explode('@', $handler, 2);
        $this->routes[$method][] = [
            'pattern' => $this->normalize($path),
            'controller' => $controller,
            'action' => $action,
        ];
    }

    public function dispatch(Request $request): Response
    {
        $method = $request->method();
        $uri = $this->normalize($request->uri());

        $match = $this->match($method, $uri);

        if ($match === null) {
            if (str_starts_with($uri, '/api')) {
                return Response::json(['message' => 'Nie znaleziono endpointu.'], 404);
            }

            return Response::html(
                $this->renderErrorView(404),
                404,
            );
        }

        $request->setRouteParams($match['params']);

        $controllerClass = $match['controller'];
        if (!class_exists($controllerClass)) {
            return Response::html(
                $this->renderErrorView(500, 'Brak kontrolera.'),
                500,
            );
        }

        $controller = new $controllerClass();
        $action = $match['action'];

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

    /**
     * @return array{controller: class-string, action: string, params: array<string, string>}|null
     */
    private function match(string $method, string $uri): ?array
    {
        foreach ($this->routes[$method] ?? [] as $route) {
            $params = $this->matchPattern($route['pattern'], $uri);
            if ($params !== null) {
                return [
                    'controller' => $route['controller'],
                    'action' => $route['action'],
                    'params' => $params,
                ];
            }
        }

        return null;
    }

    /** @return array<string, string>|null */
    private function matchPattern(string $pattern, string $uri): ?array
    {
        if ($pattern === $uri) {
            return [];
        }

        if (!str_contains($pattern, '{')) {
            return null;
        }

        $regex = preg_replace('#\{([a-zA-Z_][a-zA-Z0-9_]*)\}#', '(?P<$1>[^/]+)', $pattern);
        if ($regex === null || !preg_match('#^' . $regex . '$#', $uri, $matches)) {
            return null;
        }

        $params = [];
        foreach ($matches as $key => $value) {
            if (is_string($key)) {
                $params[$key] = $value;
            }
        }

        return $params;
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
