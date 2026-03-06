<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LeaveType extends Model
{
    protected $table = 'fb_tbl_leave_types';

    protected $fillable = [
        'name',
        'icon',
        'description',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    /**
     * Relationship with trainer leaves
     */
    public function trainerLeaves(): HasMany
    {
        return $this->hasMany(TrainerLeave::class, 'leave_type_id');
    }
}
