<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Direct model for the fb_tbl_trainer_client pivot.
 * Used by admin APIs to assign / update / remove trainer-client enrollments.
 *
 * Columns:
 *   id, trainer_id, client_id, status (1=active, 0=inactive, 2=completed),
 *   start_date, end_date, created_at, updated_at
 */
class TrainerClient extends Model
{
    use SoftDeletes;
    protected $table = 'fb_tbl_trainer_client';

    // ─── Status Constants ────────────────────────────────────────────────
    const STATUS_ACTIVE    = 1;
    const STATUS_INACTIVE  = 0;
    const STATUS_COMPLETED = 2;

    const STATUS_LABELS = [
        self::STATUS_INACTIVE  => 'inactive',
        self::STATUS_ACTIVE    => 'active',
        self::STATUS_COMPLETED => 'completed',
    ];

    protected $fillable = [
        'trainer_id',
        'client_id',
        'status',
        'start_date',
        'end_date',
    ];

    protected $casts = [
        'status'     => 'integer',
        'start_date' => 'date',
        'end_date'   => 'date',
    ];

    protected $appends = ['status_label'];

    // ─── Accessors ───────────────────────────────────────────────────────

    public function getStatusLabelAttribute(): string
    {
        return self::STATUS_LABELS[$this->status] ?? 'unknown';
    }

    // ─── Relationships ───────────────────────────────────────────────────

    public function trainer(): BelongsTo
    {
        return $this->belongsTo(Trainer::class, 'trainer_id');
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class, 'client_id');
    }
}
