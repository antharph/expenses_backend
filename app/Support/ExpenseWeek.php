<?php

namespace App\Support;

use Carbon\Carbon;
use Carbon\CarbonInterface;

/**
 * Sunday–Saturday weeks in the user's display timezone. Week 1 is the week
 * that contains January 1 (its Sunday may fall in the prior calendar year).
 */
final class ExpenseWeek
{
    public static function weekStart(int $year, int $week, ExpenseTimezone $timezone): CarbonInterface
    {
        $display = $timezone->name();
        $jan1 = Carbon::create($year, 1, 1, 0, 0, 0, $display);
        $firstSunday = $jan1->copy()->startOfWeek(Carbon::SUNDAY);

        return $firstSunday->copy()->addWeeks($week - 1)->startOfDay();
    }

    public static function weekEnd(int $year, int $week, ExpenseTimezone $timezone): CarbonInterface
    {
        return self::weekStart($year, $week, $timezone)->copy()->addDays(6)->endOfDay();
    }

    /**
     * @return array{0: string, 1: string} start and end Y-m-d (inclusive)
     */
    public static function weekDateRange(int $year, int $week, ExpenseTimezone $timezone): array
    {
        $start = self::weekStart($year, $week, $timezone);
        $end = $start->copy()->addDays(6);

        return [$start->format('Y-m-d'), $end->format('Y-m-d')];
    }

    public static function weeksInYear(int $year, ExpenseTimezone $timezone): int
    {
        $display = $timezone->name();
        $yearEnd = Carbon::create($year, 12, 31, 0, 0, 0, $display);

        for ($week = 52; $week >= 1; $week--) {
            if (self::weekStart($year, $week, $timezone)->lte($yearEnd)) {
                return $week;
            }
        }

        return 1;
    }

    public static function weekOverlapsYear(int $year, int $week, ExpenseTimezone $timezone): bool
    {
        $display = $timezone->name();
        $yearStart = Carbon::create($year, 1, 1, 0, 0, 0, $display);
        $yearEnd = Carbon::create($year, 12, 31, 23, 59, 59, $display);
        $weekStart = self::weekStart($year, $week, $timezone);
        $weekEnd = $weekStart->copy()->addDays(6)->endOfDay();

        return $weekStart->lte($yearEnd) && $weekEnd->gte($yearStart);
    }

    /**
     * @return array{year: int, week: int}
     */
    public static function previous(int $year, int $week, ExpenseTimezone $timezone): array
    {
        if ($week > 1) {
            return ['year' => $year, 'week' => $week - 1];
        }

        $prevYear = $year - 1;

        return ['year' => $prevYear, 'week' => self::weeksInYear($prevYear, $timezone)];
    }

    /**
     * @return array{year: int, week: int}
     */
    public static function next(int $year, int $week, ExpenseTimezone $timezone): array
    {
        if ($week < self::weeksInYear($year, $timezone)) {
            return ['year' => $year, 'week' => $week + 1];
        }

        return ['year' => $year + 1, 'week' => 1];
    }

    public static function weekUrl(int $year, int $week): string
    {
        return url("/api/v1/expenses/y/{$year}/w/{$week}");
    }

    /**
     * @return list<CarbonInterface> Seven local midnights, Sunday through Saturday.
     */
    public static function daysInWeek(int $year, int $week, ExpenseTimezone $timezone): array
    {
        $start = self::weekStart($year, $week, $timezone);
        $days = [];
        for ($offset = 0; $offset < 7; $offset++) {
            $days[] = $start->copy()->addDays($offset)->startOfDay();
        }

        return $days;
    }
}
