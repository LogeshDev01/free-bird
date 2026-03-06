<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DietPlan extends Model
{
    use SoftDeletes;

    protected $table = 'fb_tbl_diet_plan';

    protected $fillable = [
        'category_id',
        'trainer_id',
        'name',
        'description',
        'image',
        'calories',
        'protein_grams',
        'carbs_grams',
        'fat_grams',
        'meal_type',      // legacy string – kept for backward compat
        'meal_type_id',   // FK → fb_tbl_meal_type
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    // ─── Relationships ─────────────────────────────────────

    public function category(): BelongsTo
    {
        return $this->belongsTo(DietPlanCategory::class, 'category_id');
    }

    public function mealType(): BelongsTo
    {
        return $this->belongsTo(MealType::class, 'meal_type_id');
    }

    public function trainer(): BelongsTo
    {
        return $this->belongsTo(Trainer::class, 'trainer_id');
    }

    public function assignments(): HasMany
    {
        return $this->hasMany(DietPlanAssignment::class, 'diet_plan_id');
    }
}
