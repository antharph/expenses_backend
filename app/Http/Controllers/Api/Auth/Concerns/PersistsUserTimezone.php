<?php

namespace App\Http\Controllers\Api\Auth\Concerns;

use App\Models\User;
use Illuminate\Http\Request;

trait PersistsUserTimezone
{
    protected function applyTimezoneFromRequest(User $user, Request $request): void
    {
        if (! $request->filled('timezone')) {
            return;
        }

        $user->forceFill([
            'timezone' => User::normalizeTimezone($request->string('timezone')->toString()),
        ])->save();
    }
}
