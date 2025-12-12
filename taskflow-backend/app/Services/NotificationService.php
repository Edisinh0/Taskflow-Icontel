<?php

namespace App\Services;

use App\Models\Notification;
use App\Models\Task;
use App\Models\User;
use Illuminate\Support\Facades\Log;

class NotificationService
{
    /**
     * Crear notificación cuando una tarea se bloquea
     */
    public static function taskBlocked(Task $task): void
    {
        if (!$task->assignee_id) {
            return;
        }

        Log::info('📬 Creando notificación: Tarea bloqueada', [
            'task_id' => $task->id,
            'assignee_id' => $task->assignee_id
        ]);

        Notification::create([
            'user_id' => $task->assignee_id,
            'task_id' => $task->id,
            'flow_id' => $task->flow_id,
            'type' => 'task_blocked',
            'title' => 'Tarea Bloqueada',
            'message' => "La tarea '{$task->title}' ha sido bloqueada por dependencias",
            'priority' => 'medium',
        ]);
    }

    /**
     * Crear notificación cuando una tarea se desbloquea
     */
    public static function taskUnblocked(Task $task): void
    {
        if (!$task->assignee_id) {
            return;
        }

        Log::info('📬 Creando notificación: Tarea desbloqueada', [
            'task_id' => $task->id,
            'assignee_id' => $task->assignee_id
        ]);

        Notification::create([
            'user_id' => $task->assignee_id,
            'task_id' => $task->id,
            'flow_id' => $task->flow_id,
            'type' => 'task_unblocked',
            'title' => '¡Tarea Desbloqueada!',
            'message' => "La tarea '{$task->title}' ha sido desbloqueada y puede iniciarse",
            'priority' => 'medium',
        ]);
    }

    /**
     * Crear notificación cuando se asigna una tarea a un usuario
     */
    public static function taskAssigned(Task $task, int $newAssigneeId): void
    {
        Log::info('📬 Creando notificación: Tarea asignada', [
            'task_id' => $task->id,
            'assignee_id' => $newAssigneeId
        ]);

        Notification::create([
            'user_id' => $newAssigneeId,
            'task_id' => $task->id,
            'flow_id' => $task->flow_id,
            'type' => 'task_assigned',
            'title' => 'Nueva Tarea Asignada',
            'message' => "Se te ha asignado la tarea '{$task->title}'",
            'priority' => 'medium',
        ]);
    }

    /**
     * Crear notificación cuando una tarea se completa
     */
    public static function taskCompleted(Task $task): void
    {
        // Notificar al creador del flujo
        $flow = $task->flow;
        if (!$flow || !$flow->created_by) {
            return;
        }

        // No notificar si el creador es quien completó la tarea
        if ($flow->created_by === $task->assignee_id) {
            return;
        }

        Log::info('📬 Creando notificación: Tarea completada', [
            'task_id' => $task->id,
            'flow_creator' => $flow->created_by
        ]);

        Notification::create([
            'user_id' => $flow->created_by,
            'task_id' => $task->id,
            'flow_id' => $task->flow_id,
            'type' => 'task_completed',
            'title' => 'Tarea Completada',
            'message' => "La tarea '{$task->title}' ha sido completada",
            'priority' => 'medium',
        ]);
    }

    /**
     * Crear notificación cuando se completa un milestone
     */
    public static function milestoneCompleted(Task $milestone): void
    {
        $flow = $milestone->flow;
        if (!$flow) {
            return;
        }

        Log::info('📬 Creando notificación: Milestone completado', [
            'milestone_id' => $milestone->id,
            'flow_id' => $flow->id
        ]);

        // Notificar al creador del flujo
        if ($flow->created_by) {
            Notification::create([
                'user_id' => $flow->created_by,
                'task_id' => $milestone->id,
                'flow_id' => $milestone->flow_id,
                'type' => 'milestone_completed',
                'title' => '🎯 Milestone Completado',
                'message' => "El milestone '{$milestone->title}' ha sido completado",
                'priority' => 'high',
            ]);
        }

        // Notificar a todos los asignados de tareas que dependían de este milestone
        $dependentTasks = Task::where('depends_on_milestone_id', $milestone->id)
            ->whereNotNull('assignee_id')
            ->get();

        foreach ($dependentTasks as $task) {
            if ($task->assignee_id !== $flow->created_by) {
                Notification::create([
                    'user_id' => $task->assignee_id,
                    'task_id' => $task->id,
                    'flow_id' => $task->flow_id,
                    'type' => 'milestone_completed',
                    'title' => '🎯 Milestone Completado',
                    'message' => "El milestone '{$milestone->title}' ha sido completado. Tu tarea '{$task->title}' puede continuar",
                    'priority' => 'medium',
                ]);
            }
        }
    }
}
