<?php

namespace App\Http\Controllers\Api\v1\Mobile\Trainer;

use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Models\Session;
use App\Models\Trainer;
use App\Models\WorkoutAssignment;
use App\Models\DietPlanAssignment;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ClientController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | 👥 CLIENT MANAGEMENT APIs
    |--------------------------------------------------------------------------
    */

    /**
     * GET /api/v1/mobile/trainer/clients
     * List all clients assigned to the trainer
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $trainer = auth('trainer')->user();

            $query = $trainer->clients();

            // Filter by status
            if ($request->has('status')) {
                $query->wherePivot('status', $request->status);
            }

            // ✅ FIX: Sanitize LIKE wildcards in search input
            if ($request->has('search') && $request->search !== '') {
                $search = str_replace(['%', '_'], ['\\%', '\\_'], $request->search);
                $query->where(function ($q) use ($search) {
                    $q->where('first_name', 'LIKE', "%{$search}%")
                      ->orWhere('last_name', 'LIKE', "%{$search}%");
                });
            }

            $clients = $query->paginate($request->get('per_page', 20));

            return response()->json([
                'status'  => true,
                'message' => 'Clients fetched successfully',
                'data'    => $clients,
            ], 200);
        } catch (\Exception $e) {
            Log::error('Client list failed', [
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
     * GET /api/v1/mobile/trainer/clients/{id}
     * Get detailed client info
     */
    public function show($id): JsonResponse
    {
        try {
            $trainer = auth('trainer')->user();

            $client = $trainer->clients()->where('fb_tbl_client.id', $id)->first();

            if (!$client) {
                return response()->json([
                    'status'  => false,
                    'message' => 'Client not found or not assigned to you',
                ], 404);
            }

            // Enrich with assignments
            $client->load([
                'workoutAssignments' => function ($q) {
                    $q->with('workout.category')->latest()->limit(10);
                },
                'dietPlanAssignments' => function ($q) {
                    $q->with('dietPlan.category')->latest()->limit(10);
                },
            ]);

            return response()->json([
                'status'  => true,
                'message' => 'Client details fetched successfully',
                'data'    => [
                    'client' => $client,
                    'stats'  => [
                        'goal'   => $client->goal,
                        'age'    => $client->dob ? Carbon::parse($client->dob)->age : null,
                        'weight' => $client->weight,
                        'height' => $client->height,
                    ],
                ],
            ], 200);
        } catch (\Exception $e) {
            Log::error('Client detail failed', [
                'trainer_id' => auth('trainer')->id(),
                'client_id'  => $id,
                'error'      => $e->getMessage(),
            ]);

            return response()->json([
                'status'  => false,
                'message' => 'Something went wrong. Please try again.',
            ], 500);
        }
    }

    /**
     * GET /api/v1/mobile/trainer/clients/today
     * Get today's clients with session details
     */
    public function today(): JsonResponse
    {
        try {
            $trainer = auth('trainer')->user();
            $today = Carbon::today();

            // ✅ FIX: Eager load all nested relationships (N+1 fix)
            $todaySessions = $trainer->sessions()
                ->with([
                    'client:id,first_name,last_name,profile_pic,goal',
                    'client.workoutAssignments' => function ($q) {
                        $q->where('status', '!=', WorkoutAssignment::STATUS_COMPLETED)
                          ->with('workout.category:id,name');
                    },
                    'client.dietPlanAssignments' => function ($q) {
                        $q->where('status', '!=', DietPlanAssignment::STATUS_COMPLETED)
                          ->with('dietPlan.category:id,name');
                    },
                ])
                ->where('session_date', $today)
                ->where('status', Session::STATUS_SCHEDULED)
                ->orderBy('start_time', 'asc')
                ->get()
                ->map(function ($session) {
                    $client = $session->client;

                    // Tags already eager loaded — no extra queries
                    $workoutTags = $client->workoutAssignments
                        ->pluck('workout.category.name')
                        ->filter()
                        ->unique()
                        ->values();

                    $dietTags = $client->dietPlanAssignments
                        ->pluck('dietPlan.category.name')
                        ->filter()
                        ->unique()
                        ->values();

                    return [
                        'session_id'   => $session->id,
                        'client_id'    => $client->id,
                        'client_name'  => $client->full_name,
                        'profile_pic'  => $client->profile_pic,
                        'start_time'   => Carbon::parse($session->start_time)->format('g:i A'),
                        'end_time'     => Carbon::parse($session->end_time)->format('g:i A'),
                        'location'     => $session->location,
                        'status'       => $session->status,
                        'workout_tags' => $workoutTags,
                        'diet_tags'    => $dietTags,
                    ];
                });

            return response()->json([
                'status'  => true,
                'message' => 'Today\'s clients fetched successfully',
                'data'    => $todaySessions,
            ], 200);
        } catch (\Exception $e) {
            Log::error('Today clients failed', [
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
