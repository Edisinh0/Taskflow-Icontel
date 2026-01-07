<?php

namespace App\Console\Commands;

use App\Models\CrmCase;
use App\Models\Task;
use App\Models\User;
use App\Services\SweetCrmService;
use Illuminate\Console\Command;

class VerifyCrmSync extends Command
{
    protected $signature = 'sweetcrm:verify
                            {username? : SugarCRM username}
                            {password? : SugarCRM password}
                            {--user= : Filter by user (username or sweetcrm_id)}
                            {--fix : Apply fixes to discrepancies}';

    protected $description = 'Verificar y corregir discrepancias entre SugarCRM y Taskflow';

    protected SweetCrmService $sweetCrmService;

    public function __construct(SweetCrmService $sweetCrmService)
    {
        parent::__construct();
        $this->sweetCrmService = $sweetCrmService;
    }

    public function handle()
    {
        $this->info('🔍 Verificando sincronización SweetCRM <-> Taskflow...');
        $this->newLine();

        $username = $this->argument('username') ?? config('services.sweetcrm.username');
        $password = $this->argument('password') ?? config('services.sweetcrm.password');

        if (!$username || !$password) {
            $this->error('❌ Faltan credenciales. Proporciónalas como argumentos o configúralas en .env');
            return 1;
        }

        // Autenticar
        $this->line('1️⃣  Autenticando con SweetCRM...');
        $sessionId = $this->sweetCrmService->getSessionId($username, $password);

        if (!$sessionId) {
            $this->error('❌ Error de autenticación.');
            return 1;
        }
        $this->info('   ✅ Sesión iniciada');
        $this->newLine();

        // Filtrar por usuario si se especifica
        $userFilter = $this->option('user');
        $targetUser = null;

        if ($userFilter) {
            $targetUser = User::where('name', 'like', "%{$userFilter}%")
                ->orWhere('email', 'like', "%{$userFilter}%")
                ->orWhere('sweetcrm_id', $userFilter)
                ->first();

            if (!$targetUser) {
                $this->error("❌ Usuario '{$userFilter}' no encontrado");
                return 1;
            }

            $this->info("📋 Filtrando por usuario: {$targetUser->name} (sweetcrm_id: {$targetUser->sweetcrm_id})");
            $this->newLine();
        }

        // 2. Obtener casos activos desde CRM
        $this->line('2️⃣  Obteniendo casos activos desde SweetCRM...');

        $query = "(cases.status IS NULL OR cases.status = '' OR cases.status NOT IN ('Closed', 'Rejected', 'Duplicate', 'Closed_Closed', 'Merged', 'Cerrado', 'Cerrado_Cerrado'))";
        if ($targetUser && $targetUser->sweetcrm_id) {
            $query .= " AND cases.assigned_user_id = '{$targetUser->sweetcrm_id}'";
        }

        $crmCases = $this->sweetCrmService->getCases($sessionId, [
            'query' => $query,
            'max_results' => 500,
        ]);

        $crmCaseIds = collect($crmCases)->pluck('id')->toArray();
        $this->info("   📊 Casos activos en CRM: " . count($crmCaseIds));

        // 3. Obtener casos locales
        $this->line('3️⃣  Comparando con base de datos local...');

        $localQuery = CrmCase::whereNotNull('sweetcrm_id')
            ->whereNotIn('status', ['Cerrado', 'Rechazado', 'Duplicado']);

        if ($targetUser && $targetUser->sweetcrm_id) {
            $localQuery->where('sweetcrm_assigned_user_id', $targetUser->sweetcrm_id);
        }

        $localCases = $localQuery->get();
        $this->info("   📊 Casos activos en Taskflow: " . $localCases->count());
        $this->newLine();

        // 4. Identificar discrepancias
        $localCaseIds = $localCases->pluck('sweetcrm_id')->toArray();

        // Casos en local pero no en CRM (deberían cerrarse)
        $toClose = array_diff($localCaseIds, $crmCaseIds);

        // Casos en CRM pero no en local (deberían sincronizarse)
        $toSync = array_diff($crmCaseIds, $localCaseIds);

        $this->newLine();
        $this->info('📊 RESUMEN DE DISCREPANCIAS:');
        $this->table(
            ['Métrica', 'Cantidad'],
            [
                ['Casos activos en CRM', count($crmCaseIds)],
                ['Casos activos en Taskflow', $localCases->count()],
                ['Casos a cerrar (no activos en CRM)', count($toClose)],
                ['Casos a sincronizar (nuevos en CRM)', count($toSync)],
            ]
        );

        // Mostrar casos a cerrar
        if (!empty($toClose)) {
            $this->newLine();
            $this->warn('⚠️  Casos que deberían estar CERRADOS:');
            $casesToClose = CrmCase::whereIn('sweetcrm_id', $toClose)->get();

            foreach ($casesToClose as $case) {
                $this->line("   - #{$case->case_number}: {$case->subject} (Status actual: {$case->status})");
            }
        }

        // 5. Verificar tareas
        $this->newLine();
        $this->line('4️⃣  Verificando tareas...');

        $taskQuery = "tasks.parent_type = 'Cases' AND (tasks.status IS NULL OR tasks.status = '' OR tasks.status NOT IN ('Completed', 'Deferred'))";
        if ($targetUser && $targetUser->sweetcrm_id) {
            $taskQuery .= " AND tasks.assigned_user_id = '{$targetUser->sweetcrm_id}'";
        }

        $crmTasks = $this->sweetCrmService->getTasks($sessionId, [
            'query' => $taskQuery,
            'max_results' => 500,
        ]);

        $crmTaskIds = collect($crmTasks)->pluck('id')->toArray();

        $localTaskQuery = Task::whereNotNull('sweetcrm_id')
            ->whereNotIn('status', ['completed', 'cancelled']);

        if ($targetUser) {
            $localTaskQuery->where('assignee_id', $targetUser->id);
        }

        $localTasks = $localTaskQuery->get();

        $this->info("   📊 Tareas activas en CRM: " . count($crmTaskIds));
        $this->info("   📊 Tareas activas en Taskflow: " . $localTasks->count());

        // Mostrar detalles de tareas
        if ($targetUser) {
            $this->newLine();
            $this->info('📋 TAREAS DEL USUARIO EN TASKFLOW:');
            foreach ($localTasks as $task) {
                $caseInfo = $task->crmCase ? "#{$task->crmCase->case_number}" : "Sin caso";
                $this->line("   - [{$task->status}] {$task->title} ({$caseInfo})");
            }
        }

        // 6. Aplicar correcciones si --fix
        if ($this->option('fix') && !empty($toClose)) {
            $this->newLine();
            if ($this->confirm('¿Deseas marcar los casos discrepantes como CERRADOS?')) {
                CrmCase::whereIn('sweetcrm_id', $toClose)
                    ->update(['status' => 'Cerrado', 'sweetcrm_synced_at' => now()]);

                $this->info("✅ Se marcaron " . count($toClose) . " casos como cerrados.");
            }
        }

        $this->newLine();
        $this->info('✅ Verificación completada.');

        return 0;
    }
}
