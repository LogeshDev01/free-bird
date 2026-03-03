<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TrainerRating extends Model
{
    protected $table = 'fb_tbl_trainer_rating';

    protected $fillable = [
        'trainer_id',
        'client_id',
        'rating',
        'review',
    ];

    protected $casts = [
        'rating' => 'decimal:1',
    ];

    // ─── Relationships ─────────────────────────────────────

    public function trainer(): BelongsTo
    {
        return $this->belongsTo(Trainer::class, 'trainer_id');
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class, 'client_id');
    }
}
