<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\Expense */
class ExpenseResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'item' => $this->item,
            'quantity' => $this->quantity,
            'price' => $this->price,
            'total' => $this->total,
            'category_id' => $this->category_id,
            'category' => $this->when(
                $this->relationLoaded('category') && $this->category !== null,
                fn (): array => [
                    'id' => $this->category->id,
                    'code' => $this->category->code,
                    'name' => $this->category->name,
                ],
            ),
            'date' => $this->formattedExpenseDate(),
            'receipt_url' => null,
        ];
    }

    private function formattedExpenseDate(): ?string
    {
        if ($this->created_at === null) {
            return null;
        }

        $tz = (string) config('app.expenses_display_timezone', 'UTC');

        return $this->created_at->copy()->timezone($tz)->format('n/j');
    }
}
