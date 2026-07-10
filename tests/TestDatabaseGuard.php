<?php

namespace Tests;

use RuntimeException;

final class TestDatabaseGuard
{
    /** @var list<string> */
    public const BLOCKED_MYSQL_DATABASES = [
        'expenses',
    ];

    public static function assertSafe(): void
    {
        $connection = (string) config('database.default');
        $database = (string) config("database.connections.{$connection}.database");

        if ($connection === 'sqlite' && $database === ':memory:') {
            return;
        }

        if ($connection === 'mysql' && str_ends_with($database, '_test')) {
            return;
        }

        if ($connection === 'mysql' && in_array($database, self::BLOCKED_MYSQL_DATABASES, true)) {
            throw new RuntimeException(
                "Refusing to run tests against the development database [{$database}]. "
                .'Use sqlite :memory: (see .env.testing) or a dedicated *_test database.',
            );
        }

        throw new RuntimeException(
            "Unsafe test database configuration [{$connection}:{$database}]. "
            .'Use sqlite :memory: or a MySQL database whose name ends with _test.',
        );
    }
}
