<?php

namespace App\Models;

use App\Models\Concerns\AuditsCompanyChanges;
use Database\Factories\CompanyFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Facades\Storage;
use OwenIt\Auditing\Contracts\Auditable;

class Company extends Model implements Auditable
{
    /** @use HasFactory<CompanyFactory> */
    use AuditsCompanyChanges, HasFactory;

    protected $fillable = [
        'name',
        'legal_name',
        'tax_id',
        'phone',
        'email',
        'website',
        'description',
        'address',
        'city',
        'country',
        'logo_path',
        'tax_document_path',
        'legal_representative_first_name',
        'legal_representative_last_name',
        'legal_representative_document_number',
        'legal_representative_document_type',
        'report_footer',
        'is_active',
        'approval_status',
        'last_reviewed_at',
        'approved_at',
        'approved_by',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'last_reviewed_at' => 'datetime',
            'approved_at' => 'datetime',
        ];
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function tours(): HasMany
    {
        return $this->hasMany(Tour::class);
    }

    public function registrationRequest(): HasOne
    {
        return $this->hasOne(RegistrationRequest::class);
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(CompanyReview::class);
    }

    public function creditBalance(): HasOne
    {
        return $this->hasOne(CompanyCreditBalance::class);
    }

    public function creditMovements(): HasMany
    {
        return $this->hasMany(CompanyCreditMovement::class);
    }

    public function monetizationAuditLogs(): HasMany
    {
        return $this->hasMany(MonetizationAuditLog::class);
    }

    public function creditPurchaseRequests(): HasMany
    {
        return $this->hasMany(CreditPurchaseRequest::class);
    }

    public function getLogoUrlAttribute(): ?string
    {
        return $this->logo_path ? Storage::disk('public')->url($this->logo_path) : null;
    }

    public function getTaxDocumentUrlAttribute(): ?string
    {
        return $this->tax_document_path ? Storage::disk('public')->url($this->tax_document_path) : null;
    }
}
