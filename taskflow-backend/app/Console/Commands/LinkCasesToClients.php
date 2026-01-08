<?php

namespace App\Console\Commands;

use App\Models\CrmCase;
use App\Models\Client;
use Illuminate\Console\Command;

class LinkCasesToClients extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'cases:link-to-clients';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Link existing cases to clients based on sweetcrm_account_id';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🔗 Vinculando casos a clientes...');
        $this->newLine();

        // Obtener todos los casos sin cliente
        $casesWithoutClient = CrmCase::whereNull('client_id')
            ->whereNotNull('sweetcrm_account_id')
            ->get();

        $this->info("Total casos sin cliente: {$casesWithoutClient->count()}");
        $this->newLine();

        $bar = $this->output->createProgressBar($casesWithoutClient->count());
        $bar->start();

        $linked = 0;
        $notFound = 0;

        foreach ($casesWithoutClient as $case) {
            // Buscar el cliente por sweetcrm_account_id
            $client = Client::where('sweetcrm_id', $case->sweetcrm_account_id)->first();

            if ($client) {
                $case->update(['client_id' => $client->id]);
                $linked++;
            } else {
                $notFound++;
            }

            $bar->advance();
        }

        $bar->finish();
        $this->newLine();
        $this->newLine();

        $this->info("✅ Casos vinculados: {$linked}");
        $this->info("⚠️  Clientes no encontrados: {$notFound}");
        $this->newLine();

        return 0;
    }
}
