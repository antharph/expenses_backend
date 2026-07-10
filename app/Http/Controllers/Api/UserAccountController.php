<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\DeleteUserAccountRequest;
use App\Services\DeleteUserAccountService;
use Illuminate\Http\JsonResponse;
use RuntimeException;

class UserAccountController extends Controller
{
    public function destroy(
        DeleteUserAccountRequest $request,
        DeleteUserAccountService $deleteUserAccount,
    ): JsonResponse {
        $user = $request->user();

        if ($user === null || $user->isDeletedAccount()) {
            return response()->json([
                'message' => 'Unauthenticated.',
            ], 401);
        }

        try {
            $deleteUserAccount->delete($user);
        } catch (RuntimeException $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], 503);
        }

        return response()->json([
            'message' => 'Account deleted.',
        ]);
    }
}
