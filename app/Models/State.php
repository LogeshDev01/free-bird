<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class State extends Model
{
    protected $table = 'fb_tbl_state';

    protected $fillable = [
        'country_id',
        'name',
        'iso_code',
        'fips_code',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function cities(): HasMany
    {
        return $this->hasMany(City::class, 'state_id');
    }

    public function trainers(): HasMany
    {
        return $this->hasMany(Trainer::class, 'state_id');
    }

    public function clients(): HasMany
    {
        return $this->hasMany(Client::class, 'state_id');
    }
}
