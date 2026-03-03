<?php

namespace App\Http\Controllers\Api\v1\Mobile\Trainer;

use App\Http\Controllers\Controller;
use App\Models\Trainer;
use App\Models\Workout;
use App\Models\WorkoutCategory;
use App\Models\WorkoutAssignment;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class WorkoutController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | 💪 WORKOUT LIBRARY APIs
    |--------------------------------------------------------------------------
    */

    /**
     * GET /api/v1/mobile/trainer/workouts/categories
     * List all workout categories
     */
    public function categories(): JsonResponse
    {
        try {
            $categories = WorkoutCategory::where('is_active', true)
                ->withCount('workouts')
                ->get();

            return response()->json([
                'status'  => true,
                'message' => 'Workout categories fetched successfully',
                'data'    => $categories,
            ], 200);
        } catch (\Exception $e) {
            Log::error('Workout categories failed', ['error' => $e->getMessage()]);

            return response()->json([
                'status'  => false,
                'message' => 'Something went wrong. Please try again.',
            ], 500);
        }
    }

    /**
     * GET /api/v1/mobile/trainer/workouts
     * List workouts with optional category filter
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $query = Workout::with('category:id,name')
                ->where('is_active', true);

            if ($request->has('category_id')) {
                $query->where('category_id', $request->category_id);
            }

            if ($request->has('difficulty')) {
                $query->where('difficulty', $request->difficulty);
            }

            // ✅ FIX: Sanitize LIKE wildcards
            if ($request->has('search') && $request->search !== '') {
                $search = str_replace(['%', '_'], ['\\%', '\\_'], $request->search);
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'LIKE', "%{$search}%")
                      ->orWhere('muscle_group', 'LIKE', "%{$search}%");
                });
            }

            $workouts = $query->orderBy('name')
                              ->paginate($request->get('per_page', 20));

            return response()->json([
                'status'  => true,
                'message' => 'Workouts fetched successfully',
                'data'    => $workouts,
            ], 200);
        } catch (\Exception $e) {
            Log::error('Workout list failed', ['error' => $e->getMessage()]);

            return response()->json([
                'status'  => false,
                'message' => 'Something went wrong. Please try again.',
            ], 500);
        }
    }

    /**
     * GET /api/v1/mobile/trainer/workouts/{id}
     * Get workout detail
     */
    public function show($id): JsonResponse
    {
        try {
            $workout = Workout::with('category:id,name')->find($id);

            if (!$workout) {
                return response()->json([
                    'status'  => false,
                    'message' => 'Workout not found',
                ], 404);
            }

            return response()->json([
                'status'  => true,
                'message' => 'Workout details fetched successfully',
                'data'    => $workout,
            ], 200);
        } catch (\Exception $e) {
            Log::error('Workout detail failed', ['workout_id' => $id, 'error' => $e->getMessage()]);

            return response()->json([
                'status'  => false,
                'message' => 'Something went wrong. Please try again.',
            ], 500);
        }
    }

    /**
     * POST /api/v1/mobile/trainer/workouts/assign
     * Assign workout to a client
     */
    public function assign(Request $request): JsonResponse
    {
        try {
            $trainer = auth('trainer')->user();

            $validated = $request->validate([
                'client_id'     => 'required|exists:fb_tbl_client,id',
                'workout_id'    => 'required|exists:fb_tbl_workout,id',
                'assigned_date' => 'required|date',
                'due_date'      => 'nullable|date|after_or_equal:assigned_date',
                'notes'         => 'nullable|string|max:1000',
            ]);

            // Verify client belongs to trainer
            $isAssigned = $trainer->clients()
                ->where('fb_tbl_client.id', $validated['client_id'])
                ->wherePivot('status', Trainer::CLIENT_ACTIVE)
                ->exists();

            if (!$isAssigned) {
                return response()->json([
                    'status'  => false,
                    'message' => 'Client not assigned to you',
                ], 403);
            }

            $assignment = WorkoutAssignment::create([
                'trainer_id'    => $trainer->id,
                'client_id'     => $validated['client_id'],
                'workout_id'    => $validated['workout_id'],
                'assigned_date' => $validated['assigned_date'],
                'due_date'      => $validated['due_date'] ?? null,
                'notes'         => $validated['notes'] ?? null,
            ]);

            return response()->json([
                'status'  => true,
                'message' => 'Workout assigned successfully',
                'data'    => $assignment->load(['workout.category', 'client:id,first_name,last_name']),
            ], 201);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'status'  => false,
                'message' => 'Validation failed',
                'errors'  => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            Log::error('Workout assign failed', [
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
