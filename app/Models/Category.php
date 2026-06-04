<?php

namespace App\Models;

use App\Models\Concerns\AuditsCompanyChanges;
use App\Models\Concerns\HasTranslations;
use Database\Factories\CategoryFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use OwenIt\Auditing\Contracts\Auditable;

class Category extends Model implements Auditable
{
    /** @use HasFactory<CategoryFactory> */
    use AuditsCompanyChanges, HasFactory, HasTranslations;

    protected $fillable = [
        'name',
        'description',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }

    public function tours(): HasMany
    {
        return $this->hasMany(Tour::class);
    }

    public function getLocalizedNameAttribute(): string
    {
        return $this->translated('name', fallback: $this->name);
    }

    public function getLocalizedDescriptionAttribute(): ?string
    {
        return $this->translated('description', fallback: $this->description);
    }

    protected function translationModel(): string
    {
        return CategoryTranslation::class;
    }
}
