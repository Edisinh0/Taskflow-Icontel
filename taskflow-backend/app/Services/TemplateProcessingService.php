<?php

namespace App\Services;

use App\Models\Flow;
use App\Models\Task;
use App\Models\TaskDependency;
use Illuminate\Support\Facades\Log;

class TemplateProcessingService
{
    /**
     * Create tasks for a flow from a template configuration.
     *
     * @param Flow $flow
     * @param array $templateTasks
     * @return void
     */
    public function createTasksFromTemplate(Flow $flow, array $templateTasks): void
    {
        $idMap = [];
        $pendingDependencies = [];

        $this->recursiveCreateTasks($flow->id, $templateTasks, null, $idMap, $pendingDependencies);

        Log::info("Flow ID {$flow->id}: Tareas creadas. ID Map: " . json_encode($idMap));
        Log::info("Flow ID {$flow->id}: Dependencias pendientes: " . json_encode($pendingDependencies));

        $this->processPendingDependencies($idMap, $pendingDependencies);
        $this->recalculateInitialBlocking($idMap);
    }

    /**
     * Recursively create tasks from template configuration.
     * 
     * @param int $flowId The ID of the flow being created.
     * @param array $tasks The array of task definitions from the template config.
     * @param int|null $parentId The ID of the parent task (if any).
     * @param array &$idMap Reference to the map used to resolve temp_ref_id to real IDs.
     * @param array &$pendingDependencies Reference to the array of dependencies to be processed after creation.
     * @return void
     */
    private function recursiveCreateTasks($flowId, $tasks, $parentId = null, &$idMap = [], &$pendingDependencies = [])
    {
        $previousSubtaskId = null;
        $isFirstSubtask = true;

        foreach ($tasks as $taskData) {
            // Determinar el estado inicial de la tarea
            $initialStatus = 'pending';

            // Si es una subtarea (tiene parent_task_id) y es la primera, debe estar "in_progress"
            if ($parentId !== null && $isFirstSubtask) {
                $initialStatus = 'in_progress';
                $isFirstSubtask = false;
            }

            $task = Task::create([
                'flow_id' => $flowId,
                'parent_task_id' => $parentId,
                'title' => $taskData['title'],
                'description' => $taskData['description'] ?? null,
                'is_milestone' => $taskData['is_milestone'] ?? false,
                'priority' => $taskData['priority'] ?? 'medium',
                'status' => $initialStatus,
                'estimated_start_at' => isset($taskData['start_day_offset']) ? now()->addDays($taskData['start_day_offset']) : null,
                'estimated_end_at' => isset($taskData['duration_days']) ? now()->addDays(($taskData['start_day_offset'] ?? 0) + $taskData['duration_days']) : null,
            ]);

            // Guardar mapeo si existe referencia
            if (isset($taskData['temp_ref_id'])) {
                $idMap[$taskData['temp_ref_id']] = $task->id;
            }

            // Guardar dependencias para procesar después
            $pendingData = [
                'new_task_id' => $task->id
            ];
            $hasPending = false;

            if (isset($taskData['dependencies']) && is_array($taskData['dependencies']) && !empty($taskData['dependencies'])) {
                $pendingData['dependency_refs'] = $taskData['dependencies'];
                $hasPending = true;
            }

            if (isset($taskData['depends_on_task_ref']) && $taskData['depends_on_task_ref']) {
                $pendingData['depends_on_task_ref'] = $taskData['depends_on_task_ref'];
                $hasPending = true;
            }

            // Si es una subtarea (no la primera) y hay una subtarea anterior, debe depender de ella
            if ($parentId !== null && $previousSubtaskId !== null) {
                $pendingData['depends_on_task_ref'] = 'prev_subtask_' . $previousSubtaskId;
                $idMap['prev_subtask_' . $previousSubtaskId] = $previousSubtaskId;
                $hasPending = true;
            }

            if ($hasPending) {
                $pendingDependencies[] = $pendingData;
            }

            // Actualizar el ID de la subtarea anterior para la próxima iteración
            if ($parentId !== null) {
                $previousSubtaskId = $task->id;
            }

            // Si tiene subtareas, crearlas recursivamente
            if (isset($taskData['subtasks']) && is_array($taskData['subtasks'])) {
                $this->recursiveCreateTasks($flowId, $taskData['subtasks'], $task->id, $idMap, $pendingDependencies);
            }
        }
    }

    /**
     * Process pending dependencies after all tasks are created.
     * 
     * Iterates through the captured dependencies and maps them to the newly created task IDs
     * using the idMap. Supports both pivot table and single column dependency systems.
     * 
     * @param array $idMap Map of temporary reference IDs to database IDs.
     * @param array $pendingDependencies List of dependencies waiting for resolution.
     * @return void
     */
    private function processPendingDependencies(array $idMap, array $pendingDependencies): void
    {
        foreach ($pendingDependencies as $pending) {
            $taskModified = false;
            $task = null;

            // 1. Sistema complejo (TaskDependency M:N)
            if (isset($pending['dependency_refs']) && is_array($pending['dependency_refs'])) {
                foreach ($pending['dependency_refs'] as $refId) {
                    if (isset($idMap[$refId])) {
                        Log::info("Service: Creando dependencia Pivot: Tarea {$pending['new_task_id']} depende de {$idMap[$refId]}");
                        TaskDependency::create([
                            'task_id' => $pending['new_task_id'],
                            'depends_on_task_id' => $idMap[$refId],
                            'dependency_type' => 'finish_to_start'
                        ]);
                    }
                }
            }

            // 2. Sistema simple (Columna depends_on_task_id 1:N)
            if (isset($pending['depends_on_task_ref'])) {
                $refId = $pending['depends_on_task_ref'];
                if (isset($idMap[$refId])) {
                    if (!$task) $task = Task::find($pending['new_task_id']);
                    
                    if ($task) {
                        Log::info("Service: Asignando depends_on_task_id: Tarea {$task->id} depende de {$idMap[$refId]}");
                        $task->depends_on_task_id = $idMap[$refId];
                        $taskModified = true;
                    }
                }
            }

            if ($taskModified && $task) {
                $task->save(); // Dispara Observer updating -> recalcula bloqueo
            }
        }
    }

    /**
     * Final recalculation of blocking status for all created tasks.
     * 
     * Ensures consistent state by checking if any task should be blocked based on its 
     * resolved dependencies. Updates status to 'blocked' if necessary.
     * 
     * @param array $idMap Map of created task IDs.
     * @return void
     */
    private function recalculateInitialBlocking(array $idMap): void
    {
        foreach ($idMap as $newTaskId) {
            $task = Task::find($newTaskId);
            if ($task) {
                // Forzar refresco por si el observer no corrió o si usamos sistema pivot
                $task->is_blocked = $task->isBlocked(); 
                if ($task->is_blocked && $task->status !== 'completed' && $task->status !== 'blocked') {
                    $task->status = 'blocked';
                    $task->blocked_reason = 'Esperando tareas precedentes';
                    $task->saveQuietly();
                }
            }
        }
    }
}
