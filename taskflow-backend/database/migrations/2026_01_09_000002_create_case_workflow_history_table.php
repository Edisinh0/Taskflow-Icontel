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
        Schema::create('case_workflow_history', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('case_id');

            // Transición de estado
            $table->string('from_status');
            $table->string('to_status');

            // Acción que generó la transición
            $table->string('action'); // delegate, handover_to_validation, approve, reject, close

            // Usuario que realizó la acción
            $table->unsignedBigInteger('performed_by_id');

            // Detalles adicionales
            $table->text('notes')->nullable();
            $table->text('reason')->nullable(); // Razón del rechazo, etc.

            // SuiteCRM sync info
            $table->string('sweetcrm_sync_status')->default('pending'); // pending, synced, failed
            $table->text('sweetcrm_sync_response')->nullable();
            $table->timestamp('sweetcrm_synced_at')->nullable();

            $table->timestamps();

            // Foreign keys
            $table->foreign('case_id')->references('id')->on('crm_cases')->onDelete('cascade');
            $table->foreign('performed_by_id')->references('id')->on('users')->onDelete('restrict');

            // Indexes
            $table->index('case_id');
            $table->index('performed_by_id');
            $table->index('action');
            $table->index('sweetcrm_sync_status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('case_workflow_history');
    }
};
