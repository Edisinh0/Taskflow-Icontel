<?php

namespace App\Console\Commands;

use App\Models\Task;
use App\Models\CrmCase;
use App\Models\User;
use App\Services\SweetCrmService;
use Illuminate\Console\Command;

class SyncDelegatedTasks extends Command
{
    protected $signature = 'sweetcrm:sync-delegated-tasks {--limit=50}';
    protected $description = 'Sincroniza tareas delegadas (reasignadas) desde SweetCRM, incluyendo completadas';

    protected SweetCrmService $sweetCrmService;

    public function __construct(SweetCrmService $sweetCrmService)
    {
        parent::__construct();
        $this->sweetCrmService = $sweetCrmService;
    }

    public function handle()
    {
        $this->info('🔄 Sincronizando Tareas Delegadas desde SweetCRM...');
        $this->newLine();

        $username = config('services.sweetcrm.username');
        $password = config('services.sweetcrm.password');

        if (!$username || !$password) {
            $this->error('❌ Credenciales de SweetCRM no configuradas en .env');
            return 1;
        }

        $sessionResult = $this->sweetCrmService->getCachedSession($username, $password);

        if (!$sessionResult['success']) {
            $this->error('❌ Error de autenticación con SweetCRM: ' . $sessionResult['error']);
            return 1;
        }

        $this->syncDelegatedTasks($sessionResult['session_id']);

        $this->info('✅ Sincronización de tareas delegadas completada');
        return 0;
    }

    protected function syncDelegatedTasks(string $sessionId)
    {
        $offset = 0;
        $chunkSize = 500;
        $synced = 0;
        $limit = (int) $this->option('limit');

        $this->info('📋 Obteniendo todas las tareas de SweetCRM (incluyendo completadas)...');

        while ($synced < ($limit > 0 ? $limit * 5 : 5000)) {
            // Obtener TODAS las tareas sin importar estatus
            // (para detectar cambios de asignación en tareas completadas)
            $entries = $this->sweetCrmService->getTasks($sessionId, [
                'query' => "tasks.parent_type = 'Cases'",  // Sin filtro de estado
                'offset' => $offset,
                'max_results' => $chunkSize,
            ]);

            if (empty($entries)) break;

            $bar = $this->output->createProgressBar(count($entries));
            $bar->start();

            foreach ($entries as $entry) {
                try {
                    $nvl = $entry['name_value_list'];
                    $sweetId = $entry['id'];
                    $parentId = $nvl['parent_id']['value'] ?? '';

                    $crmCase = CrmCase::where('sweetcrm_id', $parentId)->first();
                    if (!$crmCase) {
                        $bar->advance();
                        continue;
                    }

                    // Obtener usuario creador y asignado
                    $creator = User::where('sweetcrm_id', $nvl['created_by']['value'] ?? '')->first();
                    $assignee = User::where('sweetcrm_id', $nvl['assigned_user_id']['value'] ?? '')->first();

                    // Buscar la tarea en BD
                    $task = Task::where('sweetcrm_id', $sweetId)->first();

                    if ($task) {
                        // Verificar si hay cambios de asignación o creador
                        $oldAssigneeId = $task->assignee_id;
                        $oldCreatedBy = $task->created_by;

                        if ($oldAssigneeId !== $assignee?->id || $oldCreatedBy !== $creator?->id) {
                            // Hay cambios - actualizar
                            $task->update([
                                'assignee_id' => $assignee?->id,
                                'created_by' => $creator?->id,
                                'sweetcrm_synced_at' => now(),
                            ]);

                            if ($oldAssigneeId !== $assignee?->id) {
                                $oldName = User::find($oldAssigneeId)?->name ?? 'Unknown';
                                $newName = $assignee?->name ?? 'Unassigned';
                                $this->line("\n  ✏️  Tarea '{$task->title}' reasignada: {$oldName} → {$newName}");
                            }
                        }

                        $synced++;
                    }

                } catch (\Exception $e) {
                    $this->error("\n  ❌ Error en Tarea {$entry['id']}: " . $e->getMessage());
                }

                $bar->advance();
            }

            $bar->finish();
            $this->newLine();

            $offset += count($entries);
            if (count($entries) < $chunkSize) break;
        }

        $this->info("   📊 Total tareas procesadas: $synced");
    }
}
