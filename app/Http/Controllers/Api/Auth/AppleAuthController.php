<?php

namespace App\Http\Controllers\Api\Auth;

use App\Enums\AuthProvider;
use App\Http\Controllers\Api\Auth\Concerns\PersistsUserTimezone;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Rules\IanaTimezone;
use App\Services\AppleIdTokenVerifier;
use App\Services\FirebaseAccountLinker;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use InvalidArgumentException;

class AppleAuthController extends Controller
{
    use PersistsUserTimezone;

    public function __invoke(
        Request $request,
        AppleIdTokenVerifier $verifier,
        FirebaseAccountLinker $linker,
    ): JsonResponse {
        $validated = $request->validate([
            'id_token' => ['required', 'string'],
            'timezone' => ['nullable', 'string', 'max:255', new IanaTimezone],
            'name' => ['nullable', 'string', 'max:255'],
        ]);

        try {
            $claims = $verifier->verify($validated['id_token']);
        } catch (InvalidArgumentException $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], 422);
        }

        $appleUid = 'apple:'.(string) $claims->sub;
        $email = isset($claims->email) ? (string) $claims->email : '';

        if ($email === '') {
            return response()->json([
                'message' => 'Apple token is missing an email claim.',
            ], 422);
        }

        $displayName = trim((string) ($validated['name'] ?? ''));
        if ($displayName === '') {
            $displayName = strstr($email, '@', true) ?: 'User';
        }

        $user = User::query()->where('firebase_uid', $appleUid)->first();

        if (! $user) {
            $user = User::query()->where('email', $email)->first();

            if (! $user) {
                $user = User::query()->create([
                    'name' => $displayName,
                    'email' => $email,
                    'password' => Hash::make(Str::password(32)),
                    'firebase_uid' => $appleUid,
                    'password_auth_enabled' => false,
                    'auth_provider' => AuthProvider::Apple->value,
                    'email_verified_at' => filter_var($claims->email_verified ?? false, FILTER_VALIDATE_BOOLEAN)
                        ? now()
                        : null,
                    'timezone' => User::normalizeTimezone($request->input('timezone')),
                ]);
            } else {
                $user->forceFill(
                    $linker->updatesForSocialSignIn($user, $appleUid, AuthProvider::Apple),
                )->save();
            }
        } else {
            $user->forceFill(
                $linker->updatesForSocialSignIn($user, $appleUid, AuthProvider::Apple),
            )->save();
        }

        $this->applyTimezoneFromValidated($user, $validated);

        $token = $user->createToken('mobile')->plainTextToken;

        return response()->json([
            'message' => 'Signed in with Apple successfully.',
            'token' => $token,
            'token_type' => 'Bearer',
            'user' => $user->fresh()->toApiArray(),
        ]);
    }
}
