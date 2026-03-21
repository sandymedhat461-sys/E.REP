<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Doctor extends Model
{
    protected $table = 'doctors';

    protected $fillable = [
        'full_name',
        'phone',
        'national_id',
        'email',
        'password',
        'specialization',
        'hospital_name',
        'status',
        'syndicate_id',
        'profile_image',
        'syndicate_id_image',
    ];

    protected $hidden = ['password'];

    protected function casts(): array
    {
        return [
            'password' => 'hashed',
        ];
    }

    public function rewardRedemptions(): HasMany
    {
        return $this->hasMany(RewardRedemption::class);
    }

    public function eventInvitations(): HasMany
    {
        return $this->hasMany(EventInvitation::class);
    }

    public function meetings(): HasMany
    {
        return $this->hasMany(Meeting::class);
    }

    public function doctorPoints(): HasMany
    {
        return $this->hasMany(DoctorPoint::class);
    }

    public function drugSamples(): HasMany
    {
        return $this->hasMany(DrugSample::class);
    }
}
