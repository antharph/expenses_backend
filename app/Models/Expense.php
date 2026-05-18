<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Expense extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'user_id',
        'category_id',
        'store_id',
        'item',
        'quantity',
        'price',
        'total',
        'transaction_number',
        'invoice_number',
        'transaction_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'quantity' => 'integer',
            'price' => 'decimal:2',
            'total' => 'decimal:2',
            'transaction_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(static function (Expense $expense): void {
            if ($expense->transaction_at === null) {
                $expense->transaction_at = now();
            }
        });
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }
}
