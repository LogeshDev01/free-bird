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
        'assigned_by_id',
        'assigned_by_type',
        'batch_id',
        'client_id',
        'category_id',
        'workout_id',
        'custom_sets',
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
    ];

    // ─── Relationships ────────────────────────────────────

    /**
     * Polymorphic relation to whoever assigned the workout (Admin or Trainer)
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
