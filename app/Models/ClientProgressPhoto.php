<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ClientProgressPhoto extends Model
{
    protected $table = 'fb_tbl_client_progress_photos';

    protected $fillable = [
        'client_id',
        'log_date',
        'front_view',
        'side_view',
        'back_view',
    ];

    protected $casts = [
        'log_date' => 'date',
    ];

    public function getFrontViewAttribute($value): ?string
    {
        return $value ? asset('storage/' . $value) : null;
    }

    public function getSideViewAttribute($value): ?string
    {
        return $value ? asset('storage/' . $value) : null;
    }

    public function getBackViewAttribute($value): ?string
    {
        return $value ? asset('storage/' . $value) : null;
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class, 'client_id');
    }
}
