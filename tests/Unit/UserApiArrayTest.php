<?php

namespace Tests\Unit;

use App\Enums\AuthProvider;
use App\Models\User;
use PHPUnit\Framework\TestCase;

class UserApiArrayTest extends TestCase
{
    public function test_includes_auth_provider_in_api_payload(): void
    {
        $user = new User([
            'id' => 1,
            'name' => 'Ada Lovelace',
            'email' => 'ada@example.com',
            'password_auth_enabled' => true,
            'auth_provider' => AuthProvider::Email->value,
        ]);

        $payload = $user->toApiArray();

        $this->assertSame('Ada Lovelace', $payload['name']);
        $this->assertSame('ada@example.com', $payload['email']);
        $this->assertTrue($payload['password_auth_enabled']);
        $this->assertSame('email', $payload['auth_provider']);
    }

    public function test_defaults_auth_provider_to_email_when_missing(): void
    {
        $user = new User([
            'id' => 2,
            'name' => 'Social User',
            'email' => 'social@example.com',
            'password_auth_enabled' => false,
        ]);

        $this->assertSame('email', $user->toApiArray()['auth_provider']);
    }
}
