<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DietPlanCategory extends Model
{
    protected $table = 'fb_tbl_diet_plan_category';

    protected $fillable = [
        'name',
        'icon',
        'description',
        'is_active',
        'minimum_plan_tier',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    // ─── Relationships ─────────────────────────────────────

    public function dietPlans(): HasMany
    {
        return $this->hasMany(DietPlan::class, 'category_id');
    }
}
