<?php

namespace App\Http\Controllers\Api\v1\Mobile\Trainer;

use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

class TrainerController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | 🏢 EMPLOYMENT INFORMATION API
    |--------------------------------------------------------------------------
    | Returns employment card info + status history timeline.
    */

    /**
     * GET /api/v1/mobile/trainer/employment-info
     */
    public function employmentInfo(): JsonResponse
    {
        try {
            $trainer = auth('trainer')->user();

            // ── 1. Employment Card ────────────────────────────
            $employment = [
                'id'             => $trainer->id,
                'profile_pic'    => $trainer->profile_pic,
                'full_name'      => $trainer->full_name,
                'emp_id'         => $trainer->emp_id,
                'current_status' => $trainer->job_status,
                'joining_date'   => $trainer->joining_date ? Carbon::parse($trainer->joining_date)->format('d M Y') : null,
                'department'     => $trainer->specialization ?? 'Training',
                'work_email'     => $trainer->email,
                'monthly_salary' => $trainer->monthly_salary,
                'shift'          => $trainer->shift,
            ];

            // ── 2. Status History Timeline ────────────────────
            $statusHistory = $trainer->statusHistory()
                ->orderBy('effective_date', 'desc')
                ->get()
                ->map(function ($entry) {
                    return [
                        'id'             => $entry->id,
                        'date'           => $entry->effective_date->format('M Y'),
                        'date_full'      => $entry->effective_date->format('d M Y'),
                        'title'          => $entry->title,
                        'status'         => $entry->status,
                        'note'           => $entry->note,
                    ];
                });

            return response()->json([
                'status'  => true,
                'message' => 'Employment info fetched successfully',
                'data'    => [
                    'employment'     => $employment,
                    'status_history' => $statusHistory,
                ],
            ], 200);
        } catch (\Exception $e) {
            Log::error('Employment info fetch failed', [
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
