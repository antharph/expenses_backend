<?php

namespace App\Support;

use App\Models\User;
use Carbon\Carbon;

/**
 * Calendar-day boundaries for expense dates use the authenticated user's
 * {@see User::$timezone} (IANA identifier, e.g. Asia/Manila). Values are stored
 * in UTC on {@see \App\Models\Expense::$transaction_at}.
 */
final class ExpenseTimezone
{
    public function __construct(private readonly string $timezone) {}

    public static function forUser(?User $user): self
    {
        return new self($user?->displayTimezone() ?? 'UTC');
    }

    public function name(): string
    {
        return $this->timezone;
    }

    /**
     * Inclusive start of a local calendar day (00:00:00.000), as UTC.
     */
    public function startOfLocalDayUtc(string $ymd): Carbon
    {
        $boundary = $this->parseDateBoundary($ymd);

        if (! str_contains($ymd, ' ')) {
            $boundary->startOfDay();
        }

        return $boundary->utc();
    }

    /**
     * Inclusive end of a local calendar day (23:59:59.999999), as UTC.
     */
    public function endOfLocalDayUtc(string $ymd): Carbon
    {
        $boundary = $this->parseDateBoundary($ymd);

        if (! str_contains($ymd, ' ')) {
            $boundary->endOfDay();
        }

        return $boundary->utc();
    }

    private function parseDateBoundary(string $value): Carbon
    {
        $format = str_contains($value, ' ') ? 'Y-m-d H:i:s' : 'Y-m-d';

        return Carbon::createFromFormat($format, $value, $this->timezone);
    }
}
