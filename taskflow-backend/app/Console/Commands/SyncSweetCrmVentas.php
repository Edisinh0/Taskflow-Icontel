<?php

namespace App\Console\Commands;

use App\Models\CrmOpportunity;
use App\Models\CrmQuote;
use App\Models\Client;
use App\Services\SweetCrmService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SyncSweetCrmVentas extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'sweetcrm:sync-ventas
                            {username? : SweetCRM username}
                            {password? : SweetCRM password}
                            {--limit=50 : Maximum number of entries per batch}';

    /**
     * The console command description.
     */
    protected $description = 'Sincronizar Oportunidades y Cotizaciones desde SweetCRM';

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
        $this->info('🔄 Iniciando sincronización de Ventas (Oportunidades y Cotizaciones)...');

        $username = $this->argument('username') ?? config('services.sweetcrm.username');
        $password = $this->argument('password') ?? config('services.sweetcrm.password');

        if (!$username || !$password) {
            $this->error('❌ Credenciales no proporcionadas ni encontradas en .env');
            return 1;
        }

        // 1. Autenticar
        $sessionId = $this->sweetCrmService->getSessionId($username, $password);
        if (!$sessionId) {
            $this->error('❌ Error de autenticación en SweetCRM.');
            return 1;
        }

        $this->info('✅ Autenticación exitosa.');

        // 2. Sincronizar Oportunidades
        $this->syncOpportunities($sessionId);

        // 3. Sincronizar Cotizaciones
        $this->syncQuotes($sessionId);

        $this->info('✅ Sincronización de Ventas completada.');
        return 0;
    }

    protected function syncOpportunities(string $sessionId)
    {
        $this->line('📦 Sincronizando Oportunidades...');
        
        $opportunities = $this->sweetCrmService->getOpportunities($sessionId, [
            'max_results' => $this->option('limit'),
            'order_by' => 'date_modified DESC'
        ]);

        $bar = $this->output->createProgressBar(count($opportunities));
        $bar->start();

        foreach ($opportunities as $oppData) {
            $oppId = $oppData['id'];
            $nvl = $oppData['name_value_list'];

            // Buscar cliente local
            $accountId = $nvl['account_id']['value'] ?? null;
            $client = Client::where('sweetcrm_id', $accountId)->first();

            // Limpiar valores numéricos y fechas
            $amount = $nvl['amount']['value'] ?? 0;
            $amount = is_numeric($amount) ? $amount : 0;
            
            $closedDate = $nvl['date_closed']['value'] ?? null;
            if ($closedDate === '') $closedDate = null;

            CrmOpportunity::updateOrCreate(
                ['sweetcrm_id' => $oppId],
                [
                    'name' => $nvl['name']['value'] ?? 'Sin nombre',
                    'sales_stage' => $nvl['sales_stage']['value'] ?? null,
                    'amount' => $amount,
                    'currency' => $nvl['currency_id']['value'] ?? 'CLP',
                    'expected_closed_date' => $closedDate,
                    'client_id' => $client?->id,
                    'sweetcrm_assigned_user_id' => $nvl['assigned_user_id']['value'] ?? null,
                    'description' => $nvl['description']['value'] ?? null,
                    'sweetcrm_synced_at' => now(),
                ]
            );
            $bar->advance();
        }

        $bar->finish();
        $this->newLine();
    }

    protected function syncQuotes(string $sessionId)
    {
        $this->line('📦 Sincronizando Cotizaciones...');

        $quotes = $this->sweetCrmService->getQuotes($sessionId, [
            'max_results' => $this->option('limit'),
            'order_by' => 'date_modified DESC'
        ]);

        $bar = $this->output->createProgressBar(count($quotes));
        $bar->start();

        foreach ($quotes as $quoteData) {
            $quoteId = $quoteData['id'];
            $nvl = $quoteData['name_value_list'];

            // Buscar oportunidad local
            $oppId = $nvl['opportunity_id']['value'] ?? null;
            $opportunity = CrmOpportunity::where('sweetcrm_id', $oppId)->first();

            // Buscar cliente local
            $accountId = $nvl['billing_account_id']['value'] ?? null;
            $client = Client::where('sweetcrm_id', $accountId)->first();

            // Limpiar valores numéricos
            $totalAmount = $nvl['total']['value'] ?? 0;
            $totalAmount = is_numeric($totalAmount) ? $totalAmount : 0;

            CrmQuote::updateOrCreate(
                ['sweetcrm_id' => $quoteId],
                [
                    'quote_number' => $nvl['quote_num']['value'] ?? null,
                    'subject' => $nvl['name']['value'] ?? null,
                    'status' => $nvl['quote_stage']['value'] ?? null,
                    'total_amount' => $totalAmount,
                    'opportunity_id' => $opportunity?->id,
                    'client_id' => $client?->id,
                    'description' => $nvl['description']['value'] ?? null,
                    'sweetcrm_synced_at' => now(),
                ]
            );
            $bar->advance();
        }

        $bar->finish();
        $this->newLine();
    }
}
