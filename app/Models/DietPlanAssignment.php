<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DietPlanAssignment extends Model
{
    protected $table = 'fb_tbl_diet_plan_assignment';

    // ─── Status Constants ─────────────────────────────────
    const STATUS_PENDING     = 1;
    const STATUS_IN_PROGRESS = 2;
    const STATUS_COMPLETED   = 3;

    protected $fillable = [
        'trainer_id',
        'client_id',
        'diet_plan_id',
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

    public function dietPlan(): BelongsTo
    {
        return $this->belongsTo(DietPlan::class, 'diet_plan_id');
    }
}
