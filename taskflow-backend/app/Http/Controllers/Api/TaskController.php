<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Task;
use Illuminate\Http\Request;

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
        $task = Task::findOrFail($id);

        $validated = $request->validate([
            'title' => 'sometimes|string|max:255',
            'description' => 'nullable|string',
            'assignee_id' => 'nullable|exists:users,id',
            'priority' => 'sometimes|in:low,medium,high,urgent',
            'status' => 'sometimes|in:pending,blocked,in_progress,paused,completed,cancelled',
            'progress' => 'sometimes|integer|min:0|max:100',
            'blocked_reason' => 'nullable|string',
        ]);

        // Si se marca como iniciada, guardar fecha de inicio
        if (isset($validated['status']) && $validated['status'] === 'in_progress' && !$task->actual_start_at) {
            $validated['actual_start_at'] = now();
        }

        // Si se marca como completada, guardar fecha de fin
        if (isset($validated['status']) && $validated['status'] === 'completed' && !$task->actual_end_at) {
            $validated['actual_end_at'] = now();
            $validated['progress'] = 100;
        }

        $task->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Tarea actualizada exitosamente',
            'data' => $task->load(['flow', 'assignee']),
        ], 200);
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
}