<?php

declare(strict_types=1);

namespace App\Core;

use PDO;
use PDOException;

class Database
{
    private static ?PDO $connection = null;

    public static function getConnection(array $config): PDO
    {
        if (self::$connection instanceof PDO) {
            return self::$connection;
        }

        $db = $config['database'];
        $dsn = sprintf(
            'pgsql:host=%s;port=%d;dbname=%s',
            $db['host'],
            $db['port'],
            $db['name']
        );

        try {
            self::$connection = new PDO($dsn, $db['user'], $db['password'], [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ]);
        } catch (PDOException $e) {
            throw new PDOException('Połączenie z bazą danych nie powiodło się: ' . $e->getMessage(), (int) $e->getCode(), $e);
        }

        return self::$connection;
    }

    public static function reset(): void
    {
        self::$connection = null;
    }
}
