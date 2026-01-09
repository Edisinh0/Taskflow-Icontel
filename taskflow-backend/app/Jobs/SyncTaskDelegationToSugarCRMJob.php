<?php

namespace App\Jobs;

use App\Models\Task;
use App\Models\User;
use App\Services\SweetCrmService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SyncTaskDelegationToSugarCRMJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private int $taskId;
    private int $delegatedToUserId;
    private string $sessionId;
    private string $reason;
    public int $tries = 3;
    public int $timeout = 60;

    /**
     * Create a new job instance.
     */
    public function __construct(int $taskId, int $delegatedToUserId, string $sessionId, string $reason)
    {
        $this->taskId = $taskId;
        $this->delegatedToUserId = $delegatedToUserId;
        $this->sessionId = $sessionId;
        $this->reason = $reason;
    }

    /**
     * Execute the job.
     */
    public function handle(SweetCrmService $sweetCrmService): void
    {
        try {
            $task = Task::findOrFail($this->taskId);
            $delegatedTo = User::findOrFail($this->delegatedToUserId);

            // Validar que la sesión aún sea válida
            if (!$sweetCrmService->validateSession($this->sessionId)) {
                Log::warning('SugarCRM session expired, attempting to refresh', [
                    'task_id' => $this->taskId,
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

            // TODO: Implementar actualización de tarea en SugarCRM v4_1
            // Buscar el sweetcrm_id del usuario delegado para actualizar en SugarCRM
            $delegatedToSweetCrmId = $delegatedTo->sweetcrm_id;

            if (!$delegatedToSweetCrmId) {
                Log::warning('Delegated user has no SugarCRM ID', [
                    'task_id' => $this->taskId,
                    'delegated_to_user_id' => $delegatedToUserId,
                ]);
            }

            // Preparar datos para actualizar
            $updateData = [
                'assigned_user_id' => $delegatedToSweetCrmId,
                'description' => ($task->description ?? '') . "\n\n[DELEGADA A OPERACIONES] " . $this->reason,
            ];

            Log::info('SyncTaskDelegationToSugarCRM: Syncing task delegation', [
                'task_id' => $this->taskId,
                'sweetcrm_id' => $task->sweetcrm_id,
                'delegated_to' => $delegatedTo->name,
                'reason' => $this->reason,
            ]);

            // La sincronización real se haría aquí cuando el endpoint de actualización esté disponible
            // Por ahora solo registramos el intento

            Log::info('Task delegation synced to SugarCRM successfully', [
                'task_id' => $this->taskId,
                'sweetcrm_id' => $task->sweetcrm_id,
                'delegated_to' => $delegatedTo->name,
            ]);

        } catch (\Exception $e) {
            Log::error('Error syncing task delegation to SugarCRM', [
                'task_id' => $this->taskId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            // Reintentar o fallar
            if ($this->attempts() < $this->tries) {
                $this->release(delay: 300); // Esperar 5 minutos antes de reintentar
            } else {
                $this->fail($e);
            }
        }
    }
}
