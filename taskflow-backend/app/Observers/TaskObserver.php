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
        // Registrar quién modifica (Creación o Actualización)
        if (auth()->check()) {
            $task->last_updated_by = auth()->id();
        }

        // Solo ejecutar en creación, no en actualización
        if ($task->exists) {
            return;
        }

        Log::info('🔧 TaskObserver::saving() ejecutándose', [
            'task_id' => $task->id ?? 'nuevo',
        'title' => $task->title,
        'parent_task_id' => $task->parent_task_id,
        'depends_on_task_id' => $task->depends_on_task_id,
        'depends_on_milestone_id' => $task->depends_on_milestone_id,
    ]);

    try {
        // Si tiene dependencias, la tarea DEBE estar bloqueada al inicio
        if ($task->depends_on_task_id || $task->depends_on_milestone_id) {
            $task->is_blocked = true;
            Log::info('🔒 Tarea será creada BLOQUEADA');
        } else {
            // Sin dependencias, la tarea está libre
            $task->is_blocked = false;
            Log::info('🔓 Tarea será creada LIBRE (sin dependencias)');
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
        // 4. Calcular progreso basado en cambio de estado
        if ($task->isDirty('status')) {
            $this->calculateProgressFromStatus($task);
        }
    }

    /**
     * Calcular progreso basado en el estado
     */
    protected function calculateProgressFromStatus(Task $task): void
    {
        $oldProgress = $task->progress ?? 0;
            
        switch ($task->status) {
            case 'pending':
                $task->progress = 0;
                break;
            case 'in_progress':
                // Si está en progreso y tenía 0%, ponerlo en 50%
                // Si ya tenía progreso, mantenerlo (permite ajustes manuales)
                if ($task->progress === 0 || is_null($task->progress)) {
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
                'task_id' => $task->id ?? 'new',
                'status' => $task->status
            ]);
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
     * Handle the Task "saved" event (created or updated).
     * Actualizar progreso del flujo padre y del milestone padre.
     */
    /**
     * Handle the Task "saved" event (created or updated).
     * Actualizar progreso del flujo padre y del milestone padre.
     */
    public function saved(Task $task): void
    {
        // 1. Primero actualizamos el padre (Milestone) para que su progreso esté al día
        $this->updateParentProgress($task);
        
        // 2. Luego actualizamos el flujo, que podría depender del valor actualizado del padre
        $this->updateFlowProgress($task);
    }

    /**
     * Handle the Task "deleted" event.
     * Actualizar progreso del flujo padre y del milestone padre.
     */
    public function deleted(Task $task): void
    {
        $this->updateParentProgress($task);
        $this->updateFlowProgress($task);
    }

    /**
     * Actualizar el progreso general del flujo basado en sus tareas
     */
    protected function updateFlowProgress(Task $task): void
    {
        if (!$task->flow_id) {
            return;
        }

        try {
            $flow = $task->flow;
            if (!$flow) {
                return;
            }

            // Calcular promedio de progreso SOLO de las tareas RAÍZ (Milestones y tareas sueltas)
            // Esto evita que las sub-tareas se cuenten doble (una vez solas y otra dentro del milestone)
            // Y da el peso correcto a los Milestones como unidades de progreso.
            $avgProgress = $flow->rootTasks()->avg('progress') ?? 0;
            $avgProgress = round($avgProgress);

            // Actualizar progreso
            $flow->progress = $avgProgress;

            // Actualizar estado del flujo automáticamente
            if ($avgProgress == 100) {
                $flow->status = 'completed';
                if (!$flow->completed_at) {
                    $flow->completed_at = now();
                }
            } elseif ($avgProgress > 0 && $flow->status === 'pending') {
                $flow->status = 'in_progress';
                if (!$flow->started_at) {
                    $flow->started_at = now();
                }
            } elseif ($avgProgress == 0 && $flow->status === 'completed') {
                // Caso raro: se reabren tareas de un flujo completado
                $flow->status = 'in_progress';
                $flow->completed_at = null;
            }

            $flow->saveQuietly(); // saveQuietly para evitar dispara eventos del flujo recursivos si los hubiera

            Log::info("📊 Progreso del flujo actualizado (basado en root tasks): {$flow->id} -> {$avgProgress}% ({$flow->status})");

        } catch (\Exception $e) {
            Log::error("❌ Error actualizando progreso del flujo {$task->flow_id}: " . $e->getMessage());
        }
    }

    /**
     * Actualizar el progreso del Milestone padre si esta es una subtarea
     */
    protected function updateParentProgress(Task $task): void
    {
        if (!$task->parent_task_id) {
            return;
        }

        try {
            $parent = $task->parentTask;
            if (!$parent) {
                return;
            }

            // Re-calcular progreso del padre usando helper del modelo o consulta directa
            $newProgress = $parent->calculateProgress();
             
            // Solo actualizar si hay cambios
            if ($parent->progress !== $newProgress) {
                $parent->progress = $newProgress;
                
                // Actualizar estado del padre automáticamente basado en el nuevo progreso
                if ($newProgress === 100 && $parent->status !== 'completed') {
                    $parent->status = 'completed';
                } elseif ($newProgress > 0 && $newProgress < 100 && $parent->status === 'pending') {
                    $parent->status = 'in_progress';
                } elseif ($newProgress === 0 && $parent->status === 'completed') {
                    $parent->status = 'in_progress';
                }
                
                $parent->saveQuietly();
                Log::info("📊 Progreso del Milestone actualizado: {$parent->id} -> {$newProgress}% ({$parent->status})");
            }
            
        } catch (\Exception $e) {
            Log::error("❌ Error actualizando progreso del padre {$task->parent_task_id}: " . $e->getMessage());
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