<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Workout extends Model
{
    use SoftDeletes;

    protected $table = 'fb_tbl_workout';

    protected $fillable = [
        'category_id',
        'trainer_id',
        'name',
        'description',
        'image',
        'video_url',
        'difficulty',
        'muscle_group',
        'duration_minutes',
        'sets',
        'reps',
        'rest_seconds',
        'is_active',
        'lbs',
        'kg',
        'weight_unit',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'lbs' => 'decimal:2',
        'kg' => 'decimal:2',
    ];

    // ─── Relationships ─────────────────────────────────────

    public function category(): BelongsTo
    {
        return $this->belongsTo(WorkoutCategory::class, 'category_id');
    }

    public function trainer(): BelongsTo
    {
        return $this->belongsTo(Trainer::class, 'trainer_id');
    }

    public function assignments(): HasMany
    {
        return $this->hasMany(WorkoutAssignment::class, 'workout_id');
    }
}
