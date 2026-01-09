<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\CaseDetailResource;
use App\Models\CrmCase;
use App\Models\CaseWorkflowHistory;
use App\Services\SugarCRMWorkflowService;
use App\Services\SweetCrmService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class CaseValidationController extends Controller
{
    private SugarCRMWorkflowService $workflowService;
    private SweetCrmService $sweetCrmService;

    public function __construct(SugarCRMWorkflowService $workflowService, SweetCrmService $sweetCrmService)
    {
        $this->workflowService = $workflowService;
        $this->sweetCrmService = $sweetCrmService;
        $this->middleware('auth:sanctum');
    }

    /**
     * Obtener casos pendientes de validación
     * GET /api/v1/cases/validation/pending
     */
    public function pendingValidation(Request $request)
    {
        $user = auth()->user();

        // Solo Operaciones puede validar
        if ($user->department !== 'Operaciones') {
            return response()->json([
                'message' => 'Solo usuarios de Operaciones pueden validar casos'
            ], 403);
        }

        $cases = $this->workflowService->getPendingValidationCases();

        return response()->json([
            'data' => CaseDetailResource::collection($cases),
            'total' => $cases->count(),
        ]);
    }

    /**
     * Obtener historial de workflow de un caso
     * GET /api/v1/cases/{id}/workflow-history
     */
    public function getWorkflowHistory(CrmCase $case)
    {
        $history = $this->workflowService->getCaseWorkflowHistory($case);

        return response()->json([
            'case_id' => $case->id,
            'case_number' => $case->case_number,
            'workflow_status' => $case->workflow_status,
            'history' => $history->map(function ($record) {
                return [
                    'id' => $record->id,
                    'from_status' => $record->from_status,
                    'to_status' => $record->to_status,
                    'action' => $record->action,
                    'performed_by' => [
                        'id' => $record->performedBy?->id,
                        'name' => $record->performedBy?->name,
                        'email' => $record->performedBy?->email,
                    ],
                    'notes' => $record->notes,
                    'reason' => $record->reason,
                    'created_at' => $record->created_at,
                ];
            })
        ]);
    }

    /**
     * Enviar un caso a validación
     * POST /api/v1/cases/{id}/handover-to-validation
     */
    public function handoverToValidation(CrmCase $case)
    {
        $user = auth()->user();

        // Solo Ventas puede enviar a validación
        if ($user->department !== 'Ventas') {
            return response()->json([
                'message' => 'Solo usuarios de Ventas pueden enviar casos a validación'
            ], 403);
        }

        // Obtener sesión de SugarCRM
        $sessionId = $this->getOrRefreshSugarCRMSession();
        if (!$sessionId) {
            return response()->json([
                'message' => 'No se pudo conectar con SugarCRM'
            ], 500);
        }

        $result = $this->workflowService->handoverCaseToValidation($case, $sessionId);

        if (!$result['success']) {
            return response()->json([
                'message' => $result['message']
            ], 400);
        }

        // Recargar el caso actualizado
        $case->refresh();

        return response()->json([
            'message' => $result['message'],
            'data' => new CaseDetailResource($case),
        ]);
    }

    /**
     * Aprobar validación de un caso
     * POST /api/v1/cases/{id}/validate/approve
     */
    public function approve(CrmCase $case)
    {
        $user = auth()->user();

        // Solo Operaciones puede aprobar
        if ($user->department !== 'Operaciones') {
            return response()->json([
                'message' => 'Solo usuarios de Operaciones pueden aprobar validaciones'
            ], 403);
        }

        // Obtener sesión de SugarCRM
        $sessionId = $this->getOrRefreshSugarCRMSession();
        if (!$sessionId) {
            return response()->json([
                'message' => 'No se pudo conectar con SugarCRM'
            ], 500);
        }

        $result = $this->workflowService->approveCaseValidation($case, $user, $sessionId);

        if (!$result['success']) {
            return response()->json([
                'message' => $result['message']
            ], 400);
        }

        // Recargar el caso actualizado
        $case->refresh();

        return response()->json([
            'message' => $result['message'],
            'data' => new CaseDetailResource($case),
        ]);
    }

    /**
     * Rechazar validación de un caso
     * POST /api/v1/cases/{id}/validate/reject
     */
    public function reject(CrmCase $case, Request $request)
    {
        $request->validate([
            'reason' => 'required|string|max:1000'
        ]);

        $user = auth()->user();

        // Solo Operaciones puede rechazar
        if ($user->department !== 'Operaciones') {
            return response()->json([
                'message' => 'Solo usuarios de Operaciones pueden rechazar validaciones'
            ], 403);
        }

        // Obtener sesión de SugarCRM
        $sessionId = $this->getOrRefreshSugarCRMSession();
        if (!$sessionId) {
            return response()->json([
                'message' => 'No se pudo conectar con SugarCRM'
            ], 500);
        }

        $result = $this->workflowService->rejectCaseValidation(
            $case,
            $user,
            $request->input('reason'),
            $sessionId
        );

        if (!$result['success']) {
            return response()->json([
                'message' => $result['message']
            ], 400);
        }

        // Recargar el caso actualizado
        $case->refresh();

        return response()->json([
            'message' => $result['message'],
            'data' => new CaseDetailResource($case),
        ]);
    }

    /**
     * Obtener o renovar sesión de SugarCRM
     */
    private function getOrRefreshSugarCRMSession(): ?string
    {
        try {
            $username = config('services.sweetcrm.username');
            $password = config('services.sweetcrm.password');

            if (!$username || !$password) {
                Log::error('SugarCRM credentials not configured');
                return null;
            }

            $result = $this->sweetCrmService->getCachedSession($username, $password);

            if ($result['success']) {
                return $result['session_id'];
            }

            Log::error('Failed to get SugarCRM session', ['error' => $result['error'] ?? 'Unknown']);
            return null;
        } catch (\Exception $e) {
            Log::error('Error getting SugarCRM session', ['error' => $e->getMessage()]);
            return null;
        }
    }
}
