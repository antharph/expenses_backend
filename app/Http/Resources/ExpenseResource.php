<?php

namespace App\Http\Resources;

use App\Models\Expense;
use App\Support\ExpenseTimezone;
use Carbon\CarbonInterface;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Expense */
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
            'store_id' => $this->store_id,
            'store' => $this->when(
                $this->relationLoaded('store') && $this->store !== null,
                fn (): array => [
                    'id' => $this->store->id,
                    'name' => $this->store->name,
                ],
            ),
            'transaction_at' => $this->formattedTransactionAt(),
            'transaction_number' => $this->transaction_number,
            'invoice_number' => $this->invoice_number,
            'date' => $this->formattedExpenseDate(),
            'receipt_url' => null,
        ];
    }

    private function formattedExpenseDate(): ?string
    {
        $instant = $this->transactionInstant();
        if ($instant === null) {
            return null;
        }

        return $instant->copy()->timezone(ExpenseTimezone::display())->format('n/j');
    }

    private function formattedTransactionAt(): ?string
    {
        $instant = $this->transactionInstant();
        if ($instant === null) {
            return null;
        }

        return $instant->copy()->timezone(ExpenseTimezone::display())->toIso8601String();
    }

    private function transactionInstant(): ?CarbonInterface
    {
        return $this->transaction_at ?? $this->created_at;
    }
}
