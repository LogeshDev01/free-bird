<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ClientSubscription extends Model
{
    use HasFactory;

    protected $table = 'fb_tbl_client_subscriptions';

    protected $fillable = [
        'client_id',
        'plan_id',
        'status',
        'starts_at',
        'ends_at',
        'payment_gateway_ref',
    ];

    protected $casts = [
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
    ];

    public function client()
    {
        return $this->belongsTo(Client::class, 'client_id');
    }

    public function plan()
    {
        return $this->belongsTo(Plan::class);
    }

    public function usages()
    {
        return $this->hasMany(ClientFeatureUsage::class);
    }
}
