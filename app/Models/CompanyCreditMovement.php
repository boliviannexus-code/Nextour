<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CompanyCreditMovement extends Model
{
    use HasFactory;

    public const TYPE_PURCHASE = 'purchase';

    public const TYPE_MANUAL_ADDITION = 'manual_addition';

    public const TYPE_MANUAL_SUBTRACTION = 'manual_subtraction';

    public const TYPE_BOOKING_CONSUMPTION = 'booking_consumption';

    public const TYPE_BOOKING_REFUND = 'booking_refund';

    public const TYPE_ADJUSTMENT = 'adjustment';

    public const TYPES = [
        self::TYPE_PURCHASE,
        self::TYPE_MANUAL_ADDITION,
        self::TYPE_MANUAL_SUBTRACTION,
        self::TYPE_BOOKING_CONSUMPTION,
        self::TYPE_BOOKING_REFUND,
        self::TYPE_ADJUSTMENT,
    ];

    protected $fillable = [
        'company_id',
        'booking_id',
        'tour_id',
        'movement_type',
        'credits',
        'balance_before',
        'balance_after',
        'reason',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'credits' => 'integer',
            'balance_before' => 'integer',
            'balance_after' => 'integer',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function booking(): BelongsTo
    {
        return $this->belongsTo(TourBooking::class, 'booking_id');
    }

    public function tour(): BelongsTo
    {
        return $this->belongsTo(Tour::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
