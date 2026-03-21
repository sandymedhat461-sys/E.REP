<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class PostLike extends Model
{
    protected $table = 'post_likes';

    protected $fillable = [
        'post_id',
        'user_type',
        'user_id',
    ];

    public function post(): BelongsTo
    {
        return $this->belongsTo(Post::class);
    }

    public function user(): MorphTo
    {
        return $this->morphTo();
    }
}

