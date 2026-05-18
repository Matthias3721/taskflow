<?php

declare(strict_types=1);

namespace App\Core;

class HttpException extends \RuntimeException
{
    public function __construct(
        private readonly int $statusCode,
        string $message = '',
        ?\Throwable $previous = null,
    ) {
        parent::__construct($message !== '' ? $message : self::defaultMessage($statusCode), 0, $previous);
    }

    public function statusCode(): int
    {
        return $this->statusCode;
    }

    public static function badRequest(string $message = ''): self
    {
        return new self(400, $message);
    }

    public static function unauthorized(string $message = ''): self
    {
        return new self(401, $message);
    }

    public static function forbidden(string $message = ''): self
    {
        return new self(403, $message);
    }

    public static function notFound(string $message = ''): self
    {
        return new self(404, $message);
    }

    public static function serverError(string $message = ''): self
    {
        return new self(500, $message);
    }

    private static function defaultMessage(int $code): string
    {
        return match ($code) {
            400 => 'Nieprawidłowe żądanie.',
            401 => 'Wymagane logowanie.',
            403 => 'Brak uprawnień.',
            404 => 'Nie znaleziono zasobu.',
            default => 'Wystąpił błąd serwera.',
        };
    }
}
