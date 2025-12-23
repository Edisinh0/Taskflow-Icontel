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
        try {
            $validated = $request->validate([
                'name' => 'required|string|max:255',
                'description' => 'nullable|string',
                'template_id' => 'nullable|exists:templates,id',
                'client_id' => 'nullable|exists:clients,id',
                'status' => 'nullable|in:active,paused,completed,cancelled',
            ]);

            // Verificar que el usuario esté autenticado
            if (!$request->user()) {
                \Illuminate\Support\Facades\Log::error('Flow creation failed: User not authenticated');
                return response()->json([
                    'success' => false,
                    'message' => 'Usuario no autenticado',
                ], 401);
            }

            // Autorización mediante Policy (Solo PM/Admin pueden crear)
            \Illuminate\Support\Facades\Gate::authorize('create', Flow::class);

            $flow = Flow::create([
                ...$validated,
                'created_by' => $request->user()->id,
                'started_at' => now(),
            ]);

            // Instanciar tareas desde la plantilla si existe
            if ($request->template_id) {
                $template = Template::find($request->template_id);
                if ($template && isset($template->config['tasks']) && is_array($template->config['tasks'])) {
                    app(\App\Services\TemplateProcessingService::class)->createTasksFromTemplate($flow, $template->config['tasks']);
                }
            }

            return response()->json([
                'success' => true,
                'message' => 'Flujo creado exitosamente',
                'data' => $flow->load(['template', 'creator']),
            ], 201);
        
        } catch (\Illuminate\Validation\ValidationException $e) {
            \Illuminate\Support\Facades\Log::error('Flow creation validation failed: ' . json_encode($e->errors()));
            throw $e;
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Flow creation failed: ' . $e->getMessage());
            \Illuminate\Support\Facades\Log::error('Stack trace: ' . $e->getTraceAsString());
            
            return response()->json([
                'success' => false,
                'message' => 'Error al crear el flujo: ' . $e->getMessage(),
            ], 500);
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
            'lastEditor',
            'tasks.assignee',
            'tasks.lastEditor', // Cargar creador/editor de tareas
            'tasks.subtasks',
            'tasks.subtasks.lastEditor', // Cargar creador/editor de subtareas
            'tasks.dependsOnTask',      // Tarea precedente
            'tasks.dependsOnMilestone', // Milestone requerido
            'tasks.subtasks.dependsOnTask', // Dependencias de subtareas
            'tasks.subtasks.dependsOnMilestone',
            'tasks.attachments.uploader', // Adjuntos
            'tasks.subtasks.attachments.uploader', // Adjuntos de subtareas
            'tasks.subtasks.assignee', // Responsable de subtareas
            'milestones.lastEditor',
            'milestones.assignee'
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

        // Autorización mediante Policy (Solo PM/Admin pueden actualizar)
        \Illuminate\Support\Facades\Gate::authorize('update', $flow);

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
        
        // Autorización mediante Policy (Solo PM/Admin pueden eliminar)
        \Illuminate\Support\Facades\Gate::authorize('delete', $flow);
        
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