<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SubscriptionSetting extends Model
{
    use HasFactory;

    protected $fillable = [
        'free_active_tours_limit',
        'free_daily_pax_limit',
        'free_weekly_pax_limit',
        'daily_warning_threshold',
        'weekly_warning_threshold',
        'warning_thresholds',
        'credits_per_booking',
        'credit_price',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'free_active_tours_limit' => 'integer',
            'free_daily_pax_limit' => 'integer',
            'free_weekly_pax_limit' => 'integer',
            'daily_warning_threshold' => 'integer',
            'weekly_warning_threshold' => 'integer',
            'warning_thresholds' => 'array',
            'credits_per_booking' => 'integer',
            'credit_price' => 'decimal:2',
            'is_active' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (SubscriptionSetting $setting): void {
            if (! $setting->is_active) {
                return;
            }

            static::query()
                ->when($setting->exists, fn (Builder $query): Builder => $query->whereKeyNot($setting->getKey()))
                ->update(['is_active' => false]);
        });
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }
}
