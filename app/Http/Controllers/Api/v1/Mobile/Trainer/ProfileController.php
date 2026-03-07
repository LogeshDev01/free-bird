<?php

namespace App\Http\Controllers\Api\v1\Mobile\Trainer;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class ProfileController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | 👤 TRAINER PROFILE APIs
    |--------------------------------------------------------------------------
    */

    /**
     * GET /api/v1/mobile/trainer/profile
     * Fetch the complete trainer profile
     */
    public function show(): JsonResponse
    {
        try {
            $trainer = auth('trainer')->user();

            return response()->json([
                'status'  => true,
                'message' => 'Profile fetched successfully',
                'data'    => [
                    'id'                       => $trainer->id,
                    'profile_pic'              => $trainer->profile_pic,
                    'first_name'               => $trainer->first_name,
                    'last_name'                => $trainer->last_name,
                    'full_name'                => $trainer->full_name,
                    'gender'                   => $trainer->gender,
                    'dob'                      => $trainer->dob ? \Illuminate\Support\Carbon::parse($trainer->dob)->format('d M Y') : null,
                    'phone'                    => $trainer->phone,
                    'email'                    => $trainer->email,
                    'address'                  => $trainer->address,
                    'city'                     => $trainer->city->name ?? null,
                    'state'                    => $trainer->state->name ?? null,
                    'city_id'                  => $trainer->city_id,
                    'state_id'                 => $trainer->state_id,
                    'zone_id'                  => $trainer->zone_id,
                    'zone'                     => $trainer->zone->name ?? $trainer->zone,
                    'zip_code'                 => $trainer->zip_code,
                    'country'                  => $trainer->country,
                    'specialization'           => $trainer->specialization,
                    'experience'               => $trainer->experience,
                    'qualification'            => $trainer->qualification,
                    'emp_id'                   => $trainer->emp_id,
                    'joining_date'             => $trainer->joining_date ? \Illuminate\Support\Carbon::parse($trainer->joining_date)->format('d M Y') : null,
                    'monthly_salary'           => $trainer->monthly_salary,
                    'shift'                    => $trainer->shift,
                    'job_status'               => $trainer->job_status,
                    'status'                   => $trainer->status,
                    'emergency_contact_person' => $trainer->emergency_contact_person,
                    'emergency_phone'          => $trainer->emergency_phone,
                    'rating'                   => $trainer->getAverageRating(),
                    'qr_code'                  => $trainer->qr_code,
                    "bio"                      => $trainer->bio,
                ],
            ], 200);
        } catch (\Exception $e) {
            Log::error('Trainer Profile fetch failed', [
                'trainer_id' => auth('trainer')->id(),
                'error'      => $e->getMessage(),
            ]);

            return response()->json([
                'status'  => false,
                'message' => 'Something went wrong. Please try again.',
            ], 500);
        }
    }

    /**
     * PUT /api/v1/mobile/trainer/profile
     * Update trainer profile information
     */
    public function update(Request $request): JsonResponse
    {
        try {
            $trainer = auth('trainer')->user();

            $validated = $request->validate([
                'full_name'               => 'sometimes|string|max:255',
                'profile_pic'              => 'sometimes|image|mimes:jpeg,png,jpg|max:5120',
                'bio'                      => 'sometimes|string|max:500',
                'address'                  => 'sometimes|string|max:255',
                'zip_code'                 => 'sometimes|string|max:255',
                'city_id'                  => 'sometimes|exists:fb_tbl_city,id',
                'state_id'                 => 'sometimes|exists:fb_tbl_state,id',
                'zone_id'                  => 'sometimes|exists:fb_tbl_zone,id',
                'country'                  => 'sometimes|string|max:255',
                'specialization'           => 'sometimes|string|max:255',
                'experience'               => 'sometimes|string|max:255',
                'qualification'            => 'sometimes|string|max:255',
                'joining_date'             => 'sometimes|string|max:255',
                'monthly_salary'           => 'sometimes|string|max:255',
                'shift'                    => 'sometimes|string|max:255',
                'job_status'               => 'sometimes|string|max:255',
                'status'                   => 'sometimes|string|max:255',
                'emergency_contact_person' => 'sometimes|string|max:255',
                'emergency_phone'          => 'sometimes|string|max:255',
            ]);

            if ($request->hasFile('profile_pic')) {
                $path = $request->file('profile_pic')->store('profile_images', 'public');
                $validated['profile_pic'] = $path;
            }
            
            // Split full_name into first_name and last_name ONLY if provided
            if ($request->has('full_name')) {
                $fullName = $request->full_name;
                $validated['first_name'] = Str::before($fullName, ' ');
                $validated['last_name']  = Str::after($fullName, ' ');
                unset($validated['full_name']); // Remove from validated as it's not a DB column
            }
            
            // The $validated array now contains only the fields passed in the request
            $trainer->update($validated);

            return response()->json([
                'status'  => true,
                'message' => 'Profile updated successfully',
            ], 200);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'status'  => false,
                'message' => 'Validation failed',
                'errors'  => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            Log::error('Trainer Profile update failed', [
                'trainer_id' => auth('trainer')->id(),
                'error'      => $e->getMessage(),
            ]);

            return response()->json([
                'status'  => false,
                'message' => 'Something went wrong. Please try again.',
            ], 500);
        }
    }

    /**
     * POST /api/v1/mobile/trainer/profile/upload-pic
     * Upload and update profile picture
     */
    public function uploadProfilePic(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'profile_pic' => 'required|image|mimes:jpeg,png,jpg|max:5120',
            ]);

            $trainer = auth('trainer')->user();

            // 🧺 Cleanup old profile picture if it exists to keep storage lean
            if ($trainer->getRawOriginal('profile_pic')) {
                \Illuminate\Support\Facades\Storage::disk('public')->delete($trainer->getRawOriginal('profile_pic'));
            }

            $path = $request->file('profile_pic')->store('profile_images', 'public');

            $trainer->update(['profile_pic' => $path]);

            return response()->json([
                'status'  => true,
                'message' => 'Profile picture updated successfully',
                'data'    => [
                    'profile_pic' => $trainer->profile_pic,
                ],
            ], 200);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'status'  => false,
                'message' => 'Validation failed',
                'errors'  => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            Log::error('Trainer Profile picture upload failed', [
                'trainer_id' => auth('trainer')->id(),
                'error'      => $e->getMessage(),
            ]);

            return response()->json([
                'status'  => false,
                'message' => 'Something went wrong. Please try again.',
            ], 500);
        }
    }
    /**
     * GET /api/v1/mobile/trainer/profile/overview
     * Provides stats and analytics with dynamic filtering.
     * Params: ?month=12&year=2025
     */
    /**
     * GET /api/v1/mobile/trainer/profile/overview
     * Provides profile info, fixed current stats, AND initial current-month analytics.
     */
    public function overview(): JsonResponse
    {
        try {
            $trainer = auth('trainer')->user();
            $now = now();

            // 1. Fixed Stats (Current Month)
            $sessionMonthCount = $trainer->sessions()
                ->whereMonth('session_date', $now->month)
                ->whereYear('session_date', $now->year)
                ->whereIn('status', [\App\Models\Session::STATUS_SCHEDULED, \App\Models\Session::STATUS_COMPLETED])
                ->count();

            $stats = [
                'total_clients'      => $trainer->clients()->count(),
                'active_clients'     => $trainer->activeClients()->count(),
                'sessions_month'     => $sessionMonthCount,
                'sessions_all_time'  => $trainer->sessions()->whereIn('status', [\App\Models\Session::STATUS_SCHEDULED, \App\Models\Session::STATUS_COMPLETED])->count(),
                'current_month_name' => $now->format('F'),
            ];

            // 2. Initial Analytics (Current Month - Week Ranges)
            $analytics = $this->calculateWeeklyData($trainer, $now->year, $now->month);

            return response()->json([
                'status'  => true,
                'message' => 'Profile overview fetched successfully',
                'data'    => [
                    'profile' => [
                        'id'             => $trainer->id,
                        'profile_pic'    => $trainer->profile_pic,
                        'full_name'      => $trainer->full_name,
                        'specialization' => $trainer->specialization ?? 'Strength & Conditioning Coach',
                        'rating'         => $trainer->getAverageRating(),
                        'joining_date'   => $trainer->joining_date ? \Illuminate\Support\Carbon::parse($trainer->joining_date)->format('d M Y') : null,
                    ],
                    'stats'     => $stats,
                    'analytics' => $analytics,
                ],
            ], 200);

        } catch (\Exception $e) {
            Log::error('Trainer Profile Overview failed', ['error' => $e->getMessage()]);
            return response()->json(['status' => false, 'message' => 'Something went wrong.'], 500);
        }
    }

    /**
     * GET /api/v1/mobile/trainer/profile/analytics
     * Provides dynamic weekly analytics for a specific month/year.
     */
    public function analytics(Request $request): JsonResponse
    {
        try {
            $trainer = auth('trainer')->user();
            $year  = $request->get('year', now()->year);
            $month = $request->get('month', now()->month); 

            $analytics = $this->calculateWeeklyData($trainer, $year, $month);

            return response()->json([
                'status'  => true,
                'message' => 'Analytics fetched successfully',
                'data'    => $analytics,
            ], 200);

        } catch (\Exception $e) {
            Log::error('Trainer Profile Analytics failed', ['error' => $e->getMessage()]);
            return response()->json(['status' => false, 'message' => 'Something went wrong.'], 500);
        }
    }

    /**
     * Helper to calculate 7-day groups for a month.
     */
    private function calculateWeeklyData($trainer, $year, $month)
    {
        $referenceDate = \Carbon\Carbon::create($year, $month, 1);
        $daysInMonth   = $referenceDate->daysInMonth;
        $monthShort    = $referenceDate->format('M');

        $data = [];
        $currentDay = 1;

        while ($currentDay <= $daysInMonth) {
            $endDay = min($currentDay + 6, $daysInMonth);
            
            $count = \App\Models\Session::where('trainer_id', $trainer->id)
                ->whereYear('session_date', $year)
                ->whereMonth('session_date', $month)
                ->whereDay('session_date', '>=', $currentDay)
                ->whereDay('session_date', '<=', $endDay)
                ->whereIn('status', [\App\Models\Session::STATUS_SCHEDULED, \App\Models\Session::STATUS_COMPLETED])
                ->count();

            $data[] = [
                'label' => "$currentDay-$endDay $monthShort",
                'value' => $count,
            ];
            
            $currentDay += 7;
        }

        return [
            'title' => $referenceDate->format('F, Y'),
            'data'  => $data
        ];
    }
}
