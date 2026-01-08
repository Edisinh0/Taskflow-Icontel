<?php

namespace App\Console\Commands;

use App\Models\CrmOpportunity;
use App\Models\CrmCase;
use App\Models\Task;
use Illuminate\Console\Command;

class VerifySyncedFields extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'verify:synced-fields';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Verify that synced fields are populated correctly';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🔍 Verificando campos sincronizados...');
        $this->newLine();

        // Oportunidades
        $opp_total = CrmOpportunity::count();
        $opp_with_prob = CrmOpportunity::whereNotNull('probability')->where('probability', '>', 0)->count();
        $opp_with_amount_usd = CrmOpportunity::whereNotNull('amount_usd')->count();
        $opp_with_lead_source = CrmOpportunity::whereNotNull('lead_source')->count();
        $opp_with_created_by = CrmOpportunity::whereNotNull('created_by_id')->count();

        $this->info('📊 OPORTUNIDADES');
        $this->line("  Total: {$opp_total}");
        $this->line("  Con Probability: {$opp_with_prob} ({$this->percent($opp_with_prob, $opp_total)}%)");
        $this->line("  Con Amount USD: {$opp_with_amount_usd} ({$this->percent($opp_with_amount_usd, $opp_total)}%)");
        $this->line("  Con Lead Source: {$opp_with_lead_source} ({$this->percent($opp_with_lead_source, $opp_total)}%)");
        $this->line("  Con Created By ID: {$opp_with_created_by} ({$this->percent($opp_with_created_by, $opp_total)}%)");

        $opp_sample = CrmOpportunity::where('probability', '>', 0)->first();
        if ($opp_sample) {
            $this->newLine();
            $this->line('  📌 Ejemplo:');
            $this->line("     Name: {$opp_sample->name}");
            $this->line("     Probability: {$opp_sample->probability}%");
            $this->line("     Amount USD: {$opp_sample->amount_usd}");
            $this->line("     Lead Source: {$opp_sample->lead_source}");
            $this->line("     Created By: {$opp_sample->created_by_name}");
        }

        $this->newLine();
        $this->newLine();

        // Casos
        $case_total = CrmCase::count();
        $case_with_sla_status = CrmCase::whereNotNull('sla_status')->count();
        $case_with_priority = CrmCase::whereNotNull('priority_score')->count();
        $case_with_internal_notes = CrmCase::whereNotNull('internal_notes')->count();
        $case_with_last_activity = CrmCase::whereNotNull('last_activity_at')->count();

        $this->info('📋 CASOS');
        $this->line("  Total: {$case_total}");
        $this->line("  Con SLA Status: {$case_with_sla_status} ({$this->percent($case_with_sla_status, $case_total)}%)");
        $this->line("  Con Priority Score: {$case_with_priority} ({$this->percent($case_with_priority, $case_total)}%)");
        $this->line("  Con Internal Notes: {$case_with_internal_notes} ({$this->percent($case_with_internal_notes, $case_total)}%)");
        $this->line("  Con Last Activity At: {$case_with_last_activity} ({$this->percent($case_with_last_activity, $case_total)}%)");

        $case_sample = CrmCase::whereNotNull('priority_score')->first();
        if ($case_sample) {
            $this->newLine();
            $this->line('  📌 Ejemplo:');
            $this->line("     Name: {$case_sample->name}");
            $this->line("     Priority Score: {$case_sample->priority_score}");
            $this->line("     SLA Status: {$case_sample->sla_status}");
            $this->line("     Internal Notes: " . substr($case_sample->internal_notes ?? 'N/A', 0, 50) . "...");
        }

        $this->newLine();
        $this->newLine();

        // Tareas
        $task_total = Task::count();
        $task_with_date_entered = Task::whereNotNull('date_entered')->count();
        $task_with_created_by = Task::whereNotNull('created_by_id')->count();
        $task_with_sweetcrm_parent = Task::whereNotNull('sweetcrm_parent_id')->count();

        $this->info('✓ TAREAS');
        $this->line("  Total: {$task_total}");
        $this->line("  Con Date Entered: {$task_with_date_entered} ({$this->percent($task_with_date_entered, $task_total)}%)");
        $this->line("  Con Created By ID: {$task_with_created_by} ({$this->percent($task_with_created_by, $task_total)}%)");
        $this->line("  Con SweetCRM Parent ID: {$task_with_sweetcrm_parent} ({$this->percent($task_with_sweetcrm_parent, $task_total)}%)");

        $task_sample = Task::whereNotNull('date_entered')->first();
        if ($task_sample) {
            $this->newLine();
            $this->line('  📌 Ejemplo:');
            $this->line("     Name: {$task_sample->name}");
            $this->line("     Date Entered: {$task_sample->date_entered}");
            $this->line("     Created By: {$task_sample->created_by_id}");
        }

        $this->newLine();
        $this->info('✅ Verificación completada');

        return 0;
    }

    private function percent($value, $total)
    {
        return $total > 0 ? round(($value / $total) * 100, 2) : 0;
    }
}
