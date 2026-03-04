<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ClientFeatureUsage extends Model
{
    use HasFactory;

    protected $table = 'fb_tbl_client_feature_usages';

    protected $fillable = [
        'client_subscription_id',
        'feature_id',
        'used_count',
        'reset_at',
    ];

    protected $casts = [
        'reset_at' => 'datetime',
    ];

    public function subscription()
    {
        return $this->belongsTo(ClientSubscription::class, 'client_subscription_id');
    }

    public function feature()
    {
        return $this->belongsTo(Feature::class);
    }
}
