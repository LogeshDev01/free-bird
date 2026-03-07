<?php

namespace App\Http\Controllers\Api\v1\Mobile\Trainer;

use App\Http\Controllers\Controller;
use App\Models\Session;
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
     * List all workout categories formatted for UI (Assign Workouts screen)
     */
    public function categories(Request $request): JsonResponse
    {
        try {
            $query = WorkoutCategory::where('is_active', true)
                ->with('workoutCategoryType:id,name');

            // ✅ Search functionality
            if ($request->has('search') && $request->search !== '') {
                $search = str_replace(['%', '_'], ['\\%', '\\_'], $request->search);
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'LIKE', "%{$search}%")
                      ->orWhereHas('workoutCategoryType', function ($tq) use ($search) {
                          $tq->where('name', 'LIKE', "%{$search}%");
                      });
                });
            }

            $categories = $query->paginate($request->get('per_page', 20));

            $formattedCategories = collect($categories->items())->map(function ($cat) {
                return [
                    'id'       => $cat->id,
                    'title'    => $cat->name,
                    'tag'      => $cat->workoutCategoryType->name ?? 'General',
                    'duration' => $cat->duration ? $cat->duration . ' Duration' : '00:00:00 Duration',
                    'image'    => $cat->image,
                ];
            });

            return response()->json([
                'status'  => true,
                'message' => 'Workout categories fetched successfully',
                'data'    => [
                    'list' => $formattedCategories,
                    'meta' => [
                        'current_page' => $categories->currentPage(),
                        'last_page'    => $categories->lastPage(),
                        'total'        => $categories->total(),
                        'per_page'     => $categories->perPage(),
                    ]
                ],
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
     * List workouts with optional category filter, optimized for mobile UI
     */
    public function index(Request $request): JsonResponse
    { 
        try {
            $query = Workout::where('is_active', true)
                ->with('category:id,name')
                ->select(['id', 'category_id', 'name', 'image', 'sets', 'reps', 'lbs', 'kg', 'rest_seconds', 'muscle_group', 'duration_minutes', 'created_at']);

            // 1. Category Filter
            if ($request->filled('category_ids')) {
                $categoryIds = $request->input('category_ids');
                if (is_string($categoryIds)) {
                    $categoryIds = array_filter(array_map('intval', explode(',', $categoryIds)));
                } else {
                    $categoryIds = array_filter(array_map('intval', (array) $categoryIds));
                }

                if (!empty($categoryIds)) {
                    $query->whereIn('category_id', $categoryIds);
                }
            }

            // 2. Search Functionality
            if ($request->filled('search')) {
                $search = str_replace(['%', '_'], ['\\%', '\\_'], $request->search);
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'LIKE', "%{$search}%")
                      ->orWhere('muscle_group', 'LIKE', "%{$search}%");
                });
            }

            $workouts = $query->orderBy('name')->paginate($request->get('per_page', 20));

            // 3. Format & Grouping
            $formattedWorkouts = collect($workouts->items())->map(function ($w) {
                return [
                    'id'            => $w->id,
                    'category_id'   => $w->category_id,
                    'category_name' => $w->category->name ?? 'N/A',
                    'name'          => $w->name,
                    'thumbnail'     => $w->image,
                    'sets'          => $w->sets,
                    'reps'          => $w->reps,
                    'lbs'           => $w->lbs,
                    'kg'            => $w->kg,
                    'rest'          => $w->rest_seconds . 's',
                    'duration'      => $w->duration_minutes,
                    'muscle_group'  => $w->muscle_group,
                    'created_at'    => \Illuminate\Support\Carbon::parse($w->created_at)->format('d M Y'),
                ];
            });

            // Group the formatted workouts for the current page
            $groupedData = $formattedWorkouts->groupBy('category_id')->map(function ($items, $categoryId) {
                return [
                    'category_id'   => $categoryId,
                    'category_name' => $items->first()['category_name'],
                    'workouts'      => $items->values()
                ];
            })->values();

            return response()->json([
                'status'  => true,
                'message' => 'Workouts fetched successfully',
                'data'    => [
                    'list' => $groupedData,
                    'meta' => [
                        'current_page' => $workouts->currentPage(),
                        'last_page'    => $workouts->lastPage(),
                        'total'        => $workouts->total(),
                        'per_page'     => $workouts->perPage(),
                    ]
                ],
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
                'data'    => [
                    'id'            => $workout->id,
                    'name'          => $workout->name,
                    'category'      => $workout->category->name ?? 'N/A',
                    'thumbnail'     => $workout->image,
                    'sets'          => $workout->sets,
                    'reps'          => $workout->reps,
                    'lbs'           => $workout->lbs,
                    'kg'            => $workout->kg,
                    'rest'          => $workout->rest_seconds . 's',
                    'duration'      => $workout->duration_minutes,
                    'muscle_group'  => $workout->muscle_group,
                    'trainer'       => $workout->trainer->full_name ?? 'Admin',
                    'created_at'    => \Illuminate\Support\Carbon::parse($workout->created_at)->format('d M Y'),
                ],
            ], 200);
        } catch (\Exception $e) {
            Log::error('Workout detail failed', ['workout_id' => $id, 'error' => $e->getMessage()]);

            return response()->json([
                'status'  => false,
                'message' => 'Something went wrong. Please try again.',
            ], 500);
        }
    }

    /*
    |--------------------------------------------------------------------------
    | 📋 WORKOUT ASSIGNMENT APIs
    |--------------------------------------------------------------------------
    |
    | assign()               POST   /workouts/assign
    | updateAssignment()     PATCH  /workouts/assignments/{id}
    | removeAssignment()     DELETE /workouts/assignments/{id}
    | removeBatchAssignment() DELETE /workouts/batch/{batch_id}
    |
    */

    /**
     * POST /api/v1/mobile/trainer/workouts/assign
     *
     * Robust multi-client, multi-date assignment.
     *
     * Payload shape:
     * {
     *   "client_ids":     [12, 34, 56],          // one or many
     *   "assigned_dates": ["2026-03-05", "2026-03-06"],  // one or many dates
     *   "due_date":       "2026-03-12",           // optional
     *   "notes":          "Focus on form",        // optional
     *   "assignments": [
     *     {
     *       "category_id": 1,
     *       "workout_id":  10,
     *       "custom_sets": [
     *         { "set": 1, "reps": 12, "lbs": 45, "kg": 20.4, "rest": 30, "duration": 60 }
     *       ]
     *     }
     *   ]
     * }
     *
     * Creates: (clients × dates × workouts) rows sharing a single batch_id.
     */

    // json data to be passed in the performance data
    //       {
    //     "set": 1,
    //     "actual_reps": 12,
    //     "actual_kg": 55.0,
    //     "actual_duration": 45,
    //     "rpe": 7,
    //     "status": "done"
    //   }

    public function assign(Request $request): JsonResponse
    {
        try {
            $trainer = auth('trainer')->user();

            // ── Validation ────────────────────────────────────────────────────
            $validated = $request->validate([
                'client_ids'                        => 'required|array|min:1',
                'client_ids.*'                      => 'required|integer|exists:fb_tbl_client,id',
                'assigned_dates'                    => 'required|array|min:1',
                'assigned_dates.*'                  => 'required|date',
                'due_date'                          => 'nullable|date',
                'notes'                             => 'nullable|string|max:1000',
                'assignments'                       => 'required|array|min:1',
                'assignments.*.category_id'         => 'required|exists:fb_tbl_workout_category,id',
                'assignments.*.workout_id'          => 'required|exists:fb_tbl_workout,id',
                'assignments.*.custom_sets'         => 'nullable|array',
                'assignments.*.custom_sets.*.set'      => 'nullable|integer|min:1',
                'assignments.*.custom_sets.*.reps'     => 'nullable|integer|min:0',
                'assignments.*.custom_sets.*.lbs'      => 'nullable|numeric|min:0',
                'assignments.*.custom_sets.*.kg'       => 'nullable|numeric|min:0',
                'assignments.*.custom_sets.*.rest'     => 'nullable|integer|min:0',
                'assignments.*.custom_sets.*.duration' => 'nullable|integer|min:0',
            ]);

            // ── Verify ALL clients belong to this trainer ──────────────────────
            $trainerClientIds = $trainer->clients()
                ->wherePivot('status', Trainer::CLIENT_ACTIVE)
                ->pluck('fb_tbl_client.id')
                ->toArray();

            $invalidClients = array_diff($validated['client_ids'], $trainerClientIds);
            if (!empty($invalidClients)) {
                return response()->json([
                    'status'             => false,
                    'message'            => 'One or more clients are not assigned to you.',
                    'invalid_client_ids' => array_values($invalidClients),
                ], 403);
            }

            // ── Verify every (client, date) pair has a session booked ──────────
            //
            // Strategy (1 DB query, O(1) lookups in PHP memory):
            //   1. Pull all sessions this trainer has with these clients on these dates.
            //   2. Build a Set of "clientId_YYYY-MM-DD" strings.
            //   3. Walk every combination and collect ALL missing pairs.
            //   4. Return the full list so the caller fixes everything at once.
            //
            $clientIds     = $validated['client_ids'];
            $assignedDates = array_map(
                fn($d) => \Carbon\Carbon::parse($d)->format('Y-m-d'),
                $validated['assigned_dates']
            );

            // Single query — fetch only client_id + session_date
            $existingSessions = Session::where('trainer_id', $trainer->id)
                ->whereIn('client_id', $clientIds)
                ->whereIn('session_date', $assignedDates)
                ->where('status', Session::STATUS_SCHEDULED)   // only active/scheduled sessions count
                ->get(['client_id', 'session_date']);

            // Build a hash-set: "12_2026-03-06" => true
            $sessionSet = $existingSessions
                ->mapWithKeys(fn($s) => [
                    $s->client_id . '_' . \Carbon\Carbon::parse($s->session_date)->format('Y-m-d') => true
                ])
                ->all();

            // Collect every missing (client, date) combination
            $missingPairs = [];
            foreach ($clientIds as $clientId) {
                foreach ($assignedDates as $date) {
                    $key = $clientId . '_' . $date;
                    if (!isset($sessionSet[$key])) {
                        $missingPairs[] = [
                            'client_id' => $clientId,
                            'date'      => $date,
                        ];
                    }
                }
            }

            if (!empty($missingPairs)) {
                return response()->json([
                    'status'        => false,
                    'message'       => 'Workout can only be assigned on dates when the client has a scheduled session. '
                                     . 'The following client-date pairs have no session booked.',
                    'missing_pairs' => $missingPairs,
                ], 422);
            }

            // ── Build rows inside a transaction ───────────────────────────────
            $batchId = (string) \Illuminate\Support\Str::uuid();
            $createdCount = 0;
            $createdAssignments = [];

            \Illuminate\Support\Facades\DB::transaction(function () use (
                $validated, $trainer, $batchId, &$createdCount, &$createdAssignments
            ) {
                foreach ($validated['client_ids'] as $clientId) {
                    foreach ($validated['assigned_dates'] as $assignedDate) {
                        foreach ($validated['assignments'] as $item) {
                            // ── Auto-calculate total duration from this workout's custom_sets ──
                            $totalDuration = $this->calculateDuration($item['custom_sets'] ?? []);

                            $row = WorkoutAssignment::create([
                                'trainer_id'       => $trainer->id,
                                'assigned_by_id'   => $trainer->id,
                                'assigned_by_type' => Trainer::class,
                                'batch_id'         => $batchId,
                                'client_id'        => $clientId,
                                'category_id'      => $item['category_id'],
                                'workout_id'       => $item['workout_id'],
                                'custom_sets'      => $item['custom_sets'] ?? null,
                                'duration'         => $totalDuration,
                                'assigned_date'    => $assignedDate,
                                'due_date'         => $validated['due_date'] ?? null,
                                'notes'            => $validated['notes'] ?? null,
                                'status'           => WorkoutAssignment::STATUS_PENDING,
                            ]);
                            $createdAssignments[] = $row->id;
                            $createdCount++;
                        }
                    }
                }
            });

            return response()->json([
                'status'   => true,
                'message'  => "Successfully assigned {$createdCount} workout(s) across "
                              . count($validated['client_ids']) . " client(s) and "
                              . count($validated['assigned_dates']) . " date(s).",
                'data'     => [
                    'batch_id'          => $batchId,
                    'total_assignments' => $createdCount,
                    'assignment_ids'    => $createdAssignments,
                ],
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
                'trace'      => $e->getTraceAsString(),
            ]);
            return response()->json([
                'status'  => false,
                'message' => 'Something went wrong. Please try again.',
            ], 500);
        }
    }

    /**
     * PATCH /api/v1/mobile/trainer/workouts/assignments/{id}
     *
     * Update a single assignment. Supports enriched custom_sets with
     * set/reps/lbs/kg/rest/duration per set and date changes.
     *
     * Guards:
     *   - Trainer must own the assignment
     *   - Blocked if status = STATUS_COMPLETED
     */
    public function updateAssignment(Request $request, $id): JsonResponse
    {
        try {
            $trainer    = auth('trainer')->user();
            $assignment = WorkoutAssignment::findOrFail($id);

            // ── Ownership check ───────────────────────────────────────────────
            if ((int)$assignment->trainer_id !== (int)$trainer->id) {
                return response()->json([
                    'status'  => false,
                    'message' => 'Unauthorized. This assignment does not belong to you.',
                ], 403);
            }

            // ── Block completed assignments ────────────────────────────────────
            if ($assignment->status === WorkoutAssignment::STATUS_COMPLETED) {
                return response()->json([
                    'status'  => false,
                    'message' => 'Cannot update a completed workout assignment.',
                ], 400);
            }

            // ── Validation ────────────────────────────────────────────────────
            $validated = $request->validate([
                'category_id'              => 'nullable|exists:fb_tbl_workout_category,id',
                'workout_id'               => 'nullable|exists:fb_tbl_workout,id',
                'assigned_date'            => 'nullable|date',
                'due_date'                 => 'nullable|date',
                'notes'                    => 'nullable|string|max:1000',
                'status'                   => 'nullable|integer|in:0,1,2,3',
                'custom_sets'              => 'nullable|array',
                'custom_sets.*.set'        => 'nullable|integer|min:1',
                'custom_sets.*.reps'       => 'nullable|integer|min:0',
                'custom_sets.*.lbs'        => 'nullable|numeric|min:0',
                'custom_sets.*.kg'         => 'nullable|numeric|min:0',
                'custom_sets.*.rest'       => 'nullable|integer|min:0',
                'custom_sets.*.duration'   => 'nullable|integer|min:0',
            ]);

            // ── Session-date check when assigned_date is being changed ─────────
            // Only run if a new date is provided AND it differs from the current date.
            if (!empty($validated['assigned_date'])) {
                $newDate   = \Carbon\Carbon::parse($validated['assigned_date'])->format('Y-m-d');
                $oldDate   = \Carbon\Carbon::parse($assignment->assigned_date)->format('Y-m-d');

                if ($newDate !== $oldDate) {
                    // One query: does the client have a scheduled session with this trainer on the new date?
                    $sessionExists = Session::where('trainer_id', $trainer->id)
                        ->where('client_id', $assignment->client_id)
                        ->where('session_date', $newDate)
                        ->where('status', Session::STATUS_SCHEDULED)
                        ->exists();

                    if (!$sessionExists) {
                        return response()->json([
                            'status'  => false,
                            'message' => 'Cannot reschedule: no session booked for this client on ' . $newDate . '. '
                                        . 'Please book a session on that date first.',
                            'client_id'     => $assignment->client_id,
                            'requested_date' => $newDate,
                        ], 422);
                    }
                }
            }

            // ── Re-calculate duration if custom_sets are being updated ─────────
            $updatePayload = array_filter($validated, fn($v) => $v !== null);
            if (isset($validated['custom_sets'])) {
                $updatePayload['duration'] = $this->calculateDuration($validated['custom_sets']);
            }

            $assignment->update($updatePayload);

            return response()->json([
                'status'  => true,
                'message' => 'Workout assignment updated successfully.',
                'data'    => $assignment->fresh(),
            ], 200);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'status'  => false,
                'message' => 'Assignment not found.',
            ], 404);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'status'  => false,
                'message' => 'Validation failed',
                'errors'  => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            Log::error('Workout assignment update failed', [
                'assignment_id' => $id,
                'trainer_id'    => auth('trainer')->id(),
                'error'         => $e->getMessage(),
            ]);
            return response()->json([
                'status'  => false,
                'message' => 'Update failed. Please try again.',
            ], 500);
        }
    }

    /**
     * DELETE /api/v1/mobile/trainer/workouts/assignments/{id}
     * Remove a single workout assignment.
     */
    public function removeAssignment($id): JsonResponse
    {
        try {
            $trainer    = auth('trainer')->user();
            $assignment = WorkoutAssignment::findOrFail($id);

            // ── Ownership check ───────────────────────────────────────────────
            if ((int)$assignment->trainer_id !== (int)$trainer->id) {
                return response()->json([
                    'status'  => false,
                    'message' => 'Unauthorized. This assignment does not belong to you.',
                ], 403);
            }

            // ── Active pivot check ────────────────────────────────────────────
            // Confirm the client is still actively enrolled under this trainer.
            $isActiveClient = $trainer->clients()
                ->where('fb_tbl_client.id', $assignment->client_id)
                ->wherePivot('status', Trainer::CLIENT_ACTIVE)
                ->exists();

            if (!$isActiveClient) {
                return response()->json([
                    'status'  => false,
                    'message' => 'Cannot modify assignments for a client that is no longer active under you.',
                ], 403);
            }

            $assignment->delete();

            return response()->json([
                'status'  => true,
                'message' => 'Workout assignment removed successfully.',
            ], 200);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'status'  => false,
                'message' => 'Assignment not found.',
            ], 404);
        } catch (\Exception $e) {
            Log::error('Workout assignment delete failed', [
                'assignment_id' => $id,
                'trainer_id'    => auth('trainer')->id(),
                'error'         => $e->getMessage(),
            ]);
            return response()->json([
                'status'  => false,
                'message' => 'Deletion failed. Please try again.',
            ], 500);
        }
    }

    /**
     * DELETE /api/v1/mobile/trainer/workouts/batch/{batch_id}
     * Remove ALL assignments belonging to a batch (entire session).
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
                'message' => "Successfully removed {$deletedCount} workout assignment(s) from the session.",
                'data'    => ['deleted_count' => $deletedCount],
            ], 200);

        } catch (\Exception $e) {
            Log::error('Batch assignment delete failed', [
                'batch_id'   => $batchId,
                'trainer_id' => auth('trainer')->id(),
                'error'      => $e->getMessage(),
            ]);
            return response()->json([
                'status'  => false,
                'message' => 'Batch deletion failed. Please try again.',
            ], 500);
        }
    }

    // ─── Private Helpers ─────────────────────────────────────────────────────

    /**
     * Sum the `duration` field (seconds) across all sets in a custom_sets array.
     *
     * Each item looks like: { set, reps, lbs, kg, rest, duration }
     * Returns 0 when custom_sets is empty or no duration values are provided.
     *
     * @param  array $customSets
     * @return int   Total duration in seconds
     */
    private function calculateDuration(array $customSets): int
    {
        return (int) collect($customSets)->sum(fn($set) => (int) ($set['duration'] ?? 0));
    }
}
