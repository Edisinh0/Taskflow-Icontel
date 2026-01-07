<?php

namespace App\Console\Commands;

use App\Models\CrmCase;
use App\Models\Client;
use App\Models\Task;
use App\Models\User;
use App\Models\CaseUpdate;
use App\Services\SweetCrmService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class SyncSugarCrmCases extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sweetcrm:sync-cases 
                            {username? : SugarCRM username} 
                            {password? : SugarCRM password} 
                            {--limit=0 : Maximum number of cases to sync}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sincronizar Casos y sus Tareas relacionadas desde SugarCRM a Taskflow';

    protected SweetCrmService $sweetCrmService;

    public function __construct(SweetCrmService $sweetCrmService)
    {
        parent::__construct();
        $this->sweetCrmService = $sweetCrmService;
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🔄 Iniciando sincronización de Casos y Tareas desde SugarCRM...');
        $this->newLine();

        $username = $this->argument('username') ?? config('services.sweetcrm.username');
        $password = $this->argument('password') ?? config('services.sweetcrm.password');
        
        if (!$username || !$password) {
            $this->error('❌ Faltan credenciales (username/password). Proporciónalas como argumentos o configúralas en .env');
            return 1;
        }
        $limitOption = (int)$this->option('limit');
        
        // 1. Autenticar
        $this->line('1️⃣  Autenticando...');
        $sessionId = $this->sweetCrmService->getSessionId($username, $password);

        if (!$sessionId) {
            $this->error('❌ Error de autenticación. Verifica las credenciales.');
            return 1;
        }

        $this->info('   ✅ Sesión iniciada');
        $this->newLine();

        // 2. Sincronizar Casos
        $this->line('2️⃣  Sincronizando Casos (Modulos: Cases)...');
        $this->syncCases($sessionId, $limitOption);
        $this->newLine();

        // 3. Sincronizar Tareas asociadas a Casos
        $this->line('3️⃣  Sincronizando Tareas de los Casos (Modulos: Tasks)...');
        $this->syncTasks($sessionId, $limitOption);
        $this->newLine();

        $this->info('✅ Sincronización masiva de Casos y Tareas completada.');
        return 0;
    }

    protected function syncCases(string $sessionId, int $limit)
    {
        $offset = 0;
        $chunkSize = 250;
        $synced = 0;
        $skippedClosed = 0;
        $maxToSync = $limit > 0 ? $limit : 100000;

        // Estados que se consideran "cerrados" o inactivos en SuiteCRM
        $closedStatuses = ['Closed', 'Rejected', 'Duplicate', 'Closed_Closed', 'Merged'];

        $statusMap = [
            'New' => 'Nuevo',
            'Assigned' => 'Asignado',
            'Closed' => 'Cerrado',
            'Pending Input' => 'Pendiente Datos',
            'Rejected' => 'Rechazado',
            'Duplicate' => 'Duplicado',
            'Closed_Closed' => 'Cerrado',
            'Merged' => 'Cerrado',
        ];

        $priorityMap = [
            'P1' => 'Alta',
            'P2' => 'Media',
            'P3' => 'Baja',
            'High' => 'Alta',
            'Medium' => 'Media',
            'Low' => 'Baja',
        ];

        // Obtener IDs de casos sincronizados desde CRM para detectar casos eliminados/cerrados
        $syncedCrmIds = [];

        while ($synced < $maxToSync) {
            // Filtrar solo casos activos (no cerrados/rechazados/duplicados)
            // y deleted = 0 (el servicio ya lo hace, pero la query adicional filtra estados)
            $entries = $this->sweetCrmService->getCases($sessionId, [
                'offset' => $offset,
                'max_results' => $chunkSize,
                'query' => "(cases.status IS NULL OR cases.status = '' OR cases.status NOT IN ('Closed', 'Rejected', 'Duplicate', 'Closed_Closed', 'Merged', 'Cerrado', 'Cerrado_Cerrado'))",
            ]);

            if (empty($entries)) break;

            $bar = $this->output->createProgressBar(count($entries));
            $bar->start();

            foreach ($entries as $entry) {
                try {
                    $nvl = $entry['name_value_list'];
                    $sweetId = $entry['id'];
                    $accountId = $nvl['account_id']['value'] ?? null;
                    $client = $accountId ? Client::where('sweetcrm_id', $accountId)->first() : null;

                    // Guardar ID de CRM para tracking
                    $syncedCrmIds[] = $sweetId;

                    $crmCase = CrmCase::updateOrCreate(
                        ['sweetcrm_id' => $sweetId],
                        [
                            'case_number' => $nvl['case_number']['value'] ?? '',
                            'subject' => $nvl['name']['value'] ?? 'Sin asunto',
                            'description' => $nvl['description']['value'] ?? null,
                            'status' => $statusMap[$nvl['status']['value'] ?? ''] ?? ($nvl['status']['value'] ?: 'Nuevo'),
                            'priority' => $priorityMap[$nvl['priority']['value'] ?? ''] ?? ($nvl['priority']['value'] ?? 'Media'),
                            'type' => $nvl['type']['value'] ?? null,
                            'area' => $nvl['area_c']['value'] ?? null, // Campo personalizado de área
                            'client_id' => $client?->id,
                            'sweetcrm_account_id' => $accountId,
                            'sweetcrm_assigned_user_id' => $nvl['assigned_user_id']['value'] ?? null,
                            'original_creator_id' => $nvl['created_by']['value'] ?? null,
                            'original_creator_name' => $nvl['created_by_name']['value'] ?? null,
                            'assigned_user_name' => $nvl['assigned_user_name']['value'] ?? null,
                            'sweetcrm_created_at' => $nvl['date_entered']['value'] ?? null,
                            'sweetcrm_synced_at' => now(),
                        ]
                    );

                    // Sincronizar avances (campos personalizados de CRM)
                    $this->syncCaseAdvances($crmCase, $nvl);

                    $synced++;
                } catch (\Exception $e) {
                    $this->error("\n Error en Caso {$entry['id']}: " . $e->getMessage());
                }
                $bar->advance();
            }

            $bar->finish();
            $this->newLine();

            $offset += count($entries);
            if (count($entries) < $chunkSize) break;
        }

        // Marcar como "Cerrado" los casos locales que ya no están activos en CRM
        // (casos que están en nuestra BD pero no vinieron en la respuesta de CRM activos)
        if (!empty($syncedCrmIds)) {
            $this->line('   🔄 Verificando casos cerrados/eliminados en CRM...');
            $casesToMarkClosed = CrmCase::whereNotNull('sweetcrm_id')
                ->whereNotIn('sweetcrm_id', $syncedCrmIds)
                ->whereNotIn('status', ['Cerrado', 'Rechazado', 'Duplicado'])
                ->count();

            if ($casesToMarkClosed > 0) {
                CrmCase::whereNotNull('sweetcrm_id')
                    ->whereNotIn('sweetcrm_id', $syncedCrmIds)
                    ->whereNotIn('status', ['Cerrado', 'Rechazado', 'Duplicado'])
                    ->update(['status' => 'Cerrado', 'sweetcrm_synced_at' => now()]);

                $this->info("   ⚠️  Marcados como cerrados: $casesToMarkClosed casos (no activos en CRM)");
            }
        }

        $this->info("   📊 Total casos sincronizados: $synced");
    }

    protected function syncTasks(string $sessionId, int $limit)
    {
        $offset = 0;
        $chunkSize = 500;
        $synced = 0;
        $maxToSync = $limit > 0 ? $limit * 5 : 200000; // Generalmente hay más tareas que casos

        // Obtener IDs de tareas sincronizadas para marcar las eliminadas
        $syncedTaskIds = [];

        while ($synced < $maxToSync) {
            // Filtrar tareas que pertenecen a casos y no están completadas/diferidas
            // El servicio ya filtra deleted = 0
            $entries = $this->sweetCrmService->getTasks($sessionId, [
                'query' => "tasks.parent_type = 'Cases' AND (tasks.status IS NULL OR tasks.status = '' OR tasks.status NOT IN ('Completed', 'Deferred'))",
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

                    // Mapeo de estados
                    $crmStatus = $nvl['status']['value'] ?? 'Not Started';
                    $status = 'pending';
                    if ($crmStatus === 'In Progress') $status = 'in_progress';
                    if ($crmStatus === 'Completed') $status = 'completed';
                    if ($crmStatus === 'Deferred') $status = 'blocked';

                    // Mapeo de prioridad
                    $crmPriority = strtolower($nvl['priority']['value'] ?? 'medium');
                    $priority = 'medium';
                    if (in_array($crmPriority, ['high', 'p1'])) $priority = 'high';
                    if (in_array($crmPriority, ['low', 'p3'])) $priority = 'low';

                    // Asignado
                    $assignee = User::where('sweetcrm_id', $nvl['assigned_user_id']['value'] ?? '')->first();

                    // Creador de la tarea
                    $creator = User::where('sweetcrm_id', $nvl['created_by']['value'] ?? '')->first();

                    $dateStart = $this->parseCrmDate($nvl['date_start']['value'] ?? null);
                    $dateDue = $this->parseCrmDate($nvl['date_due']['value'] ?? null);

                    // Si no hay fecha de inicio pero sí de vencimiento, usar la fecha de creación del caso
                    if (!$dateStart && $dateDue) {
                        $dateCreated = $this->parseCrmDate($nvl['date_entered']['value'] ?? null);
                        $dateStart = $dateCreated ?: $crmCase->created_at;
                    }

                    // Guardar ID para tracking
                    $syncedTaskIds[] = $sweetId;

                    $task = Task::updateOrCreate(
                        ['sweetcrm_id' => $sweetId],
                        [
                            'title' => $nvl['name']['value'] ?? 'Tarea de CRM',
                            'description' => $nvl['description']['value'] ?? null,
                            'case_id' => $crmCase->id,
                            'assignee_id' => $assignee?->id,
                            'created_by' => $creator?->id,
                            'priority' => $priority,
                            'status' => $status,
                            'sweetcrm_synced_at' => now(),
                            'estimated_start_at' => $dateStart,
                            'estimated_end_at' => $dateDue,
                        ]
                    );

                    // Sincronizar avances de la tarea
                    $this->syncTaskAdvances($task, $nvl);

                    $synced++;
                } catch (\Exception $e) {
                    $this->error("\n Error en Tarea {$entry['id']}: " . $e->getMessage());
                }
                $bar->advance();
            }

            $bar->finish();
            $this->newLine();

            $offset += count($entries);
            if (count($entries) < $chunkSize) break;
        }

        // Marcar como completadas las tareas que ya no están activas en CRM
        if (!empty($syncedTaskIds)) {
            $this->line('   🔄 Verificando tareas completadas/eliminadas en CRM...');
            $tasksToComplete = Task::whereNotNull('sweetcrm_id')
                ->whereNotIn('sweetcrm_id', $syncedTaskIds)
                ->whereNotIn('status', ['completed', 'cancelled'])
                ->count();

            if ($tasksToComplete > 0) {
                Task::whereNotNull('sweetcrm_id')
                    ->whereNotIn('sweetcrm_id', $syncedTaskIds)
                    ->whereNotIn('status', ['completed', 'cancelled'])
                    ->update(['status' => 'completed', 'sweetcrm_synced_at' => now()]);

                $this->info("   ⚠️  Marcadas como completadas: $tasksToComplete tareas (no activas en CRM)");
            }
        }

        $this->info("   📊 Total tareas sincronizadas: $synced");
    }

    protected function parseCrmDate(?string $date): ?string
    {
        if (!$date || $date === '0000-00-00 00:00:00') return null;
        return $date;
    }

    protected function syncCaseAdvances(CrmCase $crmCase, array $nvl)
    {
        $advanceFields = ['avances_1_c', 'avances_2_c', 'avances_3_c', 'avances_4_c'];
        
        foreach ($advanceFields as $field) {
            $rawContent = $nvl[$field]['value'] ?? '';
            if (empty(trim($rawContent))) continue;
            
            // Dejar HTML si viene del CRM para mejor visualización
            $content = trim(html_entity_decode($rawContent));
            if (empty(strip_tags($content))) continue;

            // Evitar duplicados
            $exists = CaseUpdate::where('case_id', $crmCase->id)
                ->where('content', $content)
                ->exists();
                
            if (!$exists) {
                $update = new CaseUpdate([
                    'case_id' => $crmCase->id,
                    'user_id' => $crmCase->sweetcrm_assigned_user_id 
                        ? (User::where('sweetcrm_id', $crmCase->sweetcrm_assigned_user_id)->first()?->id ?? User::where('role', 'admin')->first()?->id) 
                        : User::where('role', 'admin')->first()?->id,
                    'content' => $content,
                    'type' => 'update'
                ]);

                // Usar fecha del CRM si está disponible
                if (isset($nvl['date_modified']['value'])) {
                    $update->created_at = $nvl['date_modified']['value'];
                }

                $update->save();
            }
        }
    }

    protected function syncTaskAdvances(Task $task, array $nvl)
    {
        $rawContent = $nvl['avance_c']['value'] ?? '';
        if (empty(trim($rawContent))) return;
        
        // Dejar HTML
        $content = trim(html_entity_decode($rawContent));
        if (empty(strip_tags($content))) return;

        // Evitar duplicados
        $exists = CaseUpdate::where('task_id', $task->id)
            ->where('content', $content)
            ->exists();
            
        if (!$exists) {
            $update = new CaseUpdate([
                'case_id' => $task->case_id,
                'task_id' => $task->id,
                'user_id' => $task->assignee_id ?: User::where('role', 'admin')->first()?->id,
                'content' => $content,
                'type' => 'update'
            ]);

            // Usar fecha del CRM
            if (isset($nvl['date_modified']['value'])) {
                $update->created_at = $nvl['date_modified']['value'];
            }

            $update->save();
        }
    }
}
