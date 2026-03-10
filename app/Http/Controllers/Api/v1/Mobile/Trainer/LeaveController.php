<?php

namespace App\Http\Controllers\Api\v1\Mobile\Trainer;

use App\Http\Controllers\Controller;
use App\Models\LeaveType;
use App\Models\TrainerLeave;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class LeaveController extends Controller
{
    /**
     * GET /api/v1/mobile/trainer/leaves/summary
     * Get counts: Approved, Pending, Rejected.
     */
    public function summary(): JsonResponse
    {
        try {
            $trainerId = auth('trainer')->id();

            $summary = TrainerLeave::where('trainer_id', $trainerId)
                ->select('status', DB::raw('count(*) as count'))
                ->groupBy('status')
                ->get()
                ->pluck('count', 'status');

            return response()->json([
                'status'  => true,
                'message' => 'Leave summary fetched successfully',
                'data'    => [
                    'approved' => $summary->get(TrainerLeave::STATUS_APPROVED, 0),
                    'pending'  => $summary->get(TrainerLeave::STATUS_PENDING, 0),
                    'rejected' => $summary->get(TrainerLeave::STATUS_REJECTED, 0),
                ]
            ], 200);
        } catch (\Exception $e) {
            Log::error('Leave summary failed', ['error' => $e->getMessage()]);
            return response()->json([
                'status'  => false,
                'message' => 'Something went wrong. Please try again.',
            ], 500);
        }
    }

    /**
     * GET /api/v1/mobile/trainer/leaves/types
     * List all active leave types.
     */
    public function types(): JsonResponse
    {
        try {
            $types = LeaveType::where('is_active', true)->get(['id', 'name', 'icon', 'description']);

            return response()->json([
                'status'  => true,
                'message' => 'Leave types fetched successfully',
                'data'    => $types,
            ], 200);
        } catch (\Exception $e) {
            Log::error('Leave types failed', ['error' => $e->getMessage()]);
            return response()->json([
                'status'  => false,
                'message' => 'Something went wrong. Please try again.',
            ], 500);
        }
    }

    /**
     * GET /api/v1/mobile/trainer/leaves
     * Paginated history of leave requests.
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $trainerId = auth('trainer')->id();
            $perPage = (int) $request->get('per_page', 10);

            $leaves = TrainerLeave::with('leaveType:id,name,icon')
                ->where('trainer_id', $trainerId)
                ->orderBy('created_at', 'desc')
                ->paginate($perPage);

            $formattedData = collect($leaves->items())->map(function ($leave) {
                return [
                    'id'              => $leave->id,
                    'leave_type'      => $leave->leaveType->name ?? 'N/A',
                    'leave_type_icon' => $leave->leaveType->icon ?? null,
                    'start_date'      => Carbon::parse($leave->start_date)->format('d M Y'),
                    'end_date'        => Carbon::parse($leave->end_date)->format('d M Y'),
                    'start_time'      => $leave->start_time ? Carbon::parse($leave->start_time)->format('g:i A') : null,
                    'end_time'        => $leave->end_time ? Carbon::parse($leave->end_time)->format('g:i A') : null,
                    'total_days'      => $leave->total_days,
                    'status'          => $leave->status, // Map to string if needed, currently 0,1,2
                    'status_label'    => $this->getStatusLabel($leave->status),
                    'reason'          => $leave->reason,
                    'admin_comment'   => $leave->admin_comment,
                    'actioned_at'     => $leave->actioned_at ? Carbon::parse($leave->actioned_at)->format('d M Y') : null,
                    'processed_at'    => $leave->actioned_at ? Carbon::parse($leave->actioned_at)->format('d M Y') : null, // Alias for clarity
                    'created_at'      => Carbon::parse($leave->created_at)->format('d M Y'),
                ];
            });

            return response()->json([
                'status'  => true,
                'message' => 'Leave history fetched successfully',
                'data'    => $formattedData,
                'pagination' => [
                    'current_page' => $leaves->currentPage(),
                    'per_page'     => $leaves->perPage(),
                    'total'        => $leaves->total(),
                    'last_page'    => $leaves->lastPage(),
                ],
            ], 200);
        } catch (\Exception $e) {
            Log::error('Leave list failed', ['error' => $e->getMessage()]);
            return response()->json([
                'status'  => false,
                'message' => 'Something went wrong. Please try again.',
            ], 500);
        }
    }

    /**
     * Helper to get status label.
     */
    private function getStatusLabel(int $status): string
    {
        return match ($status) {
            TrainerLeave::STATUS_APPROVED => 'Approved',
            TrainerLeave::STATUS_REJECTED => 'Rejected',
            default                       => 'Pending',
        };
    }

    /**
     * POST /api/v1/mobile/trainer/leaves
     * Submit a new leave request.
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $trainerId = auth('trainer')->id();
            
            $validated = $request->validate([
                'leave_type_id'   => 'required|exists:fb_tbl_leave_types,id',
                'start_date'      => 'required|date|after_or_equal:today',
                'end_date'        => 'required|date|after_or_equal:start_date',
                'start_time'      => 'nullable|date_format:H:i',
                'end_time'        => 'nullable|date_format:H:i|after:start_time',
                'reason'          => 'nullable|string|max:1000',
                'additional_note' => 'nullable|string|max:1000',
            ]);
            
            // Calculate total days
            $start = Carbon::parse($validated['start_date']);
            $end = Carbon::parse($validated['end_date']);
            $totalDays = $start->diffInDays($end) + 1;

            // 30 days leave validation: Must apply 1 month prior
            if ($totalDays >= 30) {
                $oneMonthFromNow = Carbon::today()->addMonth();
                if ($start->lessThan($oneMonthFromNow)) {
                    return response()->json([
                        'status'  => false,
                        'message' => 'You cannot create a leave for 1 mon. if you need then from the leave start date it should be 1 month prior.',
                    ], 422);
                }
            }

            // Short leave detection (same day + time provided)
            $isShortLeave = ($validated['start_date'] === $validated['end_date']) && 
                            !empty($validated['start_time']) && 
                            !empty($validated['end_time']);

            // 1. Check for overlapping leaves
            $overlapQuery = TrainerLeave::where('trainer_id', $trainerId)
                ->where('status', '!=', TrainerLeave::STATUS_REJECTED)
                ->where(function ($query) use ($validated) {
                    $query->whereBetween('start_date', [$validated['start_date'], $validated['end_date']])
                          ->orWhereBetween('end_date', [$validated['start_date'], $validated['end_date']])
                          ->orWhere(function ($q) use ($validated) {
                              $q->where('start_date', '<=', $validated['start_date'])
                                ->where('end_date', '>=', $validated['end_date']);
                          });
                });

            if ($isShortLeave) {
                // If submitting a short leave, only check for time overlaps if there's already a leave on THAT day
                $overlapQuery->where(function ($q) use ($validated) {
                    $q->whereNull('start_time') // Full day leave overlaps with anything
                      ->orWhere(function ($sub) use ($validated) {
                          $sub->where('start_time', '<', $validated['end_time'])
                              ->where('end_time', '>', $validated['start_time']);
                      });
                });
            }

            if ($overlapQuery->exists()) {
                return response()->json([
                    'status'  => false,
                    'message' => 'You already have a leave request that overlaps with this period.',
                ], 422);
            }

            // 2. Conflict Detection: Sessions
            $sessionQuery = \App\Models\Session::where('trainer_id', $trainerId)
                ->whereIn('status', [\App\Models\Session::STATUS_SCHEDULED])
                ->whereBetween('session_date', [$validated['start_date'], $validated['end_date']]);

            if ($isShortLeave) {
                $sessionQuery->where(function ($q) use ($validated) {
                    $q->where('start_time', '<', $validated['end_time'])
                      ->where('end_time', '>', $validated['start_time']);
                });
            }
            $conflictingSessionsCount = $sessionQuery->count();

            // 3. Conflict Detection: Slots
            $slotQuery = \App\Models\TrainerSlot::where('trainer_id', $trainerId)
                ->whereBetween('date', [$validated['start_date'], $validated['end_date']]);

            if ($isShortLeave) {
                $slotQuery->where(function ($q) use ($validated) {
                    $q->where('start_time', '<', $validated['end_time'])
                      ->where('end_time', '>', $validated['start_time']);
                });
            }
            $conflictingSlotsCount = $slotQuery->count();

            $leave = TrainerLeave::create([
                'trainer_id'      => $trainerId,
                'leave_type_id'   => $validated['leave_type_id'],
                'start_date'      => $validated['start_date'],
                'end_date'        => $validated['end_date'],
                'start_time'      => $validated['start_time'] ?? null,
                'end_time'        => $validated['end_time'] ?? null,
                'total_days'      => $isShortLeave ? 0 : $totalDays, // 0 days for short permission
                'reason'          => $validated['reason'] ?? null,
                'additional_note' => $validated['additional_note'] ?? null,
                'status'          => TrainerLeave::STATUS_PENDING,
            ]);

            $leave->load('leaveType:id,name,icon');

            return response()->json([
                'status'  => true,
                'message' => 'Leave request submitted successfully',
                'data'    => [
                    'id'              => $leave->id,
                    'leave_type'      => $leave->leaveType->name ?? 'N/A',
                    'leave_type_icon' => $leave->leaveType->icon ?? null,
                    'start_date'      => Carbon::parse($leave->start_date)->format('d M Y'),
                    'end_date'        => Carbon::parse($leave->end_date)->format('d M Y'),
                    'start_time'      => $leave->start_time ? Carbon::parse($leave->start_time)->format('g:i A') : null,
                    'end_time'        => $leave->end_time ? Carbon::parse($leave->end_time)->format('g:i A') : null,
                    'total_days'      => $leave->total_days,
                    'status'          => $leave->status,
                    'status_label'    => $this->getStatusLabel($leave->status),
                    'reason'          => $leave->reason,
                    'additional_note' => $leave->additional_note,
                    'created_at'      => Carbon::parse($leave->created_at)->format('d M Y'),
                    'conflicts'       => [
                        'sessions_count' => $conflictingSessionsCount,
                        'slots_count'    => $conflictingSlotsCount,
                    ]
                ],
            ], 201);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'status'  => false,
                'message' => 'Validation failed',
                'errors'  => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            Log::error('Leave submit failed', ['error' => $e->getMessage()]);
            return response()->json([
                'status'  => false,
                'message' => 'Something went wrong. Please try again.',
            ], 500);
        }
    }
}
