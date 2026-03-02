<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OtpVerification extends Model
{
    protected $table = 'fb_tbl_otp_verification';

    protected $fillable = [
        'mobile_number',
        'otp',
        'expires_at',
        'is_verified',
        'device'
    ];

    protected $hidden = [
        'created_at',
        'updated_at',
    ];
}