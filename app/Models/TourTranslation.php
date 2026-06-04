<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TourTranslation extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'locale',
        'title',
        'slug',
        'short_description',
        'description',
        'includes',
        'excludes',
        'recommendations',
        'meta_title',
        'meta_description',
        'og_title',
        'og_description',
    ];

    public function tour(): BelongsTo
    {
        return $this->belongsTo(Tour::class);
    }
}
