<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CreditConsumptionRule extends Model
{
    use HasFactory;

    protected $fillable = [
        'min_tour_price',
        'max_tour_price',
        'credits_required',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'min_tour_price' => 'decimal:2',
            'max_tour_price' => 'decimal:2',
            'credits_required' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }
}
