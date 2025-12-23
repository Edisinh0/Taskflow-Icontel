<?php

namespace App\Observers;

use App\Models\Task;
use Illuminate\Support\Facades\Log;

class TaskLifecycleObserver
{
    /**
     * Handle the Task "saving" event.
     */
    public function saving(Task $task): void
    {
        // Registrar quién modifica (Creación o Actualización)
        if (auth()->check()) {
            $task->last_updated_by = auth()->id();
        }

        // Solo ejecutar en creación, no en actualización
        if ($task->exists) {
            return;
        }

        Log::info('🔧 TaskLifecycleObserver::saving()', [
            'task_id' => $task->id ?? 'nuevo',
            'title' => $task->title,
        ]);

        try {
            // Si tiene dependencias, la tarea DEBE estar bloqueada al inicio
            if ($task->depends_on_task_id || $task->depends_on_milestone_id) {
                $task->is_blocked = true;
            } else {
                $task->is_blocked = false;
            }
        } catch (\Exception $e) {
            Log::error('❌ Error en TaskLifecycleObserver::saving()', ['error' => $e->getMessage()]);
            throw $e;
        }
    }
}
