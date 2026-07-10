<?php

namespace Tests\Unit;

use App\Models\User;
use PHPUnit\Framework\TestCase;

class UserDeletedAccountTest extends TestCase
{
    public function test_tombstone_email_includes_user_id_and_original_email(): void
    {
        $this->assertSame(
            'deleted-42-user@example.com',
            User::tombstoneEmail(42, 'user@example.com'),
        );
    }

    public function test_is_deleted_account_detects_tombstone_prefix(): void
    {
        $deleted = new User(['email' => 'deleted-1-user@example.com']);
        $active = new User(['email' => 'user@example.com']);

        $this->assertTrue($deleted->isDeletedAccount());
        $this->assertFalse($active->isDeletedAccount());
    }
}
