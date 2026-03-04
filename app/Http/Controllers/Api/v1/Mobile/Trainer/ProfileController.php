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
                    'dob'                      => $trainer->dob,
                    'phone'                    => $trainer->phone,
                    'email'                    => $trainer->email,
                    'address'                  => $trainer->address,
                    'city'                     => $trainer->city,
                    'state'                    => $trainer->state,
                    'zip_code'                 => $trainer->zip_code,
                    'country'                  => $trainer->country,
                    'specialization'           => $trainer->specialization,
                    'experience'               => $trainer->experience,
                    'qualification'            => $trainer->qualification,
                    'emp_id'                   => $trainer->emp_id,
                    'joining_date'             => $trainer->joining_date,
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
                'city'                     => 'sometimes|string|max:255',
                'state'                    => 'sometimes|string|max:255',
                'zip_code'                 => 'sometimes|string|max:255',
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
}
