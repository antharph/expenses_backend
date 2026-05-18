<?php

namespace App\Http\Controllers\Api\Auth\Concerns;

use App\Models\User;
use Illuminate\Http\Request;

trait PersistsUserTimezone
{
    /**
     * @param  array<string, mixed>  $validated
     */
    protected function applyTimezoneFromValidated(User $user, array $validated): void
    {
        if (! array_key_exists('timezone', $validated) || $validated['timezone'] === null) {
            return;
        }

        $normalized = User::normalizeTimezone((string) $validated['timezone']);

        if ($user->timezone === $normalized) {
            return;
        }

        $user->forceFill(['timezone' => $normalized])->save();
    }
}
