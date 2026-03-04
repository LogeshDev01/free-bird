<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class CommunityLike extends Model
{
    protected $table = 'fb_tbl_community_like';

    protected $fillable = [
        'post_id',
        'user_id',
        'user_type',
    ];

    public function post(): BelongsTo
    {
        return $this->belongsTo(CommunityPost::class, 'post_id');
    }

    public function user(): MorphTo
    {
        return $this->morphTo();
    }
}
