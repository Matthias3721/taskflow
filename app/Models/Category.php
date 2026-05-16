<?php

declare(strict_types=1);

namespace App\Models;

class Category
{
    public function __construct(
        public readonly ?int $id,
        public readonly string $name,
        public readonly ?string $color = null,
    ) {
    }

    /** @param array<string, mixed> $row */
    public static function fromArray(array $row): self
    {
        return new self(
            isset($row['id']) ? (int) $row['id'] : null,
            (string) ($row['name'] ?? ''),
            $row['color'] ?? null,
        );
    }
}
