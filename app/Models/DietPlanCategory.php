<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class DietPlanCategory extends Model
{
    protected $table = 'fb_tbl_diet_plan_category';

    protected $fillable = [
        'name',
        'icon',
        'image',
        'description',
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

    // ─── Relationships ─────────────────────────────────────

    public function dietPlans(): HasMany
    {
        return $this->hasMany(DietPlan::class, 'category_id');
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(Plan::class, 'minimum_plan_tier');
    }
}
