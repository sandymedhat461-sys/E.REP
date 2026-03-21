<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Message extends Model
{
    protected $table = 'messages';

    protected $fillable = [
        'sender_type',
        'sender_id',
        'receiver_doctor_id',
        'receiver_rep_id',
        'content',
        'read_status',
    ];

    protected function casts(): array
    {
        return [
            'read_status' => 'boolean',
        ];
    }

    public function receiverDoctor(): BelongsTo
    {
        return $this->belongsTo(Doctor::class, 'receiver_doctor_id');
    }

    public function receiverRep(): BelongsTo
    {
        return $this->belongsTo(MedicalRep::class, 'receiver_rep_id');
    }
}
