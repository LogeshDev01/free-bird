<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BusinessSetting extends Model
{
    use HasFactory;

    protected $table = 'fb_tbl_business_settings';

    protected $fillable = [
        'key_name',
        'value'
    ];
}
