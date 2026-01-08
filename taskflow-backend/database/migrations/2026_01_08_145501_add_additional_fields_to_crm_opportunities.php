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
        Schema::table('crm_opportunities', function (Blueprint $table) {
            $table->integer('probability')->nullable()->default(0)->comment('Probabilidad de cierre 0-100');
            $table->decimal('amount_usd', 15, 2)->nullable()->comment('Monto en USD');
            $table->string('created_by_id')->nullable()->comment('ID creador en SweetCRM');
            $table->string('created_by_name')->nullable()->comment('Nombre del creador');
            $table->text('next_step')->nullable()->comment('Próximo paso a realizar');
            $table->string('lead_source')->nullable()->comment('Fuente del lead');
            $table->string('opportunity_type')->nullable()->comment('Tipo de oportunidad');
            $table->timestamp('date_entered')->nullable()->comment('Fecha creación en SweetCRM');
            $table->timestamp('date_modified')->nullable()->comment('Fecha última modificación en SweetCRM');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('crm_opportunities', function (Blueprint $table) {
            $table->dropColumn([
                'probability',
                'amount_usd',
                'created_by_id',
                'created_by_name',
                'next_step',
                'lead_source',
                'opportunity_type',
                'date_entered',
                'date_modified'
            ]);
        });
    }
};
