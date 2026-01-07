<?php

namespace App\Console\Commands;

use App\Models\Task;
use App\Models\Notification;
use Illuminate\Console\Command;
use Carbon\Carbon;

class CheckSlaCommand extends Command
{
    protected $signature = 'sla:check';
    protected $description = 'Verificar SLA de tareas y enviar notificaciones';

    public function handle()
    {
        $this->info('🔍 Iniciando verificación maestra de SLA...');

        // 1. Verificar tareas próximas a vencer (Warning 24h)
        $this->checkUpcomingDeadlines();

        // 2. Verificar SLAs Vencidos (+0 min) -> Marcar como breached
        $this->checkBreachedSlas();

        // 3. Notificación (+1 día de retraso) -> Scope NeedsAssigneeNotification
        $this->checkDayOneOverdue();

        // 4. Escalamiento (+2 días de retraso) -> Scope NeedsEscalation
        $this->checkDayTwoEscalation();

        $this->info("✅ Verificación de SLA completada.");
        return 0;
    }

    private function checkUpcomingDeadlines()
    {
        $tasks = Task::whereIn('status', ['pending', 'in_progress'])
            ->whereNotNull('estimated_end_at')
            ->where('sla_breached', false) // No vencidas aun
            ->get();

        foreach ($tasks as $task) {
            $hoursUntil = Carbon::parse($task->estimated_end_at)->diffInHours(now(), false);
            // Aviso preventivo entre 1 y 24 horas antes
            if ($hoursUntil > 0 && $hoursUntil <= 24) {
                 $this->createNotification(
                    $task, 
                    'sla_warning', 
                    '⚠️ Tarea próxima a vencer', 
                    "Vence en " . round($hoursUntil) . " horas",
                    'high'
                );
            }
        }
    }

    private function checkBreachedSlas()
    {
        // Revisar tareas que vencieron y aún no han sido marcadas
        $tasks = Task::whereIn('status', ['pending', 'in_progress'])
            ->whereNotNull('estimated_end_at')
            ->where('sla_breached', false)
            ->where('estimated_end_at', '<', now())
            ->get();

        foreach ($tasks as $task) {
            $task->checkSlaStatus(); // Esto marca sla_breached = true y calcula days_overdue
            $this->createNotification(
                $task,
                'task_overdue',
                '🚨 Tarea Vencida',
                "La tarea ha vencido. Por favor regularizar.",
                'urgent'
            );
        }
    }

    private function checkDayOneOverdue()
    {
        // Scope definido en Task.php para +1 día
        $tasks = Task::needsAssigneeNotification()->get();
        $count = 0;

        foreach ($tasks as $task) {
            $this->createNotification(
                $task,
                'sla_overdue_1day',
                '⏰ Recordatorio de Retraso (+1 Día)',
                "Esta tarea tiene 1 día de retraso. Se requiere actualización inmediata.",
                'urgent'
            );
            
            $task->update(['sla_notified_assignee' => true, 'sla_notified_at' => now()]);
            $count++;
        }
        
        if ($count > 0) $this->info("   - Notificadas $count tareas con +1 día de retraso.");
    }

    private function checkDayTwoEscalation()
    {
        // Scope definido en Task.php para +2 días (Escalamiento)
        $tasks = Task::needsEscalation()->get();
        $count = 0;

        foreach ($tasks as $task) {
            // Lógica de escalamiento: Notificar al supervisor/PM
            $supervisor = $task->getSupervisor(); // Método en Task.php
            
            if ($supervisor) {
                Notification::create([
                    'user_id' => $supervisor->id,
                    'task_id' => $task->id,
                    'flow_id' => $task->flow_id,
                    'type' => 'sla_escalation',
                    'title' => '🔥 Escalamiento de Tarea (+2 Días)',
                    'message' => "La tarea '{$task->title}' (Asignada a: {$task->assignee->name}) tiene 2 días de retraso.",
                    'priority' => 'critical',
                    'data' => [
                        'days_overdue' => $task->sla_days_overdue,
                        'assignee_id' => $task->assignee_id
                    ]
                ]);
            }

            // También notificar al usuario que ha sido escalado
            $this->createNotification(
                $task,
                'sla_escalated_user',
                '🛑 Tarea Escalada',
                "Tu tarea tiene 2 días de retraso y ha sido escalada a supervisión.",
                'critical'
            );
            
            $task->update(['sla_escalated' => true, 'sla_escalated_at' => now()]);
            $count++;
        }

        if ($count > 0) $this->info("   - Escaladas $count tareas con +2 días de retraso.");
    }

    private function createNotification($task, $type, $title, $message, $priority)
    {
        // Evitar spam: solo 1 notif del mismo tipo cada 24h
        $exists = Notification::where('task_id', $task->id)
            ->where('type', $type)
            ->where('created_at', '>', now()->subDay())
            ->exists();

        if ($exists) return;

        Notification::create([
            'user_id' => $task->assignee_id,
            'task_id' => $task->id,
            'flow_id' => $task->flow_id,
            'type' => $type,
            'title' => $title,
            'message' => $message,
            'priority' => $priority,
            'data' => [
                'deadline' => $task->estimated_end_at,
                'days_overdue' => $task->sla_days_overdue ?? 0
            ],
        ]);
    }
}