<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CrmCase;
use App\Models\CaseClosureRequest;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CaseClosureRequestController extends Controller
{
    /**
     * GET /api/v1/closure-requests
     * Obtener solicitudes de cierre pendientes (para jefes de área)
     */
    public function index(Request $request)
    {
        try {
            $user = $request->user();

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'No autenticado'
                ], 401);
            }

            // Filtrar por estado
            $status = $request->query('status', 'pending');
            $query = CaseClosureRequest::with(['case', 'requestedBy', 'assignedTo']);

            if ($status !== 'all') {
                $query->where('status', $status);
            }

            // Verificar que el usuario puede ver solicitudes de cierre
            if (!$user->canApproveClosures()) {
                return response()->json([
                    'success' => false,
                    'message' => 'No tienes permiso para ver solicitudes de cierre'
                ], 403);
            }

            // Si no es admin, mostrar solo las asignadas al usuario
            if (!$user->isAdmin()) {
                $query->where('assigned_to_user_id', $user->id);
            }

            $requests = $query->latest('created_at')->paginate(20);

            return response()->json([
                'success' => true,
                'data' => $requests,
            ]);

        } catch (\Exception $e) {
            Log::error('Error en closure-requests index: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener solicitudes'
            ], 500);
        }
    }

    /**
     * GET /api/v1/closure-requests/{id}
     * Obtener detalle de una solicitud de cierre
     */
    public function show(Request $request, CaseClosureRequest $closureRequest)
    {
        try {
            $closureRequest->load(['case', 'requestedBy', 'assignedTo', 'reviewedBy']);

            return response()->json([
                'success' => true,
                'data' => $closureRequest,
            ]);

        } catch (\Exception $e) {
            Log::error('Error en closure-requests show: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener solicitud'
            ], 500);
        }
    }

    /**
     * POST /api/v1/cases/{caseId}/request-closure
     * Crear solicitud de cierre (usuarios regulares)
     */
    public function store(Request $request, $caseId)
    {
        try {
            $user = $request->user();

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'No autenticado'
                ], 401);
            }

            // Validar datos
            $validated = $request->validate([
                'reason' => 'required|string|max:500',
                'completion_percentage' => 'integer|min:0|max:100',
            ]);

            // Obtener el caso
            $case = CrmCase::find($caseId);

            if (!$case) {
                return response()->json([
                    'success' => false,
                    'message' => 'Caso no encontrado'
                ], 404);
            }

            // Verificar que el usuario pueda solicitar cierre del caso
            // Puede solicitar si: es el asignado OR es el creador OR es jefe de departamento
            $isAssigned = $case->sweetcrm_assigned_user_id === $user->sweetcrm_id;
            $isCreator = $case->created_by === $user->id;
            $isDeptHead = $user->isDepartmentHead();

            if (!$isAssigned && !$isCreator && !$isDeptHead) {
                return response()->json([
                    'success' => false,
                    'message' => 'Solo el usuario asignado, creador o jefe de departamento pueden solicitar cierre'
                ], 403);
            }

            // Verificar si el caso está abierto
            if ($case->closure_status !== 'open') {
                return response()->json([
                    'success' => false,
                    'message' => 'Este caso ya tiene una solicitud de cierre pendiente o está cerrado'
                ], 422);
            }

            // Verificar si ya hay una solicitud pendiente
            $existingRequest = CaseClosureRequest::where('case_id', $caseId)
                ->where('status', 'pending')
                ->first();

            if ($existingRequest) {
                return response()->json([
                    'success' => false,
                    'message' => 'Ya existe una solicitud de cierre pendiente para este caso'
                ], 422);
            }

            // Obtener jefe de departamento SAC usando el nuevo método
            $serviceHead = User::getDepartmentHead('SAC');

            if (!$serviceHead) {
                Log::warning('No se encontró jefe de departamento SAC', [
                    'case_id' => $caseId,
                    'requested_by' => $user->id,
                ]);

                // Fallback: buscar cualquier admin de SAC
                $serviceHead = User::where('department', 'SAC')
                    ->where('role', 'admin')
                    ->first();
            }

            // Crear solicitud de cierre
            $closureRequest = CaseClosureRequest::create([
                'case_id' => $caseId,
                'requested_by_user_id' => $user->id,
                'assigned_to_user_id' => $serviceHead?->id,
                'reason' => $validated['reason'],
                'completion_percentage' => $validated['completion_percentage'] ?? 100,
            ]);

            // Actualizar estado del caso
            $case->update([
                'closure_status' => 'closure_requested',
                'closure_requested_by_id' => $user->id,
                'closure_requested_at' => now(),
            ]);

            $closureRequest->load(['case', 'requestedBy', 'assignedTo']);

            Log::info('Solicitud de cierre creada', [
                'case_id' => $caseId,
                'requested_by' => $user->id,
                'assigned_to' => $serviceHead?->id,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Solicitud de cierre enviada a Servicio al Cliente',
                'data' => $closureRequest,
            ], 201);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validación fallida',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            Log::error('Error en request-closure: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error al crear solicitud de cierre'
            ], 500);
        }
    }

    /**
     * POST /api/v1/closure-requests/{id}/approve
     * Aprobar solicitud de cierre (jefes de área)
     */
    public function approve(Request $request, CaseClosureRequest $closureRequest)
    {
        try {
            $user = $request->user();

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'No autenticado'
                ], 401);
            }

            // Verificar que el usuario puede aprobar
            // Solo usuarios de SAC pueden aprobar
            if (!$user->canApproveClosures()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Solo usuarios de SAC pueden aprobar solicitudes de cierre'
                ], 403);
            }

            // El usuario debe estar asignado a la solicitud o ser admin
            if ($closureRequest->assigned_to_user_id !== $user->id && !$user->isAdmin()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Solo puedes aprobar solicitudes asignadas a ti'
                ], 403);
            }

            // Verificar que la solicitud está pendiente
            if ($closureRequest->status !== 'pending') {
                return response()->json([
                    'success' => false,
                    'message' => 'Esta solicitud ya fue procesada'
                ], 422);
            }

            DB::beginTransaction();

            try {
                // Actualizar solicitud
                $closureRequest->update([
                    'status' => 'approved',
                    'reviewed_by_user_id' => $user->id,
                    'reviewed_at' => now(),
                ]);

                // Actualizar caso
                $case = $closureRequest->case;
                $case->update([
                    'closure_status' => 'closed',
                    'status' => 'Closed',
                    'closure_approved_by_id' => $user->id,
                    'closure_approved_at' => now(),
                ]);

                DB::commit();

                $closureRequest->load(['case', 'requestedBy', 'reviewedBy']);

                Log::info('Solicitud de cierre aprobada', [
                    'closure_request_id' => $closureRequest->id,
                    'case_id' => $case->id,
                    'approved_by' => $user->id,
                ]);

                return response()->json([
                    'success' => true,
                    'message' => 'Caso cerrado exitosamente',
                    'data' => $closureRequest,
                ]);

            } catch (\Exception $e) {
                DB::rollBack();
                throw $e;
            }

        } catch (\Exception $e) {
            Log::error('Error en approve closure: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error al aprobar solicitud'
            ], 500);
        }
    }

    /**
     * POST /api/v1/closure-requests/{id}/reject
     * Rechazar solicitud de cierre (jefes de área)
     */
    public function reject(Request $request, CaseClosureRequest $closureRequest)
    {
        try {
            $user = $request->user();

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'No autenticado'
                ], 401);
            }

            // Validar datos
            $validated = $request->validate([
                'rejection_reason' => 'required|string|max:500',
            ]);

            // Verificar que el usuario puede rechazar
            // Solo usuarios de SAC pueden rechazar
            if (!$user->canApproveClosures()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Solo usuarios de SAC pueden rechazar solicitudes de cierre'
                ], 403);
            }

            // El usuario debe estar asignado a la solicitud o ser admin
            if ($closureRequest->assigned_to_user_id !== $user->id && !$user->isAdmin()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Solo puedes rechazar solicitudes asignadas a ti'
                ], 403);
            }

            // Verificar que la solicitud está pendiente
            if ($closureRequest->status !== 'pending') {
                return response()->json([
                    'success' => false,
                    'message' => 'Esta solicitud ya fue procesada'
                ], 422);
            }

            DB::beginTransaction();

            try {
                // Actualizar solicitud
                $closureRequest->update([
                    'status' => 'rejected',
                    'rejection_reason' => $validated['rejection_reason'],
                    'reviewed_by_user_id' => $user->id,
                    'reviewed_at' => now(),
                ]);

                // Actualizar caso (volver a estado open)
                $case = $closureRequest->case;
                $case->update([
                    'closure_status' => 'open',
                    'closure_requested_by_id' => null,
                    'closure_requested_at' => null,
                ]);

                DB::commit();

                $closureRequest->load(['case', 'requestedBy', 'reviewedBy']);

                Log::info('Solicitud de cierre rechazada', [
                    'closure_request_id' => $closureRequest->id,
                    'case_id' => $case->id,
                    'rejected_by' => $user->id,
                ]);

                return response()->json([
                    'success' => true,
                    'message' => 'Solicitud de cierre rechazada',
                    'data' => $closureRequest,
                ]);

            } catch (\Exception $e) {
                DB::rollBack();
                throw $e;
            }

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validación fallida',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            Log::error('Error en reject closure: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error al rechazar solicitud'
            ], 500);
        }
    }

    /**
     * GET /api/v1/cases/{caseId}/closure-request
     * Obtener estado de solicitud de cierre de un caso
     */
    public function getCaseClosureStatus(Request $request, $caseId)
    {
        try {
            $case = CrmCase::with(['latestClosureRequest' => function ($query) {
                $query->latest('created_at')->limit(1);
            }])->find($caseId);

            if (!$case) {
                return response()->json([
                    'success' => false,
                    'message' => 'Caso no encontrado'
                ], 404);
            }

            $latestRequest = $case->latestClosureRequest()->first();

            return response()->json([
                'success' => true,
                'closure_status' => $case->closure_status,
                'closure_request' => $latestRequest,
            ]);

        } catch (\Exception $e) {
            Log::error('Error en get case closure status: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener estado'
            ], 500);
        }
    }
}
