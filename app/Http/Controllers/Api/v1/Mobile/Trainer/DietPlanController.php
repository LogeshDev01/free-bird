<?php

namespace App\Http\Controllers\Api\v1\Mobile\Trainer;

use App\Http\Controllers\Controller;
use App\Models\Trainer;
use App\Models\DietPlan;
use App\Models\DietPlanCategory;
use App\Models\DietPlanAssignment;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class DietPlanController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | 🥗 DIET PLAN LIBRARY APIs
    |--------------------------------------------------------------------------
    */

    /**
     * GET /api/v1/mobile/trainer/diet-plans/categories
     * List all diet plan categories
     */
    public function categories(): JsonResponse
    {
        try {
            $categories = DietPlanCategory::where('is_active', true)
                ->withCount('dietPlans')
                ->get();

            return response()->json([
                'status'  => true,
                'message' => 'Diet plan categories fetched successfully',
                'data'    => $categories,
            ], 200);
        } catch (\Exception $e) {
            Log::error('Diet plan categories failed', ['error' => $e->getMessage()]);

            return response()->json([
                'status'  => false,
                'message' => 'Something went wrong. Please try again.',
            ], 500);
        }
    }

    /**
     * GET /api/v1/mobile/trainer/diet-plans
     * List diet plans with optional category filter
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $query = DietPlan::with('category:id,name')
                ->where('is_active', true);

            if ($request->has('category_id')) {
                $query->where('category_id', $request->category_id);
            }

            if ($request->has('meal_type')) {
                $query->where('meal_type', $request->meal_type);
            }

            // ✅ FIX: Sanitize LIKE wildcards
            if ($request->has('search') && $request->search !== '') {
                $search = str_replace(['%', '_'], ['\\%', '\\_'], $request->search);
                $query->where('name', 'LIKE', "%{$search}%");
            }

            $dietPlans = $query->orderBy('name')
                               ->paginate($request->get('per_page', 20));

            return response()->json([
                'status'  => true,
                'message' => 'Diet plans fetched successfully',
                'data'    => $dietPlans,
            ], 200);
        } catch (\Exception $e) {
            Log::error('Diet plan list failed', ['error' => $e->getMessage()]);

            return response()->json([
                'status'  => false,
                'message' => 'Something went wrong. Please try again.',
            ], 500);
        }
    }

    /**
     * GET /api/v1/mobile/trainer/diet-plans/{id}
     * Get diet plan detail
     */
    public function show($id): JsonResponse
    {
        try {
            $dietPlan = DietPlan::with('category:id,name')->find($id);

            if (!$dietPlan) {
                return response()->json([
                    'status'  => false,
                    'message' => 'Diet plan not found',
                ], 404);
            }

            return response()->json([
                'status'  => true,
                'message' => 'Diet plan details fetched successfully',
                'data'    => $dietPlan,
            ], 200);
        } catch (\Exception $e) {
            Log::error('Diet plan detail failed', ['diet_plan_id' => $id, 'error' => $e->getMessage()]);

            return response()->json([
                'status'  => false,
                'message' => 'Something went wrong. Please try again.',
            ], 500);
        }
    }

    /**
     * POST /api/v1/mobile/trainer/diet-plans/assign
     * Assign diet plan to a client
     */
    public function assign(Request $request): JsonResponse
    {
        try {
            $trainer = auth('trainer')->user();

            $validated = $request->validate([
                'client_id'      => 'required|exists:fb_tbl_client,id',
                'diet_plan_id'   => 'required|exists:fb_tbl_diet_plan,id',
                'assigned_date'  => 'required|date',
                'due_date'       => 'nullable|date|after_or_equal:assigned_date',
                'notes'          => 'nullable|string|max:1000',
            ]);

            // Verify client belongs to trainer
            $isAssigned = $trainer->clients()
                ->where('fb_tbl_client.id', $validated['client_id'])
                ->wherePivot('status', Trainer::CLIENT_ACTIVE)
                ->exists();

            if (!$isAssigned) {
                return response()->json([
                    'status'  => false,
                    'message' => 'Client not assigned to you',
                ], 403);
            }

            $assignment = DietPlanAssignment::create([
                'trainer_id'    => $trainer->id,
                'client_id'     => $validated['client_id'],
                'diet_plan_id'  => $validated['diet_plan_id'],
                'assigned_date' => $validated['assigned_date'],
                'due_date'      => $validated['due_date'] ?? null,
                'notes'         => $validated['notes'] ?? null,
            ]);

            return response()->json([
                'status'  => true,
                'message' => 'Diet plan assigned successfully',
                'data'    => $assignment->load(['dietPlan.category', 'client:id,first_name,last_name']),
            ], 201);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'status'  => false,
                'message' => 'Validation failed',
                'errors'  => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            Log::error('Diet plan assign failed', [
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
