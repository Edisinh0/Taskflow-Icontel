<?php

namespace App\Observers;

use App\Models\Task;
use App\Services\NotificationService;

class TaskNotificationObserver
{
    public function created(Task $task): void
    {
        if ($task->assignee_id) {
            NotificationService::taskAssigned($task, $task->assignee_id);
        }
    }

    public function updating(Task $task): void
    {
        // Detectar cambio en is_blocked para notificaciones
        if ($task->isDirty('is_blocked')) {
            if (!$task->getOriginal('is_blocked') && $task->is_blocked) {
                NotificationService::taskBlocked($task);
            } elseif ($task->getOriginal('is_blocked') && !$task->is_blocked) {
                NotificationService::taskUnblocked($task);
            }
        }

        // Detectar cambio de asignado
        if ($task->isDirty('assignee_id') && $task->assignee_id) {
            NotificationService::taskAssigned($task, $task->assignee_id);
        }
    }

    public function updated(Task $task): void
    {
        if ($task->isDirty('status') && $task->status === 'completed') {
            NotificationService::taskCompleted($task);

            if ($task->is_milestone) {
                NotificationService::milestoneCompleted($task);
            }
        }
    }
}
