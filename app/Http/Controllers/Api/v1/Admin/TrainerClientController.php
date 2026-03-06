<?php

namespace App\Http\Controllers\Api\v1\Admin;

use App\Http\Controllers\Controller;
use App\Models\Trainer;
use App\Models\Client;
use App\Models\TrainerClient;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class TrainerClientController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | 🔗 TRAINER-CLIENT ENROLLMENT APIs  (Admin only)
    |--------------------------------------------------------------------------
    |
    | This is the GATEWAY for the entire trainer workflow.
    | Every trainer action (session, workout assign, diet assign) is gated
    | behind an active enrollment record in fb_tbl_trainer_client.
    |
    | POST   /admin/trainer-clients              → assign
    | GET    /admin/trainer-clients              → list all enrollments
    | GET    /admin/trainers/{id}/clients        → clients of one trainer
    | GET    /admin/clients/{id}/trainers        → trainers of one client
    | PATCH  /admin/trainer-clients/{id}/status  → change status
    | DELETE /admin/trainer-clients/{id}         → remove enrollment
    |
    */

    /**
     * POST /api/v1/admin/trainer-clients
     * Assign a client to a trainer (creates enrollment record).
     */
    public function assign(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'trainer_id' => 'required|exists:fb_tbl_trainer,id',
                'client_id'  => 'required|exists:fb_tbl_client,id',
                'start_date' => 'nullable|date',
                'end_date'   => 'nullable|date|after_or_equal:start_date',
            ]);

            // Guard: prevent duplicate active enrollment
            $existing = TrainerClient::where('trainer_id', $validated['trainer_id'])
                ->where('client_id', $validated['client_id'])
                ->first();

            if ($existing) {
                if ($existing->status === TrainerClient::STATUS_ACTIVE) {
                    return response()->json([
                        'status'  => false,
                        'message' => 'This client is already actively enrolled with this trainer.',
                        'data'    => $existing->load(['trainer:id,first_name,last_name,emp_id', 'client:id,first_name,last_name']),
                    ], 409);
                }

                // Re-activate if previously completed/inactive
                $existing->update([
                    'status'     => TrainerClient::STATUS_ACTIVE,
                    'start_date' => $validated['start_date'] ?? now()->toDateString(),
                    'end_date'   => $validated['end_date'] ?? null,
                ]);

                return response()->json([
                    'status'  => true,
                    'message' => 'Client re-enrolled with trainer successfully.',
                    'data'    => $existing->fresh()->load([
                        'trainer:id,first_name,last_name,emp_id',
                        'client:id,first_name,last_name',
                    ]),
                ], 200);
            }

            // Create fresh enrollment
            $enrollment = TrainerClient::create([
                'trainer_id' => $validated['trainer_id'],
                'client_id'  => $validated['client_id'],
                'status'     => TrainerClient::STATUS_ACTIVE,
                'start_date' => $validated['start_date'] ?? now()->toDateString(),
                'end_date'   => $validated['end_date'] ?? null,
            ]);

            return response()->json([
                'status'  => true,
                'message' => 'Client assigned to trainer successfully.',
                'data'    => $enrollment->load([
                    'trainer:id,first_name,last_name,emp_id',
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
            Log::error('Trainer-client assign failed', ['error' => $e->getMessage()]);
            return response()->json([
                'status'  => false,
                'message' => 'Assignment failed. Please try again.',
            ], 500);
        }
    }

    /**
     * GET /api/v1/admin/trainer-clients
     * List all enrollments with optional filters.
     *
     * Query params:
     *   ?trainer_id=5
     *   ?client_id=12
     *   ?status=1          (0=inactive, 1=active, 2=completed)
     *   ?per_page=20
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $query = TrainerClient::with([
                'trainer:id,first_name,last_name,emp_id',
                'client:id,first_name,last_name',
            ]);

            if ($request->filled('trainer_id')) {
                $query->where('trainer_id', $request->trainer_id);
            }

            if ($request->filled('client_id')) {
                $query->where('client_id', $request->client_id);
            }

            if ($request->filled('status')) {
                $query->where('status', $request->status);
            }

            $enrollments = $query->orderBy('created_at', 'desc')
                ->paginate($request->get('per_page', 20));

            return response()->json([
                'status'  => true,
                'message' => 'Enrollments fetched successfully',
                'data'    => $enrollments->items(),
                'pagination' => [
                    'current_page' => $enrollments->currentPage(),
                    'per_page'     => $enrollments->perPage(),
                    'total'        => $enrollments->total(),
                    'last_page'    => $enrollments->lastPage(),
                ],
            ], 200);

        } catch (\Exception $e) {
            Log::error('Trainer-client list failed', ['error' => $e->getMessage()]);
            return response()->json([
                'status'  => false,
                'message' => 'Something went wrong. Please try again.',
            ], 500);
        }
    }

    /**
     * GET /api/v1/admin/trainers/{trainerId}/clients
     * List all clients enrolled under a specific trainer.
     */
    public function trainerClients(Request $request, int $trainerId): JsonResponse
    {
        try {
            $trainer = Trainer::findOrFail($trainerId);

            $query = TrainerClient::where('trainer_id', $trainerId)
                ->with('client:id,first_name,last_name,phone,email,goal,status');

            if ($request->filled('status')) {
                $query->where('status', $request->status);
            }

            $enrollments = $query->orderBy('created_at', 'desc')
                ->paginate($request->get('per_page', 20));

            $data = collect($enrollments->items())->map(fn($e) => [
                'enrollment_id' => $e->id,
                'status'        => $e->status,
                'status_label'  => $e->status_label,
                'start_date'    => $e->start_date?->toDateString(),
                'end_date'      => $e->end_date?->toDateString(),
                'client'        => $e->client,
            ]);

            return response()->json([
                'status'  => true,
                'message' => 'Trainer\'s clients fetched successfully',
                'trainer' => [
                    'id'   => $trainer->id,
                    'name' => $trainer->full_name,
                    'emp_id' => $trainer->emp_id,
                ],
                'data'    => $data,
                'pagination' => [
                    'current_page' => $enrollments->currentPage(),
                    'per_page'     => $enrollments->perPage(),
                    'total'        => $enrollments->total(),
                    'last_page'    => $enrollments->lastPage(),
                ],
            ], 200);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json(['status' => false, 'message' => 'Trainer not found.'], 404);
        } catch (\Exception $e) {
            Log::error('Trainer clients list failed', ['trainer_id' => $trainerId, 'error' => $e->getMessage()]);
            return response()->json(['status' => false, 'message' => 'Something went wrong.'], 500);
        }
    }

    /**
     * GET /api/v1/admin/clients/{clientId}/trainers
     * List all trainers a specific client is enrolled with.
     */
    public function clientTrainers(Request $request, int $clientId): JsonResponse
    {
        try {
            $client = Client::findOrFail($clientId);

            $enrollments = TrainerClient::where('client_id', $clientId)
                ->with('trainer:id,first_name,last_name,emp_id,specialization,status')
                ->orderBy('created_at', 'desc')
                ->paginate($request->get('per_page', 20));

            $data = collect($enrollments->items())->map(fn($e) => [
                'enrollment_id' => $e->id,
                'status'        => $e->status,
                'status_label'  => $e->status_label,
                'start_date'    => $e->start_date?->toDateString(),
                'end_date'      => $e->end_date?->toDateString(),
                'trainer'       => $e->trainer,
            ]);

            return response()->json([
                'status'  => true,
                'message' => 'Client\'s trainers fetched successfully',
                'client'  => [
                    'id'   => $client->id,
                    'name' => $client->full_name,
                ],
                'data'    => $data,
                'pagination' => [
                    'current_page' => $enrollments->currentPage(),
                    'per_page'     => $enrollments->perPage(),
                    'total'        => $enrollments->total(),
                    'last_page'    => $enrollments->lastPage(),
                ],
            ], 200);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json(['status' => false, 'message' => 'Client not found.'], 404);
        } catch (\Exception $e) {
            Log::error('Client trainers list failed', ['client_id' => $clientId, 'error' => $e->getMessage()]);
            return response()->json(['status' => false, 'message' => 'Something went wrong.'], 500);
        }
    }

    /**
     * PATCH /api/v1/admin/trainer-clients/{id}/status
     * Update enrollment status (active / inactive / completed) and optionally set end_date.
     */
    public function updateStatus(Request $request, int $id): JsonResponse
    {
        try {
            $enrollment = TrainerClient::findOrFail($id);

            $validated = $request->validate([
                'status'   => 'required|integer|in:0,1,2',
                'end_date' => 'nullable|date',
            ]);

            $enrollment->update([
                'status'   => $validated['status'],
                'end_date' => $validated['end_date']
                    ?? ($validated['status'] === TrainerClient::STATUS_COMPLETED ? now()->toDateString() : $enrollment->end_date),
            ]);

            return response()->json([
                'status'  => true,
                'message' => 'Enrollment status updated to "' . $enrollment->fresh()->status_label . '".',
                'data'    => $enrollment->fresh()->load([
                    'trainer:id,first_name,last_name,emp_id',
                    'client:id,first_name,last_name',
                ]),
            ], 200);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json(['status' => false, 'message' => 'Enrollment record not found.'], 404);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json(['status' => false, 'message' => 'Validation failed', 'errors' => $e->errors()], 422);
        } catch (\Exception $e) {
            Log::error('Trainer-client status update failed', ['id' => $id, 'error' => $e->getMessage()]);
            return response()->json(['status' => false, 'message' => 'Update failed. Please try again.'], 500);
        }
    }

    /**
     * DELETE /api/v1/admin/trainer-clients/{id}
     * Permanently remove an enrollment. Use PATCH status=2 (completed) for soft closure instead.
     */
    public function destroy(int $id): JsonResponse
    {
        try {
            $enrollment = TrainerClient::findOrFail($id);

            $trainerName = $enrollment->trainer?->full_name ?? 'Trainer';
            $clientName  = $enrollment->client?->full_name  ?? 'Client';

            $enrollment->delete();

            return response()->json([
                'status'  => true,
                'message' => "Enrollment between {$trainerName} and {$clientName} removed successfully.",
            ], 200);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json(['status' => false, 'message' => 'Enrollment record not found.'], 404);
        } catch (\Exception $e) {
            Log::error('Trainer-client delete failed', ['id' => $id, 'error' => $e->getMessage()]);
            return response()->json(['status' => false, 'message' => 'Deletion failed. Please try again.'], 500);
        }
    }
}
