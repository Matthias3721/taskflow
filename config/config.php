<?php

declare(strict_types=1);

/**
 * Ładuje zmienne z pliku .env (jeśli istnieje) i zwraca konfigurację aplikacji.
 */
function loadEnv(string $path): void
{
    if (!is_readable($path)) {
        return;
    }

    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    if ($lines === false) {
        return;
    }

    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '' || str_starts_with($line, '#')) {
            continue;
        }
        if (!str_contains($line, '=')) {
            continue;
        }
        [$name, $value] = explode('=', $line, 2);
        $name = trim($name);
        $value = trim($value, " \t\"'");
        if ($name !== '' && getenv($name) === false) {
            putenv("{$name}={$value}");
            $_ENV[$name] = $value;
        }
    }
}

$root = dirname(__DIR__);
loadEnv($root . '/.env');

return [
    'app' => [
        'name' => getenv('APP_NAME') ?: 'TaskFlow',
        'env' => getenv('APP_ENV') ?: 'local',
        'debug' => filter_var(getenv('APP_DEBUG') ?: 'true', FILTER_VALIDATE_BOOLEAN),
        'url' => rtrim(getenv('APP_URL') ?: 'http://localhost:8080', '/'),
    ],
    'database' => [
        'host' => getenv('DB_HOST') ?: 'localhost',
        'port' => (int) (getenv('DB_PORT') ?: '5432'),
        'name' => getenv('DB_NAME') ?: 'taskflow',
        'user' => getenv('DB_USER') ?: 'taskflow',
        'password' => getenv('DB_PASSWORD') ?: '',
    ],
    'session' => [
        'name' => getenv('SESSION_NAME') ?: 'taskflow_session',
    ],
];
