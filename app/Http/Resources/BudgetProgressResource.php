<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\Budget;
use App\Services\BudgetService;
use Carbon\CarbonInterface;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @property-read Budget $resource
 */
class BudgetProgressResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $budget = $this->resource;
        $budget->loadMissing('categories:id,name');
        $timezone = $budget->user?->displayTimezone() ?? 'UTC';
        $progress = app(BudgetService::class)->getBudgetProgress($budget);

        return [
            'id' => $budget->id,
            'name' => $budget->name,
            'rollover_enabled' => (bool) $budget->rollover,
            'period' => [
                'start_date' => $this->formatDate($progress->start_date, $timezone),
                'end_date' => $progress->end_date !== null
                    ? $this->formatDate($progress->end_date, $timezone)
                    : null,
            ],
            'base_amount' => $progress->base_amount,
            'rollover_amount' => $progress->rollover_amount,
            'allocated_amount' => $progress->allocated_amount,
            'spent_amount' => $progress->amount_spent,
            'remaining_amount' => $progress->amount_remaining,
            'percentage_spent' => $progress->percentage_spent,
            'is_over_budget' => $progress->is_over_budget,
            'categories' => $budget->categories
                ->map(static fn ($category): array => [
                    'id' => $category->id,
                    'name' => $category->name,
                ])
                ->values(),
        ];
    }

    private function formatDate(CarbonInterface $date, string $timezone): string
    {
        return $date->copy()->timezone($timezone)->toDateString();
    }
}
