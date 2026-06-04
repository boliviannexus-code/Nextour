<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class TourBooking extends Model
{
    use HasFactory;

    public const STATUS_PENDING = 'pending';

    public const STATUS_CONFIRMED = 'confirmed';

    public const STATUS_CANCELLED = 'cancelled';

    public const STATUS_COMPLETED = 'completed';

    public const CREDIT_STATUS_FREE = 'free';

    public const CREDIT_STATUS_CREDIT_CONSUMED = 'credit_consumed';

    public const CREDIT_STATUS_HELD_FOR_CREDITS = 'held_for_credits';

    public const CREDIT_STATUSES = [
        self::CREDIT_STATUS_FREE => 'Gratis',
        self::CREDIT_STATUS_CREDIT_CONSUMED => 'Credito consumido',
        self::CREDIT_STATUS_HELD_FOR_CREDITS => 'Retenida por creditos',
    ];

    public const STATUSES = [
        self::STATUS_PENDING => 'Pendiente',
        self::STATUS_CONFIRMED => 'Confirmada',
        self::STATUS_CANCELLED => 'Cancelada',
        self::STATUS_COMPLETED => 'Completada',
    ];

    protected $fillable = [
        'user_id',
        'tour_id',
        'tour_availability_id',
        'booking_code',
        'travel_date',
        'people',
        'first_name',
        'last_name',
        'email',
        'phone',
        'country',
        'special_requirements',
        'unit_price_usd',
        'total_usd',
        'status',
        'credit_status',
        'visible_to_company',
        'credits_required',
        'credits_consumed',
    ];

    protected function casts(): array
    {
        return [
            'travel_date' => 'date',
            'people' => 'integer',
            'unit_price_usd' => 'decimal:2',
            'total_usd' => 'decimal:2',
            'visible_to_company' => 'boolean',
            'credits_required' => 'integer',
            'credits_consumed' => 'integer',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function tour(): BelongsTo
    {
        return $this->belongsTo(Tour::class);
    }

    public function availability(): BelongsTo
    {
        return $this->belongsTo(TourAvailability::class, 'tour_availability_id');
    }

    public function review(): HasOne
    {
        return $this->hasOne(TourReview::class);
    }

    public function creditMovements(): HasMany
    {
        return $this->hasMany(CompanyCreditMovement::class, 'booking_id');
    }

    public function monetizationAuditLogs(): HasMany
    {
        return $this->hasMany(MonetizationAuditLog::class, 'booking_id');
    }

    public function getStatusLabelAttribute(): string
    {
        return self::STATUSES[$this->status] ?? ucfirst((string) $this->status);
    }

    public function getCreditStatusLabelAttribute(): string
    {
        return self::CREDIT_STATUSES[$this->credit_status] ?? ucfirst((string) $this->credit_status);
    }
}
