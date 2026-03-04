<?php

namespace App\Http\Controllers\Api\v1\Admin;

use App\Http\Controllers\Controller;
use App\Models\Feature;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class FeatureController extends Controller
{
    public function index()
    {
        $features = Feature::latest()->get();
        return response()->json([
            'status' => true,
            'message' => 'Features retrieved successfully',
            'data' => $features
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'type' => ['required', Rule::in(['boolean', 'quota'])],
            'resets_on_billing' => 'boolean'
        ]);

        $validated['slug'] = Str::slug($validated['name']);
        
        // Ensure slug is unique
        $originalSlug = $validated['slug'];
        $counter = 1;
        while (Feature::where('slug', $validated['slug'])->exists()) {
            $validated['slug'] = $originalSlug . '-' . $counter;
            $counter++;
        }

        $feature = Feature::create($validated);

        return response()->json([
            'status' => true,
            'message' => 'Feature created successfully',
            'data' => $feature
        ], 201);
    }

    public function show(Feature $feature)
    {
        return response()->json([
            'status' => true,
            'message' => 'Feature details',
            'data' => $feature
        ]);
    }

    public function update(Request $request, Feature $feature)
    {
        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'type' => ['sometimes', Rule::in(['boolean', 'quota'])],
            'resets_on_billing' => 'boolean'
        ]);

        if (isset($validated['name'])) {
            $validated['slug'] = Str::slug($validated['name']);
            // Uniqueness check for update
            $originalSlug = $validated['slug'];
            $counter = 1;
            while (Feature::where('slug', $validated['slug'])->where('id', '!=', $feature->id)->exists()) {
                $validated['slug'] = $originalSlug . '-' . $counter;
                $counter++;
            }
        }

        $feature->update($validated);

        return response()->json([
            'status' => true,
            'message' => 'Feature updated successfully',
            'data' => $feature
        ]);
    }

    public function destroy(Feature $feature)
    {
        $feature->delete();
        return response()->json([
            'status' => true,
            'message' => 'Feature deleted successfully'
        ]);
    }
}
