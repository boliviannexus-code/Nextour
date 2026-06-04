<?php

namespace App\Models;

use App\Models\Concerns\AuditsCompanyChanges;
use App\Models\Concerns\HasTranslations;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use OwenIt\Auditing\Contracts\Auditable;

class TransportType extends Model implements Auditable
{
    use AuditsCompanyChanges, HasFactory, HasTranslations;

    protected $fillable = [
        'title',
        'description',
    ];

    public function getLocalizedTitleAttribute(): string
    {
        return $this->translated('title', fallback: $this->title);
    }

    protected function translationModel(): string
    {
        return TransportTypeTranslation::class;
    }
}
