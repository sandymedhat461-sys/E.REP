<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MedicalRep extends Model
{
    protected $table = 'medical_reps';

    protected $fillable = [
        'company_id',
        'full_name',
        'email',
        'password',
        'phone',
        'national_id',
        'company_name',
        'status',
        'profile_image',
        'company_id_image',
    ];

    protected $hidden = ['password'];

    protected function casts(): array
    {
        return [
            'password' => 'hashed',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class, 'company_id');
    }

    public function eventInvitations(): HasMany
    {
        return $this->hasMany(EventInvitation::class, 'invited_by_rep_id');
    }

    public function meetings(): HasMany
    {
        return $this->hasMany(Meeting::class, 'rep_id');
    }

    public function repTargets(): HasMany
    {
        return $this->hasMany(RepTarget::class, 'rep_id');
    }
}
