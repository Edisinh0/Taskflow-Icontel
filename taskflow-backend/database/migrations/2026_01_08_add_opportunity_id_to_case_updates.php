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
        Schema::table('case_updates', function (Blueprint $table) {
            // Agregar columna opportunity_id para soportar actualizaciones de oportunidades
            $table->foreignId('opportunity_id')->nullable()->constrained('crm_opportunities')->cascadeOnDelete();
            
            // Índice para búsquedas rápidas
            $table->index(['opportunity_id', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('case_updates', function (Blueprint $table) {
            $table->dropForeignIdFor('crm_opportunities');
            $table->dropIndex(['opportunity_id', 'created_at']);
        });
    }
};
