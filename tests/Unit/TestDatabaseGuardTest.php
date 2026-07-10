<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use Tests\TestDatabaseGuard;

class TestDatabaseGuardTest extends TestCase
{
    public function test_blocked_mysql_database_names_include_expenses(): void
    {
        $this->assertContains('expenses', TestDatabaseGuard::BLOCKED_MYSQL_DATABASES);
    }
}
