<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class PostShare extends Model
{
    protected $table = 'post_shares';

    protected $fillable = ['post_id', 'sharer_id', 'sharer_type'];

    public function post(): BelongsTo
    {
        return $this->belongsTo(Post::class);
    }

    public function sharer(): MorphTo
    {
        return $this->morphTo();
    }
}
