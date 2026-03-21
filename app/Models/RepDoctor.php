<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RepDoctor extends Model
{
    protected $table = 'rep_doctors';

    protected $fillable = [
        'rep_id',
        'doctor_id',
        'assigned_at',
    ];

    protected function casts(): array
    {
        return [
            'assigned_at' => 'datetime',
        ];
    }

    public function rep(): BelongsTo
    {
        return $this->belongsTo(MedicalRep::class, 'rep_id');
    }

    public function doctor(): BelongsTo
    {
        return $this->belongsTo(Doctor::class);
    }
}

