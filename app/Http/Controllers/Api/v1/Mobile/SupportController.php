<?php

namespace App\Http\Controllers\Api\v1\Mobile;

use App\Http\Controllers\Controller;
use App\Models\Faq;
use App\Models\BusinessSetting;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class SupportController extends Controller
{
    /**
     * GET FAQs
     */
    public function getFaqs(Request $request): JsonResponse
    {
        try {
            $type = $request->query('type');
            $query = Faq::where('is_active', true);

            if ($type) {
                $query->whereIn('type', [$type, 'common']);
            }

            $faqs = $query->orderBy('sort_order', 'asc')->get();

            return response()->json(['status' => true, 'data' => $faqs], 200);
        } catch (\Exception $e) {
            return response()->json(['status' => false, 'message' => 'Failed to fetch FAQs.'], 500);
        }
    }

    /**
     * GET Page content
     */
    public function getPage(string $key): JsonResponse
    {
        try {
            $setting = BusinessSetting::where('key_name', $key)->first();

            if (!$setting) {
                return response()->json(['status' => false, 'message' => 'Not found.'], 404);
            }

            return response()->json([
                'status' => true,
                'data'   => ['key' => $setting->key_name, 'content' => $setting->value]
            ], 200);
        } catch (\Exception $e) {
            return response()->json(['status' => false, 'message' => 'Something went wrong.'], 500);
        }
    }
}
