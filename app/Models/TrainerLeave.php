<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TrainerLeave extends Model
{
    use SoftDeletes;

    protected $table = 'fb_tbl_trainer_leaves';

    // ─── Status Constants ─────────────────────────────────
    const STATUS_PENDING  = 0;
    const STATUS_APPROVED = 1;
    const STATUS_REJECTED = 2;

    protected $fillable = [
        'trainer_id',
        'leave_type_id',
        'start_date',
        'end_date',
        'start_time',
        'end_time',
        'total_days',
        'reason',
        'additional_note',
        'status',
        'admin_comment',
        'actioned_at',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date'   => 'date',
        'start_time' => 'datetime:H:i',
        'end_time'   => 'datetime:H:i',
        'actioned_at'=> 'datetime',
        'status'     => 'integer'
    ];

    // ─── Relationships ─────────────────────────────────────

    public function trainer(): BelongsTo
    {
        return $this->belongsTo(Trainer::class, 'trainer_id');
    }

    public function leaveType(): BelongsTo
    {
        return $this->belongsTo(LeaveType::class, 'leave_type_id');
    }

    // ─── Scopes ───────────────────────────────────────────

    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    public function scopeApproved($query)
    {
        return $query->where('status', self::STATUS_APPROVED);
    }

    public function scopeRejected($query)
    {
        return $query->where('status', self::STATUS_REJECTED);
    }
}
