<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CompanyCreditBalance extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_id',
        'credits_balance',
    ];

    protected function casts(): array
    {
        return [
            'credits_balance' => 'integer',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }
}
