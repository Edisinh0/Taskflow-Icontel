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
            // Campo de control: Por defecto, toda tarea es bloqueada al ser creada.
            $table->boolean('is_blocked')->default(true)->after('status')->comment('Indica si la tarea está bloqueada por dependencia.');
            
            // Dependencia de Tarea (Task-to-Task)
            $table->foreignId('depends_on_task_id')
                  ->nullable()
                  ->after('is_milestone')
                  ->constrained('tasks') // Apunta a la misma tabla tasks
                  ->onDelete('set null'); // Si la tarea precedente es borrada, la dependencia se elimina.
            
            // Dependencia de Hito (Milestone)
            // Asumimos que los milestones son tareas con is_milestone=true.
            $table->foreignId('depends_on_milestone_id')
                  ->nullable()
                  ->after('depends_on_task_id')
                  ->constrained('tasks')
                  ->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tasks', function (Blueprint $table) {
            // Eliminar las claves foráneas antes que las columnas.
            $table->dropForeign(['depends_on_task_id']);
            $table->dropForeign(['depends_on_milestone_id']);
            
            $table->dropColumn(['is_blocked', 'depends_on_task_id', 'depends_on_milestone_id']);
        });
    }
};