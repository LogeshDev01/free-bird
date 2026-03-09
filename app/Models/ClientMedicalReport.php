<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ClientMedicalReport extends Model
{
    protected $table = 'fb_tbl_client_medical_reports';

    protected $fillable = [
        'client_id',
        'name',
        'file_path',
        'report_date',
    ];

    protected $casts = [
        'report_date' => 'date',
    ];

    public function getFilePathAttribute($value): ?string
    {
        return $value ? asset('storage/' . $value) : null;
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class, 'client_id');
    }
}
