<?php

declare(strict_types=1);

namespace App\Core;

abstract class Controller
{
    protected array $config;

    public function __construct()
    {
        $this->config = require dirname(__DIR__, 2) . '/config/config.php';
    }

    protected function view(string $name, array $data = []): string
    {
        $viewFile = dirname(__DIR__, 2) . '/views/' . str_replace('.', '/', $name) . '.php';
        if (!is_readable($viewFile)) {
            throw new \RuntimeException("Widok nie istnieje: {$name}");
        }

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
