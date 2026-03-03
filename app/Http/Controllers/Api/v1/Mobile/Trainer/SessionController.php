<?php

namespace App\Http\Controllers\Api\v1\Mobile\Trainer;

use App\Http\Controllers\Controller;
use App\Models\Session;
use App\Models\Trainer;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class SessionController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | 📅 SESSION MANAGEMENT APIs
    |--------------------------------------------------------------------------
    */

    /**
     * GET /api/v1/mobile/trainer/sessions
     * List sessions with optional date filtering
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $trainer = auth('trainer')->user();

            $query = $trainer->sessions()->with('client:id,first_name,last_name,profile_pic');

            // Filter by date
            if ($request->has('date')) {
                $query->where('session_date', $request->date);
            }

            // Filter by date range
            if ($request->has('from') && $request->has('to')) {
                $query->whereBetween('session_date', [$request->from, $request->to]);
            }

            // Filter by status
            if ($request->has('status')) {
                $query->where('status', $request->status);
            }

            $sessions = $query->orderBy('session_date', 'asc')
                              ->orderBy('start_time', 'asc')
                              ->paginate($request->get('per_page', 20));

            return response()->json([
                'status'  => true,
                'message' => 'Sessions fetched successfully',
                'data'    => $sessions,
            ], 200);
        } catch (\Exception $e) {
            Log::error('Session list failed', [
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
     * GET /api/v1/mobile/trainer/sessions/today
     * Get today's sessions
     */
    public function today(): JsonResponse
    {
        try {
            $trainer = auth('trainer')->user();

            $sessions = $trainer->sessions()
                ->with('client:id,first_name,last_name,profile_pic')
                ->where('session_date', Carbon::today())
                ->orderBy('start_time', 'asc')
                ->get();

            return response()->json([
                'status'  => true,
                'message' => 'Today\'s sessions fetched successfully',
                'data'    => $sessions,
            ], 200);
        } catch (\Exception $e) {
            Log::error('Today sessions failed', [
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
     * POST /api/v1/mobile/trainer/sessions
     * Create a new session
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $trainer = auth('trainer')->user();

            $validated = $request->validate([
                'client_id'    => 'required|exists:fb_tbl_client,id',
                'slot_id'      => 'nullable|exists:fb_tbl_trainer_slot,id',
                'session_date' => 'required|date|after_or_equal:today',
                'start_time'   => 'required|date_format:H:i',
                'end_time'     => 'required|date_format:H:i|after:start_time',
                'location'     => 'nullable|string|max:255',
                'notes'        => 'nullable|string|max:1000',
            ]);

            // Verify the client belongs to this trainer
            $isAssigned = $trainer->clients()
                ->where('fb_tbl_client.id', $validated['client_id'])
                ->wherePivot('status', Trainer::CLIENT_ACTIVE)
                ->exists();

            if (!$isAssigned) {
                return response()->json([
                    'status'  => false,
                    'message' => 'This client is not assigned to you',
                ], 403);
            }

            // ✅ FIX: Correct time overlap detection — catches ALL 4 cases
            // Case 1: New starts during existing
            // Case 2: New ends during existing
            // Case 3: New completely wraps existing
            // Case 4: New is inside existing
            // The single condition "start < end AND end > start" handles all cases.
            $conflict = $trainer->sessions()
                ->where('session_date', $validated['session_date'])
                ->where('status', Session::STATUS_SCHEDULED)
                ->where('start_time', '<', $validated['end_time'])
                ->where('end_time', '>', $validated['start_time'])
                ->exists();

            if ($conflict) {
                return response()->json([
                    'status'  => false,
                    'message' => 'Time slot conflicts with an existing session',
                ], 409);
            }

            $session = $trainer->sessions()->create($validated);

            return response()->json([
                'status'  => true,
                'message' => 'Session created successfully',
                'data'    => $session->load('client:id,first_name,last_name,profile_pic'),
            ], 201);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'status'  => false,
                'message' => 'Validation failed',
                'errors'  => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            Log::error('Session creation failed', [
                'trainer_id' => auth('trainer')->id(),
                'input'      => $request->except('password'),
                'error'      => $e->getMessage(),
            ]);

            return response()->json([
                'status'  => false,
                'message' => 'Something went wrong. Please try again.',
            ], 500);
        }
    }

    /**
     * PUT /api/v1/mobile/trainer/sessions/{id}/status
     * Update session status (complete / cancel)
     */
    public function updateStatus(Request $request, $id): JsonResponse
    {
        try {
            $trainer = auth('trainer')->user();

            $session = $trainer->sessions()->find($id);

            if (!$session) {
                return response()->json([
                    'status'  => false,
                    'message' => 'Session not found',
                ], 404);
            }

            $validated = $request->validate([
                'status' => 'required|integer|in:' . Session::STATUS_COMPLETED . ',' . Session::STATUS_CANCELLED . ',' . Session::STATUS_NO_SHOW,
                'notes'  => 'nullable|string|max:1000',
            ]);

            $session->update($validated);

            return response()->json([
                'status'  => true,
                'message' => 'Session status updated successfully',
                'data'    => $session,
            ], 200);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'status'  => false,
                'message' => 'Validation failed',
                'errors'  => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            Log::error('Session status update failed', [
                'trainer_id' => auth('trainer')->id(),
                'session_id' => $id,
                'error'      => $e->getMessage(),
            ]);

            return response()->json([
                'status'  => false,
                'message' => 'Something went wrong. Please try again.',
            ], 500);
        }
    }
}
