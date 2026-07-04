<?php

namespace Tests\Unit;

use App\Enums\AuthProvider;
use App\Models\User;
use App\Services\FirebaseAccountLinker;
use PHPUnit\Framework\TestCase;

class FirebaseAccountLinkerTest extends TestCase
{
    public function test_preserves_password_auth_when_linking_email_user(): void
    {
        $user = new User([
            'password_auth_enabled' => true,
            'auth_provider' => AuthProvider::Email->value,
        ]);

        $updates = (new FirebaseAccountLinker)->updatesForSocialSignIn(
            $user,
            'firebase-linked-uid',
            AuthProvider::Google,
        );

        $this->assertSame(['firebase_uid' => 'firebase-linked-uid'], $updates);
    }

    public function test_updates_provider_for_social_only_user(): void
    {
        $user = new User([
            'password_auth_enabled' => false,
            'auth_provider' => AuthProvider::Google->value,
        ]);

        $updates = (new FirebaseAccountLinker)->updatesForSocialSignIn(
            $user,
            'firebase-apple-uid',
            AuthProvider::Apple,
        );

        $this->assertSame([
            'firebase_uid' => 'firebase-apple-uid',
            'auth_provider' => AuthProvider::Apple->value,
        ], $updates);
    }
}
