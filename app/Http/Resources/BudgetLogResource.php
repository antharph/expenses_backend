<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\BudgetLog;
use App\Services\BudgetService;
use Carbon\Carbon;
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
        $allocated = $this->allocated_amount !== null ? (float) $this->allocated_amount : null;
        $budget = $this->budget;
        $spent = $budget !== null
            ? (float) app(BudgetService::class)->getLogSpentAmount($budget, $this->resource)
            : (float) $this->actual_spent;

        $allocatedFormatted = $allocated !== null
            ? number_format($allocated, 2, '.', '')
            : null;
        $rollover = $allocated !== null
            ? number_format(max(0.0, $allocated - $spent), 2, '.', '')
            : null;

        return [
            'id' => $this->id,
            'start_date' => $this->formatDate($this->start_date, $timezone),
            'end_date' => $this->end_date !== null
                ? $this->formatDate($this->end_date, $timezone)
                : null,
            'allocated_amount' => $allocatedFormatted,
            'spent_amount' => number_format($spent, 2, '.', ''),
            'rollover_amount' => $rollover,
            'categories' => $this->categories
                ->map(static fn ($category): array => [
                    'id' => $category->id,
                    'name' => $category->name,
                ])
                ->values(),
        ];
    }

    private function formatDate(?CarbonInterface $date, string $timezone): ?string
    {
        if ($date === null) {
            return null;
        }

        return Carbon::parse($date->format('Y-m-d H:i:s'), 'UTC')
            ->timezone($timezone)
            ->toDateString();
    }
}
