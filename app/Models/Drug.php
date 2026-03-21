<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Drug extends Model
{
    protected $table = 'drugs';

    protected $fillable = [
        'company_id',
        'market_name',
        'description',
        'price',
        'dosage',
        'side_effects',
        'image',
    ];

    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function drugSamples(): HasMany
    {
        return $this->hasMany(DrugSample::class);
    }
}
