<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\ProfileUpdateRequest;
use Illuminate\Http\JsonResponse;

class UserProfileController extends Controller
{
    public function update(ProfileUpdateRequest $request): JsonResponse
    {
        $user = $request->user();
        $user->update($request->validated());

        return response()->json([
            'message' => 'Profile updated.',
            'user' => $user->fresh()->toApiArray(),
        ]);
    }
}
