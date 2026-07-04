<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\PasswordUpdateRequest;
use Illuminate\Http\JsonResponse;

class UserPasswordController extends Controller
{
    public function update(PasswordUpdateRequest $request): JsonResponse
    {
        $user = $request->user();

        if (! $user->password_auth_enabled) {
            return response()->json([
                'message' => 'Password changes are not available for Google sign-in accounts.',
            ], 403);
        }

        $user->update([
            'password' => $request->password,
        ]);

        return response()->json([
            'message' => 'Password updated.',
        ]);
    }
}
