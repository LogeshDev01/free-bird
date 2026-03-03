<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WaterIntake extends Model
{
    protected $table = 'fb_tbl_water_intake';

    protected $fillable = [
        'water_daily_log_id',
        'amount_ml',
        'logged_at',
    ];

    protected $casts = [
        'amount_ml' => 'integer',
    ];

    // ─── Relationships ────────────────────────────────────

    /**
     * The parent daily log this intake belongs to
     */
    public function dailyLog(): BelongsTo
    {
        return $this->belongsTo(WaterDailyLog::class, 'water_daily_log_id');
    }
}
