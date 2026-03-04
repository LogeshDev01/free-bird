<?php

namespace App\Http\Controllers\Api\v1\Admin;

use App\Http\Controllers\Controller;
use App\Models\Plan;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class PlanController extends Controller
{
    public function index()
    {
        $plans = Plan::with('features.feature')->latest()->get();
        return response()->json([
            'status' => true,
            'message' => 'Plans retrieved successfully',
            'data' => $plans
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'price' => 'required|numeric|min:0',
            'billing_cycle' => ['required', Rule::in(['monthly', 'yearly', 'lifetime'])],
            'is_active' => 'boolean',
            'features' => 'array',
            'features.*.feature_id' => 'required|exists:features,id',
            'features.*.limit' => 'nullable|integer'
        ]);

        $validated['slug'] = Str::slug($validated['name']);

        $plan = Plan::create($validated);

        if (!empty($validated['features'])) {
            $plan->features()->createMany($validated['features']);
        }

        return response()->json([
            'status' => true,
            'message' => 'Plan created successfully',
            'data' => $plan->load('features.feature')
        ], 201);
    }

    public function show(Plan $plan)
    {
        return response()->json([
            'status' => true,
            'message' => 'Plan details',
            'data' => $plan->load('features.feature')
        ]);
    }

    public function update(Request $request, Plan $plan)
    {
        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'price' => 'sometimes|numeric|min:0',
            'billing_cycle' => ['sometimes', Rule::in(['monthly', 'yearly', 'lifetime'])],
            'is_active' => 'boolean',
            'features' => 'array',
            'features.*.feature_id' => 'required|exists:features,id',
            'features.*.limit' => 'nullable|integer'
        ]);

        if (isset($validated['name'])) {
            $validated['slug'] = Str::slug($validated['name']);
        }

        $plan->update($validated);

        if (isset($validated['features'])) {
            $plan->features()->delete();
            $plan->features()->createMany($validated['features']);
        }

        return response()->json([
            'status' => true,
            'message' => 'Plan updated successfully',
            'data' => $plan->load('features.feature')
        ]);
    }

    public function destroy(Plan $plan)
    {
        $plan->delete();
        return response()->json([
            'status' => true,
            'message' => 'Plan deleted successfully'
        ]);
    }
}
