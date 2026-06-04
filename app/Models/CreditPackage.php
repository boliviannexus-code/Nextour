<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;

class CreditPackage extends Model
{
    use HasFactory;

    public const CURRENCY_USD = 'USD';

    protected $fillable = [
        'name',
        'description',
        'credits_amount',
        'price',
        'currency',
        'payment_qr_path',
        'is_active',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'credits_amount' => 'integer',
            'price' => 'decimal:2',
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    public function purchaseRequests(): HasMany
    {
        return $this->hasMany(CreditPurchaseRequest::class);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function getPaymentQrUrlAttribute(): ?string
    {
        return $this->payment_qr_path ? Storage::disk('public')->url($this->payment_qr_path) : null;
    }
}
