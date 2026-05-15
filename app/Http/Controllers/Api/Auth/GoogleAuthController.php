<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\FirebaseIdTokenVerifier;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use InvalidArgumentException;

class GoogleAuthController extends Controller
{
    public function __invoke(Request $request, FirebaseIdTokenVerifier $verifier): JsonResponse
    {
        $validated = $request->validate([
            'id_token' => ['required', 'string'],
        ]);

        try {
            $claims = $verifier->verify($validated['id_token']);
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

            if ($user) {
                $user->forceFill(['firebase_uid' => $firebaseUid])->save();
            } else {
                $user = User::query()->create([
                    'name' => (string) ($claims->name ?? strstr($email, '@', true) ?: 'User'),
                    'email' => $email,
                    'password' => Hash::make(Str::password(32)),
                    'firebase_uid' => $firebaseUid,
                    'email_verified_at' => ($claims->email_verified ?? false) ? now() : null,
                ]);
            }
        }

        $token = $user->createToken('mobile')->plainTextToken;

        return response()->json([
            'message' => 'Signed in with Google successfully.',
            'token' => $token,
            'token_type' => 'Bearer',
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
            ],
        ]);
    }
}
