<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\BudgetLog;
use App\Services\BudgetService;
use Carbon\CarbonInterface;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin BudgetLog */
class BudgetLogResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $timezone = $this->budget?->user?->displayTimezone() ?? 'UTC';
        $allocated = (float) $this->allocated_amount;
        $budget = $this->budget;
        $spent = $budget !== null
            ? (float) app(BudgetService::class)->getLogSpentAmount($budget, $this->resource)
            : (float) $this->actual_spent;
        $rollover = max(0.0, $allocated - $spent);

        return [
            'id' => $this->id,
            'start_date' => $this->formatDate($this->start_date, $timezone),
            'end_date' => $this->end_date !== null
                ? $this->formatDate($this->end_date, $timezone)
                : null,
            'allocated_amount' => number_format($allocated, 2, '.', ''),
            'spent_amount' => number_format($spent, 2, '.', ''),
            'rollover_amount' => number_format($rollover, 2, '.', ''),
        ];
    }

    private function formatDate(?CarbonInterface $date, string $timezone): ?string
    {
        if ($date === null) {
            return null;
        }

        return $date->copy()->timezone($timezone)->toDateString();
    }
}
