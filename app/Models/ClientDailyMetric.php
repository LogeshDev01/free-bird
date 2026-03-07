<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ClientDailyMetric extends Model
{
    protected $table = 'fb_tbl_client_daily_metrics';

    protected $fillable = [
        'client_id',
        'log_date',
        'steps',
        'weight_kg',
        'fat_percent',
        'bmi',
        'bmr',
        'ideal_weight',
        'calories_consumed',
        'calories_burnt',
        'chest_cm',
        'waist_cm',
        'neck_cm',
    ];

    protected $casts = [
        'log_date' => 'date',
        'steps' => 'integer',
        'weight_kg' => 'decimal:2',
        'fat_percent' => 'decimal:2',
        'bmi' => 'decimal:2',
        'bmr' => 'integer',
        'ideal_weight' => 'decimal:2',
        'calories_consumed' => 'integer',
        'calories_burnt' => 'integer',
        'chest_cm' => 'decimal:2',
        'waist_cm' => 'decimal:2',
        'neck_cm' => 'decimal:2',
    ];

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class, 'client_id');
    }
}
