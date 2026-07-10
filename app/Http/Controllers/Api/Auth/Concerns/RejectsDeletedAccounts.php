<?php

namespace App\Http\Controllers\Api\Auth\Concerns;

use App\Models\User;
use Illuminate\Validation\ValidationException;

trait RejectsDeletedAccounts
{
    protected function rejectIfDeletedAccount(?User $user): void
    {
        if ($user?->isDeletedAccount()) {
            throw ValidationException::withMessages([
                'email' => __('auth.failed'),
            ]);
        }
    }
}
