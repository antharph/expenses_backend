<?php

namespace App\Services;

use App\Contracts\DeletesFirebaseUsers;
use Kreait\Firebase\Contract\Auth as FirebaseAuth;
use Kreait\Firebase\Exception\Auth\UserNotFound;
use Kreait\Firebase\Factory;
use RuntimeException;

class FirebaseUserDeleter implements DeletesFirebaseUsers
{
    private ?FirebaseAuth $auth = null;

    public function delete(string $firebaseUid): void
    {
        try {
            $this->auth()->deleteUser($firebaseUid);
        } catch (UserNotFound) {
            // Idempotent: user may already be removed from Firebase Auth.
        }
    }

    private function auth(): FirebaseAuth
    {
        if ($this->auth !== null) {
            return $this->auth;
        }

        $credentials = config('services.firebase.credentials');

        if (! is_string($credentials) || $credentials === '' || ! is_readable($credentials)) {
            throw new RuntimeException('Firebase credentials are not configured.');
        }

        $this->auth = (new Factory)
            ->withServiceAccount($credentials)
            ->createAuth();

        return $this->auth;
    }
}
