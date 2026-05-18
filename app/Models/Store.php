<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Store extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'legal_name',
        'address',
    ];

    public function expenses(): HasMany
    {
        return $this->hasMany(Expense::class);
    }
}
