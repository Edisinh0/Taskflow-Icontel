<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Task;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class TaskController extends Controller
{
    /**
     * Listar tareas (con filtros opcionales)
     * GET /api/v1/tasks
     */
    public function index(Request $request)
    {
        $query = Task::with(['flow', 'assignee', 'parentTask', 'subtasks']);

        // Filtrar por flujo
        if ($request->has('flow_id')) {
            $query->where('flow_id', $request->flow_id);
        }

        // Filtrar por usuario asignado
        if ($request->has('assignee_id')) {
            $query->where('assignee_id', $request->assignee_id);
        }

        // Filtrar por estado
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        // Solo milestones
        if ($request->has('milestones_only') && $request->milestones_only) {
            $query->where('is_milestone', true);
        }

        // Solo tareas raíz (sin padre)
        if ($request->has('root_only') && $request->root_only) {
            $query->whereNull('parent_task_id');
        }

        $tasks = $query->orderBy('order')->get();

        return response()->json([
            'success' => true,
            'data' => $tasks,
        ], 200);
    }

    /**
     * Crear nueva tarea
     * POST /api/v1/tasks
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'flow_id' => 'required|exists:flows,id',
            'parent_task_id' => 'nullable|exists:tasks,id',
            'assignee_id' => 'nullable|exists:users,id',
            'priority' => 'nullable|in:low,medium,high,urgent',
            'status' => 'nullable|in:pending,blocked,in_progress,paused,completed,cancelled',
            'is_milestone' => 'nullable|boolean',
            'estimated_start_at' => 'nullable|date',
            'estimated_end_at' => 'nullable|date',
        ]);

        $task = Task::create($validated);

        return response()->json([
            'success' => true,
            'message' => 'Tarea creada exitosamente',
            'data' => $task->load(['flow', 'assignee']),
        ], 201);
    }

    /**
     * Ver una tarea específica
     * GET /api/v1/tasks/{id}
     */
    public function show($id)
    {
        $task = Task::with([
            'flow',
            'assignee',
            'parentTask',
            'subtasks.assignee',
            'dependencies.dependsOnTask',
            'dependents.task'
        ])->findOrFail($id);

        // Verificar si está bloqueada
        $task->is_blocked = $task->isBlocked();

        return response()->json([
            'success' => true,
            'data' => $task,
        ], 200);
    }

    /**
     * Actualizar tarea
     * PUT /api/v1/tasks/{id}
     */
    public function update(Request $request, $id)
    {
        // 1. Encontrar la tarea
        $task = Task::findOrFail($id);
        
        // 2. Validar los datos de entrada
        $validated = $request->validate([
            'flow_id' => 'sometimes|exists:flows,id',
            'title' => 'sometimes|string|max:255',
            'description' => 'nullable|string',
            // Asegúrate de que los estados coincidan con tu lógica
            'status' => ['sometimes', 'string', Rule::in(['pending', 'in_progress', 'completed', 'paused', 'cancelled'])],
            'assignee_id' => 'nullable|exists:users,id',
            'estimated_end_at' => 'nullable|date',
            'is_milestone' => 'sometimes|boolean',
            'order' => 'sometimes|integer|min:0',
            
            // ATENCIÓN: Los siguientes campos DEBEN existir en la migración de la tabla tasks
            // 'is_blocked' se gestiona por el Observer
            // 'depends_on_task_id'
            // 'depends_on_milestone_id'
        ]);

        // ==========================================================
        // 🎯 MOTOR DE CONTROL DE FLUJOS (Lógica de Bloqueo)
        // ==========================================================
        
        if (isset($validated['status']) && $task->is_blocked) {
            $newStatus = $validated['status'];
            
            // Si intenta iniciarla ('in_progress') o finalizarla ('completed') estando bloqueada.
            if ($newStatus === 'in_progress' || $newStatus === 'completed') {
                
                // --- Generación del mensaje de bloqueo ---
                $dependency = "una tarea previa";
                if ($task->depends_on_milestone_id) {
                    // Si tienes la relación cargada: $task->milestone->name
                    $dependency = "el hito asociado";
                } elseif ($task->depends_on_task_id) {
                    // Si tienes la relación cargada: $task->parentTask->title
                    $dependency = "la tarea #{$task->depends_on_task_id}";
                }
                
                // Devolvemos el error 403 (Sin permisos para la acción)
                return response()->json([
                    'success' => false,
                    // Mensaje basado en la documentación (ejemplo: 'No puede iniciar esta tarea hasta completar el milestone anterior...')
                    'message' => "❌ Acción prohibida. Esta tarea está bloqueada. Debe completarse {$dependency} primero.",
                ], 403); 
            }
        }
        
        // ==========================================================
        // 🎯 CONTINUACIÓN NORMAL DEL UPDATE
        // ==========================================================
        
        try {
            // Asignación de usuario (si aplica)
            if (isset($validated['assignee_id']) && !isset($task->assigned_at)) {
                $validated['assigned_at'] = now();
            }

            // Actualización del registro
            $task->update($validated);
            
            // (Opcional) Aquí lanzarías un evento si el estado cambió a 'completed'

            return response()->json([
                'success' => true,
                'message' => 'Tarea actualizada exitosamente',
                // Cargar relaciones para la respuesta del frontend
                'data' => $task->load(['flow', 'assignee', 'parentTask', 'subtasks']),
            ], 200);

        } catch (\Exception $e) {
            // Manejo de error genérico
            return response()->json([
                'success' => false,
                'message' => 'Error al actualizar tarea: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Eliminar tarea
     * DELETE /api/v1/tasks/{id}
     */
    public function destroy($id)
    {
        $task = Task::findOrFail($id);
        $task->delete();

        return response()->json([
            'success' => true,
            'message' => 'Tarea eliminada exitosamente',
        ], 200);
    }

    public function reorder(Request $request)
    {
        $validated = $request->validate([
            'tasks' => 'required|array',
            'tasks.*.id' => 'required|exists:tasks,id',
            'tasks.*.order' => 'required|integer|min:0',
            'tasks.*.parent_task_id' => 'nullable|exists:tasks,id',
        ]);

        try {
            DB::beginTransaction();

            foreach ($validated['tasks'] as $taskData) {
                Task::where('id', $taskData['id'])->update([
                    'order' => $taskData['order'],
                    'parent_task_id' => $taskData['parent_task_id'] ?? null,
                ]);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Tareas reordenadas exitosamente',
            ], 200);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Error al reordenar tareas: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Mover tarea a otro milestone/parent
     * POST /api/v1/tasks/{id}/move
     */
    public function move(Request $request, $id)
    {
        $task = Task::findOrFail($id);

        $validated = $request->validate([
            'parent_task_id' => 'nullable|exists:tasks,id',
            'order' => 'nullable|integer|min:0',
        ]);

        try {
            $task->update([
                'parent_task_id' => $validated['parent_task_id'] ?? null,
                'order' => $validated['order'] ?? 0,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Tarea movida exitosamente',
                'data' => $task->load(['flow', 'assignee', 'parentTask']),
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al mover tarea: ' . $e->getMessage(),
            ], 500);
        }
    }
}