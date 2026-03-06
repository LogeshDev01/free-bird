<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Database\Eloquent\SoftDeletes;
use PHPOpenSourceSaver\JWTAuth\Contracts\JWTSubject;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;


class Trainer extends Authenticatable implements JWTSubject
{
    use SoftDeletes;

    protected $table = 'fb_tbl_trainer';

    // ─── Status Constants ─────────────────────────────────
    const STATUS_ACTIVE    = 1;
    const STATUS_INACTIVE  = 0;
    const STATUS_SUSPENDED = 2;

    // ─── Trainer-Client Pivot Status Constants ────────────
    const CLIENT_ACTIVE    = 1;
    const CLIENT_INACTIVE  = 0;
    const CLIENT_COMPLETED = 2;

    protected $fillable = [
        'profile_pic',
        'qr_code',
        'first_name',
        'last_name',
        'gender',
        'dob',
        'phone',
        'email',
        'address',
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
        'status',

        'emergency_contact_person',
        'emergency_phone',

        'bio',
        'state_id',
        'city_id',
        'zone_id',
    ];

    protected $hidden = [
        'created_at',
        'updated_at',
    ];

    protected $appends = ['full_name'];

    // ─── Accessors ─────────────────────────────────────────

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

    /**
     * Get the full URL for the QR code
     */
    public function getQrCodeAttribute($value): ?string
    {
        return $value ? asset('storage/' . $value) : null;
    }

    // ─── JWT ───────────────────────────────────────────────

    public function getJWTIdentifier()
    {
        return $this->getKey();
    }

    public function getJWTCustomClaims()
    {
        return [];
    }

    // ─── Relationships ─────────────────────────────────────

    /**
     * Refresh tokens (polymorphic)
     */
    public function refreshTokens(): MorphMany
    {
        return $this->morphMany(RefreshToken::class, 'tokenable');
    }

    /**
     * Clients assigned to this trainer
     */
    public function clients(): BelongsToMany
    {
        return $this->belongsToMany(Client::class, 'fb_tbl_trainer_client', 'trainer_id', 'client_id')
                    ->withPivot('status', 'start_date', 'end_date')
                    ->withTimestamps();
    }

    /**
     * Active clients only
     */
    public function activeClients(): BelongsToMany
    {
        return $this->clients()->wherePivot('status', self::CLIENT_ACTIVE);
    }

    /**
     * Sessions (training bookings)
     */
    public function sessions(): HasMany
    {
        return $this->hasMany(Session::class, 'trainer_id');
    }

    /**
     * Time slots
     */
    public function slots(): HasMany
    {
        return $this->hasMany(TrainerSlot::class, 'trainer_id');
    }

    /**
     * Workouts created by this trainer
     */
    public function workouts(): HasMany
    {
        return $this->hasMany(Workout::class, 'trainer_id');
    }

    /**
     * Workout assignments made by this trainer
     */
    public function workoutAssignments(): HasMany
    {
        return $this->hasMany(WorkoutAssignment::class, 'trainer_id');
    }

    /**
     * Diet plans created by this trainer
     */
    public function dietPlans(): HasMany
    {
        return $this->hasMany(DietPlan::class, 'trainer_id');
    }

    /**
     * Diet plan assignments made by this trainer
     */
    public function dietPlanAssignments(): HasMany
    {
        return $this->hasMany(DietPlanAssignment::class, 'trainer_id');
    }

    /**
     * Notifications for this trainer (polymorphic)
     */
    public function notifications(): MorphMany
    {
        return $this->morphMany(Notification::class, 'notifiable');
    }

    /**
     * Session reviews where a client has rated this trainer.
     * Replaces the old TrainerRating relationship.
     */
    public function ratings(): HasMany
    {
        return $this->hasMany(SessionReview::class, 'trainer_id')
                    ->whereNotNull('client_rating');
    }

    /**
     * Water daily logs for this trainer
     */
    public function waterDailyLogs(): MorphMany
    {
        return $this->morphMany(WaterDailyLog::class, 'loggable');
    }

    /**
     * Status history timeline for this trainer
     */
    public function statusHistory(): HasMany
    {
        return $this->hasMany(TrainerStatusHistory::class, 'trainer_id');
    }

    public function state(): BelongsTo
    {
        return $this->belongsTo(State::class, 'state_id');
    }

    public function city(): BelongsTo
    {
        return $this->belongsTo(City::class, 'city_id');
    }

    public function zone(): BelongsTo
    {
        return $this->belongsTo(Zone::class, 'zone_id');
    }

    // ─── Helper Methods ────────────────────────────────────

    /**
     * Average rating this trainer has received from clients.
     * Reads client_rating from fb_tbl_session_review.
     * Return type stays float — Dashboard API unchanged.
     */
    public function getAverageRating(): float
    {
        $avg = SessionReview::where('trainer_id', $this->id)
            ->whereNotNull('client_rating')
            ->avg('client_rating');

        return round((float) ($avg ?? 0), 1);
    }

    /**
     * Client satisfaction percentage for the current month.
     * Reads client_rating from fb_tbl_session_review, uses client_reviewed_at.
     * Return type stays float — Dashboard API unchanged.
     */
    public function getMonthlyClientSatisfaction(): float
    {
        $reviews = SessionReview::where('trainer_id', $this->id)
            ->whereNotNull('client_rating')
            ->whereMonth('client_reviewed_at', now()->month)
            ->whereYear('client_reviewed_at', now()->year)
            ->get();

        if ($reviews->isEmpty()) {
            return 0;
        }

        $satisfied = $reviews->where('client_rating', '>=', 4)->count();
        return round(($satisfied / $reviews->count()) * 100, 0);
    }
}