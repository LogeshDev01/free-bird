<?php

namespace App\Http\Controllers\Api\v1\Admin;

use App\Http\Controllers\Controller;
use App\Models\BusinessSetting;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class BusinessSettingController extends Controller
{
    /**
     * GET /api/v1/admin/business-settings
     */
    public function index(): JsonResponse
    {
        try {
            $settings = BusinessSetting::all();
            return response()->json(['status' => true, 'data' => $settings], 200);
        } catch (\Exception $e) {
            return response()->json(['status' => false, 'message' => 'Failed to fetch settings.'], 500);
        }
    }

    /**
     * POST /api/v1/admin/business-settings
     */
    public function updateSetting(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'key_name' => 'required|string',
                'value'    => 'nullable|string',
            ]);

            $setting = BusinessSetting::updateOrCreate(
                ['key_name' => $validated['key_name']],
                ['value'    => $validated['value']]
            );

            return response()->json(['status' => true, 'message' => 'Setting updated.', 'data' => $setting], 200);

        } catch (\Exception $e) {
            return response()->json(['status' => false, 'message' => 'Update failed.'], 500);
        }
    }
}
