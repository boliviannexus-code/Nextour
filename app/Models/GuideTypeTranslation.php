<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GuideTypeTranslation extends Model
{
    public $timestamps = false;

    protected $fillable = ['locale', 'title', 'slug', 'description'];

    public function guideType(): BelongsTo
    {
        return $this->belongsTo(GuideType::class);
    }
}
