<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Flow;
use App\Models\Template;
use App\Models\Task;
use Illuminate\Http\Request;

class FlowController extends Controller
{
    /**
     * Listar todos los flujos
     * GET /api/v1/flows
     */
    public function index(Request $request)
    {
        $query = Flow::with(['template', 'creator', 'tasks']);

        // Filtrar por estado si se envía
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        // Filtrar por plantilla
        if ($request->has('template_id')) {
            $query->where('template_id', $request->template_id);
        }

        $flows = $query->orderBy('created_at', 'desc')->get();

        return response()->json([
            'success' => true,
            'data' => $flows,
        ], 200);
    }

    /**
     * Crear nuevo flujo
     * POST /api/v1/flows
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'template_id' => 'nullable|exists:templates,id',
            'status' => 'nullable|in:active,paused,completed,cancelled',
        ]);

        $flow = Flow::create([
            ...$validated,
            'created_by' => $request->user()->id,
            'started_at' => now(),
        ]);

        // Instanciar tareas desde la plantilla si existe
        if ($request->template_id) {
            $template = Template::find($request->template_id);
            if ($template && isset($template->config['tasks']) && is_array($template->config['tasks'])) {
                $this->createTasksFromTemplate($flow->id, $template->config['tasks']);
            }
        }

        return response()->json([
            'success' => true,
            'message' => 'Flujo creado exitosamente',
            'data' => $flow->load(['template', 'creator']),
        ], 201);
    }

    /**
     * Recursively create tasks from template configuration
     */
    private function createTasksFromTemplate($flowId, $tasks, $parentId = null)
    {
        foreach ($tasks as $taskData) {
            $task = Task::create([
                'flow_id' => $flowId,
                'parent_task_id' => $parentId,
                'title' => $taskData['title'],
                'description' => $taskData['description'] ?? null,
                'is_milestone' => $taskData['is_milestone'] ?? false,
                'priority' => $taskData['priority'] ?? 'medium',
                'status' => 'pending',
                'estimated_start_at' => isset($taskData['start_day_offset']) ? now()->addDays($taskData['start_day_offset']) : null,
                'estimated_end_at' => isset($taskData['duration_days']) ? now()->addDays(($taskData['start_day_offset'] ?? 0) + $taskData['duration_days']) : null,
            ]);

            // Si tiene subtareas, crearlas recursivamente
            if (isset($taskData['subtasks']) && is_array($taskData['subtasks'])) {
                $this->createTasksFromTemplate($flowId, $taskData['subtasks'], $task->id);
            }
        }
    }

    /**
     * Ver un flujo específico con todas sus tareas
     * GET /api/v1/flows/{id}
     */
    public function show($id)
    {
        $flow = Flow::with([
            'template',
            'creator',
            'tasks.assignee',
            'tasks.subtasks',
            'tasks.dependsOnTask',      // Tarea precedente
            'tasks.dependsOnMilestone', // Milestone requerido
            'milestones'
        ])->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $flow,
        ], 200);
    }

    /**
     * Actualizar flujo
     * PUT /api/v1/flows/{id}
     */
    public function update(Request $request, $id)
    {
        $flow = Flow::findOrFail($id);

        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'description' => 'nullable|string',
            'status' => 'sometimes|in:active,paused,completed,cancelled',
        ]);

        // Si se marca como completado, guardar la fecha
        if (isset($validated['status']) && $validated['status'] === 'completed' && !$flow->completed_at) {
            $validated['completed_at'] = now();
        }

        $flow->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Flujo actualizado exitosamente',
            'data' => $flow->load(['template', 'creator']),
        ], 200);
    }

    /**
     * Eliminar flujo
     * DELETE /api/v1/flows/{id}
     */
    public function destroy($id)
    {
        $flow = Flow::findOrFail($id);
        
        // Eliminar tareas asociadas (Soft Delete) explícitamente
        // Esto previene que queden tareas huérfanas si el Observer falla
        $flow->tasks()->delete();
        
        $flow->delete();

        return response()->json([
            'success' => true,
            'message' => 'Flujo eliminado exitosamente',
        ], 200);
    }
}