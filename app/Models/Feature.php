<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Feature extends Model
{
    use HasFactory;

    protected $table = 'fb_tbl_features';

    protected $fillable = [
        'name',
        'slug',
        'type',
        'resets_on_billing',
    ];

    public function planFeatures()
    {
        return $this->hasMany(PlanFeature::class);
    }

    public function usages()
    {
        return $this->hasMany(ClientFeatureUsage::class);
    }
}
