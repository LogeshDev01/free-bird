<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SlotType extends Model
{
    protected $table = 'fb_tbl_slot_type';

    protected $fillable = ['name', 'status'];

    protected $casts = [
        'status' => 'integer',
    ];

    public function slots(): HasMany
    {
        return $this->hasMany(TrainerSlot::class, 'slot_type_id');
    }
}
