<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class WaterDailyLog extends Model
{
    protected $table = 'fb_tbl_water_daily_log';

    protected $fillable = [
        'loggable_id',
        'loggable_type',
        'log_date',
        'water_goal_ml',
        'total_consumed_ml',
    ];

    protected $casts = [
        'log_date'          => 'date',
        'water_goal_ml'     => 'integer',
        'total_consumed_ml' => 'integer',
    ];

    // ─── Relationships ────────────────────────────────────

    /**
     * Polymorphic owner: Client or Trainer
     */
    public function loggable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Individual intake entries for this day
     */
    public function intakes(): HasMany
    {
        return $this->hasMany(WaterIntake::class, 'water_daily_log_id');
    }

    // ─── Helpers ──────────────────────────────────────────

    /**
     * Recalculate the denormalized total from child rows.
     * Call this after any insert / update / delete on intakes.
     */
    public function recalculateTotal(): void
    {
        $this->total_consumed_ml = $this->intakes()->sum('amount_ml');
        $this->saveQuietly(); // avoid triggering model events
    }
}
