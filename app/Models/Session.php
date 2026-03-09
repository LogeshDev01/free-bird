<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Session extends Model
{
    use SoftDeletes;

    protected $table = 'fb_tbl_session';

    // ─── Status Constants ─────────────────────────────────
    const STATUS_SCHEDULED = 1;
    const STATUS_COMPLETED = 2;
    const STATUS_CANCELLED = 3;
    const STATUS_NO_SHOW   = 4;

    protected $fillable = [
        'trainer_id',
        'client_id',
        'slot_id',
        'session_date',
        'start_time',
        'end_time',
        'location',
        'status',
        'notes',
    ];

    protected $casts = [
        'session_date' => 'date',
        'status'       => 'integer',
    ];

    protected $appends = ['is_workout_assigned'];

    // ─── Accessors ─────────────────────────────────────────

    public function getIsWorkoutAssignedAttribute(): bool
    {
        return $this->workoutAssignments()->exists();
    }

    // ─── Relationships ────────────────────────────────────

    public function trainer(): BelongsTo
    {
        return $this->belongsTo(Trainer::class, 'trainer_id');
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class, 'client_id');
    }

    public function slot(): BelongsTo
    {
        return $this->belongsTo(TrainerSlot::class, 'slot_id');
    }

    public function locationDetail(): BelongsTo
    {
        return $this->belongsTo(City::class, 'location', 'id');
    }

    // Note: 'location' is a plain VARCHAR string column (not a FK).
    // $session->location returns the string directly — no relation needed.

    public function workoutAssignments(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(WorkoutAssignment::class, 'session_id');
    }
}
