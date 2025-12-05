<?php

namespace App\Observers;

use App\Models\Task;
use Illuminate\Support\Facades\Log;

class TaskObserver
{
    /**
     * Handle the Task "updated" event.
     * Dispara la lógica de liberación en cascada al completar una tarea.
     */
    public function updated(Task $task): void
    {
        // 1. Solo actuamos si el estado cambió A 'completed'
        if ($task->isDirty('status') && $task->status === 'completed') {
            Log::info("Tarea {$task->id} completada. Iniciando liberación de dependientes.");
            
            // 2. Liberar tareas que dependían DIRECTAMENTE de esta tarea.
            Task::where('depends_on_task_id', $task->id)
                ->where('is_blocked', true)
                ->get()
                ->each(fn (Task $dependentTask) => $this->checkAndUnlock($dependentTask));
            
            // 3. Liberar tareas que dependían de esta tarea por ser un MILESTONE completado.
            if ($task->is_milestone) {
                 Task::where('depends_on_milestone_id', $task->id)
                     ->where('is_blocked', true)
                     ->get()
                     ->each(fn (Task $dependentTask) => $this->checkAndUnlock($dependentTask));
            }
        }
        
        // 4. Lógica de Re-bloqueo: Si se reabre una tarea (status != completed)
        if ($task->isDirty('status') && $task->status !== 'completed' && $task->getOriginal('status') === 'completed') {
            
            Log::warning("Tarea {$task->id} reabierta. Re-bloqueando dependientes.");
            
            // Re-bloqueamos las tareas que dependían de esta
            Task::where('depends_on_task_id', $task->id)
                ->where('is_blocked', false)
                ->update(['is_blocked' => true]);
            
            // Re-bloqueamos las tareas que dependían de este milestone
            if ($task->is_milestone) {
                Task::where('depends_on_milestone_id', $task->id)
                    ->where('is_blocked', false)
                    ->update(['is_blocked' => true]);
            }
        }
    }

    /**
     * Verifica si TODAS las dependencias de una tarea se han cumplido y la desbloquea.
     */
    protected function checkAndUnlock(Task $task): void
    {
        $canUnlock = true;
        
        // Verificar dependencia de Tarea Precedente
        if ($task->depends_on_task_id) {
            $parentTask = Task::find($task->depends_on_task_id);
            if ($parentTask && $parentTask->status !== 'completed') {
                $canUnlock = false;
            }
        }
        
        // Verificar dependencia de Hito
        if ($task->depends_on_milestone_id) {
            $milestoneTask = Task::find($task->depends_on_milestone_id);
            if ($milestoneTask && $milestoneTask->status !== 'completed') {
                $canUnlock = false;
            }
        }
        
        // Si no hay dependencias pendientes Y la tarea está bloqueada, la liberamos.
        if ($canUnlock && $task->is_blocked) {
            $task->update(['is_blocked' => false]);
            // Opcional: event(new TaskUnlocked($task)); para WebSockets
        }
    }
}