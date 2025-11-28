<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Template;
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
}