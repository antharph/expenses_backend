<?php

namespace Database\Seeders;

use App\Enums\BudgetResetType;
use App\Models\Budget;
use App\Models\BudgetLog;
use App\Models\User;
use App\Services\BudgetService;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class BudgetLogHistorySeeder extends Seeder
{
    /**
     * Backfill closed budget_logs rows for interval and date_fixed budgets from
     * budgets.created_at through the period before the current one.
     */
    public function run(BudgetService $budgetService, int $userId): void
    {
        if ($userId < 1) {
            throw new InvalidArgumentException('A valid user ID is required.');
        }

        User::query()->findOrFail($userId);

        Budget::query()
            ->where('user_id', $userId)
            ->whereIn('reset_type', [
                BudgetResetType::Interval->value,
                BudgetResetType::DateFixed->value,
            ])
            ->with(['user', 'categories:id'])
            ->orderBy('id')
            ->each(function (Budget $budget) use ($budgetService): void {
                $this->backfillBudgetHistory($budget, $budgetService);
            });
    }

    private function backfillBudgetHistory(Budget $budget, BudgetService $budgetService): void
    {
        $periodStarts = $budgetService->collectPeriodStarts($budget);

        if (count($periodStarts) < 2) {
            return;
        }

        $categoryIds = $budget->categories->pluck('id')->all();
        $rolloverAmount = 0.0;
        $lastIndex = count($periodStarts) - 1;
        $timezone = $budget->user?->displayTimezone() ?? 'UTC';

        DB::transaction(function () use (
            $budget,
            $budgetService,
            $periodStarts,
            $categoryIds,
            &$rolloverAmount,
            $lastIndex,
            $timezone,
        ): void {
            $validStartKeys = collect($periodStarts)
                ->map(static fn (Carbon $start): string => $start->copy()->startOfDay()->format('Y-m-d'))
                ->all();

            $budget->logs()
                ->get()
                ->each(function (BudgetLog $log) use ($validStartKeys, $timezone): void {
                    $logKey = $log->start_date
                        ->copy()
                        ->timezone($timezone)
                        ->startOfDay()
                        ->format('Y-m-d');

                    if (! in_array($logKey, $validStartKeys, true)) {
                        $log->delete();
                    }
                });

            foreach ($periodStarts as $index => $periodStart) {
                $isCurrent = $index === $lastIndex;
                $startDate = $periodStart->copy()->startOfDay();
                $endDate = $budgetService->getPeriodEndDate($budget, $startDate);

                $allocatedAmount = $budget->amount !== null
                    ? number_format((float) $budget->amount + $rolloverAmount, 2, '.', '')
                    : null;

                if ($isCurrent) {
                    $log = $this->upsertLog($budget, $startDate, [
                        'end_date' => $endDate,
                        'allocated_amount' => $allocatedAmount,
                        'actual_spent' => '0.00',
                    ], $budgetService);

                    continue;
                }

                $spent = $budgetService->budgetSpentBetween(
                    $budget,
                    $startDate,
                    $endDate ?? Carbon::now($startDate->timezone)->endOfDay(),
                );

                $log = $this->upsertLog($budget, $startDate, [
                    'end_date' => $endDate,
                    'allocated_amount' => $allocatedAmount,
                    'actual_spent' => $spent,
                ], $budgetService);

                if ($categoryIds !== []) {
                    $log->categories()->sync($categoryIds);
                }

                if ($budget->rollover && $allocatedAmount !== null) {
                    $rolloverAmount = max(
                        0.0,
                        (float) $allocatedAmount - (float) $spent,
                    );
                } else {
                    $rolloverAmount = 0.0;
                }
            }
        });
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function upsertLog(Budget $budget, Carbon $startDate, array $attributes, BudgetService $budgetService): BudgetLog
    {
        $storedStart = $budgetService->storeLogBoundary($startDate);
        $storedAttributes = $attributes;

        if (array_key_exists('end_date', $storedAttributes) && $storedAttributes['end_date'] !== null) {
            $storedAttributes['end_date'] = $budgetService->storeLogBoundary($storedAttributes['end_date']);
        }

        $existing = $budget->logs()
            ->where('start_date', '>=', $startDate->copy()->utc())
            ->where('start_date', '<=', $startDate->copy()->endOfDay()->utc())
            ->first();

        if ($existing !== null) {
            $existing->forceFill($storedAttributes)->save();

            return $existing->refresh();
        }

        return $budget->logs()->create([
            'start_date' => $storedStart,
            ...$storedAttributes,
        ]);
    }
}
