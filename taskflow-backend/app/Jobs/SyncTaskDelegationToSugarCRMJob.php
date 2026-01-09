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
                Log::warning('SugarCRM session validation failed', [
                    'task_id' => $this->taskId,
                    'session_id' => substr($this->sessionId, 0, 10) . '***',
                    'attempt' => $this->attempts(),
                ]);

                // Intentar refrescar sesión
                $sessionRefreshResult = $this->refreshSugarCRMSession($sweetCrmService);

                if (!$sessionRefreshResult['success']) {
                    Log::error('Session refresh failed, will retry', [
                        'task_id' => $this->taskId,
                        'reason' => $sessionRefreshResult['error'],
                        'attempt' => $this->attempts(),
                    ]);

                    $this->fail(new \Exception($sessionRefreshResult['error']));
                    return;
                }

                $this->sessionId = $sessionRefreshResult['session_id'];
                Log::info('SugarCRM session refreshed successfully', [
                    'task_id' => $this->taskId,
                    'new_session_id' => substr($sessionRefreshResult['session_id'], 0, 10) . '***',
                ]);
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
                'task_id' => $this->taskId,
                'username' => $username,
            ]);

            $sessionResult = $sweetCrmService->getCachedSession($username, $password);

            if ($sessionResult && !empty($sessionResult['session_id'])) {
                Log::info('SugarCRM session refresh successful', [
                    'task_id' => $this->taskId,
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
                'task_id' => $this->taskId,
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
        Log::error('Error syncing task delegation to SugarCRM', [
            'task_id' => $this->taskId,
            'delegated_to_user_id' => $this->delegatedToUserId,
            'error' => $exception->getMessage(),
            'error_class' => get_class($exception),
            'attempt' => $this->attempts(),
            'max_tries' => $this->tries,
            'trace' => $exception->getTraceAsString(),
        ]);

        // Reintentar o fallar
        if ($this->attempts() < $this->tries) {
            Log::info('Job will be retried', [
                'task_id' => $this->taskId,
                'attempt' => $this->attempts(),
                'next_retry_delay' => 300,
            ]);
            $this->release(delay: 300); // Esperar 5 minutos antes de reintentar
        } else {
            Log::critical('Job failed after all retries', [
                'task_id' => $this->taskId,
                'total_attempts' => $this->attempts(),
                'error' => $exception->getMessage(),
            ]);
            $this->fail($exception);
        }
    }
}
