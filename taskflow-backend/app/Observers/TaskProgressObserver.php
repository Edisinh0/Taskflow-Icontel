<?php

namespace App\Observers;

use App\Models\Task;
use Illuminate\Support\Facades\Log;

class TaskProgressObserver
{
    public function updating(Task $task): void
    {
        // 1. Recalcular is_blocked si cambiaron las dependencias
        if ($task->isDirty('depends_on_task_id') || $task->isDirty('depends_on_milestone_id')) {
            $shouldBeBlocked = false;

            if ($task->depends_on_task_id) {
                $precedentTask = Task::find($task->depends_on_task_id);
                if ($precedentTask && $precedentTask->status !== 'completed') {
                    $shouldBeBlocked = true;
                }
            }

            if ($task->depends_on_milestone_id) {
                $milestone = Task::find($task->depends_on_milestone_id);
                if ($milestone && $milestone->status !== 'completed') {
                    $shouldBeBlocked = true;
                }
            }

            $task->is_blocked = $shouldBeBlocked;
        }

        // 2. Calcular progreso basado en cambio de estado
        if ($task->isDirty('status')) {
            $this->calculateProgressFromStatus($task);
        }
    }

    public function updated(Task $task): void
    {
        // Liberación en cascada al completar una tarea
        if ($task->isDirty('status') && $task->status === 'completed') {
            $this->unlockDependents($task);
        }
        
        // Re-bloqueo si se reabre
        if ($task->isDirty('status') && $task->status !== 'completed' && $task->getOriginal('status') === 'completed') {
            Task::where('depends_on_task_id', $task->id)->update(['is_blocked' => true]);
            Task::where('depends_on_milestone_id', $task->id)->update(['is_blocked' => true]);
        }
    }

    public function saved(Task $task): void
    {
        $this->updateHierarchyProgress($task);
    }

    public function deleted(Task $task): void
    {
        $this->updateHierarchyProgress($task);
    }

    /**
     * Auto-calculate progress percentage based on task status.
     * 
     * @param Task $task
     * @return void
     */
    protected function calculateProgressFromStatus(Task $task): void
    {
        switch ($task->status) {
            case 'pending': $task->progress = 0; break;
            case 'in_progress': 
                if ($task->progress === 0 || is_null($task->progress)) $task->progress = 50; 
                break;
            case 'completed': $task->progress = 100; break;
            case 'cancelled': $task->progress = 0; break;
        }
    }

    /**
     * Update progress and status of parent milestone and flow in the hierarchy.
     * 
     * @param Task $task
     * @return void
     */
    protected function updateHierarchyProgress(Task $task): void
    {
        // Actualizar Milestone padre
        if ($task->parent_task_id) {
            $parent = $task->parentTask;
            if ($parent) {
                $parent->progress = $parent->subtasks()->count() === 0 ? 0 : $parent->calculateProgress();
                $this->autoUpdateStatus($parent);
                $parent->saveQuietly();
            }
        }

        // Actualizar Flujo
        if ($task->flow_id) {
            $flow = $task->flow;
            if ($flow) {
                $avgProgress = round($flow->rootTasks()->avg('progress') ?? 0);
                $flow->progress = $avgProgress;
                
                if ($avgProgress == 100) {
                    $flow->status = 'completed';
                    if (!$flow->completed_at) $flow->completed_at = now();
                } elseif ($avgProgress > 0 && $flow->status === 'pending') {
                    $flow->status = 'in_progress';
                    if (!$flow->started_at) $flow->started_at = now();
                }
                $flow->saveQuietly();
            }
        }
    }

    private function autoUpdateStatus(Task $parent): void
    {
        if ($parent->progress === 100 && $parent->status !== 'completed') {
            $parent->status = 'completed';
        } elseif ($parent->progress > 0 && $parent->progress < 100 && $parent->status === 'pending') {
            $parent->status = 'in_progress';
        } elseif ($parent->progress === 0 && in_array($parent->status, ['completed', 'in_progress'])) {
            $parent->status = 'pending';
        }
    }

    /**
     * Unlock dependent tasks when a task is completed.
     * 
     * Performs a check of all dependencies and releases blocking if all criteria are met.
     * 
     * @param Task $task The task that was recently completed.
     * @return void
     */
    private function unlockDependents(Task $task): void
    {
        $dependents = Task::where('depends_on_task_id', $task->id)
            ->orWhere('depends_on_milestone_id', $task->id)
            ->get();

        foreach ($dependents as $dependent) {
            $dependent->refresh();
            $canUnlock = true;

            if ($dependent->depends_on_task_id) {
                $p = Task::find($dependent->depends_on_task_id);
                if ($p && $p->status !== 'completed') $canUnlock = false;
            }
            if ($canUnlock && $dependent->depends_on_milestone_id) {
                $m = Task::find($dependent->depends_on_milestone_id);
                if ($m && $m->status !== 'completed') $canUnlock = false;
            }

            if ($canUnlock && $dependent->is_blocked) {
                $updateData = ['is_blocked' => false];
                if ($dependent->parent_task_id && $dependent->status === 'pending') {
                    $updateData['status'] = 'in_progress';
                }
                $dependent->update($updateData);
            }
        }
    }
}
