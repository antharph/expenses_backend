<?php

namespace App\Rules;

use Closure;
use DateTimeZone;
use Illuminate\Contracts\Validation\ValidationRule;

class IanaTimezone implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if ($value === null || $value === '') {
            return;
        }

        try {
            new DateTimeZone((string) $value);
        } catch (\Exception) {
            $fail('The :attribute must be a valid IANA timezone identifier.');
        }
    }
}
