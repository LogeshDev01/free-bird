<?php

namespace App\Http\Controllers\Api\v1\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class WorkoutController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $workouts = \App\Models\Workout::with('category', 'category.workoutCategoryType')->get();
        return response()->json([
            'status' => true,
            'message' => 'Workouts fetched successfully',
            'data' => $workouts
        ], 200);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'category_id' => 'required|exists:fb_tbl_workout_category,id',
            'trainer_id' => 'nullable|exists:fb_tbl_trainer,id',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'image' => 'nullable|string|max:255',
            'video_url' => 'nullable|url',
            'difficulty' => 'nullable|string|max:50',
            'muscle_group' => 'nullable|string|max:100',
            'duration_minutes' => 'nullable|integer',
            'sets' => 'nullable|integer',
            'reps' => 'nullable|integer',
            'rest_seconds' => 'nullable|integer',
            'is_active' => 'boolean',
            'lbs' => 'nullable|numeric',
            'kg' => 'nullable|numeric',
            'weight_unit' => 'nullable|string|max:10',
        ]);

        $workout = \App\Models\Workout::create($validated);

        return response()->json([
            'status' => true,
            'message' => 'Workout created successfully',
            'data' => $workout
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $workout = \App\Models\Workout::with('category', 'category.workoutCategoryType')->findOrFail($id);
        
        return response()->json([
            'status' => true,
            'message' => 'Workout fetched successfully',
            'data' => $workout
        ], 200);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $workout = \App\Models\Workout::findOrFail($id);

        $validated = $request->validate([
            'category_id' => 'sometimes|required|exists:fb_tbl_workout_category,id',
            'trainer_id' => 'nullable|exists:fb_tbl_trainer,id',
            'name' => 'sometimes|required|string|max:255',
            'description' => 'nullable|string',
            'image' => 'nullable|string|max:255',
            'video_url' => 'nullable|url',
            'difficulty' => 'nullable|string|max:50',
            'muscle_group' => 'nullable|string|max:100',
            'duration_minutes' => 'nullable|integer',
            'sets' => 'nullable|integer',
            'reps' => 'nullable|integer',
            'rest_seconds' => 'nullable|integer',
            'is_active' => 'boolean',
            'lbs' => 'nullable|numeric',
            'kg' => 'nullable|numeric',
            'weight_unit' => 'nullable|string|max:10',
        ]);

        $workout->update($validated);

        return response()->json([
            'status' => true,
            'message' => 'Workout updated successfully',
            'data' => $workout
        ], 200);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $workout = \App\Models\Workout::findOrFail($id);
        $workout->delete();

        return response()->json([
            'status' => true,
            'message' => 'Workout deleted successfully',
        ], 200);
    }
}
