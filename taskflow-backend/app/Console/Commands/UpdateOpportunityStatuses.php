<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\CrmOpportunity;

class UpdateOpportunityStatuses extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:update-opportunity-statuses';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Actualizar estados de oportunidades basado en sales_stage';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        // Mapeo de sales_stage a status
        $stageMapping = [
            // Etapas activas
            'Prospecting' => 'active',
            'Qualification' => 'active',
            'Needs Analysis' => 'active',
            'Value Proposition' => 'active',
            'Id. Decision Makers' => 'active',
            'Perception Analysis' => 'active',
            'Proposal/Price Quote' => 'active',
            'Negotiation/Review' => 'active',
            'Verbal Agreement' => 'active',

            // Etapas cerradas
            'Closed Won' => 'closed_won',
            'Closed Lost' => 'closed_lost',
        ];

        $opportunities = CrmOpportunity::all();
        $updated = 0;

        foreach ($opportunities as $opp) {
            $status = $stageMapping[$opp->sales_stage] ?? 'active';

            if ($opp->status !== $status) {
                $opp->update(['status' => $status]);
                $updated++;
            }
        }

        $this->info("✅ Se actualizaron {$updated} oportunidades");
    }
}
