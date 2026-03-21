<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DrugCategory extends Model
{
    protected $table = 'drug_categories';

    protected $fillable = [
        'name',
        'line_manager_name',
    ];

    public function drugs(): HasMany
    {
        return $this->hasMany(Drug::class, 'category_id');
    }

    public function medicalReps(): HasMany
    {
        return $this->hasMany(MedicalRep::class, 'category_id');
    }
}

