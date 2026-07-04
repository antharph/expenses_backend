<?php

namespace App\Http\Controllers\Api\Auth;

use App\Enums\AuthProvider;
use App\Http\Controllers\Api\Auth\Concerns\PersistsUserTimezone;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Rules\IanaTimezone;
use App\Services\FirebaseAccountLinker;
use App\Services\FirebaseIdTokenVerifier;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use InvalidArgumentException;
use stdClass;

class FirebaseAuthController extends Controller
{
    use PersistsUserTimezone;

    public function __invoke(
        Request $request,
        FirebaseIdTokenVerifier $verifier,
        FirebaseAccountLinker $linker,
    ): JsonResponse {
        $validated = $request->validate([
            'id_token' => ['required', 'string'],
            'timezone' => ['nullable', 'string', 'max:255', new IanaTimezone],
        ]);

        try {
            $claims = $verifier->verify($validated['id_token']);
            $authProvider = $this->resolveAuthProvider($claims);
        } catch (InvalidArgumentException $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], 422);
        }

        $firebaseUid = (string) $claims->sub;
        $email = isset($claims->email) ? (string) $claims->email : '';

        if ($email === '') {
            return response()->json([
                'message' => 'Firebase token is missing an email claim.',
            ], 422);
        }

        $user = User::query()->where('firebase_uid', $firebaseUid)->first();

        if (! $user) {
            $user = User::query()->where('email', $email)->first();

            if (! $user) {
                $user = User::query()->create([
                    'name' => (string) ($claims->name ?? strstr($email, '@', true) ?: 'User'),
                    'email' => $email,
                    'password' => Hash::make(Str::password(32)),
                    'firebase_uid' => $firebaseUid,
                    'password_auth_enabled' => false,
                    'auth_provider' => $authProvider->value,
                    'email_verified_at' => ($claims->email_verified ?? false) ? now() : null,
                    'timezone' => User::normalizeTimezone($request->input('timezone')),
                ]);
            } else {
                $user->forceFill(
                    $linker->updatesForSocialSignIn($user, $firebaseUid, $authProvider),
                )->save();
            }
        } else {
            $user->forceFill(
                $linker->updatesForSocialSignIn($user, $firebaseUid, $authProvider),
            )->save();
        }

        $this->applyTimezoneFromValidated($user, $validated);

        $token = $user->createToken('mobile')->plainTextToken;

        return response()->json([
            'message' => $this->successMessage($authProvider),
            'token' => $token,
            'token_type' => 'Bearer',
            'user' => $user->fresh()->toApiArray(),
        ]);
    }

    private function resolveAuthProvider(stdClass $claims): AuthProvider
    {
        $firebase = $claims->firebase ?? null;
        $signInProvider = is_object($firebase) ? (string) ($firebase->sign_in_provider ?? '') : '';

        if ($signInProvider === '') {
            throw new InvalidArgumentException('Firebase token is missing a sign-in provider.');
        }

        return AuthProvider::fromFirebaseSignInProvider($signInProvider);
    }

    private function successMessage(AuthProvider $authProvider): string
    {
        return match ($authProvider) {
            AuthProvider::Google => 'Signed in with Google successfully.',
            AuthProvider::Apple => 'Signed in with Apple successfully.',
            AuthProvider::Facebook => 'Signed in with Facebook successfully.',
            AuthProvider::Email => 'Signed in successfully.',
        };
    }
}
