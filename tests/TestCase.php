<?php

namespace Tests;

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\Schema;
use PDO;
use RuntimeException;

abstract class TestCase extends BaseTestCase
{
    use DatabaseTransactions;

    private static bool $testSchemaReady = false;

    public function createApplication(): Application
    {
        $app = require __DIR__.'/../bootstrap/app.php';
        $app->make(Kernel::class)->bootstrap();

        self::applyIsolatedDatabaseConfig($app);

        return $app;
    }

    protected function setUp(): void
    {
        parent::setUp();

        TestDatabaseGuard::assertSafe();
        $this->ensureTestSchemaExists();
    }

    /**
     * Never run application tests against the development database from `.env`.
     */
    public static function applyIsolatedDatabaseConfig(Application $app): void
    {
        $config = $app['config'];
        $default = (string) $config->get('database.default');
        $database = (string) $config->get("database.connections.{$default}.database");

        if ($default === 'sqlite' && extension_loaded('pdo_sqlite')) {
            $config->set('database.default', 'sqlite');
            $config->set('database.connections.sqlite.database', ':memory:');

            return;
        }

        $mysql = $config->get('database.connections.mysql');
        if (! is_array($mysql)) {
            throw new RuntimeException('MySQL connection is not configured for tests.');
        }

        $testDatabase = $database === 'expenses'
            ? 'expenses_test'
            : (str_ends_with($database, '_test') ? $database : $database.'_test');

        self::ensureMysqlDatabaseExists($mysql, $testDatabase);

        $config->set('database.default', 'mysql');
        $config->set('database.connections.mysql.database', $testDatabase);
    }

    /**
     * @param  array<string, mixed>  $mysql
     */
    private static function ensureMysqlDatabaseExists(array $mysql, string $database): void
    {
        $host = (string) ($mysql['host'] ?? '127.0.0.1');
        $port = (string) ($mysql['port'] ?? '3306');
        $username = (string) ($mysql['username'] ?? 'root');
        $password = (string) ($mysql['password'] ?? '');
        $charset = (string) ($mysql['charset'] ?? 'utf8mb4');
        $collation = (string) ($mysql['collation'] ?? 'utf8mb4_unicode_ci');

        $pdo = new PDO(
            "mysql:host={$host};port={$port}",
            $username,
            $password,
            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION],
        );

        $pdo->exec(
            "CREATE DATABASE IF NOT EXISTS `{$database}` CHARACTER SET {$charset} COLLATE {$collation}",
        );
    }

    protected function ensureTestSchemaExists(): void
    {
        if (self::$testSchemaReady) {
            return;
        }

        if (! Schema::hasTable('users')) {
            $this->artisan('migrate', ['--force' => true]);
        }

        self::$testSchemaReady = true;
    }
}
