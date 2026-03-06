<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WorkoutAssignment extends Model
{
    use SoftDeletes;
    protected $table = 'fb_tbl_workout_assignment';

    // ─── Status Constants ─────────────────────────────────────────────────────
    const STATUS_DRAFT       = 0;
    const STATUS_PENDING     = 1;
    const STATUS_IN_PROGRESS = 2;
    const STATUS_COMPLETED   = 3;

    /**
     * Table columns:
     *  id, trainer_id, client_id, category_id, workout_id,
     *  custom_sets (JSON),
     *  duration    (int — total seconds, auto-calculated from sum of custom_sets[*].duration),
     *  batch_id, assigned_date, due_date, status, notes,
     *  assigned_by_id, assigned_by_type,
     *  created_at, updated_at
     *
     * NOTE: `kg` is NOT a top-level column — it lives inside each custom_sets item:
     *   custom_sets: [{ set, reps, lbs, kg, rest, duration }, ...]
     */
    protected $fillable = [
        'trainer_id',
        'assigned_by_id',
        'assigned_by_type',
        'batch_id',
        'session_id',      // nullable — link to specific session
        'client_id',
        'category_id',
        'workout_id',
        'custom_sets',   // JSON: [{ set, reps, lbs, kg, rest, duration }]
        'duration',      // Auto-calculated: sum of all custom_sets[*].duration (seconds)
        'assigned_date',
        'due_date',
        'status',
        'notes',
    ];

    protected $casts = [
        'assigned_date' => 'date',
        'due_date'      => 'date',
        'status'        => 'integer',
        'custom_sets'   => 'array',
        'duration'      => 'integer',
    ];

    // ─── Relationships ────────────────────────────────────────────────────────

    /**
     * Polymorphic: whoever assigned the workout (Admin or Trainer)
     */
    public function assignedBy()
    {
        return $this->morphTo();
    }

    public function trainer(): BelongsTo
    {
        return $this->belongsTo(Trainer::class, 'trainer_id');
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class, 'client_id');
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(WorkoutCategory::class, 'category_id');
    }

    public function workout(): BelongsTo
    {
        return $this->belongsTo(Workout::class, 'workout_id');
    }
}
