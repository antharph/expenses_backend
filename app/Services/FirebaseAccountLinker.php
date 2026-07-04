<?php

namespace App\Services;

use App\Enums\AuthProvider;
use App\Models\User;

class FirebaseAccountLinker
{
    /**
     * @return array<string, mixed>
     */
    public function updatesForSocialSignIn(
        User $user,
        string $firebaseUid,
        AuthProvider $authProvider,
    ): array {
        $updates = ['firebase_uid' => $firebaseUid];

        if (! $user->password_auth_enabled) {
            $updates['auth_provider'] = $authProvider->value;
        }

        return $updates;
    }
}
