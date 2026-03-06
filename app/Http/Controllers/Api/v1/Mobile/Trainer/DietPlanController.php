<?php

namespace App\Http\Controllers\Api\v1\Mobile\Trainer;

use App\Http\Controllers\Controller;
use App\Models\MealType;
use App\Models\Trainer;
use App\Models\DietPlan;
use App\Models\Plan;
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
     * List all diet plan categories (paginated)
     */
    public function categories(Request $request): JsonResponse
    {
        try {
            $perPage = (int) $request->get('per_page', 10);

            // ⚠️ minimum_plan_tier must be selected — it's the FK used by subsciptionPlans relation
            $categories = DietPlanCategory::where('is_active', true)
                ->select(['id', 'name', 'image', 'description', 'minimum_plan_tier'])
                ->withCount('dietPlans')
                ->with('plan:id,name')
                ->when($request->filled('search'), function ($query) use ($request) {
                    $search = str_replace(['%', '_'], ['\\%', '\\_'], $request->search);
                    $query->where('name', 'LIKE', "%{$search}%");
                })
                ->orderBy('name')
                ->paginate($perPage);

            $data = collect($categories->items())->map(function ($category) {
                return [
                    'id'                => $category->id,
                    'name'              => $category->name,
                    'image'             => $category->image,
                    'description'       => $category->description,
                    'diet_plans_count'  => $category->diet_plans_count,
                    'subscription_plan' => $category->plan
                        ? ['id' => $category->plan->id, 'name' => $category->plan->name]
                        : null,
                ];
            });
            
            return response()->json([
                'status'  => true,
                'message' => 'Diet plan categories fetched successfully',
                'data'    => $data,
                'pagination' => [
                    'current_page' => $categories->currentPage(),
                    'per_page'     => $categories->perPage(),
                    'total'        => $categories->total(),
                    'last_page'    => $categories->lastPage(),
                ],
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
     * GET /api/v1/mobile/trainer/diet-plans/meal-types
     * List all active meal type master records
     */
    public function mealTypes(): JsonResponse
    {
        try {
            $mealTypes = MealType::where('is_active', true)
                ->orderBy('id')
                ->get(['id', 'name', 'icon', 'description']);

            return response()->json([
                'status'  => true,
                'message' => 'Meal types fetched successfully',
                'data'    => $mealTypes,
            ], 200);
        } catch (\Exception $e) {
            Log::error('Meal types fetch failed', ['error' => $e->getMessage()]);

            return response()->json([
                'status'  => false,
                'message' => 'Something went wrong. Please try again.',
            ], 500);
        }
    }

    /**
     * GET /api/v1/mobile/trainer/diet-plans
     * List diet plans with optional filters (category_id, meal_type_id, search)
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $query = DietPlan::with([
                    'category:id,name',
                    'mealType:id,name,icon',
                ])
                ->where('is_active', true);

            if ($request->filled('category_id')) {
                $query->where('category_id', $request->category_id);
            }

            // ── Filter by meal_type_id (master FK) ──────────────────────
            if ($request->filled('meal_type_id')) {
                $query->where('meal_type_id', $request->meal_type_id);
            }

            // ── Legacy string filter kept for backward compatibility ─────
            if ($request->filled('meal_type') && !$request->filled('meal_type_id')) {
                $query->where('meal_type', $request->meal_type);
            }

            // ── Search ───────────────────────────────────────────────────
            if ($request->filled('search')) {
                $search = str_replace(['%', '_'], ['\\%', '\\_'], $request->search);
                $query->where('name', 'LIKE', "%{$search}%");
            }

            $dietPlans = $query->orderBy('name')
                               ->paginate($request->get('per_page', 20));

            $formatted = collect($dietPlans->items())->map(function ($plan) {
                return [
                    'id'            => $plan->id,
                    'name'          => $plan->name,
                    'category'      => $plan->category->name ?? 'N/A',
                    'meal_type'     => $plan->mealType->name ?? 'N/A',
                    'meal_type_icon'=> $plan->mealType->icon ?? null,
                    'calories'      => $plan->calories,
                    'protein'       => $plan->protein,
                    'carbs'         => $plan->carbs,
                    'fats'          => $plan->fats,
                    'image'         => $plan->image,
                    'is_active'     => $plan->is_active,
                    'created_at'    => \Illuminate\Support\Carbon::parse($plan->created_at)->format('d M Y'),
                ];
            });

            return response()->json([
                'status'  => true,
                'message' => 'Diet plans fetched successfully',
                'data'    => $formatted,
                'pagination' => [
                    'current_page' => $dietPlans->currentPage(),
                    'per_page'     => $dietPlans->perPage(),
                    'total'        => $dietPlans->total(),
                    'last_page'    => $dietPlans->lastPage(),
                ],
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
            $dietPlan = DietPlan::with([
                'category:id,name',
                'mealType:id,name,icon',
            ])->find($id);

            if (!$dietPlan) {
                return response()->json([
                    'status'  => false,
                    'message' => 'Diet plan not found',
                ], 404);
            }

            return response()->json([
                'status'  => true,
                'message' => 'Diet plan details fetched successfully',
                'data'    => [
                    'id'            => $dietPlan->id,
                    'name'          => $dietPlan->name,
                    'category'      => $dietPlan->category->name ?? 'N/A',
                    'meal_type'     => $dietPlan->mealType->name ?? 'N/A',
                    'meal_type_icon'=> $dietPlan->mealType->icon ?? null,
                    'calories'      => $dietPlan->calories,
                    'protein'       => $dietPlan->protein,
                    'carbs'         => $dietPlan->carbs,
                    'fats'          => $dietPlan->fats,
                    'image'         => $dietPlan->image,
                    'description'   => $dietPlan->description,
                    'is_active'     => $dietPlan->is_active,
                    'created_at'    => \Illuminate\Support\Carbon::parse($dietPlan->created_at)->format('d M Y'),
                ],
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
                'data'    => $assignment->load([
                    'dietPlan.category',
                    'dietPlan.mealType:id,name,icon',
                    'client:id,first_name,last_name',
                ]),
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
