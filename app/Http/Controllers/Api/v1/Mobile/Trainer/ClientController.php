<?php

namespace App\Http\Controllers\Api\v1\Mobile\Trainer;

use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Models\Session;
use App\Models\Trainer;
use App\Models\WorkoutAssignment;
use App\Models\DietPlanAssignment;
use App\Models\ClientDailyMetric;
use App\Models\ClientProgressPhoto;
use App\Models\ClientMedicalReport;
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
     * GET /api/v1/mobile/trainer/clients/{id}/details
     * Comprehensive "Client Dashboard" for trainers.
     */
    public function details($id): JsonResponse
    {
        try {
            $trainer = auth('trainer')->user();
            $today = Carbon::today();
            $yesterday = Carbon::yesterday();

            $client = $trainer->clients()
                ->where('fb_tbl_client.id', $id)
                ->with([
                    'dailyMetrics' => function ($q) use ($today) {
                        $q->where('log_date', $today);
                    },
                    'progressPhotos' => function ($q) use ($today) {
                        $q->where('log_date', $today);
                    },
                    'medicalReports' => function ($q) {
                        $q->latest()->limit(5);
                    },
                    'waterDailyLogs' => function ($q) use ($today) {
                        $q->where('log_date', $today);
                    }
                ])
                ->first();

            if (!$client) {
                return response()->json(['status' => false, 'message' => 'Client not found.'], 404);
            }

            // 1. Current Progress (Today)
            $todayMetrics = $client->dailyMetrics->first();
            $todayWater = $client->waterDailyLogs->first();
            
            // 2. Performance Comparison (Trends)
            // Get the EARLIER recorded metric to show ↑ or ↓
            $lastMetrics = $client->dailyMetrics()
                ->where('log_date', '<', $today)
                ->orderBy('log_date', 'desc')
                ->first();

            // 3. Assignments (Today & Yesterday)
            $workoutToday = WorkoutAssignment::where('client_id', $client->id)
                ->whereDate('assigned_date', $today)
                ->with('workout.category')
                ->first();

            $dietToday = DietPlanAssignment::where('client_id', $client->id)
                ->whereDate('assigned_date', $today)
                ->with('dietPlan.category')
                ->first();

            $workoutYesterday = WorkoutAssignment::where('client_id', $client->id)
                ->whereDate('assigned_date', $yesterday)
                ->with('workout.category')
                ->first();

            $dietYesterday = DietPlanAssignment::where('client_id', $client->id)
                ->whereDate('assigned_date', $yesterday)
                ->with('dietPlan.category')
                ->first();

            // 4. Photos (Today or latest)
            $latestPhotos = $client->progressPhotos->first() ?? $client->progressPhotos()->latest()->first();

            // 5. Trend Math Helper
            $calcTrend = function ($current, $previous) {
                if (!$current || !$previous) return ['value' => 0, 'direction' => 'none'];
                $diff = $current - $previous;
                return [
                    'value' => abs(round($diff, 1)),
                    'direction' => $diff > 0 ? 'up' : ($diff < 0 ? 'down' : 'none'),
                    'display' => ($diff > 0 ? '+' : '') . round($diff, 1)
                ];
            };

            return response()->json([
                'status'  => true,
                'message' => 'Client metrics fetched successfully',
                'data'    => [
                    'header' => [
                        'id' => $client->id,
                        'name' => $client->full_name,
                        'profile_pic' => $client->profile_pic,
                        'goal' => $client->goal ?? 'Weight Loss & Toning',
                        'vitals' => [
                            'age' => $client->dob ? Carbon::parse($client->dob)->age : 25,
                            'weight' => $client->weight ?? 0 . ' kg', 
                            'height' => $client->height ?? 0 . ' cm',
                        ]
                    ],
                    'today_progress' => [
                        'water' => [
                            'value' => ($todayWater->total_consumed_ml ?? 0) / 1000 . 'L',
                            'goal'  => ($todayWater->water_goal_ml ?? 3000) / 1000 . 'L',
                            'percentage' => $todayWater ? ($todayWater->total_consumed_ml / $todayWater->water_goal_ml) * 100 : 0
                        ],
                        'steps' => [
                            'value' => $todayMetrics->steps ?? 0,
                            'goal'  => 10000, // Static goal for now or from settings
                        ]
                    ],
                    'assignments' => [
                        'workout' => $workoutToday ? [
                            'id' => $workoutToday->id,
                            'name' => $workoutToday->workout->name ?? 'Exercise',
                            'image' => $workoutToday->workout->category->image ?? null,
                            'duration' => ($workoutToday->duration ?? 0) / 60 . ' mins',
                            'is_completed' => $workoutToday->is_completed
                        ] : null,
                        'diet' => $dietToday ? [
                            'id' => $dietToday->id,
                            'name' => $dietToday->dietPlan->name ?? 'Meal Plan',
                            'image' => $dietToday->dietPlan->category->image ?? null,
                            'is_completed' => $dietToday->is_completed
                        ] : null,
                    ],
                    'yesterday_overview' => [
                        'workout' => $workoutYesterday ? [
                            'name' => $workoutYesterday->workout->name ?? 'Exercise',
                            'is_completed' => $workoutYesterday->is_completed,
                         ] : null,
                        'diet' => $dietYesterday ? [
                            'name' => $dietYesterday->dietPlan->name ?? 'Meal Plan',
                            'is_completed' => $dietYesterday->is_completed,
                        ] : null,
                    ],
                    'metrics' => [
                        'weight' => [
                            'current' => ($todayMetrics->weight_kg ?? $client->weight ?? 0) . ' kg',
                            'trend'   => $calcTrend($todayMetrics->weight_kg ?? $client->weight ?? 0, $lastMetrics->weight_kg ?? $client->weight ?? 0)
                        ],
                        'bmi' => [
                            'current' => $todayMetrics->bmi ?? 0,
                            'trend'   => $calcTrend($todayMetrics->bmi ?? 0, $lastMetrics->bmi ?? 0)
                        ],
                        'bfi' => [
                            'current' => ($todayMetrics->fat_percent ?? 0) . '%',
                            'trend'   => $calcTrend($todayMetrics->fat_percent ?? 0, $lastMetrics->fat_percent ?? 0)
                        ]
                    ],
                    'body_measurements' => [
                        'chest' => ($todayMetrics->chest_cm ?? 0) . ' cm',
                        'waist' => ($todayMetrics->waist_cm ?? 0) . ' cm',
                        'neck'  => ($todayMetrics->neck_cm ?? 0) . ' cm',
                    ],
                    'progress_photos' => [
                        'date' => $latestPhotos ? $latestPhotos->log_date->format('M d, Y') : 'No photos yet',
                        'front_view' => $latestPhotos->front_view ?? null,
                        'side_view'  => $latestPhotos->side_view ?? null,
                    ],
                    'client_status' => [
                        'health' => 'None reported', // Link to health profile/notes
                        'diet_preference' => 'Non-Veg', 
                    ],
                    'medical_reports' => $client->medicalReports->map(function ($report) {
                        return [
                            'id' => $report->id,
                            'name' => $report->name,
                            'date' => $report->report_date->format('d M Y'),
                            'file' => $report->file_path
                        ];
                    })
                ]
            ], 200);
        } catch (\Exception $e) {
            Log::error('Client detail dashboard failed', [
                'trainer_id' => auth('trainer')->id(),
                'client_id'  => $id,
                'error'      => $e->getMessage(),
            ]);

            return response()->json(['status' => false, 'message' => 'Something went wrong.'], 500);
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

            // Base query for sessions that are SCHEDULED (including past ones not yet completed)
            $query = $trainer->sessions()
                ->with([
                    'client:id,first_name,last_name,profile_pic,goal,city_id,zone_id',
                    'client.city',
                    'client.zone',
                ])
                ->where('status', Session::STATUS_SCHEDULED);

            // ✅ Search functionality (filters by all major fields)
            if ($request->has('search') && $request->search !== '') {
                $search = str_replace(['%', '_'], ['\\%', '\\_'], $request->search);
                $query->where(function ($q) use ($search) {
                    $q->whereHas('client', function ($cq) use ($search) {
                        $cq->where('first_name', 'LIKE', "%{$search}%")
                          ->orWhere('last_name', 'LIKE', "%{$search}%")
                          ->orWhere('goal', 'LIKE', "%{$search}%");
                    })
                    ->orWhere('location', 'LIKE', "%{$search}%")
                    ->orWhere('session_date', 'LIKE', "%{$search}%");
                });
            }

            // ✅ Multi-date filtering
            if ($request->filled('dates')) {
                $dates = is_array($request->dates) ? $request->dates : explode(',', $request->dates);
                $query->whereIn('session_date', $dates);
            }

            // ✅ Unique Client Logic: Get only the earliest session ID for each client matching the criteria
            // We use a subquery to find the MIN(id) grouped by client_id for rows that match our current filters
            $subQuery = clone $query;
            $firstSessionIds = $subQuery->selectRaw('MIN(fb_tbl_session.id)')
                ->groupBy('client_id');

            // Final query restricted to those first session IDs
            $sessions = $trainer->sessions()
                ->with([
                    'client:id,first_name,last_name,profile_pic,goal,city_id,zone_id',
                    'client.city',
                    'client.zone',
                ])
                ->whereIn('fb_tbl_session.id', $firstSessionIds)
                ->orderBy('session_date', 'asc')
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
