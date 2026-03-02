<?php

namespace App\Http\Controllers\Api\v1\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Carbon\Carbon;
use App\Models\User;
use App\Models\RefreshToken;

class AuthController extends Controller
{

    public function login(Request $request)
    {
        try {


            $request->validate([
                'email'    => 'required|email|max:255',
                'password' => 'required|string|max:100',
            ]);


            $admin = User::where('email', $request->email)->first();

            if (!$admin) {
                return response()->json([
                    'status'  => false,
                    'message' => 'Invalid credentials'
                ], 401);
            }


            if (!Hash::check($request->password, $admin->password)) {
                return response()->json([
                    'status'  => false,
                    'message' => 'Invalid credentials'
                ], 401);
            }

            $accessToken = Auth::guard('api')->login($admin);
            $plainRefreshToken = Str::random(80);

            $response = response()->json([
                'status'  => true,
                'message' => 'Login successful',
                'data'    => [
                    'id'    => $admin->id,
                    'name'  => $admin->name,
                    'email' => $admin->email,
                ]
            ]);

            return $this->respondWithCookies($admin, $accessToken, $plainRefreshToken, $response);

        } catch (\Illuminate\Validation\ValidationException $e) {

            return response()->json([
                'status'  => false,
                'message' => 'Validation failed',
                'errors'  => $e->errors()
            ], 422);

        } catch (\Exception $e) {

            \Log::error('Admin Login Error: ' . $e->getMessage());

            return response()->json([
                'status'  => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }


    public function me()
    {
        try {

            $admin = auth('api')->user();

            if (!$admin) {
                return response()->json([
                    'status'  => false,
                    'message' => 'Unauthorized'
                ], 401);
            }

            return response()->json([
                'status' => true,
                'data'   => [
                    'id'    => $admin->id,
                    'name'  => $admin->name,
                    'email' => $admin->email,
                ]
            ]);

        } catch (\Exception $e) {

            \Log::error('Admin Me Error: ' . $e->getMessage());

            return response()->json([
                'status'  => false,
                'message' => 'Something went wrong'
            ], 500);
        }
    }

    public function refresh(Request $request)
    {
        try {
            $refreshToken = $request->cookie('refresh_token');

            if (!$refreshToken) {
                return response()->json(['status' => false, 'message' => 'Refresh token missing'], 401);
            }

            $hashedToken = hash('sha256', $refreshToken);
            $tokenRecord = RefreshToken::where('token', $hashedToken)
                ->where('expires_at', '>', Carbon::now())
                ->first();

            if (!$tokenRecord) {
                return response()->json(['status' => false, 'message' => 'Invalid or expired refresh token'], 401);
            }

            $admin = $tokenRecord->tokenable;

            if (!$admin) {
                $tokenRecord->delete();
                return response()->json(['status' => false, 'message' => 'User not found'], 401);
            }

            $tokenRecord->delete();

            $accessToken = Auth::guard('api')->login($admin);
            $plainRefreshToken = Str::random(80);

            $response = response()->json([
                'status'  => true,
                'message' => 'Token refreshed',
                'data'    => [
                    'id'    => $admin->id,
                    'name'  => $admin->name,
                    'email' => $admin->email,
                ]
            ]);

            return $this->respondWithCookies($admin, $accessToken, $plainRefreshToken, $response);

        } catch (\Exception $e) {
            \Log::error('Admin Refresh Error: ' . $e->getMessage());
            return response()->json(['status' => false, 'message' => 'Refresh failed'], 500);
        }
    }

    private function respondWithCookies($admin, $accessToken, $plainRefreshToken, $response)
    {
        RefreshToken::create([
            'tokenable_id'   => $admin->id,
            'tokenable_type' => get_class($admin),
            'token'          => hash('sha256', $plainRefreshToken),
            'expires_at'     => Carbon::now()->addDays(7),
            'device'         => substr(request()->userAgent(), 0, 255),
        ]);

        $secure = env('APP_ENV') === 'production';

        return $response
            ->cookie('access_token', $accessToken, config('jwt.ttl'), '/', null, $secure, true, false, 'Lax')
            ->cookie('refresh_token', $plainRefreshToken, config('jwt.refresh_ttl'), '/', null, $secure, true, false, 'Lax');
    }
}
