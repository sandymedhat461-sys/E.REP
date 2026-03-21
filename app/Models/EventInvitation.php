<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EventInvitation extends Model
{
    protected $table = 'event_invitations';

    protected $fillable = [
        'event_id',
        'doctor_id',
        'invited_by_rep_id',
        'status',
        'message',
        'invited_at',
        'responded_at',
    ];

    protected function casts(): array
    {
        return [
            'invited_at' => 'datetime',
            'responded_at' => 'datetime',
        ];
    }

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    public function doctor(): BelongsTo
    {
        return $this->belongsTo(Doctor::class);
    }

    public function invitedByRep(): BelongsTo
    {
        return $this->belongsTo(MedicalRep::class, 'invited_by_rep_id');
    }
}
