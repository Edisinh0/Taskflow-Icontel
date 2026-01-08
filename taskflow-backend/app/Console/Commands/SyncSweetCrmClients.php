<?php

namespace App\Console\Commands;

use App\Models\Client;
use App\Services\SweetCrmService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class SyncSweetCrmClients extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sweetcrm:sync-clients {username? : SugarCRM username} {password? : SugarCRM password} {--limit=0 : Maximum number of clients to sync}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sincronizar Cuentas/Clientes desde SugarCRM a Taskflow';

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
        $this->info('🔄 Iniciando sincronización de Clientes/Cuentas desde SugarCRM...');
        $this->newLine();

        $username = $this->argument('username');
        $password = $this->argument('password');
        
        // Si no se proporcionan argumentos, usar configuración
        if (!$username) {
            $username = config('services.sweetcrm.username');
        }
        if (!$password) {
            $password = config('services.sweetcrm.password');
        }
        
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

        // 2. Sincronizar Clientes/Cuentas
        $this->line('2️⃣  Sincronizando Cuentas/Clientes...');
        $this->syncClients($sessionId, $limitOption);
        $this->newLine();

        $this->info('✅ Sincronización de Clientes completada.');
        return 0;
    }

    protected function syncClients(string $sessionId, int $limit)
    {
        $offset = 0;
        $chunkSize = 250;
        $synced = 0;
        $maxToSync = $limit > 0 ? $limit : 100000;

        while ($synced < $maxToSync) {
            // Obtener todas las cuentas/clientes de SweetCRM
            $entries = $this->sweetCrmService->getClients($sessionId, [
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

                    $client = Client::updateOrCreate(
                        ['sweetcrm_id' => $sweetId],
                        [
                            'name' => $nvl['name']['value'] ?? 'Sin nombre',
                            'account_number' => $nvl['account_number']['value'] ?? null,
                            'phone' => $nvl['phone_office']['value'] ?? null,
                            'email' => $nvl['email1']['value'] ?? null,
                            'website' => $nvl['website']['value'] ?? null,
                            'industry' => $nvl['industry']['value'] ?? null,
                            'annual_revenue' => $nvl['annual_revenue']['value'] ?? null,
                            'employees' => $nvl['employees']['value'] ?? null,
                            'billing_address_street' => $nvl['billing_address_street']['value'] ?? null,
                            'billing_address_city' => $nvl['billing_address_city']['value'] ?? null,
                            'billing_address_state' => $nvl['billing_address_state']['value'] ?? null,
                            'billing_address_postalcode' => $nvl['billing_address_postalcode']['value'] ?? null,
                            'billing_address_country' => $nvl['billing_address_country']['value'] ?? null,
                            'shipping_address_street' => $nvl['shipping_address_street']['value'] ?? null,
                            'shipping_address_city' => $nvl['shipping_address_city']['value'] ?? null,
                            'shipping_address_state' => $nvl['shipping_address_state']['value'] ?? null,
                            'shipping_address_postalcode' => $nvl['shipping_address_postalcode']['value'] ?? null,
                            'shipping_address_country' => $nvl['shipping_address_country']['value'] ?? null,
                            'sweetcrm_synced_at' => now(),
                        ]
                    );

                    $synced++;
                } catch (\Exception $e) {
                    $this->error("\n Error en Cuenta {$entry['id']}: " . $e->getMessage());
                    Log::error('Error syncing client', [
                        'account_id' => $entry['id'] ?? 'unknown',
                        'error' => $e->getMessage()
                    ]);
                }
                $bar->advance();
            }

            $bar->finish();
            $this->newLine();

            $offset += count($entries);
            if (count($entries) < $chunkSize) break;
        }

        $this->info("   📊 Total clientes sincronizados: $synced");
    }
}
