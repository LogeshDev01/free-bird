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
                ->with('workoutCategoryType')
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
            $query = Workout::with('category.workoutCategoryType')
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
            $workout = Workout::with(['category.workoutCategoryType', 'trainer:id,first_name,last_name'])
                ->find($id);

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
     * Assign workout(s) to a client with custom sets/reps
     */
    public function assign(Request $request): JsonResponse
    {
        try {
            $trainer = auth('trainer')->user();

            $validated = $request->validate([
                'client_id'     => 'required|exists:fb_tbl_client,id',
                'assigned_date' => 'required|date',
                'due_date'      => 'nullable|date|after_or_equal:assigned_date',
                'notes'         => 'nullable|string|max:1000',
                // Support both single workout_id and a list of assignments
                'category_id'   => 'required_without:assignments|exists:fb_tbl_workout_category,id',
                'workout_id'    => 'required_without:assignments|exists:fb_tbl_workout,id',
                'custom_sets'   => 'nullable|array', // For single workout_id
                'assignments'   => 'nullable|array', // For batch assignment
                'assignments.*.category_id' => 'required_with:assignments|exists:fb_tbl_workout_category,id',
                'assignments.*.workout_id'  => 'required_with:assignments|exists:fb_tbl_workout,id',
                'assignments.*.custom_sets' => 'nullable|array',
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

            $createdAssignments = [];
            $batchId = (string) \Illuminate\Support\Str::uuid();

            // Case A: Batch Assignments
            if ($request->has('assignments')) {
                foreach ($validated['assignments'] as $item) {
                    $createdAssignments[] = WorkoutAssignment::create([
                        'trainer_id'       => $trainer->id,
                        'assigned_by_id'   => $trainer->id,
                        'assigned_by_type' => Trainer::class,
                        'batch_id'         => $batchId,
                        'client_id'        => $validated['client_id'],
                        'category_id'      => $item['category_id'],
                        'workout_id'       => $item['workout_id'],
                        'custom_sets'      => $item['custom_sets'] ?? null,
                        'assigned_date'    => $validated['assigned_date'],
                        'due_date'         => $validated['due_date'] ?? null,
                        'notes'            => $validated['notes'] ?? null,
                    ]);
                }
            } 
            // Case B: Single Assignment
            else {
                $createdAssignments[] = WorkoutAssignment::create([
                    'trainer_id'       => $trainer->id,
                    'assigned_by_id'   => $trainer->id,
                    'assigned_by_type' => Trainer::class,
                    'batch_id'         => $batchId,
                    'client_id'        => $validated['client_id'],
                    'category_id'      => $validated['category_id'],
                    'workout_id'       => $validated['workout_id'],
                    'custom_sets'      => $validated['custom_sets'] ?? null,
                    'assigned_date'    => $validated['assigned_date'],
                    'due_date'         => $validated['due_date'] ?? null,
                    'notes'            => $validated['notes'] ?? null,
                ]);
            }

            return response()->json([
                'status'  => true,
                'message' => 'Workout(s) assigned successfully',
                'data'    => $createdAssignments,
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

    /**
     * PATCH /api/v1/mobile/trainer/workouts/assignments/{id}
     * Update custom sets or notes of an existing assignment
     */
    public function updateAssignment(Request $request, $id): JsonResponse
    {
        try {
            $trainer = auth('trainer')->user();
            $assignment = WorkoutAssignment::findOrFail($id);

            // Security: Ensure the assignment belongs to this trainer
            if ($assignment->trainer_id !== $trainer->id) {
                return response()->json([
                    'status'  => false,
                    'message' => 'Unauthorized. This assignment does not belong to you.',
                ], 403);
            }

            // Prevent editing if already completed
            if ($assignment->status === WorkoutAssignment::STATUS_COMPLETED) {
                return response()->json([
                    'status'  => false,
                    'message' => 'Cannot update a completed workout assignment.',
                ], 400);
            }

            $validated = $request->validate([
                'category_id'   => 'nullable|exists:fb_tbl_workout_category,id',
                'workout_id'    => 'nullable|exists:fb_tbl_workout,id',
                'custom_sets'   => 'nullable|array',
                'notes'         => 'nullable|string|max:1000',
                'due_date'      => 'nullable|date',
                'assigned_date' => 'nullable|date',
            ]);

            $assignment->update($validated);

            return response()->json([
                'status'  => true,
                'message' => 'Workout assignment updated successfully',
                'data'    => $assignment,
            ], 200);
        } catch (\Exception $e) {
            Log::error('Workout assignment update failed', ['error' => $e->getMessage()]);
            return response()->json([
                'status'  => false,
                'message' => 'Update failed: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * DELETE /api/v1/mobile/trainer/workouts/assignments/{id}
     * Remove a workout assignment
     */
    public function removeAssignment($id): JsonResponse
    {
        try {
            $trainer = auth('trainer')->user();
            $assignment = WorkoutAssignment::findOrFail($id);

            // Security: Ensure the assignment belongs to this trainer
            if ($assignment->trainer_id !== $trainer->id) {
                return response()->json([
                    'status'  => false,
                    'message' => 'Unauthorized. This assignment does not belong to you.',
                ], 403);
            }

            $assignment->delete();

            return response()->json([
                'status'  => true,
                'message' => 'Workout assignment removed successfully',
            ], 200);
        } catch (\Exception $e) {
            Log::error('Workout assignment delete failed', ['error' => $e->getMessage()]);
            return response()->json([
                'status'  => false,
                'message' => 'Deletion failed: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * DELETE /api/v1/mobile/trainer/workouts/batch/{batch_id}
     * Remove an entire batch of assignments (a session)
     */
    public function removeBatchAssignment($batchId): JsonResponse
    {
        try {
            $trainer = auth('trainer')->user();
            
            $deletedCount = WorkoutAssignment::where('batch_id', $batchId)
                ->where('trainer_id', $trainer->id)
                ->delete();

            if ($deletedCount === 0) {
                return response()->json([
                    'status'  => false,
                    'message' => 'No assignments found for this batch ID.',
                ], 404);
            }

            return response()->json([
                'status'  => true,
                'message' => "Successfully removed {$deletedCount} workouts from the session.",
            ], 200);
        } catch (\Exception $e) {
            Log::error('Batch delete failed', ['error' => $e->getMessage()]);
            return response()->json([
                'status'  => false,
                'message' => 'Batch deletion failed.',
            ], 500);
        }
    }
}
