<?php

namespace App\Http\Controllers\Api\v1\Mobile\Trainer;

use App\Http\Controllers\Controller;
use App\Models\Session;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class HistoryController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | 📜 TRAINER HISTORY API
    |--------------------------------------------------------------------------
    | Returns paginated session history with overall stats.
    | Grouping is encouraged on the frontend using the 'session_date'.
    | Matches the Figma History design.
    */

    /**
     * GET /api/v1/mobile/trainer/history
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $trainer = auth('trainer')->user();
            $today = Carbon::today();

            // ── 1. Summary Stats ──────────────────────────────
            // Total sessions ever scheduled for this trainer
            $totalSessions = $trainer->sessions()->count();

            // Completed sessions
            $completedSessions = $trainer->sessions()
                ->where('status', Session::STATUS_COMPLETED)
                ->count();

            // Missed sessions (Cancelled + No Show)
            $missedSessions = $trainer->sessions()
                ->whereIn('status', [Session::STATUS_CANCELLED, Session::STATUS_NO_SHOW])
                ->count();

            $stats = [
                'total_sessions'     => $totalSessions,
                'completed_sessions' => $completedSessions,
                'missed_sessions'    => $missedSessions,
            ];

            // ── 2. Session List (Paginated) ────────────────────
            // We fetch all sessions (past and today's) ordered by most recent
            $sessions = $trainer->sessions()
                ->with(['client:id,first_name,last_name,profile_pic', 'slot.type:id,name'])
                ->orderBy('session_date', 'desc')
                ->orderBy('start_time', 'desc')
                ->paginate($request->get('per_page', 10));

            // Transform for the UI requirements
            $sessions->getCollection()->transform(function ($session) use ($today) {
                // Determine date label (Yesterday, Today, or Day Name)
                $date = $session->session_date;
                $dateLabel = $date->format('l'); // e.g. Friday
                
                if ($date->isToday()) {
                    $dateLabel = 'Today';
                } elseif ($date->isYesterday()) {
                    $dateLabel = 'Yesterday';
                }

                return [
                    'id'               => $session->id,
                    'client_name'      => $session->client->full_name,
                    'client_photo'     => $session->client->profile_pic ? asset('storage/' . $session->client->profile_pic) : null,
                    'session_type'     => $session->slot && $session->slot->type ? $session->slot->type->name : 'General Session',
                    'start_time'       => Carbon::parse($session->start_time)->format('g:i A'),
                    'end_time'         => Carbon::parse($session->end_time)->format('g:i A'),
                    'location'         => $session->location ?? 'N/A',
                    'status'           => $session->status, // 2=Completed, 3=Cancelled, 4=No Show
                    'session_date'     => $date->format('Y-m-d'),
                    'session_date_raw' => $date->format('M d, Y'),
                    'day_label'        => $dateLabel,
                ];
            });

            return response()->json([
                'status'  => true,
                'message' => 'History fetched successfully',
                'data'    => [
                    'stats'    => $stats,
                    'history' => $sessions,
                ],
            ], 200);
        } catch (\Exception $e) {
            Log::error('History fetch failed', [
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
