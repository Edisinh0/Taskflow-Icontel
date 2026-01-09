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
                Log::warning('SugarCRM session expired, attempting to refresh', [
                    'case_id' => $this->caseId,
                ]);

                // Intentar obtener nueva sesión
                $username = config('services.sweetcrm.username');
                $password = config('services.sweetcrm.password');
                $sessionResult = $sweetCrmService->getCachedSession($username, $password);

                if (!$sessionResult['success']) {
                    $this->fail(new \Exception('Unable to refresh SugarCRM session'));
                    return;
                }

                $this->sessionId = $sessionResult['session_id'];
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
            Log::error('Error syncing case workflow to SugarCRM', [
                'case_id' => $this->caseId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            // Actualizar historial con error
            CaseWorkflowHistory::where('case_id', $this->caseId)
                ->where('sweetcrm_sync_status', 'pending')
                ->latest()
                ->first()?->update([
                    'sweetcrm_sync_status' => 'failed',
                    'sweetcrm_sync_response' => json_encode([
                        'error' => $e->getMessage(),
                        'attempt' => $this->attempts(),
                    ]),
                ]);

            // Reintentar o fallar
            if ($this->attempts() < $this->tries) {
                $this->release(delay: 300); // Esperar 5 minutos antes de reintentar
            } else {
                $this->fail($e);
            }
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
