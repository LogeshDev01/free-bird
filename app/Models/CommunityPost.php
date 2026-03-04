<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class CommunityPost extends Model
{
    use SoftDeletes;

    protected $table = 'fb_tbl_community_post';

    protected $fillable = [
        'category_id',
        'author_id',
        'author_type',
        'target_audience',
        'title',
        'content',
        'image',
        'total_likes',
        'total_comments',
        'total_shares',
        'is_pinned',
        'is_active',
    ];

    protected $casts = [
        'is_pinned' => 'boolean',
        'is_active' => 'boolean',
    ];

    protected $appends = ['is_liked_by_me'];

    // ─── Relationships ─────────────────────────────────────

    public function category(): BelongsTo
    {
        return $this->belongsTo(CommunityCategory::class, 'category_id');
    }

    public function author(): MorphTo
    {
        return $this->morphTo();
    }

    public function likes(): HasMany
    {
        return $this->hasMany(CommunityLike::class, 'post_id');
    }

    public function comments(): HasMany
    {
        return $this->hasMany(CommunityComment::class, 'post_id');
    }

    /**
     * Check if the current authenticated user has liked this post
     */
    public function getIsLikedByMeAttribute(): bool
    {
        $user = auth('trainer')->user() ?: auth('api')->user();
        if (!$user) return false;

        return $this->likes()
            ->where('user_id', $user->id)
            ->where('user_type', get_class($user))
            ->exists();
    }

    /**
     * Get the full URL for the post image
     */
    public function getImageAttribute($value): ?string
    {
        return $value ? asset('storage/' . $value) : null;
    }
}
