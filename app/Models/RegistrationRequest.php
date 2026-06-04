<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RegistrationRequest extends Model
{
    use HasFactory;

    public const TYPE_COMPANY = 'company';

    public const TYPE_INDEPENDENT = 'independent';

    public const STATUS_PENDING = 'pending';

    public const STATUS_IN_REVIEW = 'in_review';

    public const STATUS_OBSERVED = 'observed';

    public const STATUS_APPROVED = 'approved';

    public const STATUS_REJECTED = 'rejected';

    protected $fillable = [
        'user_id',
        'company_id',
        'independent_profile_id',
        'type',
        'status',
        'submitted_at',
        'last_reviewed_at',
        'approved_at',
        'approved_by',
        'rejected_at',
        'rejected_by',
        'rejection_reason',
    ];

    protected function casts(): array
    {
        return [
            'approved_at' => 'datetime',
            'submitted_at' => 'datetime',
            'last_reviewed_at' => 'datetime',
            'rejected_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function independentProfile(): BelongsTo
    {
        return $this->belongsTo(IndependentProfile::class);
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function rejector(): BelongsTo
    {
        return $this->belongsTo(User::class, 'rejected_by');
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(CompanyReview::class);
    }

    public static function statuses(): array
    {
        return [
            self::STATUS_PENDING,
            self::STATUS_IN_REVIEW,
            self::STATUS_OBSERVED,
            self::STATUS_APPROVED,
            self::STATUS_REJECTED,
        ];
    }

    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            self::STATUS_IN_REVIEW => 'EN REVISION',
            self::STATUS_OBSERVED => 'OBSERVADA',
            self::STATUS_APPROVED => 'APROBADA',
            self::STATUS_REJECTED => 'RECHAZADA',
            default => 'PENDIENTE',
        };
    }

    public function getStatusBadgeAttribute(): string
    {
        return match ($this->status) {
            self::STATUS_APPROVED => 'success',
            self::STATUS_REJECTED => 'danger',
            self::STATUS_OBSERVED => 'warning',
            self::STATUS_IN_REVIEW => 'info',
            default => 'secondary',
        };
    }

    public function scopeStatus(Builder $query, ?string $status): Builder
    {
        return $query->when($status, fn (Builder $query): Builder => $query->where('status', $status));
    }

    public function getDisplayNameAttribute(): string
    {
        return $this->type === self::TYPE_COMPANY
            ? (string) $this->company?->name
            : (string) ($this->company?->name ?: trim(($this->independentProfile?->first_name ?? '').' '.($this->independentProfile?->last_name ?? '')));
    }

    public function getDisplayEmailAttribute(): string
    {
        return $this->type === self::TYPE_COMPANY
            ? (string) ($this->company?->email ?: $this->user?->email)
            : (string) ($this->independentProfile?->email ?: $this->user?->email);
    }
}
