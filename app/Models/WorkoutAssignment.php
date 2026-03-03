<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WorkoutAssignment extends Model
{
    protected $table = 'fb_tbl_workout_assignment';

    // ─── Status Constants ─────────────────────────────────
    const STATUS_PENDING     = 1;
    const STATUS_IN_PROGRESS = 2;
    const STATUS_COMPLETED   = 3;

    protected $fillable = [
        'trainer_id',
        'client_id',
        'workout_id',
        'assigned_date',
        'due_date',
        'status',
        'notes',
    ];

    protected $casts = [
        'assigned_date' => 'date',
        'due_date'      => 'date',
        'status'        => 'integer',
    ];

    // ─── Relationships ────────────────────────────────────

    public function trainer(): BelongsTo
    {
        return $this->belongsTo(Trainer::class, 'trainer_id');
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class, 'client_id');
    }

    public function workout(): BelongsTo
    {
        return $this->belongsTo(Workout::class, 'workout_id');
    }
}
