<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DrugIngredient extends Model
{
    protected $table = 'drug_ingredients';

    protected $fillable = [
        'drug_id',
        'ingredient_id',
    ];

    public function drug(): BelongsTo
    {
        return $this->belongsTo(Drug::class);
    }

    public function ingredient(): BelongsTo
    {
        return $this->belongsTo(ActiveIngredient::class, 'ingredient_id');
    }
}

