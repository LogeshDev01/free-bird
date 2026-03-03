<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Notification extends Model
{
    protected $table = 'fb_tbl_notification';

    protected $fillable = [
        'notifiable_id',
        'notifiable_type',
        'title',
        'message',
        'type',
        'data',
        'read_at',
    ];

    protected $casts = [
        'data' => 'array',
        'read_at' => 'datetime',
    ];

    // ─── Relationships ─────────────────────────────────────

    public function notifiable(): MorphTo
    {
        return $this->morphTo();
    }

    // ─── Scopes ────────────────────────────────────────────

    public function scopeUnread($query)
    {
        return $query->whereNull('read_at');
    }

    public function scopeRead($query)
    {
        return $query->whereNotNull('read_at');
    }

    // ─── Helpers ────────────────────────────────────────────

    public function markAsRead(): void
    {
        $this->update(['read_at' => now()]);
    }
}
