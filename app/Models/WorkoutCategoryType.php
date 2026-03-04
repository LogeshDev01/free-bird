<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WorkoutCategoryType extends Model
{
    use HasFactory;

    protected $table = 'fb_tbl_workout_category_type';

    protected $fillable = [
        'name',
        'icon',
        'description',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function workoutCategories()
    {
        return $this->hasMany(WorkoutCategory::class, 'workout_category_type_id');
    }
}
