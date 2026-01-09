<?php

namespace App\Services;

use App\Models\CrmCase;
use App\Models\Task;
use App\Models\User;
use App\Models\CaseWorkflowHistory;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Jobs\SyncCaseWorkflowToSugarCRMJob;
use App\Jobs\SyncTaskDelegationToSugarCRMJob;

class SugarCRMWorkflowService
{
    private SweetCrmService $sweetCrmService;

    public function __construct(SweetCrmService $sweetCrmService)
    {
        $this->sweetCrmService = $sweetCrmService;
    }

    /**
     * Delegue una tarea de Ventas a Operaciones
     *
     * @param Task $task
     * @param User $delegatedTo Usuario de operaciones a quien se delega
     * @param string $sessionId Session ID de SugarCRM
     * @param string $reason Razón de la delegación
     *
     * @return array ['success' => bool, 'message' => string]
     */
    public function delegateTaskToOperations(Task $task, User $delegatedTo, string $sessionId, string $reason): array
    {
        try {
            return DB::transaction(function () use ($task, $delegatedTo, $sessionId, $reason) {
                // Validar que el usuario tenga department=Operaciones
                if ($delegatedTo->department !== 'Operaciones') {
                    return [
                        'success' => false,
                        'message' => 'El usuario debe pertenecer al departamento de Operaciones'
                    ];
                }

                // Guardar usuario original de ventas si no está registrado
                if (!$task->original_sales_user_id) {
                    $task->original_sales_user_id = $task->assignee_id ?? $task->created_by;
                }

                // Actualizar la tarea con datos de delegación
                $task->update([
                    'delegated_to_user_id' => $delegatedTo->id,
                    'delegated_to_ops_at' => now(),
                    'delegation_status' => 'delegated',
                    'delegation_reason' => $reason,
                    'assignee_id' => $delegatedTo->id, // Cambiar responsable a Operaciones
                ]);

                // Registrar en historial (si es caso)
                if ($task->case_id) {
                    CaseWorkflowHistory::create([
                        'case_id' => $task->case_id,
                        'from_status' => $task->status,
                        'to_status' => $task->status, // Status no cambia, pero se registra delegación
                        'action' => 'delegate',
                        'performed_by_id' => auth()->id() ?? $task->assignee_id,
                        'notes' => "Tarea {$task->title} delegada a {$delegatedTo->name} ({$delegatedTo->department})",
                        'reason' => $reason,
                        'sweetcrm_sync_status' => 'pending',
                    ]);
                }

                // Disparar job asincrónico para sincronizar con SugarCRM
                if ($task->sweetcrm_id) {
                    SyncTaskDelegationToSugarCRMJob::dispatch(
                        $task->id,
                        $delegatedTo->id,
                        $sessionId,
                        $reason
                    );
                }

                Log::info('Task delegated to operations', [
                    'task_id' => $task->id,
                    'delegated_to' => $delegatedTo->name,
                    'reason' => $reason,
                ]);

                return [
                    'success' => true,
                    'message' => "Tarea delegada exitosamente a {$delegatedTo->name}"
                ];
            });
        } catch (\Exception $e) {
            Log::error('Error delegating task', [
                'task_id' => $task->id,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'message' => 'Error al delegar la tarea: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Enviar un caso de Ventas a validación por Operaciones
     *
     * @param CrmCase $case
     * @param string $sessionId Session ID de SugarCRM
     *
     * @return array ['success' => bool, 'message' => string]
     */
    public function handoverCaseToValidation(CrmCase $case, string $sessionId): array
    {
        try {
            return DB::transaction(function () use ($case, $sessionId) {
                // Validar que el caso esté en estado "pending" o "in_progress"
                if (!in_array($case->workflow_status, ['pending', null])) {
                    return [
                        'success' => false,
                        'message' => 'El caso debe estar en estado pendiente para enviarlo a validación'
                    ];
                }

                // Guardar usuario original de ventas
                if (!$case->original_sales_user_id) {
                    $case->original_sales_user_id = $case->created_by;
                }

                // Actualizar estado del caso
                $case->update([
                    'workflow_status' => 'in_validation',
                    'pending_validation_at' => now(),
                    'validation_initiated_by_id' => auth()->id() ?? $case->created_by,
                ]);

                // Registrar en historial
                CaseWorkflowHistory::create([
                    'case_id' => $case->id,
                    'from_status' => $case->workflow_status,
                    'to_status' => 'in_validation',
                    'action' => 'handover_to_validation',
                    'performed_by_id' => auth()->id() ?? $case->created_by,
                    'notes' => "Caso #{$case->case_number} enviado a validación por {$case->validationInitiatedBy?->name}",
                    'sweetcrm_sync_status' => 'pending',
                ]);

                // Disparar job asincrónico para sincronizar con SugarCRM
                if ($case->sweetcrm_id) {
                    SyncCaseWorkflowToSugarCRMJob::dispatch(
                        $case->id,
                        'in_validation',
                        $sessionId
                    );
                }

                Log::info('Case handover to validation', [
                    'case_id' => $case->id,
                    'case_number' => $case->case_number,
                    'initiated_by' => auth()->user()?->name ?? 'System'
                ]);

                return [
                    'success' => true,
                    'message' => "Caso #{$case->case_number} enviado a validación exitosamente"
                ];
            });
        } catch (\Exception $e) {
            Log::error('Error handover case to validation', [
                'case_id' => $case->id,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'message' => 'Error al enviar caso a validación: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Aprobar la validación de un caso
     *
     * @param CrmCase $case
     * @param User $approver Usuario que aprueba (Operaciones)
     * @param string $sessionId Session ID de SugarCRM
     *
     * @return array ['success' => bool, 'message' => string]
     */
    public function approveCaseValidation(CrmCase $case, User $approver, string $sessionId): array
    {
        try {
            return DB::transaction(function () use ($case, $approver, $sessionId) {
                // Validar que el caso esté en validación
                if ($case->workflow_status !== 'in_validation') {
                    return [
                        'success' => false,
                        'message' => 'El caso no está en estado de validación'
                    ];
                }

                // Validar que el aprobador sea de Operaciones
                if ($approver->department !== 'Operaciones') {
                    return [
                        'success' => false,
                        'message' => 'Solo usuarios de Operaciones pueden aprobar validaciones'
                    ];
                }

                // Actualizar estado del caso
                $case->update([
                    'workflow_status' => 'approved',
                    'approved_at' => now(),
                    'approved_by_id' => $approver->id,
                    'status' => 'closed', // Cambiar a cerrado en SugarCRM
                ]);

                // Registrar en historial
                CaseWorkflowHistory::create([
                    'case_id' => $case->id,
                    'from_status' => 'in_validation',
                    'to_status' => 'approved',
                    'action' => 'approve',
                    'performed_by_id' => $approver->id,
                    'notes' => "Caso #{$case->case_number} aprobado por {$approver->name}",
                    'sweetcrm_sync_status' => 'pending',
                ]);

                // Disparar job para sincronizar cierre con SugarCRM
                if ($case->sweetcrm_id) {
                    SyncCaseWorkflowToSugarCRMJob::dispatch(
                        $case->id,
                        'approved',
                        $sessionId
                    );
                }

                Log::info('Case validation approved', [
                    'case_id' => $case->id,
                    'case_number' => $case->case_number,
                    'approved_by' => $approver->name
                ]);

                return [
                    'success' => true,
                    'message' => "Caso #{$case->case_number} aprobado exitosamente"
                ];
            });
        } catch (\Exception $e) {
            Log::error('Error approving case validation', [
                'case_id' => $case->id,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'message' => 'Error al aprobar caso: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Rechazar la validación de un caso
     *
     * @param CrmCase $case
     * @param User $rejector Usuario que rechaza (Operaciones)
     * @param string $reason Razón del rechazo
     * @param string $sessionId Session ID de SugarCRM
     *
     * @return array ['success' => bool, 'message' => string]
     */
    public function rejectCaseValidation(CrmCase $case, User $rejector, string $reason, string $sessionId): array
    {
        try {
            return DB::transaction(function () use ($case, $rejector, $reason, $sessionId) {
                // Validar que el caso esté en validación
                if ($case->workflow_status !== 'in_validation') {
                    return [
                        'success' => false,
                        'message' => 'El caso no está en estado de validación'
                    ];
                }

                // Validar que el rechazador sea de Operaciones
                if ($rejector->department !== 'Operaciones') {
                    return [
                        'success' => false,
                        'message' => 'Solo usuarios de Operaciones pueden rechazar validaciones'
                    ];
                }

                // Actualizar estado del caso
                $case->update([
                    'workflow_status' => 'rejected',
                    'rejected_at' => now(),
                    'rejected_by_id' => $rejector->id,
                    'validation_rejection_reason' => $reason,
                    'status' => 'pending', // Volver a estado pendiente
                ]);

                // Registrar en historial
                CaseWorkflowHistory::create([
                    'case_id' => $case->id,
                    'from_status' => 'in_validation',
                    'to_status' => 'rejected',
                    'action' => 'reject',
                    'performed_by_id' => $rejector->id,
                    'notes' => "Caso #{$case->case_number} rechazado por {$rejector->name}",
                    'reason' => $reason,
                    'sweetcrm_sync_status' => 'pending',
                ]);

                // Disparar job para registrar rechazo en SugarCRM
                if ($case->sweetcrm_id) {
                    SyncCaseWorkflowToSugarCRMJob::dispatch(
                        $case->id,
                        'rejected',
                        $sessionId,
                        $reason
                    );
                }

                Log::info('Case validation rejected', [
                    'case_id' => $case->id,
                    'case_number' => $case->case_number,
                    'rejected_by' => $rejector->name,
                    'reason' => $reason
                ]);

                return [
                    'success' => true,
                    'message' => "Caso #{$case->case_number} rechazado. Se notificará al usuario de ventas original."
                ];
            });
        } catch (\Exception $e) {
            Log::error('Error rejecting case validation', [
                'case_id' => $case->id,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'message' => 'Error al rechazar caso: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Obtener historial de workflow de un caso
     *
     * @param CrmCase $case
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getCaseWorkflowHistory(CrmCase $case)
    {
        return $case->workflowHistory()
            ->with(['performedBy' => fn($q) => $q->select('id', 'name', 'email')])
            ->get();
    }

    /**
     * Validar si una sesión de SugarCRM aún es válida
     *
     * @param string $sessionId
     * @return bool
     */
    public function validateSugarCRMSession(string $sessionId): bool
    {
        try {
            // Usar el servicio para validar la sesión
            return $this->sweetCrmService->validateSession($sessionId);
        } catch (\Exception $e) {
            Log::warning('Error validating SugarCRM session', [
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Obtener nuevas tareas delegadas de un usuario
     *
     * @param User $user Usuario de Operaciones
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getPendingDelegatedTasks(User $user)
    {
        return Task::where('delegated_to_user_id', $user->id)
            ->where('delegation_status', 'delegated')
            ->where('status', '!=', 'completed')
            ->with([
                'crmCase' => fn($q) => $q->select('id', 'case_number', 'subject'),
                'originalSalesUser' => fn($q) => $q->select('id', 'name', 'email'),
            ])
            ->orderBy('delegated_to_ops_at', 'desc')
            ->get();
    }

    /**
     * Obtener casos pendientes de validación
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getPendingValidationCases()
    {
        return CrmCase::where('workflow_status', 'in_validation')
            ->with([
                'originalSalesUser' => fn($q) => $q->select('id', 'name', 'email'),
                'validationInitiatedBy' => fn($q) => $q->select('id', 'name', 'email'),
                'tasks' => fn($q) => $q->select('id', 'title', 'status', 'case_id'),
            ])
            ->orderBy('pending_validation_at', 'asc')
            ->get();
    }

    /**
     * Marcar una tarea delegada como completada
     *
     * @param Task $task
     * @return array ['success' => bool, 'message' => string]
     */
    public function completeDelegatedTask(Task $task): array
    {
        try {
            return DB::transaction(function () use ($task) {
                if ($task->delegation_status !== 'delegated') {
                    return [
                        'success' => false,
                        'message' => 'La tarea no está delegada'
                    ];
                }

                $task->update([
                    'delegation_status' => 'completed',
                    'delegation_completed_at' => now(),
                    'status' => 'completed',
                ]);

                // Si tiene caso asociado, registrar en historial
                if ($task->case_id) {
                    CaseWorkflowHistory::create([
                        'case_id' => $task->case_id,
                        'from_status' => 'in_validation',
                        'to_status' => 'in_validation',
                        'action' => 'task_completed',
                        'performed_by_id' => auth()->id() ?? $task->delegated_to_user_id,
                        'notes' => "Tarea delegada {$task->title} completada por {$task->delegatedToUser?->name}",
                        'sweetcrm_sync_status' => 'pending',
                    ]);
                }

                Log::info('Delegated task completed', [
                    'task_id' => $task->id,
                    'delegated_to' => $task->delegatedToUser?->name,
                ]);

                return [
                    'success' => true,
                    'message' => 'Tarea completada exitosamente'
                ];
            });
        } catch (\Exception $e) {
            Log::error('Error completing delegated task', [
                'task_id' => $task->id,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'message' => 'Error al completar tarea: ' . $e->getMessage()
            ];
        }
    }
}
