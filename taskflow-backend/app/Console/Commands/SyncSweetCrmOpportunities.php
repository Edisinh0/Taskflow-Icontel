<?php

namespace App\Console\Commands;

use App\Models\CrmOpportunity;
use App\Models\Client;
use App\Services\SweetCrmService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class SyncSweetCrmOpportunities extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sweetcrm:sync-opportunities 
                            {username? : SugarCRM username} 
                            {password? : SugarCRM password} 
                            {--limit=0 : Maximum number of opportunities to sync}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sincronizar Oportunidades desde SugarCRM a Taskflow';

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
        $this->info('🔄 Iniciando sincronización de Oportunidades desde SugarCRM...');
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

        // 2. Sincronizar Oportunidades
        $this->line('2️⃣  Sincronizando Oportunidades...');
        $this->syncOpportunities($sessionId, $limitOption);
        $this->newLine();

        $this->info('✅ Sincronización de Oportunidades completada.');
        return 0;
    }

    protected function syncOpportunities(string $sessionId, int $limit)
    {
        $offset = 0;
        $chunkSize = 250;
        $synced = 0;
        $maxToSync = $limit > 0 ? $limit : 100000;

        // Estados que se consideran "cerrados" o inactivos
        $closedStatuses = ['Closed Lost', 'Closed Won'];

        // Mapeo de probabilidades por etapa
        $stageToMaybeProbability = [
            'Prospecting' => 10,
            'Qualification' => 25,
            'Needs Analysis' => 40,
            'Value Proposition' => 50,
            'Id. Decision Makers' => 60,
            'Perception Analysis' => 70,
            'Proposal/Price Quote' => 75,
            'Negotiation/Review' => 85,
            'Verbal Agreement' => 90,
            'Closed Won' => 100,
            'Closed Lost' => 0,
        ];

        // Obtener IDs de oportunidades sincronizadas para detectar eliminadas
        $syncedCrmIds = [];

        while ($synced < $maxToSync) {
            // Obtener todas las oportunidades activas (no cerradas)
            $entries = $this->sweetCrmService->getOpportunities($sessionId, [
                'offset' => $offset,
                'max_results' => $chunkSize,
                'query' => "(opportunities.sales_stage NOT IN ('Closed Lost', 'Closed Won'))",
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

                    // Obtener etapa y mapear probabilidad
                    $salesStage = $nvl['sales_stage']['value'] ?? 'Prospecting';
                    $probability = $stageToMaybeProbability[$salesStage] ?? 50;
                    
                    // Si hay probabilidad explícita, usarla
                    $explicitProbability = $nvl['probability']['value'] ?? null;
                    if ($explicitProbability !== null) {
                        $probability = (int)$explicitProbability;
                    }

                    // Validar y limpiar valores
                    $amount = $nvl['amount']['value'] ?? null;
                    $amount = (!empty($amount) && is_numeric($amount)) ? (float)$amount : 0;
                    
                    $amountUsd = $nvl['amount_usdollar']['value'] ?? null;
                    $amountUsd = (!empty($amountUsd) && is_numeric($amountUsd)) ? (float)$amountUsd : null;
                    
                    $dateClosedRaw = $nvl['date_closed']['value'] ?? null;
                    $dateClosed = (!empty($dateClosedRaw) && $dateClosedRaw !== '') ? $dateClosedRaw : null;
                    
                    $dateEnteredRaw = $nvl['date_entered']['value'] ?? null;
                    $dateEntered = (!empty($dateEnteredRaw) && $dateEnteredRaw !== '') ? $dateEnteredRaw : null;
                    
                    $dateModifiedRaw = $nvl['date_modified']['value'] ?? null;
                    $dateModified = (!empty($dateModifiedRaw) && $dateModifiedRaw !== '') ? $dateModifiedRaw : null;

                    $opportunity = CrmOpportunity::updateOrCreate(
                        ['sweetcrm_id' => $sweetId],
                        [
                            'name' => $nvl['name']['value'] ?? 'Sin nombre',
                            'description' => $nvl['description']['value'] ?? null,
                            'sales_stage' => $salesStage,
                            'amount' => $amount,
                            'amount_usd' => $amountUsd,
                            'currency' => $nvl['currency_id']['value'] ?? 'CLP',
                            'probability' => $probability,
                            'expected_closed_date' => $dateClosed,
                            'client_id' => $client?->id,
                            'sweetcrm_assigned_user_id' => $nvl['assigned_user_id']['value'] ?? null,
                            'created_by_id' => $nvl['created_by']['value'] ?? null,
                            'created_by_name' => $nvl['created_by_name']['value'] ?? null,
                            'next_step' => $nvl['next_step']['value'] ?? null,
                            'lead_source' => $nvl['lead_source']['value'] ?? null,
                            'opportunity_type' => $nvl['opportunity_type']['value'] ?? null,
                            'date_entered' => $dateEntered,
                            'date_modified' => $dateModified,
                            'sweetcrm_synced_at' => now(),
                        ]
                    );

                    $synced++;
                } catch (\Exception $e) {
                    $this->error("\n Error en Oportunidad {$entry['id']}: " . $e->getMessage());
                    Log::error('Error syncing opportunity', [
                        'opportunity_id' => $entry['id'] ?? 'unknown',
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

        // Marcar como cerradas las oportunidades que ya no están activas en CRM
        if (!empty($syncedCrmIds)) {
            $this->line('   🔄 Verificando oportunidades cerradas/eliminadas en CRM...');
            $oppToMarkClosed = CrmOpportunity::whereNotNull('sweetcrm_id')
                ->whereNotIn('sweetcrm_id', $syncedCrmIds)
                ->whereNotIn('sales_stage', ['Closed Won', 'Closed Lost'])
                ->count();

            if ($oppToMarkClosed > 0) {
                CrmOpportunity::whereNotNull('sweetcrm_id')
                    ->whereNotIn('sweetcrm_id', $syncedCrmIds)
                    ->whereNotIn('sales_stage', ['Closed Won', 'Closed Lost'])
                    ->update(['sales_stage' => 'Closed Lost', 'sweetcrm_synced_at' => now()]);

                $this->info("   ⚠️  Marcadas como cerradas: $oppToMarkClosed oportunidades (no activas en CRM)");
            }
        }

        $this->info("   📊 Total oportunidades sincronizadas: $synced");
    }
}
