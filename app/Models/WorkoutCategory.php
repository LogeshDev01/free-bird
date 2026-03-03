<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class WorkoutCategory extends Model
{
    protected $table = 'fb_tbl_workout_category';

    protected $fillable = [
        'name',
        'icon',
        'description',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    // ─── Relationships ─────────────────────────────────────

    public function workouts(): HasMany
    {
        return $this->hasMany(Workout::class, 'category_id');
    }
}
