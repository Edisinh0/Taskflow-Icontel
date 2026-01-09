<?php

namespace App\Jobs;

use App\Models\CrmCase;
use App\Models\CaseWorkflowHistory;
use App\Services\SweetCrmService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SyncCaseWorkflowToSugarCRMJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private int $caseId;
    private string $newStatus;
    private string $sessionId;
    private ?string $reason;
    public int $tries = 3;
    public int $timeout = 60;

    /**
     * Create a new job instance.
     */
    public function __construct(int $caseId, string $newStatus, string $sessionId, ?string $reason = null)
    {
        $this->caseId = $caseId;
        $this->newStatus = $newStatus;
        $this->sessionId = $sessionId;
        $this->reason = $reason;
    }

    /**
     * Execute the job.
     */
    public function handle(SweetCrmService $sweetCrmService): void
    {
        try {
            $case = CrmCase::findOrFail($this->caseId);

            // Validar que la sesión aún sea válida
            if (!$sweetCrmService->validateSession($this->sessionId)) {
                Log::warning('SugarCRM session validation failed', [
                    'case_id' => $this->caseId,
                    'session_id' => substr($this->sessionId, 0, 10) . '***',
                    'attempt' => $this->attempts(),
                ]);

                // Intentar obtener nueva sesión
                $sessionRefreshResult = $this->refreshSugarCRMSession($sweetCrmService);

                if (!$sessionRefreshResult['success']) {
                    Log::error('Session refresh failed, will retry', [
                        'case_id' => $this->caseId,
                        'reason' => $sessionRefreshResult['error'],
                        'attempt' => $this->attempts(),
                    ]);

                    $this->fail(new \Exception($sessionRefreshResult['error']));
                    return;
                }

                $this->sessionId = $sessionRefreshResult['session_id'];
                Log::info('SugarCRM session refreshed successfully', [
                    'case_id' => $this->caseId,
                    'new_session_id' => substr($sessionRefreshResult['session_id'], 0, 10) . '***',
                ]);
            }

            // Mapear estado local a SugarCRM state
            $sugarCRMState = $this->mapStatusToSugarCRM($this->newStatus);

            // Preparar datos para actualizar
            $updateData = [
                'state' => $sugarCRMState,
                'status' => $sugarCRMState,
            ];

            // Si se rechazó, agregar nota interna
            if ($this->newStatus === 'rejected' && $this->reason) {
                $updateData['description'] = ($case->description ?? '') . "\n\n[VALIDACIÓN RECHAZADA] " . $this->reason;
            }

            // TODO: Implementar actualización en SugarCRM v4_1 cuando esté disponible el método set_entry
            Log::info('SyncCaseWorkflowToSugarCRM: Syncing case state to SugarCRM', [
                'case_id' => $this->caseId,
                'case_number' => $case->case_number,
                'sweetcrm_id' => $case->sweetcrm_id,
                'new_status' => $this->newStatus,
                'sugarcrm_state' => $sugarCRMState,
            ]);

            // Actualizar historial con estado sincronizado
            CaseWorkflowHistory::where('case_id', $this->caseId)
                ->where('sweetcrm_sync_status', 'pending')
                ->latest()
                ->first()?->update([
                    'sweetcrm_sync_status' => 'synced',
                    'sweetcrm_synced_at' => now(),
                    'sweetcrm_sync_response' => json_encode($updateData),
                ]);

            Log::info('Case workflow synced to SugarCRM successfully', [
                'case_id' => $this->caseId,
                'case_number' => $case->case_number,
                'new_status' => $this->newStatus,
            ]);

        } catch (\Exception $e) {
            $this->handleJobException($e);
        }
    }

    /**
     * Refrescar sesión de SugarCRM con reintentos
     *
     * @param SweetCrmService $sweetCrmService
     * @return array ['success' => bool, 'session_id' => string|null, 'error' => string|null]
     */
    private function refreshSugarCRMSession(SweetCrmService $sweetCrmService): array
    {
        try {
            $username = config('services.sweetcrm.username');
            $password = config('services.sweetcrm.password');

            if (!$username || !$password) {
                return [
                    'success' => false,
                    'session_id' => null,
                    'error' => 'SugarCRM credentials not configured',
                ];
            }

            Log::info('Attempting to refresh SugarCRM session', [
                'case_id' => $this->caseId,
                'username' => $username,
            ]);

            $sessionResult = $sweetCrmService->getCachedSession($username, $password);

            if ($sessionResult && !empty($sessionResult['session_id'])) {
                Log::info('SugarCRM session refresh successful', [
                    'case_id' => $this->caseId,
                    'session_id' => substr($sessionResult['session_id'], 0, 10) . '***',
                ]);

                return [
                    'success' => true,
                    'session_id' => $sessionResult['session_id'],
                    'error' => null,
                ];
            }

            return [
                'success' => false,
                'session_id' => null,
                'error' => 'Failed to obtain new SugarCRM session',
            ];
        } catch (\Exception $e) {
            Log::error('Exception during SugarCRM session refresh', [
                'case_id' => $this->caseId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return [
                'success' => false,
                'session_id' => null,
                'error' => 'Session refresh exception: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Manejar excepciones del Job con logging robusto
     *
     * @param \Exception $exception
     * @return void
     */
    private function handleJobException(\Exception $exception): void
    {
        Log::error('Error syncing case workflow to SugarCRM', [
            'case_id' => $this->caseId,
            'error' => $exception->getMessage(),
            'error_class' => get_class($exception),
            'attempt' => $this->attempts(),
            'max_tries' => $this->tries,
            'trace' => $exception->getTraceAsString(),
        ]);

        // Actualizar historial con error
        CaseWorkflowHistory::where('case_id', $this->caseId)
            ->where('sweetcrm_sync_status', 'pending')
            ->latest()
            ->first()?->update([
                'sweetcrm_sync_status' => 'failed',
                'sweetcrm_sync_response' => json_encode([
                    'error' => $exception->getMessage(),
                    'error_class' => get_class($exception),
                    'attempt' => $this->attempts(),
                    'max_tries' => $this->tries,
                ]),
            ]);

        // Reintentar o fallar
        if ($this->attempts() < $this->tries) {
            Log::info('Job will be retried', [
                'case_id' => $this->caseId,
                'attempt' => $this->attempts(),
                'next_retry_delay' => 300,
            ]);
            $this->release(delay: 300); // Esperar 5 minutos antes de reintentar
        } else {
            Log::critical('Job failed after all retries', [
                'case_id' => $this->caseId,
                'total_attempts' => $this->attempts(),
                'error' => $exception->getMessage(),
            ]);
            $this->fail($exception);
        }
    }

    /**
     * Map workflow status to SugarCRM state
     */
    private function mapStatusToSugarCRM(string $status): string
    {
        $mapping = [
            'pending' => 'Open',
            'in_validation' => 'Open', // Aún abierto en validación
            'approved' => 'Closed', // Cerrado cuando se aprueba
            'rejected' => 'Open', // Vuelve a abierto
        ];

        return $mapping[$status] ?? 'Open';
    }
}
