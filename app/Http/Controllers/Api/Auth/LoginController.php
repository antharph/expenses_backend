<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Api\Auth\Concerns\PersistsUserTimezone;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Rules\IanaTimezone;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class LoginController extends Controller
{
    use PersistsUserTimezone;

    public function __invoke(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
            'timezone' => ['nullable', 'string', 'max:255', new IanaTimezone],
        ]);

        $user = User::query()->where('email', $validated['email'])->first();

        if (! $user || ! Hash::check($validated['password'], $user->password)) {
            throw ValidationException::withMessages([
                'email' => __('auth.failed'),
            ]);
        }

        $this->applyTimezoneFromValidated($user, $validated);

        $token = $user->createToken('mobile')->plainTextToken;

        return response()->json([
            'message' => 'Logged in successfully.',
            'token' => $token,
            'token_type' => 'Bearer',
            'user' => $user->toApiArray(),
        ]);
    }
}
