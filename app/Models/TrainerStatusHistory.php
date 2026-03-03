<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TrainerStatusHistory extends Model
{
    protected $table = 'fb_tbl_trainer_status_history';

    protected $fillable = [
        'trainer_id',
        'status',
        'title',
        'note',
        'effective_date',
    ];

    protected $casts = [
        'effective_date' => 'date',
    ];

    // ─── Relationships ─────────────────────────────────────

    public function trainer(): BelongsTo
    {
        return $this->belongsTo(Trainer::class, 'trainer_id');
    }
}
