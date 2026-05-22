<?php

namespace App\Services;

use App\Enums\BudgetResetType;
use App\Models\Budget;
use App\Models\BudgetLog;
use App\Models\Expense;
use App\Models\User;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class BudgetService
{
    /**
     * Return the most recent reset date on or before today.
     *
     * @param  array<int, int|string>  $resetDays
     */
    public function calculateFixedDate(array $resetDays): Carbon
    {
        return $this->calculateFixedDateFor($resetDays, Carbon::now());
    }

    public function calculateInterval(Carbon $startDate, int $days): Carbon
    {
        if ($days < 1) {
            throw new InvalidArgumentException('Interval days must be at least 1.');
        }

        $periodStart = $startDate->copy()->startOfDay();
        $now = Carbon::now($periodStart->timezone)->startOfDay();

        while ($periodStart->copy()->addDays($days)->lessThanOrEqualTo($now)) {
            $periodStart->addDays($days);
        }

        return $periodStart;
    }

    public function getCurrentPeriodStart(Budget $budget): Carbon
    {
        $budget->loadMissing('user');

        $timezone = $budget->user?->displayTimezone() ?? 'UTC';

        return match ($this->resetType($budget)) {
            BudgetResetType::DateFixed => $this->calculateFixedDateFor(
                $this->resetDays($budget),
                Carbon::now($timezone),
            ),
            BudgetResetType::Interval => $this->calculateInterval(
                $this->intervalStartDate($budget)->timezone($timezone),
                $this->intervalDays($budget),
            ),
            BudgetResetType::Manual => $this->manualStartDate($budget)->timezone($timezone)->startOfDay(),
        };
    }

    /**
     * Current-period progress for API and dashboard clients.
     *
     * @return object{
     *     start_date: Carbon,
     *     end_date: Carbon|null,
     *     base_amount: string,
     *     rollover_amount: string,
     *     allocated_amount: string,
     *     amount_spent: string,
     *     amount_remaining: string,
     *     percentage_spent: float,
     *     is_over_budget: bool
     * }
     */
    public function getBudgetProgress(Budget $budget): object
    {
        $status = $this->getCurrentBudgetStatus($budget->id);
        $baseAmount = $this->formatMoney($budget->amount);
        $periodStart = $status->start_date->copy()->startOfDay();

        $currentLog = $budget->logs()
            ->where('start_date', '>=', $periodStart->copy()->utc())
            ->where('start_date', '<=', $periodStart->copy()->endOfDay()->utc())
            ->orderByDesc('start_date')
            ->first();

        $allocatedAmount = $currentLog !== null
            ? $this->formatMoney($currentLog->allocated_amount)
            : $baseAmount;

        $rolloverAmount = $this->formatMoney(
            max(0.0, (float) $allocatedAmount - (float) $baseAmount),
        );

        $amountRemaining = $this->formatMoney(
            (float) $allocatedAmount - (float) $status->amount_spent,
        );

        $percentageSpent = (float) $allocatedAmount > 0.0
            ? round(((float) $status->amount_spent / (float) $allocatedAmount) * 100, 2)
            : 0.0;

        return (object) [
            'start_date' => $status->start_date,
            'end_date' => $status->end_date,
            'base_amount' => $baseAmount,
            'rollover_amount' => $rolloverAmount,
            'allocated_amount' => $allocatedAmount,
            'amount_spent' => $status->amount_spent,
            'amount_remaining' => $amountRemaining,
            'percentage_spent' => $percentageSpent,
            'is_over_budget' => (float) $status->amount_spent > (float) $allocatedAmount,
        ];
    }

    /**
     * @return object{
     *     start_date: Carbon,
     *     end_date: Carbon|null,
     *     total_budget: string,
     *     amount_spent: string,
     *     amount_remaining: string,
     *     percentage_spent: float
     * }
     */
    public function getCurrentBudgetStatus(int $budgetId): object
    {
        $budget = Budget::query()
            ->with(['categories:id', 'logs', 'user'])
            ->findOrFail($budgetId);

        $startDate = $this->getCurrentPeriodStart($budget);
        $nextResetDate = $this->nextResetDate($budget, $startDate);
        $endDate = $nextResetDate?->copy()->subDay()->endOfDay();
        $queryEndDate = $endDate ?? Carbon::now($startDate->timezone)->endOfDay();

        $categoryIds = $budget->categories->pluck('id');
        $amountSpent = '0.00';

        if ($categoryIds->isNotEmpty()) {
            $amountSpent = $this->formatMoney(
                Expense::query()
                    ->where('user_id', $budget->user_id)
                    ->whereIn('category_id', $categoryIds)
                    ->whereRaw('COALESCE(transaction_at, created_at) >= ?', [$startDate->copy()->utc()])
                    ->whereRaw('COALESCE(transaction_at, created_at) <= ?', [$queryEndDate->copy()->utc()])
                    ->sum('total'),
            );
        }

        $totalBudget = $this->formatMoney($budget->amount);
        $amountRemaining = $this->formatMoney((float) $totalBudget - (float) $amountSpent);
        $percentageSpent = (float) $totalBudget > 0.0
            ? round(((float) $amountSpent / (float) $totalBudget) * 100, 2)
            : 0.0;

        return (object) [
            'start_date' => $startDate,
            'end_date' => $endDate,
            'total_budget' => $totalBudget,
            'amount_spent' => $amountSpent,
            'amount_remaining' => $amountRemaining,
            'percentage_spent' => $percentageSpent,
        ];
    }

    public function createNextCycleLog(Budget $budget): BudgetLog
    {
        $lastLog = $budget->logs()->latest('start_date')->first();
        $rolloverAmount = 0.0;

        if ($lastLog !== null) {
            $lastLog = $this->finalizeLogSpend($budget, $lastLog);
        }

        if ($budget->rollover && $lastLog !== null) {
            $rolloverAmount = max(
                0.0,
                (float) $lastLog->allocated_amount - (float) $lastLog->actual_spent,
            );
        }

        $startDate = $this->getNextStartDate($budget, $lastLog);

        return $budget->logs()->create([
            'start_date' => $startDate,
            'end_date' => $this->getNextEndDate($budget, $startDate),
            'allocated_amount' => $this->formatMoney((float) $budget->amount + $rolloverAmount),
            'actual_spent' => '0.00',
        ]);
    }

    public function createInitialManualCycleLog(Budget $budget, CarbonInterface $startDate): BudgetLog
    {
        $budget->loadMissing('user');
        $timezone = $budget->user?->displayTimezone() ?? 'UTC';
        $periodStart = Carbon::parse($startDate->toDateString(), $timezone)->startOfDay();

        return $budget->logs()->create([
            'start_date' => $periodStart,
            'end_date' => null,
            'allocated_amount' => $this->formatMoney($budget->amount),
            'actual_spent' => '0.00',
        ]);
    }

    public function finalizeManualCycle(Budget $budget): BudgetLog
    {
        if ($this->resetType($budget) !== BudgetResetType::Manual) {
            throw new InvalidArgumentException('Only manual budgets can be finalized manually.');
        }

        $budget->loadMissing(['categories:id', 'user']);

        return DB::transaction(function () use ($budget): BudgetLog {
            Budget::query()->whereKey($budget->id)->lockForUpdate()->firstOrFail();

            $lastLog = $budget->logs()
                ->latest('start_date')
                ->lockForUpdate()
                ->first();

            if ($lastLog === null) {
                return $this->ensureCurrentCycleLog($budget);
            }

            $lastLog = $this->finalizeLogSpend($budget, $lastLog);
            $rolloverAmount = $budget->rollover
                ? max(0.0, (float) $lastLog->allocated_amount - (float) $lastLog->actual_spent)
                : 0.0;

            $timezone = $budget->user?->displayTimezone() ?? 'UTC';
            $startDate = $lastLog->end_date
                ->copy()
                ->timezone($timezone)
                ->addDay()
                ->startOfDay();

            return $budget->logs()->create([
                'start_date' => $startDate,
                'end_date' => null,
                'allocated_amount' => $this->formatMoney((float) $budget->amount + $rolloverAmount),
                'actual_spent' => '0.00',
            ]);
        });
    }

    public function ensureCurrentCycleLog(Budget $budget): BudgetLog
    {
        $budget->loadMissing(['categories:id', 'user']);

        return DB::transaction(function () use ($budget): BudgetLog {
            Budget::query()->whereKey($budget->id)->lockForUpdate()->firstOrFail();

            $periodStart = $this->getCurrentPeriodStart($budget)->startOfDay();
            $existingLog = $budget->logs()
                ->where('start_date', '>=', $periodStart->copy()->utc())
                ->where('start_date', '<=', $periodStart->copy()->endOfDay()->utc())
                ->lockForUpdate()
                ->first();

            if ($existingLog !== null) {
                return $existingLog;
            }

            return $budget->logs()->create([
                'start_date' => $periodStart,
                'end_date' => $this->getNextEndDate($budget, $periodStart),
                'allocated_amount' => $this->formatMoney($budget->amount),
                'actual_spent' => '0.00',
            ]);
        });
    }

    public function syncBudgetCyclesForUser(User $user): void
    {
        $user->budgets()
            ->with(['categories:id', 'user'])
            ->orderBy('id')
            ->each(function (Budget $budget): void {
                $this->syncBudgetCycle($budget);
            });
    }

    private function getNextStartDate(Budget $budget, ?BudgetLog $lastLog = null): Carbon
    {
        $budget->loadMissing('user');
        $timezone = $budget->user?->displayTimezone() ?? 'UTC';

        if ($lastLog !== null) {
            $lastPeriodStart = $lastLog->start_date->copy()->timezone($timezone)->startOfDay();

            $nextReset = $this->nextResetDate($budget, $lastPeriodStart);
            if ($nextReset !== null) {
                return $nextReset->timezone($timezone)->startOfDay();
            }

            if ($lastLog->end_date !== null) {
                return $lastLog->end_date->copy()->timezone($timezone)->addDay()->startOfDay();
            }
        }

        return $this->getCurrentPeriodStart($budget);
    }

    private function syncBudgetCycle(Budget $budget): BudgetLog
    {
        $budget->loadMissing(['categories:id', 'user']);

        return DB::transaction(function () use ($budget): BudgetLog {
            Budget::query()->whereKey($budget->id)->lockForUpdate()->firstOrFail();

            $lastLog = $budget->logs()
                ->latest('start_date')
                ->lockForUpdate()
                ->first();

            if ($lastLog === null) {
                return $this->ensureCurrentCycleLog($budget);
            }

            if ($this->resetType($budget) === BudgetResetType::Manual) {
                return $lastLog;
            }

            $currentPeriodStart = $this->getCurrentPeriodStart($budget)->startOfDay();
            $lastPeriodStart = $lastLog->start_date->copy()
                ->timezone($currentPeriodStart->timezone)
                ->startOfDay();

            if ($lastPeriodStart->greaterThanOrEqualTo($currentPeriodStart)) {
                return $lastLog;
            }

            $lastLog = $this->finalizeLogSpend($budget, $lastLog);
            $rolloverAmount = 0.0;

            if (
                $budget->rollover
                && ! $this->hasMissedCycles($budget, $lastLog, $currentPeriodStart)
            ) {
                $rolloverAmount = max(
                    0.0,
                    (float) $lastLog->allocated_amount - (float) $lastLog->actual_spent,
                );
            }

            return $budget->logs()->create([
                'start_date' => $currentPeriodStart,
                'end_date' => $this->getNextEndDate($budget, $currentPeriodStart),
                'allocated_amount' => $this->formatMoney((float) $budget->amount + $rolloverAmount),
                'actual_spent' => '0.00',
            ]);
        });
    }

    /**
     * True when one or more automatic periods were skipped between the latest log and now.
     */
    private function hasMissedCycles(Budget $budget, BudgetLog $lastLog, Carbon $currentPeriodStart): bool
    {
        $immediateNextPeriodStart = $this->getNextStartDate($budget, $lastLog)
            ->timezone($currentPeriodStart->timezone)
            ->startOfDay();

        return ! $immediateNextPeriodStart->equalTo($currentPeriodStart);
    }

    /**
     * Spent amount for budget log API: running total for the open period, stored
     * actual_spent after the period is finalized.
     */
    public function getLogSpentAmount(Budget $budget, BudgetLog $log): string
    {
        if (! $this->isActivePeriodLog($budget, $log)) {
            return $this->formatMoney($log->actual_spent);
        }

        $budget->loadMissing('user');
        $timezone = $budget->user?->displayTimezone() ?? 'UTC';
        $startDate = $log->start_date->copy();
        $periodEnd = $log->end_date?->copy()
            ?? $this->getNextEndDate($budget, $log->start_date->copy());
        $nowEnd = Carbon::now($timezone)->endOfDay();
        $queryEndDate = $periodEnd !== null && $periodEnd->lessThan($nowEnd)
            ? $periodEnd
            : $nowEnd;

        return $this->budgetSpentBetween($budget, $startDate, $queryEndDate);
    }

    /**
     * Latest log for the current budget period with spending still in flight
     * (actual_spent is written only when the period is finalized).
     */
    public function isActivePeriodLog(Budget $budget, BudgetLog $log): bool
    {
        if ((float) $log->actual_spent > 0) {
            return false;
        }

        $latestLog = $budget->relationLoaded('logs')
            ? $budget->logs->first()
            : $budget->logs()->latest('start_date')->first();
        if ($latestLog === null || $latestLog->id !== $log->id) {
            return false;
        }

        $budget->loadMissing('user');
        $timezone = $budget->user?->displayTimezone() ?? 'UTC';
        $currentPeriodStart = $this->getCurrentPeriodStart($budget)
            ->timezone($timezone)
            ->startOfDay();
        $logPeriodStart = $log->start_date
            ->copy()
            ->timezone($timezone)
            ->startOfDay();

        return $logPeriodStart->equalTo($currentPeriodStart);
    }

    private function finalizeLogSpend(Budget $budget, BudgetLog $log): BudgetLog
    {
        if ($log->start_date === null) {
            return $log;
        }

        $endDate = $log->end_date?->copy()
            ?? $this->getNextEndDate($budget, $log->start_date->copy())
            ?? Carbon::now($log->start_date->timezone)->endOfDay();

        $log->forceFill([
            'end_date' => $endDate,
            'actual_spent' => $this->budgetSpentBetween($budget, $log->start_date->copy(), $endDate),
        ])->save();

        $log->categories()->sync($budget->categories->pluck('id')->all());

        return $log->refresh();
    }

    private function budgetSpentBetween(Budget $budget, CarbonInterface $startDate, CarbonInterface $endDate): string
    {
        $budget->loadMissing('categories:id');
        $categoryIds = $budget->categories->pluck('id');

        if ($categoryIds->isEmpty()) {
            return '0.00';
        }

        return $this->formatMoney(
            Expense::query()
                ->where('user_id', $budget->user_id)
                ->whereIn('category_id', $categoryIds)
                ->whereRaw('COALESCE(transaction_at, created_at) >= ?', [$startDate->copy()->utc()])
                ->whereRaw('COALESCE(transaction_at, created_at) <= ?', [$endDate->copy()->utc()])
                ->sum('total'),
        );
    }

    private function getNextEndDate(Budget $budget, Carbon $periodStart): ?Carbon
    {
        return $this->nextResetDate($budget, $periodStart)?->copy()->subDay()->endOfDay();
    }

    /**
     * @param  array<int, int|string>  $resetDays
     */
    private function calculateFixedDateFor(array $resetDays, Carbon $now): Carbon
    {
        $today = $now->copy()->startOfDay();

        foreach (array_reverse($this->fixedResetDatesForMonth($resetDays, $today)) as $candidate) {
            if ($candidate->lessThanOrEqualTo($today)) {
                return $candidate;
            }
        }

        return array_reverse($this->fixedResetDatesForMonth($resetDays, $today->copy()->subMonthNoOverflow()))[0];
    }

    private function nextResetDate(Budget $budget, Carbon $periodStart): ?Carbon
    {
        return match ($this->resetType($budget)) {
            BudgetResetType::DateFixed => $this->nextFixedResetDate($this->resetDays($budget), $periodStart),
            BudgetResetType::Interval => $periodStart->copy()->addDays($this->intervalDays($budget)),
            BudgetResetType::Manual => null,
        };
    }

    /**
     * @param  array<int, int|string>  $resetDays
     */
    private function nextFixedResetDate(array $resetDays, Carbon $periodStart): Carbon
    {
        $month = $periodStart->copy()->startOfMonth();

        for ($i = 0; $i < 14; $i++) {
            foreach ($this->fixedResetDatesForMonth($resetDays, $month->copy()->addMonthsNoOverflow($i)) as $candidate) {
                if ($candidate->greaterThan($periodStart)) {
                    return $candidate;
                }
            }
        }

        throw new InvalidArgumentException('Unable to project the next fixed reset date.');
    }

    /**
     * @param  array<int, int|string>  $resetDays
     * @return list<Carbon>
     */
    private function fixedResetDatesForMonth(array $resetDays, Carbon $month): array
    {
        $dates = [];

        foreach ($this->normalizeResetDays($resetDays, 31) as $day) {
            $date = $month->copy()
                ->day(min($day, $month->daysInMonth))
                ->startOfDay();

            $dates[$date->format('Y-m-d')] = $date;
        }

        usort($dates, static fn (Carbon $first, Carbon $second): int => $first <=> $second);

        return array_values($dates);
    }

    /**
     * @return array<int, int|string>
     */
    private function resetDays(Budget $budget): array
    {
        if (is_array($budget->reset_days)) {
            return $budget->reset_days;
        }

        if (is_string($budget->reset_days)) {
            $decoded = json_decode($budget->reset_days, true);

            if (is_array($decoded)) {
                return $decoded;
            }

            return [$budget->reset_days];
        }

        return [];
    }

    /**
     * @param  array<int, int|string>  $resetDays
     * @return list<int>
     */
    private function normalizeResetDays(array $resetDays, int $maximum): array
    {
        $days = array_values(array_unique(array_filter(
            array_map(static fn (int|string $day): int => (int) $day, $resetDays),
            static fn (int $day): bool => $day >= 1 && $day <= $maximum,
        )));

        if ($days === []) {
            throw new InvalidArgumentException('At least one valid reset day is required.');
        }

        rsort($days);

        return $days;
    }

    private function intervalDays(Budget $budget): int
    {
        return $this->normalizeResetDays($this->resetDays($budget), 366)[0];
    }

    private function intervalStartDate(Budget $budget): Carbon
    {
        $latestLog = $budget->logs->sortByDesc('start_date')->first();

        return $this->asMutableCarbon($latestLog?->start_date ?? $budget->created_at);
    }

    private function manualStartDate(Budget $budget): Carbon
    {
        $latestLog = $budget->logs->sortByDesc('start_date')->first();

        return $this->asMutableCarbon($latestLog?->start_date ?? $budget->created_at);
    }

    private function resetType(Budget $budget): BudgetResetType
    {
        return $budget->reset_type instanceof BudgetResetType
            ? $budget->reset_type
            : BudgetResetType::from($budget->reset_type);
    }

    private function formatMoney(float|int|string|null $amount): string
    {
        return number_format((float) ($amount ?? 0), 2, '.', '');
    }

    private function asMutableCarbon(CarbonInterface $date): Carbon
    {
        return $date instanceof Carbon
            ? $date->copy()
            : Carbon::instance($date);
    }
}
