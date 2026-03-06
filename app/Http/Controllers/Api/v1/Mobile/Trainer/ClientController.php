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

            // ── Always scope to active enrollments by default ──────────────
            // A trainer should only see clients that are status=1 (active) in
            // fb_tbl_trainer_client. Inactive/completed clients are hidden unless
            // the caller explicitly passes ?status=0 or ?status=2.
            $query = $trainer->clients()
                ->wherePivot('status', Trainer::CLIENT_ACTIVE)
                ->with([
                    'currentSubscription.plan.features.feature',
                    'currentSubscription.usages'
                ]);

            // Allow admin-style override: ?status=0 (inactive) or ?status=2 (completed)
            if ($request->filled('status')) {
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
                'data'    => \App\Http\Resources\Api\v1\Trainer\ClientResource::collection($clients)->response()->getData(true),
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

            $client = $trainer->clients()
                ->wherePivot('status', Trainer::CLIENT_ACTIVE)
                ->with([
                    'currentSubscription.plan.features.feature',
                    'currentSubscription.usages'
                ])
                ->where('fb_tbl_client.id', $id)
                ->first();

            if (!$client) {
                return response()->json([
                    'status'  => false,
                    'message' => 'Client not found or not assigned to you',
                ], 404);
            }

            // Enrich with assignments
            $client->load([
                'workoutAssignments' => function ($q) {
                    $q->with('workout.category.workoutCategoryType')->latest()->limit(10);
                },
                'dietPlanAssignments' => function ($q) {
                    $q->with('dietPlan.category')->latest()->limit(10);
                },
            ]);

            return response()->json([
                'status'  => true,
                'message' => 'Client details fetched successfully',
                'data'    => [
                    'client' => new \App\Http\Resources\Api\v1\Trainer\ClientResource($client),
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
                    'client:id,first_name,last_name,profile_pic,goal,city_id,state_id,zone_id',
                    'client.workoutAssignments' => function ($q) {
                        $q->where('status', '!=', WorkoutAssignment::STATUS_COMPLETED)
                          ->with('workout.category.workoutCategoryType');
                    },
                    'client.dietPlanAssignments' => function ($q) {
                        $q->where('status', '!=', DietPlanAssignment::STATUS_COMPLETED)
                          ->with('dietPlan.category:id,name');
                    },
                    'client.currentSubscription.plan',
                    'client.city',
                    'client.zone',
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

                    // Format Subscription Data manually for this map
                    $subscriptionData = null;
                    if ($client->currentSubscription && $client->currentSubscription->status === 'active') {
                        $subscriptionData = [
                            'plan_slug' => $client->currentSubscription->plan->slug ?? 'unknown',
                            'status' => $client->currentSubscription->status
                        ];
                    }

                    return [
                        'session_id'   => $session->id,
                        'client_id'    => $client->id,
                        'client_name'  => $client->full_name,
                        'profile_pic'  => $client->profile_pic,
                        'start_time'   => Carbon::parse($session->start_time)->format('g:i A'),
                        'end_time'     => Carbon::parse($session->end_time)->format('g:i A'),
                        'location'     => $session->location,
                        'city'         => $client->city->name ?? null,
                        'zone'         => $client->zone->name ?? $client->zone ?? null,
                        'status'       => $session->status,
                        'workout_tags' => $workoutTags,
                        'diet_tags'    => $dietTags,
                        'subscription' => $subscriptionData,
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
    /**
     * GET /api/v1/mobile/trainer/clients/sessions
     * List sessions that are not completed, formatted for the "Select Clients" UI with search
     */
    public function clientSessions(Request $request): JsonResponse
    {
        try {
            $trainer = auth('trainer')->user();

            // Initial query for sessions that are not completed
            $query = $trainer->sessions()
                ->with([
                    'client:id,first_name,last_name,profile_pic,goal,city_id,zone_id',
                    'client.city',
                    'client.zone',
                ])
                ->where('status', '!=', Session::STATUS_COMPLETED);

            // ✅ Search functionality (filters by all major fields)
            if ($request->has('search') && $request->search !== '') {
                $search = str_replace(['%', '_'], ['\\%', '\\_'], $request->search);
                $query->where(function ($q) use ($search) {
                    // Search in Client details (name, goal)
                    $q->whereHas('client', function ($cq) use ($search) {
                        $cq->where('first_name', 'LIKE', "%{$search}%")
                          ->orWhere('last_name', 'LIKE', "%{$search}%")
                          ->orWhere('goal', 'LIKE', "%{$search}%");
                    })
                    // Search in Session details (location, date)
                    ->orWhere('location', 'LIKE', "%{$search}%")
                    ->orWhere('session_date', 'LIKE', "%{$search}%");
                });
            }
            // ✅ Multi-date filtering (supports dates[] array or comma-separated string)
            if ($request->filled('dates')) {
                $dates = is_array($request->dates) 
                    ? $request->dates 
                    : explode(',', $request->dates);
                
                $query->whereIn('session_date', $dates);
            }

            $sessions = $query->orderBy('session_date', 'asc')
                ->orderBy('start_time', 'asc')
                ->paginate($request->get('per_page', 20));

            // Transform data to match UI fields
            $formattedSessions = collect($sessions->items())->map(function ($session) {
                $client = $session->client;
                
                return [
                    'session_id'  => $session->id,
                    'client_id'   => $client->id,
                    'name'        => $client->full_name,
                    'profile_pic' => $client->profile_pic,
                    'subtitle'    => $client->goal ?? 'Fitness Session',
                    'date'        => Carbon::parse($session->session_date)->format('d M Y'),
                    'time_display'=> Carbon::parse($session->start_time)->format('g:i A') . ' To ' . Carbon::parse($session->end_time)->format('g:i A'),
                    'location'    => $session->location ?? $client->zone->name ?? $client->city->name ?? 'Remote',
                ];
            });

            return response()->json([
                'status'  => true,
                'message' => 'Client sessions fetched successfully',
                'data'    => [
                    'list' => $formattedSessions,
                    'meta' => [
                        'current_page' => $sessions->currentPage(),
                        'last_page'    => $sessions->lastPage(),
                        'total'        => $sessions->total(),
                        'per_page'     => $sessions->perPage(),
                    ]
                ],
            ], 200);
        } catch (\Exception $e) {
            Log::error('Client sessions list failed', [
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
