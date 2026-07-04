<?php

namespace App\Http\Controllers\Api\Auth;

use App\Enums\AuthProvider;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Rules\IanaTimezone;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class RegisterController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'confirmed', Password::defaults()],
            'timezone' => ['nullable', 'string', 'max:255', new IanaTimezone],
        ]);

        $user = User::query()->create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'password_auth_enabled' => true,
            'auth_provider' => AuthProvider::Email->value,
            'timezone' => User::normalizeTimezone($validated['timezone'] ?? null),
        ]);

        $token = $user->createToken('mobile')->plainTextToken;

        return response()->json([
            'message' => 'Registered successfully.',
            'token' => $token,
            'token_type' => 'Bearer',
            'user' => $user->toApiArray(),
        ], 201);
    }
}
