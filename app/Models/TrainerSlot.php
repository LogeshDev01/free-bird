<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TrainerSlot extends Model
{
    protected $table = 'fb_tbl_trainer_slot';

    protected $fillable = [
        'trainer_id',
        'slot_type_id',
        'date',
        'start_time',
        'end_time',
        'note',
    ];

    protected $casts = [
        'date' => 'date',
    ];

    // ─── Relationships ─────────────────────────────────────

    /**
     * The trainer this slot belongs to
     */
    public function trainer(): BelongsTo
    {
        return $this->belongsTo(Trainer::class, 'trainer_id');
    }

    /**
     * The type (category) of this slot
     */
    public function type(): BelongsTo
    {
        return $this->belongsTo(SlotType::class, 'slot_type_id');
    }

    /**
     * Booked sessions for this specific slot instance
     */
    public function sessions(): HasMany
    {
        return $this->hasMany(Session::class, 'slot_id');
    }
}
