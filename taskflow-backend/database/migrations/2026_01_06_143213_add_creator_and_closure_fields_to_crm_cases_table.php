<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Agrega campos para creador original, solicitud de cierre y permisos
     */
    public function up(): void
    {
        Schema::table('crm_cases', function (Blueprint $table) {
            // Información del creador (desde SuiteCRM)
            $table->string('original_creator_id')->nullable()->after('sweetcrm_assigned_user_id');
            $table->string('original_creator_name')->nullable()->after('original_creator_id');
            
            // Información del usuario asignado (nombre para display)
            $table->string('assigned_user_name')->nullable()->after('original_creator_name');
            
            // Sistema de solicitud de cierre
            $table->boolean('closure_requested')->default(false)->after('status');
            $table->timestamp('closure_requested_at')->nullable()->after('closure_requested');
            $table->foreignId('closure_requested_by')->nullable()->after('closure_requested_at')
                  ->constrained('users')->nullOnDelete();
            $table->text('closure_rejection_reason')->nullable()->after('closure_requested_by');
            
            // Índices para búsquedas
            $table->index('closure_requested');
            $table->index('original_creator_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('crm_cases', function (Blueprint $table) {
            $table->dropIndex(['closure_requested']);
            $table->dropIndex(['original_creator_id']);
            
            $table->dropForeign(['closure_requested_by']);
            $table->dropColumn([
                'original_creator_id',
                'original_creator_name',
                'assigned_user_name',
                'closure_requested',
                'closure_requested_at',
                'closure_requested_by',
                'closure_rejection_reason'
            ]);
        });
    }
};
