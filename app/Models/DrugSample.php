<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DrugSample extends Model
{
    protected $table = 'drug_samples';

    protected $fillable = [
        'doctor_id',
        'drug_id',
        'rep_id',
        'quantity',
        'status',
        'requested_at',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'integer',
            'requested_at' => 'datetime',
        ];
    }

    public function doctor(): BelongsTo
    {
        return $this->belongsTo(Doctor::class);
    }

    public function drug(): BelongsTo
    {
        return $this->belongsTo(Drug::class);
    }

    public function rep(): BelongsTo
    {
        return $this->belongsTo(MedicalRep::class, 'rep_id');
    }
}
