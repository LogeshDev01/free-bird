<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SessionReview extends Model
{
    protected $table = 'fb_tbl_session_review';

    protected $fillable = [
        'session_id',
        'trainer_id',
        'client_id',
        // Client → Trainer
        'client_rating',
        'client_review',
        'client_reviewed_at',
        // Trainer → Client
        'trainer_rating',
        'trainer_review',
        'trainer_reviewed_at',
    ];

    protected $casts = [
        'client_rating'       => 'decimal:1',
        'trainer_rating'      => 'decimal:1',
        'client_reviewed_at'  => 'datetime',
        'trainer_reviewed_at' => 'datetime',
    ];

    // ─── Relationships ────────────────────────────────────

    public function session(): BelongsTo
    {
        return $this->belongsTo(Session::class, 'session_id');
    }

    public function trainer(): BelongsTo
    {
        return $this->belongsTo(Trainer::class, 'trainer_id');
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class, 'client_id');
    }
}
