<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MonetizationAuditLog extends Model
{
    use HasFactory;

    public const EVENT_BOOKING_FREE = 'booking_free';

    public const EVENT_BOOKING_WITH_CREDIT = 'booking_with_credit';

    public const EVENT_BOOKING_HELD = 'booking_held';

    public const EVENT_CREDIT_ADDED = 'credit_added';

    public const EVENT_CREDIT_SUBTRACTED = 'credit_subtracted';

    public const EVENT_BOOKING_RELEASED = 'booking_released';

    public const EVENT_CONFIGURATION_CHANGED = 'configuration_changed';

    public const EVENT_PURCHASE_REQUESTED = 'purchase_requested';

    public const EVENT_PURCHASE_APPROVED = 'purchase_approved';

    public const EVENT_PURCHASE_REJECTED = 'purchase_rejected';

    public const EVENT_LABELS = [
        self::EVENT_BOOKING_FREE => 'Reserva gratuita',
        self::EVENT_BOOKING_WITH_CREDIT => 'Reserva con credito',
        self::EVENT_BOOKING_HELD => 'Reserva retenida',
        self::EVENT_CREDIT_ADDED => 'Credito agregado',
        self::EVENT_CREDIT_SUBTRACTED => 'Credito descontado',
        self::EVENT_BOOKING_RELEASED => 'Reserva liberada',
        self::EVENT_CONFIGURATION_CHANGED => 'Cambio de configuracion',
        self::EVENT_PURCHASE_REQUESTED => 'Compra solicitada',
        self::EVENT_PURCHASE_APPROVED => 'Compra aprobada',
        self::EVENT_PURCHASE_REJECTED => 'Compra rechazada',
    ];

    protected $fillable = [
        'company_id',
        'booking_id',
        'tour_id',
        'event_type',
        'description',
        'metadata',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'metadata' => 'array',
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

    public function getEventLabelAttribute(): string
    {
        return self::EVENT_LABELS[$this->event_type] ?? ucfirst(str_replace('_', ' ', (string) $this->event_type));
    }
}
