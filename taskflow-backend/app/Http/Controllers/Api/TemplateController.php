<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Template;
use App\Models\Flow;
use Illuminate\Http\Request;

class TemplateController extends Controller
{
    /**
     * Listar todas las plantillas
     * GET /api/v1/templates
     */
    public function index()
    {
        $templates = Template::with('creator')
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $templates,
        ], 200);
    }

    /**
     * Crear nueva plantilla
     * POST /api/v1/templates
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'version' => 'nullable|string|max:50',
            'config' => 'nullable|array',
        ]);

        $template = Template::create([
            ...$validated,
            'created_by' => $request->user()->id,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Plantilla creada exitosamente',
            'data' => $template->load('creator'),
        ], 201);
    }

    /**
     * Ver una plantilla específica
     * GET /api/v1/templates/{id}
     */
    public function show($id)
    {
        $template = Template::with(['creator', 'flows'])
            ->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $template,
        ], 200);
    }

    /**
     * Actualizar plantilla
     * PUT /api/v1/templates/{id}
     */
    public function update(Request $request, $id)
    {
        $template = Template::findOrFail($id);

        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'description' => 'nullable|string',
            'version' => 'nullable|string|max:50',
            'is_active' => 'sometimes|boolean',
            'config' => 'nullable|array',
        ]);

        $template->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Plantilla actualizada exitosamente',
            'data' => $template->load('creator'),
        ], 200);
    }

    /**
     * Eliminar plantilla (soft delete)
     * DELETE /api/v1/templates/{id}
     */
    public function destroy($id)
    {
        $template = Template::findOrFail($id);
        $template->delete();

        return response()->json([
            'success' => true,
            'message' => 'Plantilla eliminada exitosamente',
        ], 200);
    }

    /**
     * Crear una plantilla desde un flujo existente
     * POST /api/v1/templates/from-flow/{flowId}
     */
    public function createFromFlow(Request $request, $flowId)
    {
        $flow = Flow::with(['tasks' => function ($query) {
            $query->orderBy('id'); // Mantener orden de creación
        }])->findOrFail($flowId);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'version' => 'nullable|string|max:50',
        ]);

        // Construir configuración de tareas
        $tasksConfig = [];
        
        // 1. Identificar hitos
        $milestones = $flow->tasks->where('is_milestone', true);
        
        foreach ($milestones as $milestone) {
            $milestoneConfig = [
                'title' => $milestone->title,
                'description' => $milestone->description,
                'is_milestone' => true,
                'subtasks' => []
            ];

            // 2. Encontrar subtareas para este hito
            // Usamos la misma lógica que en el frontend: parent_task_id o depends_on_milestone_id
            $subtasks = $flow->tasks->filter(function ($task) use ($milestone) {
                return !$task->is_milestone && 
                       ($task->parent_task_id == $milestone->id || $task->depends_on_milestone_id == $milestone->id);
            });

            foreach ($subtasks as $subtask) {
                $milestoneConfig['subtasks'][] = [
                    'title' => $subtask->title,
                    'description' => $subtask->description,
                    'priority' => $subtask->priority,
                    'duration_days' => $subtask->estimated_end_at && $subtask->estimated_start_at 
                        ? $subtask->estimated_start_at->diffInDays($subtask->estimated_end_at) 
                        : 1
                ];
            }

            $tasksConfig[] = $milestoneConfig;
        }

        // Crear la plantilla
        $template = Template::create([
            'name' => $validated['name'],
            'description' => $validated['description'] ?? "Plantilla generada desde el flujo: {$flow->name}",
            'version' => $validated['version'] ?? '1.0',
            'created_by' => $request->user()->id,
            'is_active' => true,
            'config' => [
                'tasks' => $tasksConfig,
                'estimated_duration_days' => 7, // Valor por defecto
                'priority' => 'medium'
            ]
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Plantilla generada correctamente desde el flujo',
            'data' => $template
        ], 201);
    }
}