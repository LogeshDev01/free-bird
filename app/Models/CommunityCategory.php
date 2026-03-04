<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CommunityCategory extends Model
{
    protected $table = 'fb_tbl_community_category';

    protected $fillable = [
        'name',
        'slug',
        'show_to_admin',
        'show_to_trainer',
        'show_to_client',
        'is_active',
    ];

    protected $casts = [
        'show_to_admin'   => 'boolean',
        'show_to_trainer' => 'boolean',
        'show_to_client'  => 'boolean',
        'is_active'       => 'boolean',
    ];

    public function posts(): HasMany
    {
        return $this->hasMany(CommunityPost::class, 'category_id');
    }
}
