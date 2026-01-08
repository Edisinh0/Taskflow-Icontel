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
        Schema::table('tasks', function (Blueprint $table) {
            // opportunity_id ya existe en la tabla
            $table->string('sweetcrm_parent_id')->nullable()->comment('ID padre en SweetCRM');
            $table->string('sweetcrm_parent_type')->nullable()->comment('Tipo de padre: Cases, Opportunities');
            
            // Agregar campos de fecha si no existen
            if (!Schema::hasColumn('tasks', 'date_entered')) {
                $table->timestamp('date_entered')->nullable()->comment('Fecha creación en SweetCRM');
            }
            if (!Schema::hasColumn('tasks', 'date_modified')) {
                $table->timestamp('date_modified')->nullable()->comment('Fecha última modificación en SweetCRM');
            }
            
            $table->integer('sequence')->nullable()->default(0)->comment('Secuencia de ejecución');
            // is_milestone ya existe en la tabla
            $table->string('created_by_id')->nullable()->comment('ID del creador en SweetCRM');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tasks', function (Blueprint $table) {
            $table->dropColumn([
                'sweetcrm_parent_id',
                'sweetcrm_parent_type',
                'date_entered',
                'date_modified',
                'sequence',
                'created_by_id'
            ]);
        });
    }
};
