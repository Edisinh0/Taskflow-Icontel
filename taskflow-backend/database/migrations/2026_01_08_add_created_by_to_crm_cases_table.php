<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Agrega el campo created_by para vincular casos con el usuario que los creó
     */
    public function up(): void
    {
        Schema::table('crm_cases', function (Blueprint $table) {
            // Campo para referenciar al usuario local que creó el caso
            $table->foreignId('created_by')->nullable()->after('id')
                  ->constrained('users')->nullOnDelete();

            // Índice para búsquedas de casos delegados
            $table->index('created_by');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('crm_cases', function (Blueprint $table) {
            $table->dropIndex(['created_by']);
            $table->dropForeign(['created_by']);
            $table->dropColumn('created_by');
        });
    }
};
