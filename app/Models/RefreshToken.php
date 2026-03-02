<?php 

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RefreshToken extends Model
{
    protected $fillable = [
        'tokenable_id',
        'tokenable_type',
        'token',
        'expires_at',
        'device'
    ];

    public function tokenable()
    {
        return $this->morphTo();
    }
}
