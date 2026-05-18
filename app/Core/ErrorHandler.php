<?php

declare(strict_types=1);

namespace App\Core;

class ErrorHandler
{
    private static array $config = [];

    private static bool $handling = false;

    /** @param array<string, mixed> $config */
    public static function register(array $config): void
    {
        self::$config = $config;

        set_exception_handler([self::class, 'handleException']);
        set_error_handler([self::class, 'handleError']);
        register_shutdown_function([self::class, 'handleShutdown']);
    }

    public static function handleException(\Throwable $e): void
    {
        if (self::$handling) {
            return;
        }

        self::$handling = true;
        self::respond(self::toResponse($e, self::requestUri()));
    }

    public static function handleError(
        int $severity,
        string $message,
        string $file,
        int $line,
    ): bool {
        if (!(error_reporting() & $severity)) {
            return false;
        }

        throw new \ErrorException($message, 0, $severity, $file, $line);
    }

    public static function handleShutdown(): void
    {
        if (self::$handling) {
            return;
        }

        $error = error_get_last();
        if ($error === null) {
            return;
        }

        $fatalTypes = [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR];
        if (!in_array($error['type'], $fatalTypes, true)) {
            return;
        }

        self::$handling = true;
        self::respond(self::toResponse(
            new \ErrorException($error['message'], 0, $error['type'], $error['file'], $error['line']),
            self::requestUri(),
        ));
    }

    public static function toResponse(\Throwable $e, ?string $uri = null): Response
    {
        if ($e instanceof HttpException) {
            return self::forStatus($e->statusCode(), $e->getMessage(), $uri);
        }

        return self::forStatus(500, self::internalMessage($e), $uri);
    }

    public static function forStatus(int $status, string $message = '', ?string $uri = null): Response
    {
        $uri ??= self::requestUri();
        $message = $message !== '' ? $message : self::defaultMessage($status, $uri);

        if (self::isApiRequest($uri)) {
            return Response::json(['message' => $message], $status);
        }

        return Response::html(self::renderErrorPage($status, $message), $status);
    }

    public static function isApiRequest(?string $uri): bool
    {
        $uri ??= self::requestUri();
        $path = parse_url($uri, PHP_URL_PATH) ?: $uri;

        return str_starts_with(self::normalizePath((string) $path), '/api');
    }

    public static function renderErrorPage(int $code, string $message = ''): string
    {
        $viewPath = dirname(__DIR__, 2) . "/views/errors/{$code}.php";
        if (!is_readable($viewPath)) {
            return "<h1>{$code}</h1>";
        }

        $errorMessage = $message;
        ob_start();
        include $viewPath;

        return (string) ob_get_clean();
    }

    private static function respond(Response $response): void
    {
        if (!headers_sent()) {
            $response->send();
        }
        exit(1);
    }

    private static function requestUri(): string
    {
        return (string) ($_SERVER['REQUEST_URI'] ?? '/');
    }

    private static function normalizePath(string $path): string
    {
        $path = '/' . trim($path, '/');

        return $path === '/' ? '/' : rtrim($path, '/');
    }

    private static function defaultMessage(int $status, ?string $uri): string
    {
        if ($status === 404 && self::isApiRequest($uri)) {
            return 'Nie znaleziono endpointu.';
        }

        return match ($status) {
            400 => 'Nieprawidłowe żądanie.',
            401 => 'Wymagane logowanie.',
            403 => 'Brak uprawnień.',
            404 => 'Strona nie została znaleziona.',
            default => 'Wystąpił błąd serwera.',
        };
    }

    private static function internalMessage(\Throwable $e): string
    {
        if (!empty(self::$config['app']['debug'])) {
            return $e->getMessage();
        }

        return '';
    }
}
