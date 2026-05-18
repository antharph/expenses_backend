<?php

namespace App\Support;

use Carbon\Carbon;

/**
 * Calendar-day boundaries for expense dates use the configured display timezone
 * ({@see config('app.expenses_display_timezone')}, env DEFAULT_TIMEZONE). Values
 * are stored in UTC on {@see \App\Models\Expense::$transaction_at}.
 */
final class ExpenseTimezone
{
    public static function display(): string
    {
        $tz = config('app.expenses_display_timezone') ?? config('app.timezone', 'UTC');

        return (string) $tz;
    }

    /**
     * Inclusive start of a local calendar day (00:00:00.000), as UTC.
     */
    public static function startOfLocalDayUtc(string $ymd): Carbon
    {
        return Carbon::createFromFormat('Y-m-d', $ymd, self::display())
            ->startOfDay()
            ->utc();
    }

    /**
     * Inclusive end of a local calendar day (23:59:59.999999), as UTC.
     */
    public static function endOfLocalDayUtc(string $ymd): Carbon
    {
        return Carbon::createFromFormat('Y-m-d', $ymd, self::display())
            ->endOfDay()
            ->utc();
    }
}
