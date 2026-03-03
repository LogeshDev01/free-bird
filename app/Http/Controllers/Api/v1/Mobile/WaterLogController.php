<?php

namespace App\Http\Controllers\Api\v1\Mobile;

use App\Http\Controllers\Controller;
use App\Models\WaterDailyLog;
use App\Models\WaterIntake;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class WaterLogController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | 💧 WATER LOG APIs (High-Performance Two-Table Architecture)
    |--------------------------------------------------------------------------
    | Shared by Trainers and Clients.
    | 
    | PARENT: WaterDailyLog (Daily Goals & Denormalized Totals)
    | CHILD:  WaterIntake (Individual Entries)
    */

    /**
     * GET .../water-logs?date=YYYY-MM-DD
     * Fetches daily summary and intake entries.
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $user = $this->resolveUser();
            $date = $request->input('date', Carbon::today()->toDateString());

            $dailyLog = $user->waterDailyLogs()
                ->where('log_date', $date)
                ->with(['intakes' => function($q) {
                    $q->orderBy('logged_at', 'desc');
                }])
                ->first();

            // Default response if no log exists yet for this day
            $data = [
                'date'            => $date,
                'total_consumed'  => $dailyLog->total_consumed_ml ?? 0,
                'water_goal_ml'   => $dailyLog->water_goal_ml ?? 2000,
                'emoji'           => $this->getEmoji($dailyLog->total_consumed_ml ?? 0, $dailyLog->water_goal_ml ?? 2000),
                'entries'         => $dailyLog ? $dailyLog->intakes->map(fn($item) => [
                    'id'        => $item->id,
                    'amount_ml' => $item->amount_ml,
                    'logged_at' => Carbon::parse($item->logged_at)->format('g:i A'),
                ]) : [],
            ];

            return response()->json([
                'status'  => true,
                'message' => 'Water log fetched successfully',
                'data'    => $data,
            ]);
        } catch (\Exception $e) {
            Log::error('Water log index failed', ['error' => $e->getMessage()]);
            return response()->json(['status' => false, 'message' => 'Failed to fetch water log'], 500);
        }
    }

    /**
     * GET .../water-logs/weekly?date=YYYY-MM-DD
     * Fetches 7-day stats for the calendar view.
     */
    public function weekly(Request $request): JsonResponse
    {
        try {
            $user = $this->resolveUser();
            $targetDate = Carbon::parse($request->input('date', Carbon::today()->toDateString()));
            
            $startOfWeek = $targetDate->copy()->startOfWeek(Carbon::MONDAY);
            $endOfWeek   = $targetDate->copy()->endOfWeek(Carbon::SUNDAY);

            $dailyLogs = $user->waterDailyLogs()
                ->whereBetween('log_date', [$startOfWeek->toDateString(), $endOfWeek->toDateString()])
                ->with(['intakes' => function($q) {
                    $q->orderBy('logged_at', 'asc');
                }])
                ->get()
                ->keyBy(fn($log) => $log->log_date->toDateString());

            $stats = [];
            for ($date = $startOfWeek->copy(); $date->lte($endOfWeek); $date->addDay()) {
                $dateStr = $date->toDateString();
                $log = $dailyLogs->get($dateStr);

                $stats[] = [
                    'date'           => $dateStr,
                    'day_name'       => $date->format('D'),
                    'day_number'     => (int) $date->format('j'),
                    'total_consumed' => $log->total_consumed_ml ?? 0,
                    'water_goal_ml'  => $log->water_goal_ml ?? 2000,
                    'is_today'       => $date->isToday(),
                    'intakes'        => $log ? $log->intakes->map(fn($item) => [
                        'id'        => $item->id,
                        'amount_ml' => $item->amount_ml,
                        'logged_at' => Carbon::parse($item->logged_at)->format('g:i A'),
                    ]) : [],
                ];
            }

            return response()->json([
                'status'  => true,
                'message' => 'Weekly water stats fetched',
                'data'    => $stats,
            ]);
        } catch (\Exception $e) {
            Log::error('Water log weekly failed', ['error' => $e->getMessage()]);
            return response()->json(['status' => false, 'message' => 'Failed to fetch weekly stats'], 500);
        }
    }

    /**
     * POST .../water-logs
     * Adds an intake entry. Uses a transaction for data integrity.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'amount_ml' => 'required|integer|min:1',
            'date'      => 'nullable|date_format:Y-m-d',
        ]);

        try {
            $user = $this->resolveUser();
            $date = $validated['date'] ?? Carbon::today()->toDateString();

            return DB::transaction(function () use ($user, $date, $validated) {
                // 1. Get or Create parent daily log
                $dailyLog = $user->waterDailyLogs()->firstOrCreate(
                    ['log_date' => $date],
                    ['water_goal_ml' => 2000, 'total_consumed_ml' => 0]
                );

                // 2. Create intake entry
                $intake = $dailyLog->intakes()->create([
                    'amount_ml' => $validated['amount_ml'],
                    'logged_at' => Carbon::now()->toTimeString(),
                ]);

                // 3. Update denormalized total on parent
                $dailyLog->increment('total_consumed_ml', $validated['amount_ml']);

                return response()->json([
                    'status'  => true,
                    'message' => 'Water intake recorded',
                    'data'    => [
                        'id'             => $intake->id,
                        'amount_ml'      => $intake->amount_ml,
                        'logged_at'      => Carbon::parse($intake->logged_at)->format('g:i A'),
                        'total_consumed' => $dailyLog->total_consumed_ml,
                        'water_goal_ml'  => $dailyLog->water_goal_ml,
                        'emoji'          => $this->getEmoji($dailyLog->total_consumed_ml, $dailyLog->water_goal_ml),
                    ],
                ], 201);
            });
        } catch (\Exception $e) {
            Log::error('Water log store failed', ['error' => $e->getMessage()]);
            return response()->json(['status' => false, 'message' => 'Failed to record intake'], 500);
        }
    }

    /**
     * PUT .../water-logs/goal
     * Updates daily water goal.
     */
    public function updateGoal(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'water_goal_ml' => 'required|integer|min:500',
            'date'          => 'nullable|date_format:Y-m-d',
        ]);

        try {
            $user = $this->resolveUser();
            $date = $validated['date'] ?? Carbon::today()->toDateString();

            $dailyLog = $user->waterDailyLogs()->updateOrCreate(
                ['log_date' => $date],
                ['water_goal_ml' => $validated['water_goal_ml']]
            );

            return response()->json([
                'status'  => true,
                'message' => 'Daily goal updated',
                'data'    => [
                    'date'          => $date,
                    'water_goal_ml' => $dailyLog->water_goal_ml,
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('Water goal update failed', ['error' => $e->getMessage()]);
            return response()->json(['status' => false, 'message' => 'Failed to update goal'], 500);
        }
    }

    /**
     * DELETE .../water-logs/{id}
     * Removes an intake entry.
     */
    public function destroy($id): JsonResponse
    {
        try {
            $user = $this->resolveUser();
            $intake = WaterIntake::whereHas('dailyLog', function($q) use ($user) {
                $q->where('loggable_id', $user->id)
                  ->where('loggable_type', get_class($user));
            })->findOrFail($id);

            return DB::transaction(function () use ($intake) {
                $dailyLog = $intake->dailyLog;
                $amount = $intake->amount_ml;

                $intake->delete();

                // Decerement denormalized total
                $dailyLog->decrement('total_consumed_ml', $amount);

                return response()->json([
                    'status'  => true,
                    'message' => 'Intake entry removed',
                    'data'    => [
                        'total_consumed' => $dailyLog->total_consumed_ml,
                        'water_goal_ml'  => $dailyLog->water_goal_ml,
                        'emoji'          => $this->getEmoji($dailyLog->total_consumed_ml, $dailyLog->water_goal_ml),
                    ],
                ]);
            });
        } catch (\Exception $e) {
            Log::error('Water log delete failed', ['error' => $e->getMessage()]);
            return response()->json(['status' => false, 'message' => 'Failed to remove entry'], 500);
        }
    }

    // ─── HELPERS ───────────────────────────────────────────

    private function resolveUser()
    {
        if (auth('trainer')->check()) return auth('trainer')->user();
        if (auth('client')->check()) return auth('client')->user();
        abort(401, 'Unauthenticated');
    }

    private function getEmoji(int $consumed, int $goal): string
    {
        if ($goal <= 0) return '💧';
        $pct = ($consumed / $goal) * 100;
        if ($pct >= 100) return '🎉';
        if ($pct >= 75) return '😎';
        if ($pct >= 50) return '💪';
        if ($pct >= 25) return '🙂';
        return '💧';
    }
}
