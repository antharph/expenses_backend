<?php

namespace App\Support;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\Relation;

final class ExpenseDateRangeFilter
{
    /**
     * Restrict results to expenses whose transaction instant falls on calendar
     * days between {@code $from} and {@code $to} (inclusive) in the display
     * timezone. Uses {@code transaction_at}; when null, {@code created_at} is
     * used so filtering matches the API "date" field.
     *
     * @param  Builder<\App\Models\Expense>|Relation<\App\Models\Expense, *, *>  $query
     */
    public static function apply(Builder|Relation $query, ?string $from, ?string $to): void
    {
        if ($from === null && $to === null) {
            return;
        }

        $effectiveAt = 'COALESCE(transaction_at, created_at)';

        if ($from !== null) {
            $start = ExpenseTimezone::startOfLocalDayUtc($from);
            $query->whereRaw("{$effectiveAt} >= ?", [$start]);
        }

        if ($to !== null) {
            $end = ExpenseTimezone::endOfLocalDayUtc($to);
            $query->whereRaw("{$effectiveAt} <= ?", [$end]);
        }
    }
}
