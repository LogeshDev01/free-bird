<?php

namespace App\Http\Controllers\Api\v1\Admin;

use App\Http\Controllers\Controller;
use App\Models\Faq;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class FaqController extends Controller
{
    /**
     * GET /api/v1/admin/faqs
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $query = Faq::query();

            if ($request->filled('type')) {
                $query->where('type', $request->type);
            }

            if ($request->filled('is_active')) {
                $query->where('is_active', $request->boolean('is_active'));
            }

            if ($request->filled('search')) {
                $search = str_replace(['%', '_'], ['\\%', '\\_'], $request->search);
                $query->where(function ($q) use ($search) {
                    $q->where('question', 'LIKE', "%{$search}%")
                      ->orWhere('answer', 'LIKE', "%{$search}%");
                });
            }

            $faqs = $query->orderBy('sort_order', 'asc')
                ->orderBy('created_at', 'desc')
                ->paginate($request->get('per_page', 20));

            return response()->json([
                'status'  => true,
                'message' => 'FAQs fetched successfully',
                'data'    => $faqs->items(),
                'pagination' => [
                    'current_page' => $faqs->currentPage(),
                    'per_page'     => $faqs->perPage(),
                    'total'        => $faqs->total(),
                    'last_page'    => $faqs->lastPage(),
                ],
            ], 200);

        } catch (\Exception $e) {
            Log::error('Admin FAQ list failed', ['error' => $e->getMessage()]);
            return response()->json(['status' => false, 'message' => 'Something went wrong.'], 500);
        }
    }

    /**
     * POST /api/v1/admin/faqs
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'question'   => 'required|string',
                'answer'     => 'required|string',
                'type'       => 'required|string|in:client,trainer,common',
                'is_active'  => 'boolean',
                'sort_order' => 'integer',
            ]);

            $faq = Faq::create($validated);

            return response()->json([
                'status'  => true,
                'message' => 'FAQ created successfully.',
                'data'    => $faq,
            ], 201);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json(['status' => false, 'message' => 'Validation failed', 'errors' => $e->errors()], 422);
        } catch (\Exception $e) {
            Log::error('FAQ creation failed', ['error' => $e->getMessage()]);
            return response()->json(['status' => false, 'message' => 'Failed to create FAQ.'], 500);
        }
    }

    /**
     * GET /api/v1/admin/faqs/{id}
     */
    public function show(int $id): JsonResponse
    {
        try {
            $faq = Faq::findOrFail($id);
            return response()->json(['status' => true, 'data' => $faq], 200);
        } catch (\Exception $e) {
            return response()->json(['status' => false, 'message' => 'FAQ not found.'], 404);
        }
    }

    /**
     * PUT /api/v1/admin/faqs/{id}
     */
    public function update(Request $request, int $id): JsonResponse
    {
        try {
            $faq = Faq::findOrFail($id);
            $validated = $request->validate([
                'question'   => 'sometimes|string',
                'answer'     => 'sometimes|string',
                'type'       => 'sometimes|string|in:client,trainer,common',
                'is_active'  => 'boolean',
                'sort_order' => 'integer',
            ]);

            $faq->update($validated);

            return response()->json(['status' => true, 'message' => 'FAQ updated.', 'data' => $faq], 200);

        } catch (\Exception $e) {
            return response()->json(['status' => false, 'message' => 'Update failed.'], 500);
        }
    }

    /**
     * DELETE /api/v1/admin/faqs/{id}
     */
    public function destroy(int $id): JsonResponse
    {
        try {
            Faq::findOrFail($id)->delete();
            return response()->json(['status' => true, 'message' => 'FAQ deleted.'], 200);
        } catch (\Exception $e) {
            return response()->json(['status' => false, 'message' => 'Deletion failed.'], 500);
        }
    }
}
