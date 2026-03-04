<?php

namespace App\Http\Controllers\Api\v1\Admin;

use App\Http\Controllers\Controller;
use App\Models\WorkoutAssignment;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class WorkoutAssignmentController extends Controller
{
    /**
     * POST /api/v1/admin/workouts/assign
     * Admin assigning workouts to any client
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $admin = auth('api')->user();

            $validated = $request->validate([
                'client_id'     => 'required|exists:fb_tbl_client,id',
                'assigned_date' => 'required|date',
                'due_date'      => 'nullable|date|after_or_equal:assigned_date',
                'notes'         => 'nullable|string|max:1000',
                
                // Support both single and batch
                'category_id'   => 'required_without:assignments|exists:fb_tbl_workout_category,id',
                'workout_id'    => 'required_without:assignments|exists:fb_tbl_workout,id',
                'custom_sets'   => 'nullable|array',
                'assignments'   => 'nullable|array',
                'assignments.*.category_id' => 'required_with:assignments|exists:fb_tbl_workout_category,id',
                'assignments.*.workout_id' => 'required_with:assignments|exists:fb_tbl_workout,id',
                'assignments.*.custom_sets' => 'nullable|array',
            ]);

            $createdAssignments = [];
            $batchId = (string) \Illuminate\Support\Str::uuid();

            if ($request->has('assignments')) {
                foreach ($validated['assignments'] as $item) {
                    $createdAssignments[] = WorkoutAssignment::create([
                        'assigned_by_id'   => $admin->id,
                        'assigned_by_type' => User::class,
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
            } else {
                $createdAssignments[] = WorkoutAssignment::create([
                    'assigned_by_id'   => $admin->id,
                    'assigned_by_type' => User::class,
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
                'message' => 'Workout(s) assigned by admin successfully',
                'data'    => $createdAssignments,
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'status'  => false,
                'message' => 'Assignment failed: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * PATCH /api/v1/admin/workouts/assignments/{id}
     * Admin updates a workout assignment
     */
    public function update(Request $request, string $id): JsonResponse
    {
        try {
            $assignment = WorkoutAssignment::findOrFail($id);

            $validated = $request->validate([
                'category_id'   => 'nullable|exists:fb_tbl_workout_category,id',
                'workout_id'    => 'nullable|exists:fb_tbl_workout,id',
                'custom_sets'   => 'nullable|array',
                'notes'         => 'nullable|string|max:1000',
                'due_date'      => 'nullable|date',
                'assigned_date' => 'nullable|date',
                'status'        => 'nullable|integer',
            ]);

            $assignment->update($validated);

            return response()->json([
                'status'  => true,
                'message' => 'Workout assignment updated successfully by admin',
                'data'    => $assignment,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status'  => false,
                'message' => 'Update failed: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * DELETE /api/v1/admin/workouts/assignments/{id}
     * Admin deletes a workout assignment
     */
    public function destroy(string $id): JsonResponse
    {
        try {
            $assignment = WorkoutAssignment::findOrFail($id);
            $assignment->delete();

            return response()->json([
                'status'  => true,
                'message' => 'Workout assignment deleted successfully by admin',
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status'  => false,
                'message' => 'Deletion failed: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * DELETE /api/v1/admin/workouts/batch/{batch_id}
     * Admin deletes an entire batch of assignments
     */
    public function destroyBatch(string $batchId): JsonResponse
    {
        try {
            $deletedCount = WorkoutAssignment::where('batch_id', $batchId)->delete();

            return response()->json([
                'status'  => true,
                'message' => "Successfully deleted batch session with {$deletedCount} workouts.",
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status'  => false,
                'message' => 'Batch deletion failed: ' . $e->getMessage(),
            ], 500);
        }
    }
}
