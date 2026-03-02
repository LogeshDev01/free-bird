<?php
namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use PHPOpenSourceSaver\JWTAuth\Contracts\JWTSubject;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class Trainer extends Authenticatable implements JWTSubject
{
    protected $table = 'fb_tbl_trainer';

    protected $fillable = [
        'profile_pic',
        'first_name',
        'last_name',
        'gender',
        'dob',
        'phone',
        'email',
        'address',
        'city',
        'state',
        'zip_code',
        'country',
        
        'specialization',
        'experience',
        'qualification',
        
        'emp_id',
        'joining_date',
        'monthly_salary',
        'shift',
        'job_status',
        
        'emergency_contact_person',
        'emergency_phone',

        'password',
    ];

    protected $hidden = [
        'password',
        'created_at',
        'updated_at',
    ];

    public function getJWTIdentifier()
    {
        return $this->getKey();
    }

    public function getJWTCustomClaims()
    {
        return [];
    }

    public function refreshTokens(): MorphMany
    {
        return $this->morphMany(RefreshToken::class, 'tokenable');
    }
}