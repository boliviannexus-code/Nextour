<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TransportTypeTranslation extends Model
{
    public $timestamps = false;

    protected $fillable = ['locale', 'title', 'slug', 'description'];

    public function transportType(): BelongsTo
    {
        return $this->belongsTo(TransportType::class);
    }
}
