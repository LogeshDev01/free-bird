<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class City extends Model
{
    protected $table = 'fb_tbl_city';

    protected $fillable = [
        'state_id',
        'name',
        'latitude',
        'longitude',
        'slug',
        'is_popular',
        'is_active',
    ];

    protected $casts = [
        'is_popular' => 'boolean',
        'is_active' => 'boolean',
        'latitude' => 'float',
        'longitude' => 'float',
    ];

    public function state(): BelongsTo
    {
        return $this->belongsTo(State::class, 'state_id');
    }

    public function zones(): HasMany
    {
        return $this->hasMany(Zone::class, 'city_id');
    }

    public function trainers(): HasMany
    {
        return $this->hasMany(Trainer::class, 'city_id');
    }

    public function clients(): HasMany
    {
        return $this->hasMany(Client::class, 'city_id');
    }
}
