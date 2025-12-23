<?php

namespace App\Console\Commands;

use App\Models\Client;
use App\Models\Industry;
use App\Services\SweetCrmService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class SyncSugarCrmClients extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sweetcrm:sync-clients
                            {username : SugarCRM username}
                            {password : SugarCRM password}
                            {--limit=0 : Maximum number of clients to sync (0 for all)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sincronizar clientes desde SugarCRM a Taskflow';

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
        $this->info('🔄 Sincronizando clientes desde SugarCRM...');
        $this->newLine();

        $username = $this->argument('username');
        $password = $this->argument('password');
        $limitOption = $this->option('limit');
        
        // Si el limite es 0 o menor, sincronizamos todos los disponibles
        $totalToSync = (int)$limitOption > 0 ? (int)$limitOption : 100000;
        $chunkSize = 500; 
        $offset = 0;

        // 1. Autenticar
        $this->line('1️⃣  Autenticando con SugarCRM...');
        $sessionId = $this->sweetCrmService->getSessionId($username, $password);

        if (!$sessionId) {
            $this->error('❌ Error de autenticación. Verifica las credenciales.');
            return 1;
        }

        $this->info('   ✅ Autenticación exitosa');
        $this->newLine();

        $synced = 0;
        $created = 0;
        $updated = 0;
        $errors = [];
        $hasMore = true;

        $this->line('2️⃣  Iniciando proceso de sincronización por lotes...');

        while ($hasMore && $synced < $totalToSync) {
            $currentBatchSize = min($chunkSize, $totalToSync - $synced);
            
            $this->line("   📦 Obteniendo lote desde offset {$offset}...");
            $clients = $this->sweetCrmService->getClients($sessionId, [
                'max_results' => $currentBatchSize,
                'offset' => $offset,
            ]);

            if (empty($clients)) {
                $hasMore = false;
                break;
            }

            $this->info("   📊 Procesando " . count($clients) . " clientes...");
            $bar = $this->output->createProgressBar(count($clients));
            $bar->start();

            DB::transaction(function () use ($clients, $bar, &$synced, &$created, &$updated, &$errors) {
                foreach ($clients as $sugarClient) {
                    try {
                        $clientId = $sugarClient['id'] ?? null;
                        $nameValueList = $sugarClient['name_value_list'] ?? [];

                        if (!$clientId) {
                            $bar->advance();
                            continue;
                        }

                        $client = Client::where('sweetcrm_id', $clientId)->first();

                        $name = $nameValueList['name']['value'] ?? 'Sin nombre';
                        $email = $nameValueList['email1']['value'] ?? null;
                        $phone = $nameValueList['phone_office']['value'] ?? null;

                        $industryRaw = $nameValueList['industry']['value'] ?? null;
                        $accountTypeRaw = $nameValueList['account_type']['value'] ?? null;

                        // Traducción de Industrias
                        $industryMap = [
                            'Apparel' => 'Vestuario',
                            'Banking' => 'Banca',
                            'Biotechnology' => 'Biotecnología',
                            'Chemicals' => 'Química',
                            'Communications' => 'Comunicaciones',
                            'Construction' => 'Construcción',
                            'Consulting' => 'Consultoría',
                            'Education' => 'Educación',
                            'Electronics' => 'Electrónica',
                            'Energy' => 'Energía',
                            'Engineering' => 'Ingeniería',
                            'Entertainment' => 'Entretenimiento',
                            'Environmental' => 'Medio Ambiente',
                            'Finance' => 'Finanzas',
                            'Food & Beverage' => 'Alimentos y Bebidas',
                            'Government' => 'Gobierno',
                            'Healthcare' => 'Salud',
                            'Hospitality' => 'Hostelería',
                            'Insurance' => 'Seguros',
                            'Machinery' => 'Maquinaria',
                            'Manufacturing' => 'Manufactura',
                            'Media' => 'Medios',
                            'Not For Profit' => 'Sin fines de lucro',
                            'Recreation' => 'Recreación',
                            'Retail' => 'Retail/Comercio',
                            'Shipping' => 'Transporte/Envíos',
                            'Technology' => 'Tecnología',
                            'Telecommunications' => 'Telecomunicaciones',
                            'Transportation' => 'Transporte',
                            'Utilities' => 'Servicios Públicos',
                            'Other' => 'Otro',
                        ];

                        // Traducción de Tipos de Cuenta
                        $typeMap = [
                            'Analyst' => 'Analista',
                            'Competitor' => 'Competidor',
                            'Customer' => 'Cliente',
                            'Integrator' => 'Integrador',
                            'Investor' => 'Inversor',
                            'Partner' => 'Socio/Partner',
                            'Press' => 'Prensa',
                            'Prospect' => 'Prospecto',
                            'Reseller' => 'Revendedor',
                            'Other' => 'Otro',
                        ];

                        $industryName = $industryMap[$industryRaw] ?? $industryRaw;
                        $accountType = $typeMap[$accountTypeRaw] ?? $accountTypeRaw;

                        // Traducción de Estados (estatusfinanciero_c)
                        $statusMap = [
                            'Activo' => 'active', // Normalizado para Taskflow
                            'anticipo' => 'Anticipo',
                            'Baja' => 'Baja',
                            'esporadico' => 'Esporádico',
                            'Prospecto' => 'Prospecto',
                            'Extrajudicial' => 'Extrajudicial',
                            'cobranza_comercial' => 'Cobranza Comercial',
                            'acuerdo_cobranza_comer' => 'Acuerdo Cobranza',
                            'baja_forzada' => 'Baja Forzada',
                            'Reemplazado' => 'Reemplazado',
                            'suspender' => 'Suspendido',
                            'Suspendido' => 'Suspendido',
                        ];

                        $crmStatus = $nameValueList['estatusfinanciero_c']['value'] ?? 'active';
                        $status = $statusMap[$crmStatus] ?? ($crmStatus ?: 'active');

                        $clientData = [
                            'name' => $name,
                            'industry_id' => $this->findOrCreateIndustry($industryName),
                            'email' => $email,
                            'phone' => $phone,
                            'contact_email' => $email,
                            'contact_phone' => $phone,
                            'address' => $this->formatAddress($nameValueList),
                            'notes' => $nameValueList['description']['value'] ?? null,
                            'sweetcrm_id' => $clientId,
                            'sweetcrm_assigned_user_id' => $nameValueList['assigned_user_id']['value'] ?? null,
                            'account_type' => $accountType,
                            'sweetcrm_synced_at' => now(),
                            'status' => $status,
                        ];

                        if ($client) {
                            $client->update($clientData);
                            $updated++;
                        } else {
                            Client::create($clientData);
                            $created++;
                        }

                        $synced++;
                    } catch (\Exception $e) {
                        $errors[] = [
                            'client' => $nameValueList['name']['value'] ?? 'Unknown',
                            'error' => $e->getMessage(),
                        ];
                    }

                    $bar->advance();
                }
            });

            $bar->finish();
            $this->newLine();
            
            $offset += count($clients);
            if (count($clients) < $currentBatchSize) {
                $hasMore = false;
            }
        }

        $this->newLine();

        // 4. Resumen
        $this->line('📊 Resumen final de sincronización:');
        $this->table(
            ['Métrica', 'Cantidad'],
            [
                ['Total procesados', $synced],
                ['Creados', $created],
                ['Actualizados', $updated],
                ['Errores', count($errors)],
            ]
        );

        if (!empty($errors) && $this->confirm('¿Deseas ver el detalle de los errores?', false)) {
            $this->newLine();
            $this->warn('⚠️  Errores encontrados:');
            foreach ($errors as $error) {
                $this->line("  - {$error['client']}: {$error['error']}");
            }
        }

        $this->newLine();
        $this->info('✅ Sincronización masiva completada');

        return 0;
    }

    protected function formatAddress(array $nameValueList): ?string
    {
        $addressParts = [];

        if (!empty($nameValueList['billing_address_street']['value'])) {
            $addressParts[] = $nameValueList['billing_address_street']['value'];
        }

        if (!empty($nameValueList['billing_address_city']['value'])) {
            $addressParts[] = $nameValueList['billing_address_city']['value'];
        }

        if (!empty($nameValueList['billing_address_country']['value'])) {
            $addressParts[] = $nameValueList['billing_address_country']['value'];
        }

        return !empty($addressParts) ? implode(', ', $addressParts) : null;
    }

    protected function findOrCreateIndustry(?string $industryName): ?int
    {
        if (!$industryName) {
            return null;
        }

        $industry = Industry::firstOrCreate(
            ['name' => $industryName],
            [
                'name' => $industryName,
                'slug' => \Illuminate\Support\Str::slug($industryName)
            ]
        );

        return $industry->id;
    }
}
