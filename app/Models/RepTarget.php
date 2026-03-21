<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RepTarget extends Model
{
    protected $table = 'rep_targets';

    protected $fillable = [
        'rep_id',
        'target_type',
        'target_value',
        'current_value',
        'period',
    ];

    protected function casts(): array
    {
        return [
            'target_value' => 'integer',
            'current_value' => 'integer',
        ];
    }

    public function rep(): BelongsTo
    {
        return $this->belongsTo(MedicalRep::class, 'rep_id');
    }
}
