<?php

namespace App\Http\Controllers\Api\v1\Mobile\Trainer;

use App\Http\Controllers\Controller;
use App\Models\SlotType;
use App\Models\TrainerSlot;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class SlotController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | ⏰ TRAINER SLOT MANAGEMENT APIs (Date-Based)
    |--------------------------------------------------------------------------
    */

    /**
     * GET /api/v1/mobile/trainer/slots
     * List slots for a specific date or range
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $trainer = auth('trainer')->user();
            $query = $trainer->slots()->with('type:id,name');

            if ($request->has('date')) {
                $query->where('date', $request->date);
            } elseif ($request->has('month')) {
                // Expecting YYYY-MM
                $query->where('date', 'LIKE', $request->month . '-%');
            }

            $slots = $query->orderBy('date', 'asc')
                           ->orderBy('start_time', 'asc')
                           ->get();

            return response()->json([
                'status'  => true,
                'message' => 'Slots fetched successfully',
                'data'    => $slots,
            ], 200);
        } catch (\Exception $e) {
            Log::error('Slot list failed', [
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
     * GET /api/v1/mobile/trainer/slots/types
     * List all available slot types for selection dropdown
     */
    public function types(): JsonResponse
    {
        try {
            $types = SlotType::where('status', 1)->get(['id', 'name']);

            return response()->json([
                'status'  => true,
                'message' => 'Slot types fetched',
                'data'    => $types,
            ]);
        } catch (\Exception $e) {
            return response()->json(['status' => false, 'message' => 'Failed to fetch types'], 500);
        }
    }

    /**
     * POST /api/v1/mobile/trainer/slots
     * Create a specific date-based slot
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $trainer = auth('trainer')->user();

            $validated = $request->validate([
                'date'         => 'required|date|after_or_equal:today',
                'start_time'   => 'required|date_format:H:i',
                'end_time'     => 'required|date_format:H:i|after:start_time',
                'slot_type_id' => 'required|exists:fb_tbl_slot_type,id',
                'note'         => 'nullable|string|max:500',
            ]);

            // ✅ Overlap check for specific date
            $overlap = $trainer->slots()
                ->where('date', $validated['date'])
                ->where(function ($query) use ($validated) {
                    $query->where('start_time', '<', $validated['end_time'])
                          ->where('end_time', '>', $validated['start_time']);
                })
                ->exists();

            if ($overlap) {
                return response()->json([
                    'status'  => false,
                    'message' => 'Slot already created for this time. If you want to change it, please update the existing slot.',
                ], 409);
            }

            $slot = $trainer->slots()->create($validated);

            return response()->json([
                'status'  => true,
                'message' => 'Slot created successfully',
                'data'    => $slot->load('type:id,name'),
            ], 201);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'status'  => false,
                'message' => 'Validation failed',
                'errors'  => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            Log::error('Slot creation failed', [
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
     * PUT /api/v1/mobile/trainer/slots/{id}
     * Update an existing slot
     */
    public function update(Request $request, $id): JsonResponse
    {
        try {
            $trainer = auth('trainer')->user();
            $slot = $trainer->slots()->find($id);

            if (!$slot) {
                return response()->json(['status' => false, 'message' => 'Slot not found'], 404);
            }

            $validated = $request->validate([
                'date'         => 'sometimes|date|after_or_equal:today',
                'start_time'   => 'sometimes|date_format:H:i',
                'end_time'     => 'sometimes|date_format:H:i|after:start_time',
                'slot_type_id' => 'sometimes|exists:fb_tbl_slot_type,id',
                'note'         => 'nullable|string|max:500',
            ]);

            // ✅ Overlap check excluding current slot
            $date = $validated['date'] ?? $slot->date;
            $startTime = $validated['start_time'] ?? $slot->start_time;
            $endTime = $validated['end_time'] ?? $slot->end_time;

            $overlap = $trainer->slots()
                ->where('id', '!=', $id)
                ->where('date', $date)
                ->where(function ($query) use ($startTime, $endTime) {
                    $query->where('start_time', '<', $endTime)
                          ->where('end_time', '>', $startTime);
                })
                ->exists();

            if ($overlap) {
                return response()->json([
                    'status'  => false,
                    'message' => 'Slot already created for this time. If you want to change it, please update the existing slot.',
                ], 409);
            }

            $slot->update($validated);

            return response()->json([
                'status'  => true,
                'message' => 'Slot updated successfully',
                'data'    => $slot->load('type:id,name'),
            ], 200);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'status'  => false,
                'message' => 'Validation failed',
                'errors'  => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            Log::error('Slot update failed', [
                'trainer_id' => auth('trainer')->id(),
                'slot_id'    => $id,
                'error'      => $e->getMessage(),
            ]);

            return response()->json([
                'status'  => false,
                'message' => 'Something went wrong. Please try again.',
            ], 500);
        }
    }

    /**
     * DELETE /api/v1/mobile/trainer/slots/{id}
     */
    public function destroy($id): JsonResponse
    {
        try {
            $trainer = auth('trainer')->user();
            $slot = $trainer->slots()->find($id);

            if (!$slot) {
                return response()->json(['status' => false, 'message' => 'Slot not found'], 404);
            }

            $slot->delete();

            return response()->json([
                'status'  => true,
                'message' => 'Slot removed successfully',
            ], 200);
        } catch (\Exception $e) {
            return response()->json(['status' => false, 'message' => 'Failed to delete slot'], 500);
        }
    }
}
