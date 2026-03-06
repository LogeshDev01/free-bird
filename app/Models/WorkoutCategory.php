<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class WorkoutCategory extends Model
{
    protected $table = 'fb_tbl_workout_category';

    protected $fillable = [
        'workout_category_type_id',
        'name',
        'icon',
        'image',
        'description',
        'duration',
        'is_active',
        'minimum_plan_tier',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function getImageAttribute($value)
    {
        return $value ? asset('storage/' . $value) : null;
    }
    public function workoutCategoryType()
    {
        return $this->belongsTo(WorkoutCategoryType::class, 'workout_category_type_id');
    }

    public function plan(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Plan::class, 'minimum_plan_tier');
    }
    // ─── Relationships ─────────────────────────────────────

    public function workouts(): HasMany
    {
        return $this->hasMany(Workout::class, 'category_id');
    }
}
