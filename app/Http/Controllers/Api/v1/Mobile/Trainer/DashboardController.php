<?php

namespace App\Http\Controllers\Api\v1\Mobile\Trainer;

use App\Http\Controllers\Controller;
use App\Models\Session;
use App\Models\WorkoutAssignment;
use App\Models\DietPlanAssignment;
use App\Models\Trainer;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

class DashboardController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | 📊 LANDING PAGE / DASHBOARD API
    |--------------------------------------------------------------------------
    | Returns all data needed for the trainer's home screen in a single call.
    | Matches the Figma landing page design.
    */

    /**
     * GET /api/v1/mobile/trainer/dashboard
     *
     * Returns: trainer profile, stats cards, today's clients,
     *          notification count, satisfaction percentage
     */
    public function index(): JsonResponse
    {
        try {
            $trainer = auth('trainer')->user();
            $today = Carbon::today();

            // ── 1. Trainer Profile ──────────────────────────────
            $profile = [
                'id'             => $trainer->id,
                'profile_pic'    => $trainer->profile_pic,
                'first_name'     => $trainer->first_name,
                'last_name'      => $trainer->last_name,
                'full_name'      => $trainer->full_name,
                'specialization' => $trainer->specialization,
                'rating'         => $trainer->getAverageRating(),
                'qr_code'        => $trainer->qr_code,
                'dob'            => $trainer->dob ? Carbon::parse($trainer->dob)->format('d M Y') : null,
                'joining_date'   => $trainer->joining_date ? Carbon::parse($trainer->joining_date)->format('d M Y') : null,
            ];

            // ── 2. Dashboard Stats Cards ────────────────────────
            $assignedClientsCount = $trainer->activeClients()->count();

            $todaySessionsCount = $trainer->sessions()
                ->where('session_date', $today)
                ->where('status', Session::STATUS_SCHEDULED)
                ->count();

            $totalSlots = $trainer->slots()
                ->where('date', $today)
                ->count();

            $bookedSlots = $trainer->sessions()
                ->where('session_date', $today)
                ->whereIn('status', [Session::STATUS_SCHEDULED, Session::STATUS_COMPLETED])
                ->count();

            $slotsAvailable = max($totalSlots - $bookedSlots, 0);

            $statsCards = [
                'assigned_clients' => $assignedClientsCount,
                'todays_sessions'  => $todaySessionsCount,
                'slots_available'  => $slotsAvailable,
            ];

            // ── 3. Today's Clients ──────────────────────────────
            // ✅ FIX: Eager load ALL nested relationships in ONE shot
            //    instead of querying inside the loop (N+1 fix)
            $todayClients = $trainer->sessions()
                ->with([
                    'client:id,first_name,last_name,profile_pic,city_id,state_id,zone_id',
                    'client.workoutAssignments' => function ($q) {
                        $q->where('status', '!=', WorkoutAssignment::STATUS_COMPLETED)
                            ->with('workout.category.workoutCategoryType');
                    },
                    'client.dietPlanAssignments' => function ($q) {
                        $q->where('status', '!=', DietPlanAssignment::STATUS_COMPLETED)
                            ->with('dietPlan.category:id,name');
                    },
                    'client.city',
                    'client.zone',
                ])
                ->where('session_date', $today)
                ->where('status', Session::STATUS_SCHEDULED)
                ->orderBy('start_time', 'asc')
                ->get()
                ->unique('client_id')
                ->take(5)
                ->values()
                ->map(function ($session) {
                    $client = $session->client;

                    // Tags are already eager loaded — no extra queries here
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
                        'city'         => $client->city->name ?? null,
                        'zone'         => $client->zone->name ?? $client->zone ?? null,
                        'status'       => $session->status,
                        'workout_tags' => $workoutTags,
                        'diet_tags'    => $dietTags,
                    ];
                });

            // ── 4. Notification Count ───────────────────────────
            $unreadNotificationCount = $trainer->notifications()
                ->unread()
                ->count();

            // ── 5. Client Satisfaction ───────────────────────────
            $clientSatisfaction = $trainer->getMonthlyClientSatisfaction();

            return response()->json([
                'status'  => true,
                'message' => 'Dashboard data fetched successfully',
                'data'    => [
                    'profile'                   => $profile,
                    'stats_cards'               => $statsCards,
                    'todays_clients'            => $todayClients,
                    'unread_notification_count' => $unreadNotificationCount,
                    'client_satisfaction'        => [
                        'percentage' => $clientSatisfaction,
                        'label'      => 'This Month',
                    ],
                ],
            ], 200);
        } catch (\Exception $e) {
            Log::error('Dashboard fetch failed', [
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
}
