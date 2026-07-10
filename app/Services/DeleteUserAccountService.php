<?php

namespace App\Services;

use App\Contracts\DeletesFirebaseUsers;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class DeleteUserAccountService
{
    public function __construct(
        private readonly DeletesFirebaseUsers $firebaseUserDeleter,
    ) {}

    public function delete(User $user): void
    {
        $firebaseUid = $user->firebase_uid;

        if (is_string($firebaseUid) && $firebaseUid !== '' && ! str_starts_with($firebaseUid, 'apple:')) {
            $this->firebaseUserDeleter->delete($firebaseUid);
        }

        $user->tokens()->delete();

        $originalEmail = $user->email;

        $user->forceFill([
            'email' => User::tombstoneEmail($user->id, $originalEmail),
            'firebase_uid' => null,
            'password_auth_enabled' => false,
            'name' => 'Deleted User',
            'password' => Hash::make(Str::password(64)),
        ])->save();
    }
}
