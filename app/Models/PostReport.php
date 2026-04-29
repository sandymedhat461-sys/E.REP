<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PostReport extends Model
{
    protected $table = 'post_reports';

    protected $fillable = [
        'post_id',
        'reporter_id',
        'reporter_type',
        'reason',
    ];

    public function post(): BelongsTo
    {
        return $this->belongsTo(Post::class);
    }
}
