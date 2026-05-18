<?php

namespace App\Models;

use App\Enums\BudgetResetType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Budget extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'user_id',
        'name',
        'amount',
        'reset_type',
        'reset_days',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'reset_type' => BudgetResetType::class,
            'reset_days' => 'array',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return BelongsToMany<Category, $this>
     */
    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(Category::class);
    }

    /**
     * @return HasMany<BudgetLog, $this>
     */
    public function logs(): HasMany
    {
        return $this->hasMany(BudgetLog::class);
    }
}
