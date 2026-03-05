<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Zone extends Model
{
    protected $table = 'fb_tbl_zone';

    protected $fillable = [
        'city_id',
        'name',
        'zone_code',
        'manager_id',
        'boundary_coordinates',
        'is_active',
    ];

    protected $casts = [
        'boundary_coordinates' => 'array',
        'is_active' => 'boolean',
    ];

    public function city(): BelongsTo
    {
        return $this->belongsTo(City::class, 'city_id');
    }

    public function trainers(): HasMany
    {
        return $this->hasMany(Trainer::class, 'zone_id');
    }

    public function clients(): HasMany
    {
        return $this->hasMany(Client::class, 'zone_id');
    }
}
