<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('crm_cases', function (Blueprint $table) {
            $table->text('internal_notes')->nullable()->comment('Notas internas del caso');
            $table->integer('priority_score')->nullable()->default(0)->comment('Puntuación de prioridad calculada');
            $table->timestamp('last_activity_at')->nullable()->comment('Última actividad en el caso');
            $table->string('account_name')->nullable()->comment('Nombre de la cuenta SweetCRM');
            $table->string('account_number')->nullable()->comment('Número de cuenta');
            $table->string('sla_status')->nullable()->comment('Estado del SLA');
            $table->timestamp('sla_due_date')->nullable()->comment('Fecha de vencimiento del SLA');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('crm_cases', function (Blueprint $table) {
            $table->dropColumn([
                'internal_notes',
                'priority_score',
                'last_activity_at',
                'account_name',
                'account_number',
                'sla_status',
                'sla_due_date'
            ]);
        });
    }
};
