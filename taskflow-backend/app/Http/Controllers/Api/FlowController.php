<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Flow;
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

        return response()->json([
            'success' => true,
            'message' => 'Flujo creado exitosamente',
            'data' => $flow->load(['template', 'creator']),
        ], 201);
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
        $flow->delete();

        return response()->json([
            'success' => true,
            'message' => 'Flujo eliminado exitosamente',
        ], 200);
    }
}