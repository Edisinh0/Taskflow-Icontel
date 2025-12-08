<?php

namespace App\Observers;

use App\Models\Task;
use App\Services\NotificationService;
use Illuminate\Support\Facades\Log;

class TaskObserver
{
    /**
     * Handle the Task "saving" event.
     * IMPORTANTE: Usamos 'saving' en lugar de 'creating' para asegurar que
     * el valor se establezca DESPUÉS de que todos los atributos estén asignados
     * pero ANTES del INSERT en la base de datos.
     */
    public function saving(Task $task): void
    {
        // Solo ejecutar en creación, no en actualización
        if ($task->exists) {
            return;
        }

        Log::info('🔧 TaskObserver::saving() ejecutándose', [
            'task_id' => $task->id ?? 'nuevo',
            'title' => $task->title,
            'depends_on_task_id' => $task->depends_on_task_id,
            'depends_on_milestone_id' => $task->depends_on_milestone_id,
            'is_blocked_ANTES' => $task->is_blocked ?? 'null',
        ]);

        try {
            // Si tiene dependencias, la tarea DEBE estar bloqueada al inicio
            if ($task->depends_on_task_id || $task->depends_on_milestone_id) {
                $task->is_blocked = true;
                Log::info('🔒 Tarea será creada BLOQUEADA', [
                    'is_blocked_DESPUES' => $task->is_blocked,
                    'attributes' => $task->getAttributes(),
                ]);
            } else {
                // Sin dependencias, la tarea está libre
                $task->is_blocked = false;
                Log::info('🔓 Tarea será creada LIBRE', [
                    'is_blocked_DESPUES' => $task->is_blocked,
                ]);
            }
        } catch (\Exception $e) {
            Log::error('❌ Error en TaskObserver::saving()', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }
    }

    /**
     * Handle the Task "updating" event.
     * Recalcula is_blocked cuando cambian las dependencias.
     * Calcula progress automáticamente basado en el estado.
     * Genera notificaciones automáticas.
     */
    public function updating(Task $task): void
    {
        // 1. Recalcular is_blocked si cambiaron las dependencias
        if ($task->isDirty('depends_on_task_id') || $task->isDirty('depends_on_milestone_id')) {
            Log::info('🔄 Dependencias cambiaron, recalculando is_blocked', [
                'task_id' => $task->id,
                'depends_on_task_id' => $task->depends_on_task_id,
                'depends_on_milestone_id' => $task->depends_on_milestone_id,
            ]);

            // Verificar si todas las dependencias están completadas
            $shouldBeBlocked = false;

            if ($task->depends_on_task_id) {
                $precedentTask = Task::find($task->depends_on_task_id);
                if ($precedentTask && $precedentTask->status !== 'completed') {
                    $shouldBeBlocked = true;
                    Log::info("⏸️ Tarea precedente {$precedentTask->id} no completada");
                }
            }

            if ($task->depends_on_milestone_id) {
                $milestone = Task::find($task->depends_on_milestone_id);
                if ($milestone && $milestone->status !== 'completed') {
                    $shouldBeBlocked = true;
                    Log::info("⏸️ Milestone {$milestone->id} no completado");
                }
            }

            $task->is_blocked = $shouldBeBlocked;
            Log::info($shouldBeBlocked ? '🔒 Tarea bloqueada' : '🔓 Tarea desbloqueada', [
                'is_blocked' => $task->is_blocked
            ]);
        }

        // 2. Detectar cambio en is_blocked para notificaciones
        if ($task->isDirty('is_blocked')) {
            $wasBlocked = $task->getOriginal('is_blocked');
            $isNowBlocked = $task->is_blocked;

            if (!$wasBlocked && $isNowBlocked) {
                // Se bloqueó
                NotificationService::taskBlocked($task);
            } elseif ($wasBlocked && !$isNowBlocked) {
                // Se desbloqueó
                NotificationService::taskUnblocked($task);
            }
        }

        // 3. Detectar cambio de asignado
        if ($task->isDirty('assignee_id')) {
            $newAssigneeId = $task->assignee_id;
            if ($newAssigneeId) {
                NotificationService::taskAssigned($task, $newAssigneeId);
            }
        }

        // 4. Calcular progress automáticamente basado en el estado
        if ($task->isDirty('status')) {
            $oldProgress = $task->progress;
            
            switch ($task->status) {
                case 'pending':
                    $task->progress = 0;
                    break;
                case 'in_progress':
                    // Si está en progreso y tenía 0%, ponerlo en 50%
                    // Si ya tenía progreso, mantenerlo (permite ajustes manuales)
                    if ($task->progress === 0) {
                        $task->progress = 50;
                    }
                    break;
                case 'completed':
                    $task->progress = 100;
                    break;
                case 'cancelled':
                    $task->progress = 0;
                    break;
                case 'paused':
                    // Mantener el progreso actual
                    break;
            }

            if ($oldProgress !== $task->progress) {
                Log::info("📊 Progress auto-calculado: {$oldProgress}% → {$task->progress}%", [
                    'task_id' => $task->id,
                    'status' => $task->status
                ]);
            }
        }
    }
    /**
     * Handle the Task "updated" event.
     * Dispara la liberación en cascada al completar una tarea.
     * Genera notificaciones de tarea/milestone completado.
     */
    public function updated(Task $task): void
    {
        // 1. Solo actuamos si el estado cambió A 'completed'
        if ($task->isDirty('status') && $task->status === 'completed') {
            Log::info('✅ Tarea completada, liberando dependientes', [
                'task_id' => $task->id,
                'title' => $task->title,
            ]);

            // 🔔 Generar notificación de tarea completada
            NotificationService::taskCompleted($task);

            // 🔔 Si es milestone, generar notificación especial
            if ($task->is_milestone) {
                NotificationService::milestoneCompleted($task);
            }

            // 2. Buscar tareas que dependían de esta (como tarea precedente)
            $taskDependents = Task::where('depends_on_task_id', $task->id)->get();
            Log::info("📊 Encontradas {$taskDependents->count()} tareas dependientes (depends_on_task_id)");

            foreach ($taskDependents as $dependent) {
                Log::info("🔍 Procesando tarea dependiente {$dependent->id}: {$dependent->title}");
                $this->checkAndUnlock($dependent);
            }

            // 3. Buscar tareas que dependían de esta (como milestone)
            $milestoneDependents = Task::where('depends_on_milestone_id', $task->id)->get();
            Log::info("📊 Encontradas {$milestoneDependents->count()} tareas dependientes (depends_on_milestone_id)");

            foreach ($milestoneDependents as $dependent) {
                Log::info("🔍 Procesando tarea dependiente de milestone: {$dependent->id}");
                $this->checkAndUnlock($dependent);
            }
        }
        
        // 4. Lógica de Re-bloqueo: Si se reabre una tarea completada
        if ($task->isDirty('status') && 
            $task->status !== 'completed' && 
            $task->getOriginal('status') === 'completed') {
            
            Log::warning("⚠️ Tarea {$task->id} reabierta. Re-bloqueando dependientes.");
            
            // Re-bloquear las tareas que dependían de esta
            Task::where('depends_on_task_id', $task->id)
                ->where('is_blocked', false)
                ->update(['is_blocked' => true]);
            
            // Re-bloquear las tareas que dependían de este milestone
            Task::where('depends_on_milestone_id', $task->id)
                ->where('is_blocked', false)
                ->update(['is_blocked' => true]);
        }
    }

    /**
     * Verifica si TODAS las dependencias de una tarea se han cumplido y la desbloquea.
     */
    protected function checkAndUnlock(Task $task): void
    {
        // Refrescar la tarea desde la base de datos para evitar datos obsoletos
        $task->refresh();
        
        $canUnlock = true;
        
        // Verificar dependencia de Tarea Precedente
        if ($task->depends_on_task_id) {
            $parentTask = Task::find($task->depends_on_task_id);
            if ($parentTask && $parentTask->status !== 'completed') {
                $canUnlock = false;
                Log::info("⏸️ Tarea {$task->id} sigue bloqueada por tarea precedente {$parentTask->id}");
            }
        }
        
        // Verificar dependencia de Hito
        if ($task->depends_on_milestone_id) {
            $milestoneTask = Task::find($task->depends_on_milestone_id);
            if ($milestoneTask && $milestoneTask->status !== 'completed') {
                $canUnlock = false;
                Log::info("⏸️ Tarea {$task->id} sigue bloqueada por milestone {$milestoneTask->id}");
            }
        }
        
        // Si no hay dependencias pendientes Y la tarea está bloqueada, la liberamos
        if ($canUnlock && $task->is_blocked) {
            $task->update(['is_blocked' => false]);
            Log::info("🔓 Tarea {$task->id} desbloqueada.");
        }
    }
}