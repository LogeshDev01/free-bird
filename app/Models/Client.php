<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class Client extends Authenticatable
{
    use SoftDeletes;

    protected $table = 'fb_tbl_client';

    // ─── Status Constants ─────────────────────────────────
    const STATUS_ACTIVE   = 1;
    const STATUS_INACTIVE = 0;

    protected $fillable = [
        'profile_pic',
        'first_name',
        'last_name',
        'gender',
        'dob',
        'phone',
        'email',
        'password',
        'height',
        'weight',
        'goal',
        'address',
        'zip_code',
        'country',
        'emergency_contact_person',
        'emergency_phone',
        'status',
        'current_subscription_id',
        'state_id',
        'city_id',
        'zone_id',
        'steps_goal',
        'fat_goal',
        'bmi_goal',
        'ideal_weight_goal',
        'bmr_goal',
        'calories_goal',
        'goals_updated_at',
    ];

    protected $hidden = [
        'password',
        'created_at',
        'updated_at',
    ];

    protected $casts = [
        'dob'    => 'date',
        'height' => 'decimal:2',
        'weight' => 'decimal:2',
        'status' => 'integer',
        'steps_goal' => 'integer',
        'fat_goal' => 'decimal:2',
        'bmi_goal' => 'decimal:2',
        'ideal_weight_goal' => 'decimal:2',
        'bmr_goal' => 'integer',
        'calories_goal' => 'integer',
        'goals_updated_at' => 'datetime',
    ];

    protected $appends = ['full_name'];

    // ─── Accessors ────────────────────────────────────────

    public function getFullNameAttribute(): string
    {
        return trim($this->first_name . ' ' . $this->last_name);
    }

    /**
     * Get the full URL for the profile picture
     */
    public function getProfilePicAttribute($value): ?string
    {
        return $value ? asset('storage/' . $value) : null;
    }

    // ─── Relationships ────────────────────────────────────

    public function trainers(): BelongsToMany
    {
        return $this->belongsToMany(Trainer::class, 'fb_tbl_trainer_client', 'client_id', 'trainer_id')
                    ->withPivot('status', 'start_date', 'end_date')
                    ->withTimestamps();
    }

    public function sessions(): HasMany
    {
        return $this->hasMany(Session::class, 'client_id');
    }

    public function workoutAssignments(): HasMany
    {
        return $this->hasMany(WorkoutAssignment::class, 'client_id');
    }

    public function dietPlanAssignments(): HasMany
    {
        return $this->hasMany(DietPlanAssignment::class, 'client_id');
    }

    public function notifications(): MorphMany
    {
        return $this->morphMany(Notification::class, 'notifiable');
    }

    public function ratings(): HasMany
    {
        return $this->hasMany(TrainerRating::class, 'client_id');
    }

    /**
     * Water daily logs for this client
     */
    public function waterDailyLogs(): MorphMany
    {
        return $this->morphMany(WaterDailyLog::class, 'loggable');
    }

    public function currentSubscription(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(ClientSubscription::class, 'current_subscription_id');
    }

    public function state(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(State::class, 'state_id');
    }

    public function city(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(City::class, 'city_id');
    }

    public function dailyMetrics(): HasMany
    {
        return $this->hasMany(ClientDailyMetric::class, 'client_id');
    }

    public function progressPhotos(): HasMany
    {
        return $this->hasMany(ClientProgressPhoto::class, 'client_id');
    }

    public function medicalReports(): HasMany
    {
        return $this->hasMany(ClientMedicalReport::class, 'client_id');
    }

    public function zone(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Zone::class, 'zone_id');
    }
}
