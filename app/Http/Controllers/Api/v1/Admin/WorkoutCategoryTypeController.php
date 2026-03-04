<?php

namespace App\Http\Controllers\Api\v1\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class WorkoutCategoryTypeController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $types = \App\Models\WorkoutCategoryType::with('workoutCategories')->get();
        return response()->json([
            'status' => true,
            'message' => 'Workout category types fetched successfully',
            'data' => $types
        ], 200);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'icon' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'is_active' => 'boolean',
        ]);

        $type = \App\Models\WorkoutCategoryType::create($validated);

        return response()->json([
            'status' => true,
            'message' => 'Workout category type created successfully',
            'data' => $type
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $type = \App\Models\WorkoutCategoryType::with('workoutCategories')->findOrFail($id);
        
        return response()->json([
            'status' => true,
            'message' => 'Workout category type fetched successfully',
            'data' => $type
        ], 200);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $type = \App\Models\WorkoutCategoryType::findOrFail($id);

        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'icon' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'is_active' => 'boolean',
        ]);

        $type->update($validated);

        return response()->json([
            'status' => true,
            'message' => 'Workout category type updated successfully',
            'data' => $type
        ], 200);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $type = \App\Models\WorkoutCategoryType::findOrFail($id);
        
        // Prevent deletion if connected categories exist
        if ($type->workoutCategories()->exists()) {
            return response()->json([
                'status' => false,
                'message' => 'Cannot delete type that has associated workout categories',
            ], 400);
        }

        $type->delete();

        return response()->json([
            'status' => true,
            'message' => 'Workout category type deleted successfully',
        ], 200);
    }
}
