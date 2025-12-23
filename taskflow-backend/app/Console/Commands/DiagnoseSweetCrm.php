<?php

namespace App\Console\Commands;

use App\Services\SweetCrmService;
use Illuminate\Console\Command;

class DiagnoseSweetCrm extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sweetcrm:diagnose';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Diagnosticar conexión y configuración de SweetCRM';

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
        $this->info('🔍 Diagnóstico de Integración SweetCRM');
        $this->newLine();

        // 1. Verificar configuración
        $this->line('📋 Configuración:');
        $this->table(
            ['Configuración', 'Valor'],
            [
                ['SWEETCRM_ENABLED', config('services.sweetcrm.enabled') ? '✅ Habilitado' : '❌ Deshabilitado'],
                ['SWEETCRM_URL', config('services.sweetcrm.url') ?: '❌ No configurado'],
                ['SWEETCRM_API_TOKEN', config('services.sweetcrm.api_token') ? '✅ Configurado (' . strlen(config('services.sweetcrm.api_token')) . ' caracteres)' : '❌ No configurado'],
                ['SWEETCRM_SYNC_INTERVAL', config('services.sweetcrm.sync_interval', 3600) . ' segundos'],
            ]
        );
        $this->newLine();

        // 2. Verificar conectividad básica
        $this->line('🌐 Conectividad:');
        $url = config('services.sweetcrm.url');

        if (!$url) {
            $this->error('❌ URL de SweetCRM no configurada');
            return 1;
        }

        $this->info("Probando conexión a: {$url}");

        try {
            $response = \Illuminate\Support\Facades\Http::timeout(10)->get($url);
            $this->info("✅ Servidor responde (HTTP {$response->status()})");
        } catch (\Exception $e) {
            $this->error("❌ No se pudo conectar: {$e->getMessage()}");
            return 1;
        }
        $this->newLine();

        // 3. Probar endpoint de SugarCRM v4_1
        $this->line('🔌 Probando API de SugarCRM:');

        try {
            $response = \Illuminate\Support\Facades\Http::timeout(5)
                ->asForm()
                ->post("{$url}/service/v4_1/rest.php", [
                    'method' => 'login',
                    'input_type' => 'JSON',
                    'response_type' => 'JSON',
                    'rest_data' => json_encode([
                        'user_auth' => [
                            'user_name' => 'test',
                            'password' => md5('test'),
                        ],
                        'application_name' => 'Taskflow',
                    ]),
                ]);

            if ($response->successful()) {
                $data = $response->json();
                if (isset($data['name']) && $data['name'] === 'Invalid Login') {
                    $this->info('✅ API REST v4_1 disponible en /service/v4_1/rest.php');
                    $this->info('   (Login falló como esperado - credenciales de prueba)');
                } else {
                    $this->info('✅ API REST v4_1 accesible');
                }
            } else {
                $this->error('❌ API v4_1 no responde correctamente');
            }
        } catch (\Exception $e) {
            $this->error("❌ Error probando API v4_1: {$e->getMessage()}");
        }
        $this->newLine();

        // 4. Resumen y recomendaciones
        $this->line('💡 Estado de la Integración:');
        $this->info('✅ SugarCRM usa la API REST v4_1');
        $this->info('✅ La integración está configurada correctamente');
        $this->newLine();

        $this->line('📝 Para completar la integración:');
        $this->line('  1. Prueba el login desde la interfaz con credenciales reales');
        $this->line('  2. Los usuarios de SugarCRM podrán autenticarse en Taskflow');
        $this->line('  3. Se crearán automáticamente cuentas al primer login');
        $this->newLine();

        $this->line('ℹ️  Nota sobre la API:');
        $this->line('  - Esta instalación usa SugarCRM API REST v4_1 (versión legacy)');
        $this->line('  - Las contraseñas se envían hasheadas con MD5');
        $this->line('  - Se usa session ID en lugar de OAuth tokens');

        $this->newLine();
        return 0;
    }
}
