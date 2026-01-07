<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Tabla para registrar avances/updates en casos
     */
    public function up(): void
    {
        Schema::create('case_updates', function (Blueprint $table) {
            $table->id();
            
            // Relación con el caso
            $table->foreignId('case_id')->constrained('crm_cases')->cascadeOnDelete();
            
            // Usuario que registró el avance
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            
            // Contenido del avance
            $table->text('content');
            
            // Tipo de actualización (normal, closure_request, closure_approved, closure_rejected)
            $table->enum('type', ['update', 'closure_request', 'closure_approved', 'closure_rejected'])
                  ->default('update');
            
            // Tarea relacionada (opcional, si el avance es sobre una tarea específica)
            $table->foreignId('task_id')->nullable()->constrained('tasks')->nullOnDelete();
            
            $table->timestamps();
            
            // Índices
            $table->index(['case_id', 'created_at']);
            $table->index('type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('case_updates');
    }
};
