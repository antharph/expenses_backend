<?php

declare(strict_types=1);

namespace App\Support;

use App\Models\Expense;
use App\Models\User;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use InvalidArgumentException;

/**
 * Converts client-supplied calendar date/time (from the user's locale UI) into a UTC
 * instant for {@see Expense::$transaction_at}, using the authenticated
 * user's {@see User::$timezone}.
 */
final class ExpenseTransactionAt
{
    private const int STALE_RECEIPT_DAYS = 15;

    /**
     * @param  string  $dateYmd  Calendar date {@code Y-m-d} (device UI; interpreted in user TZ)
     * @param  string|null  $timeHi  Clock time {@code H:i} or {@code H:i:s}; when null, copies time from {@code $preserveLocalTime}
     */
    public static function toUtc(
        string $dateYmd,
        ?string $timeHi,
        ExpenseTimezone $timezone,
        ?CarbonInterface $preserveLocalTime = null,
    ): Carbon {
        $local = Carbon::createFromFormat('!Y-m-d', $dateYmd, $timezone->name());

        if ($timeHi !== null && trim($timeHi) !== '') {
            $parts = self::parseTime($timeHi);
            $local->setTime($parts['hour'], $parts['minute'], $parts['second']);
        } elseif ($preserveLocalTime !== null) {
            $preserved = $preserveLocalTime->copy()->timezone($timezone->name());
            $local->setTime(
                (int) $preserved->format('H'),
                (int) $preserved->format('i'),
                (int) $preserved->format('s'),
            );
        } else {
            $local->startOfDay();
        }

        return $local->utc();
    }

    /**
     * Receipt / Gemini {@code transaction_at}: wall-clock components from ISO 8601,
     * interpreted in the authenticated user's timezone, stored as UTC.
     */
    public static function fromReceiptIso8601(string $raw, ExpenseTimezone $timezone): Carbon
    {
        $parsed = Carbon::parse($raw);

        return Carbon::create(
            $parsed->year,
            $parsed->month,
            $parsed->day,
            $parsed->hour,
            $parsed->minute,
            $parsed->second,
            $timezone->name(),
        )->utc();
    }

    /**
     * Like {@see fromReceiptIso8601}, but when the receipt datetime is more than
     * {@see STALE_RECEIPT_DAYS} before the user's current local datetime, returns
     * that current local instant in UTC instead (e.g. old permit dates or backlog scans).
     */
    public static function fromReceiptIso8601OrNowWhenStale(
        string $raw,
        ExpenseTimezone $timezone,
        ?CarbonInterface $now = null,
    ): Carbon {
        $nowLocal = ($now ?? Carbon::now())->copy()->timezone($timezone->name());
        $receiptUtc = self::fromReceiptIso8601($raw, $timezone);
        $receiptLocal = $receiptUtc->copy()->timezone($timezone->name());

        $staleBefore = $nowLocal->copy()->startOfDay()->subDays(self::STALE_RECEIPT_DAYS);

        if ($receiptLocal->copy()->startOfDay()->lt($staleBefore)) {
            return $nowLocal->utc();
        }

        return $receiptUtc;
    }

    /**
     * @return array{hour: int, minute: int, second: int}
     */
    private static function parseTime(string $timeHi): array
    {
        if (! preg_match('/^(?<hour>\d{2}):(?<minute>\d{2})(?::(?<second>\d{2}))?$/', trim($timeHi), $matches)) {
            throw new InvalidArgumentException('Invalid transaction_time format.');
        }

        $hour = (int) $matches['hour'];
        $minute = (int) $matches['minute'];
        $second = isset($matches['second']) ? (int) $matches['second'] : 0;

        if ($hour > 23 || $minute > 59 || $second > 59) {
            throw new InvalidArgumentException('Invalid transaction_time format.');
        }

        return [
            'hour' => $hour,
            'minute' => $minute,
            'second' => $second,
        ];
    }
}
