<?php

namespace Tests\Unit;

use App\Enums\AuthProvider;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class AuthProviderTest extends TestCase
{
    public function test_maps_firebase_google_provider(): void
    {
        $this->assertSame(
            AuthProvider::Google,
            AuthProvider::fromFirebaseSignInProvider('google.com'),
        );
    }

    public function test_maps_firebase_apple_provider(): void
    {
        $this->assertSame(
            AuthProvider::Apple,
            AuthProvider::fromFirebaseSignInProvider('apple.com'),
        );
    }

    public function test_maps_firebase_facebook_provider(): void
    {
        $this->assertSame(
            AuthProvider::Facebook,
            AuthProvider::fromFirebaseSignInProvider('facebook.com'),
        );
    }

    public function test_rejects_unsupported_firebase_provider(): void
    {
        $this->expectException(InvalidArgumentException::class);

        AuthProvider::fromFirebaseSignInProvider('twitter.com');
    }
}
