<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Services\SweetCrmService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class SyncSweetCrmUsers extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sweetcrm:sync-users
                            {--fresh : Eliminar usuarios no encontrados en SweetCRM}
                            {--dry-run : Mostrar cambios sin ejecutarlos}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sincroniza usuarios desde SweetCRM a la base de datos local de Taskflow';

    protected SweetCrmService $sweetCrmService;

    public function __construct(SweetCrmService $sweetCrmService)
    {
        parent::__construct();
        $this->sweetCrmService = $sweetCrmService;
    }

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('');
        $this->info('╔════════════════════════════════════════════════════════════╗');
        $this->info('║       SINCRONIZACIÓN DE USUARIOS SWEETCRM → TASKFLOW       ║');
        $this->info('╚════════════════════════════════════════════════════════════╝');
        $this->info('');

        $dryRun = $this->option('dry-run');
        $fresh = $this->option('fresh');

        if ($dryRun) {
            $this->warn('⚠️  Modo DRY-RUN activado. No se realizarán cambios.');
            $this->info('');
        }

        // 1. Autenticar con SweetCRM
        $this->info('🔐 Autenticando con SweetCRM...');

        $username = config('services.sweetcrm.username');
        $password = config('services.sweetcrm.password');

        if (!$username || !$password) {
            $this->error('❌ Credenciales de SweetCRM no configuradas en .env');
            $this->info('   Configure SWEETCRM_USERNAME y SWEETCRM_PASSWORD');
            return Command::FAILURE;
        }

        $sessionResult = $this->sweetCrmService->getCachedSession($username, $password);

        if (!$sessionResult['success']) {
            $this->error('❌ Error de autenticación: ' . ($sessionResult['error'] ?? 'Desconocido'));
            return Command::FAILURE;
        }

        $sessionId = $sessionResult['session_id'];
        $this->info('✅ Autenticación exitosa');
        $this->info('');

        // 2. Obtener usuarios desde SweetCRM
        $this->info('📥 Obteniendo usuarios desde SweetCRM...');

        $rawUsers = $this->sweetCrmService->getUsers($sessionId);

        if (empty($rawUsers)) {
            $this->warn('⚠️  No se encontraron usuarios en SweetCRM');
            return Command::SUCCESS;
        }

        $this->info("   Encontrados: " . count($rawUsers) . " usuarios");
        $this->info('');

        // 3. Procesar cada usuario
        $this->info('🔄 Procesando usuarios...');
        $this->info('');

        $stats = [
            'created' => 0,
            'updated' => 0,
            'skipped' => 0,
            'errors' => 0,
        ];

        $sweetCrmIds = [];
        $tableData = [];

        foreach ($rawUsers as $entry) {
            $nvl = $entry['name_value_list'] ?? [];

            $sweetcrmId = $entry['id'] ?? null;
            $userName = $nvl['user_name']['value'] ?? null;
            $firstName = $nvl['first_name']['value'] ?? '';
            $lastName = $nvl['last_name']['value'] ?? '';
            $email = $nvl['email1']['value'] ?? null;
            $status = $nvl['status']['value'] ?? 'Active';
            $department = $nvl['department']['value'] ?? null;
            $isAdmin = ($nvl['is_admin']['value'] ?? '0') === '1';

            // Validaciones básicas
            if (!$sweetcrmId || !$userName) {
                $stats['skipped']++;
                continue;
            }

            // Ignorar usuarios inactivos
            if ($status !== 'Active') {
                $stats['skipped']++;
                continue;
            }

            $sweetCrmIds[] = $sweetcrmId;

            // Determinar nombre completo
            $fullName = trim("{$firstName} {$lastName}");
            if (empty($fullName)) {
                $fullName = $userName;
            }

            // Determinar rol basado en departamento y is_admin
            $role = $this->determineRole($department, $isAdmin);

            // Buscar usuario existente
            $existingUser = User::where('sweetcrm_id', $sweetcrmId)
                ->orWhere('email', $email)
                ->first();

            $action = $existingUser ? 'UPDATE' : 'CREATE';

            if ($dryRun) {
                $tableData[] = [
                    $action,
                    $fullName,
                    $email ?: 'N/A',
                    $department ?: 'N/A',
                    $role,
                ];

                if ($existingUser) {
                    $stats['updated']++;
                } else {
                    $stats['created']++;
                }
                continue;
            }

            try {
                if ($existingUser) {
                    // Actualizar usuario existente
                    $existingUser->update([
                        'name' => $fullName,
                        'email' => $email ?: $existingUser->email,
                        'department' => $department,
                        'role' => $role,
                        'sweetcrm_id' => $sweetcrmId,
                        'sweetcrm_user_type' => $isAdmin ? 'admin' : 'user',
                        'sweetcrm_synced_at' => now(),
                    ]);

                    $stats['updated']++;
                    $tableData[] = [
                        'UPDATE',
                        $fullName,
                        $email ?: 'N/A',
                        $department ?: 'N/A',
                        $role,
                    ];
                } else {
                    // Crear nuevo usuario
                    // Generar email si no existe
                    if (empty($email)) {
                        $email = Str::slug($userName) . '@taskflow.local';
                    }

                    User::create([
                        'name' => $fullName,
                        'email' => $email,
                        'password' => Hash::make(Str::random(32)), // Password aleatorio
                        'department' => $department,
                        'role' => $role,
                        'sweetcrm_id' => $sweetcrmId,
                        'sweetcrm_user_type' => $isAdmin ? 'admin' : 'user',
                        'sweetcrm_synced_at' => now(),
                    ]);

                    $stats['created']++;
                    $tableData[] = [
                        'CREATE',
                        $fullName,
                        $email,
                        $department ?: 'N/A',
                        $role,
                    ];
                }
            } catch (\Exception $e) {
                $stats['errors']++;
                $this->error("   Error procesando {$userName}: " . $e->getMessage());
            }
        }

        // 4. Mostrar tabla de resultados
        if (!empty($tableData)) {
            $this->table(
                ['Acción', 'Nombre', 'Email', 'Departamento', 'Rol'],
                $tableData
            );
        }

        // 5. Eliminar usuarios no encontrados (si --fresh)
        if ($fresh && !$dryRun && !empty($sweetCrmIds)) {
            $this->info('');
            $this->info('🗑️  Verificando usuarios huérfanos...');

            $orphanCount = User::whereNotNull('sweetcrm_id')
                ->whereNotIn('sweetcrm_id', $sweetCrmIds)
                ->count();

            if ($orphanCount > 0) {
                if ($this->confirm("¿Eliminar {$orphanCount} usuarios no encontrados en SweetCRM?")) {
                    User::whereNotNull('sweetcrm_id')
                        ->whereNotIn('sweetcrm_id', $sweetCrmIds)
                        ->delete();
                    $this->info("   Eliminados: {$orphanCount} usuarios");
                }
            } else {
                $this->info('   No hay usuarios huérfanos');
            }
        }

        // 6. Resumen final
        $this->info('');
        $this->info('╔════════════════════════════════════════════════════════════╗');
        $this->info('║                    RESUMEN DE SINCRONIZACIÓN               ║');
        $this->info('╠════════════════════════════════════════════════════════════╣');
        $this->info("║  ✅ Creados:    {$this->padRight($stats['created'], 44)}║");
        $this->info("║  🔄 Actualizados: {$this->padRight($stats['updated'], 42)}║");
        $this->info("║  ⏭️  Omitidos:   {$this->padRight($stats['skipped'], 44)}║");
        $this->info("║  ❌ Errores:    {$this->padRight($stats['errors'], 44)}║");
        $this->info('╚════════════════════════════════════════════════════════════╝');
        $this->info('');

        if ($dryRun) {
            $this->warn('ℹ️  Ejecute sin --dry-run para aplicar los cambios.');
        }

        return Command::SUCCESS;
    }

    /**
     * Determinar rol del usuario basado en departamento
     */
    private function determineRole(?string $department, bool $isAdmin): string
    {
        if ($isAdmin) {
            return 'admin';
        }

        $department = strtolower($department ?? '');

        // Mapeo de departamentos a roles
        $roleMap = [
            'ventas' => 'sales',
            'sales' => 'sales',
            'comercial' => 'sales',
            'operaciones' => 'operations',
            'operations' => 'operations',
            'ops' => 'operations',
            'soporte' => 'support',
            'support' => 'support',
            'tecnico' => 'support',
            'technical' => 'support',
            'instalaciones' => 'installer',
            'installation' => 'installer',
            'terreno' => 'installer',
            'field' => 'installer',
            'gerencia' => 'manager',
            'management' => 'manager',
            'administracion' => 'admin',
            'administration' => 'admin',
        ];

        foreach ($roleMap as $key => $role) {
            if (str_contains($department, $key)) {
                return $role;
            }
        }

        return 'user'; // Rol por defecto
    }

    /**
     * Pad string to the right
     */
    private function padRight($value, int $length): string
    {
        return str_pad((string) $value, $length);
    }
}
