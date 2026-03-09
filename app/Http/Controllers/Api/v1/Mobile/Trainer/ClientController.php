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
                        $q->latest();
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
            // Get the EARLIER recorded metrics to show ↑ or ↓
            $lastMetrics = $client->dailyMetrics()
                ->where('log_date', '<', $today)
                ->orderBy('log_date', 'desc')
                ->get();
            
            $compareMetric = $lastMetrics->first();

            // 3. Assignments (Today & Yesterday)
            $workoutToday = WorkoutAssignment::where('client_id', $client->id)
                ->whereDate('assigned_date', $today)
                ->with('workout.category')
                ->get()
                ->unique('category_id');

            $dietToday = DietPlanAssignment::where('client_id', $client->id)
                ->whereDate('assigned_date', $today)
                ->with('dietPlan.category')
                ->get()
                ->unique(function ($item) {
                    return $item->dietPlan->category_id ?? $item->id;
                });

            $workoutYesterday = WorkoutAssignment::where('client_id', $client->id)
                ->whereDate('assigned_date', $yesterday)
                ->with('workout.category')
                ->get()
                ->unique('category_id');

            $dietYesterday = DietPlanAssignment::where('client_id', $client->id)
                ->whereDate('assigned_date', $yesterday)
                ->with('dietPlan.category')
                ->get()
                ->unique(function ($item) {
                    return $item->dietPlan->category_id ?? $item->id;
                });

            // 4. Photos (Today or latest)
            $latestPhotos = $client->progressPhotos->isNotEmpty() 
                ? $client->progressPhotos 
                : $client->progressPhotos()->latest()->limit(1)->get();

            // 5. Trend Math Helper
            $calcTrend = function ($current, $previous) {
                if ($current === null || $previous === null) return ['value' => 0, 'direction' => 'none', 'display' => '0'];
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
                        'goal' => $client->goal ?? 'Not Set',
                        'vitals' => [
                            'age' => $client->dob ? Carbon::parse($client->dob)->age : 25,
                            'weight' => ($client->weight ?? 0) . ' kg', 
                            'height' => ($client->height ?? 0) . ' cm',
                        ]
                    ],
                    'today_progress' => [
                        'water' => [
                            'value' => ($todayWater->total_consumed_ml ?? 0) / 1000 . 'L',
                            'goal'  => ($todayWater->water_goal_ml ?? 3000) / 1000 . 'L',
                            'percentage' => $todayWater && $todayWater->water_goal_ml > 0 
                                ? ($todayWater->total_consumed_ml / $todayWater->water_goal_ml) * 100 
                                : 0,
                            'updated_at' => $todayWater ? $todayWater->updated_at->format('Y M d, g.i a') : null,
                        ],
                        'steps' => [
                            'value' => (int)($todayMetrics->steps ?? 0),
                            'goal'  => (int)($client->steps_goal ?? 10000), 
                            'updated_at' => $todayMetrics ? $todayMetrics->updated_at->format('Y M d, g.i a') : null,
                        ],
                        'fat' => [
                            'value' => ($todayMetrics->fat_percent ?? 0) . '%',
                            'goal'  => ($client->fat_goal ?? 20) . '%', 
                            'updated_at' => $todayMetrics ? $todayMetrics->updated_at->format('Y M d, g.i a') : null,
                        ],
                        'bmi' => [
                            'value' => $todayMetrics->bmi ?? 0,
                            'goal'  => $client->bmi_goal ?? 22.1,
                            'updated_at' => $todayMetrics ? $todayMetrics->updated_at->format('Y M d, g.i a') : null,
                        ],
                        'ideal_weight' => [
                            'value' => ($todayMetrics->ideal_weight ?? 0) . ' kg',
                            'goal'  => ($client->ideal_weight_goal ?? 70) . ' kg', 
                            'updated_at' => $todayMetrics ? $todayMetrics->updated_at->format('Y M d, g.i a') : null,
                        ],
                        'bmr' => [
                            'value' => ($todayMetrics->bmr ?? 0),
                            'goal'  => $client->bmr_goal ?? 1400,
                            'updated_at' => $todayMetrics ? $todayMetrics->updated_at->format('Y M d, g.i a') : null,
                        ],
                        'calories' => [
                            'value' => ($todayMetrics->calories_consumed ?? 0) . ' kcal',
                            'goal'  => ($client->calories_goal ?? 1800) . ' kcal',
                            'updated_at' => $todayMetrics ? $todayMetrics->updated_at->format('Y M d, g.i a') : null,
                        ],
                        'last_goal_update' => $client->goals_updated_at ? $client->goals_updated_at->format('Y M d, g.i a') : null,
                    ],
                    'assignments' => [
                        'workouts' => $workoutToday->map(function($w) {
                            return [
                                'id' => $w->id,
                                'name' => $w->workout->category->name ?? 'Exercise',
                                'image' => $w->workout->category->image ?? null,
                                'duration' => ($w->duration ?? 0) / 60 . ' mins',
                                'is_completed' => $w->is_completed
                            ];
                        }),
                        'diets' => $dietToday->map(function($d) {
                            return [
                                'id' => $d->id,
                                'name' => $d->dietPlan->category->name ?? 'Meal Plan',
                                'image' => $d->dietPlan->category->image ?? null,
                                'is_completed' => $d->is_completed
                            ];
                        }),
                    ],
                    'yesterday_overview' => [
                        'workouts' => $workoutYesterday->map(function($w) {
                            return [
                                'id' => $w->id,
                                'name' => $w->workout->category->name ?? 'Exercise',
                                'image' => $w->workout->category->image ?? null,
                                'duration' => ($w->duration ?? 0) . ' mins',
                                'is_completed' => $w->is_completed,
                            ];
                        }),
                        'diets' => $dietYesterday->map(function($d) {
                            return [
                                'id' => $d->id,
                                'name' => $d->dietPlan->name ?? 'Meal Plan',
                                'image' => $d->dietPlan->image ?? null,
                                'duration' => ($d->duration ?? 0) . ' mins',
                                'is_completed' => $d->is_completed,
                            ];
                        }),
                    ],
                    'metrics' => [
                        'weight' => [
                            'current' => ($todayMetrics->weight_kg ?? $client->weight ?? 0) . ' kg',
                            'trend'   => $calcTrend(
                                $todayMetrics->weight_kg ?? $client->weight ?? 0, 
                                $compareMetric->weight_kg ?? $client->weight ?? 0
                            )
                        ],
                        'bmi' => [
                            'current' => $todayMetrics->bmi ?? 0,
                            'trend'   => $calcTrend($todayMetrics->bmi ?? 0, $compareMetric->bmi ?? 0)
                        ],
                        'bfi' => [
                            'current' => ($todayMetrics->fat_percent ?? 0) . '%',
                            'trend'   => $calcTrend($todayMetrics->fat_percent ?? 0, $compareMetric->fat_percent ?? 0)
                        ]
                    ],
                    'body_measurements' => [
                        'chest' => ($todayMetrics->chest_cm ?? 0) . ' cm',
                        'waist' => ($todayMetrics->waist_cm ?? 0) . ' cm',
                        'neck'  => ($todayMetrics->neck_cm ?? 0) . ' cm',
                    ],
                    'progress_photos' => $latestPhotos->map(function($photo) {
                        return [
                            'date' => $photo->log_date ? $photo->log_date->format('M d, Y') : 'N/A',
                            'front_view' => $photo->front_view ?? null,
                            'side_view'  => $photo->side_view ?? null,
                            'back_view'  => $photo->back_view ?? null,
                        ];
                    }),
                    'client_status' => [
                        'health' => 'None reported', 
                        'diet_preference' => 'Non-Veg', 
                    ],
                    'medical_reports' => $client->medicalReports->map(function ($report) {
                        return [
                            'id' => $report->id,
                            'name' => $report->name,
                            'date' => $report->report_date ? $report->report_date->format('d M Y') : 'N/A',
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
                'trace'      => $e->getTraceAsString(),
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

            // ✅ Multi-date filtering (Intersection Logic)
            $uniqueDateCount = 0;
            if ($request->filled('dates')) {
                $dates = is_array($request->dates) ? $request->dates : explode(',', $request->dates);
                $dates = array_unique(array_filter($dates));
                $uniqueDateCount = count($dates);
                $query->whereIn('session_date', $dates);
            }

            // ✅ Unique Client Logic: Get only the earliest session ID for each client
            $subQuery = clone $query;
            
            // We use selectRaw to specifically pick the MIN(id) and clear any other selects
            $firstSessionIds = $subQuery->selectRaw('MIN(fb_tbl_session.id)')
                ->groupBy('client_id');

            // If multiple dates are requested, ensure client has sessions on ALL of them
            if ($uniqueDateCount > 1) {
                $firstSessionIds->havingRaw('COUNT(DISTINCT session_date) >= ?', [$uniqueDateCount]);
            }

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
                    'is_workout_assigned' => $session->is_workout_assigned,
                    'session_id'  => $session->id,
                    'client_id'   => $client->id,
                    'name'        => $client->full_name,
                    'profile_pic' => $client->profile_pic,
                    'subtitle'    => $client->goal ?? 'Fitness Session',
                    'date'        => Carbon::parse($session->session_date)->format('d M Y'),
                    'time_display'=> Carbon::parse($session->start_time)->format('g:i A') . ' To ' . Carbon::parse($session->end_time)->format('g:i A'),
                    'location'    => $session->locationDetail->name ?? $client->city->name ?? $client->zone->name ?? 'Remote',
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
