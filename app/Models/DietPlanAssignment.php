<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DietPlanAssignment extends Model
{
    use SoftDeletes;
    protected $table = 'fb_tbl_diet_plan_assignment';

    // ─── Status Constants ─────────────────────────────────
    const STATUS_DRAFT       = 0;  // Unified with WorkoutAssignment
    const STATUS_PENDING     = 1;
    const STATUS_IN_PROGRESS = 2;
    const STATUS_COMPLETED   = 3;

    protected $fillable = [
        'trainer_id',
        'client_id',
        'diet_plan_id',
        'session_id',        // nullable — link to specific session
        'assigned_by_id',    // nullable — who assigned (Trainer or Admin)
        'assigned_by_type',  // nullable — App\Models\Trainer or App\Models\User
        'assigned_date',
        'due_date',
        'status',
        'notes',
        'performance_data',
        'is_completed',
        'completed_at',
    ];

    protected $casts = [
        'assigned_date' => 'date',
        'due_date'      => 'date',
        'status'        => 'integer',
        'performance_data' => 'array',
        'is_completed'  => 'boolean',
        'completed_at'  => 'datetime',
        'deleted_at'    => 'datetime',
    ];

    // ─── Scopes ───────────────────────────────────────────────────────────────

    public function scopeCompleted($query)
    {
        return $query->where('is_completed', true);
    }

    public function scopeYesterday($query)
    {
        return $query->whereDate('assigned_date', \Carbon\Carbon::yesterday());
    }

    // ─── Relationships ────────────────────────────────────

    /**
     * Polymorphic: whoever assigned the diet plan (Trainer or Admin)
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

    public function dietPlan(): BelongsTo
    {
        return $this->belongsTo(DietPlan::class, 'diet_plan_id');
    }
}
