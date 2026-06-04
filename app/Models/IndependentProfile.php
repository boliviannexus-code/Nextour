<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Facades\Storage;

class IndependentProfile extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'first_name',
        'last_name',
        'document_number',
        'phone',
        'email',
        'address',
        'work_type',
        'description',
        'is_certified_guide',
        'id_front_path',
        'id_back_path',
        'profile_photo_path',
    ];

    protected function casts(): array
    {
        return [
            'is_certified_guide' => 'boolean',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function registrationRequest(): HasOne
    {
        return $this->hasOne(RegistrationRequest::class);
    }

    public function getIdFrontUrlAttribute(): ?string
    {
        return $this->id_front_path ? Storage::disk('public')->url($this->id_front_path) : null;
    }

    public function getIdBackUrlAttribute(): ?string
    {
        return $this->id_back_path ? Storage::disk('public')->url($this->id_back_path) : null;
    }

    public function getProfilePhotoUrlAttribute(): ?string
    {
        return $this->profile_photo_path ? Storage::disk('public')->url($this->profile_photo_path) : null;
    }
}
