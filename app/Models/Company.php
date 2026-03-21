<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Company extends Model
{
    protected $table = 'companies';

    protected $hidden = ['password'];

    protected function casts(): array
    {
        return [
            'password' => 'hashed',
        ];
    }

    public function rewards(): HasMany
    {
        return $this->hasMany(Reward::class);
    }

    public function events(): HasMany
    {
        return $this->hasMany(Event::class);
    }
}
