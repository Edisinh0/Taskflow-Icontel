<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Models\Template;
use Illuminate\Http\Request;

class ClientController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = Client::query();

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        if ($request->has('account_type')) {
            $query->where('account_type', $request->account_type);
        }

        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('sweetcrm_id', 'like', "%{$search}%");
            });
        }

        return response()->json($query->orderBy('name')->get());
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:50',
            'address' => 'nullable|string|max:255',
            'industry' => 'nullable|string|max:100',
            'industry_id' => 'nullable|exists:industries,id',
            'sweetcrm_id' => 'nullable|string|max:100',
            'status' => 'required|in:active,inactive',
        ]);

        $client = Client::create($validated);

        return response()->json($client, 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Client $client)
    {
        // Cargar flujos con sus tareas, estadísticas, contactos y adjuntos
        $client->load([
            'flows' => function($query) {
                $query->with(['tasks', 'creator:id,name'])
                      ->orderBy('created_at', 'desc');
            },
            'contacts' => function($query) {
                $query->orderBy('is_primary', 'desc')->orderBy('name');
            },
            'attachments.uploader:id,name',
            'industry'
        ]);

        // Calcular estadísticas
        $totalFlows = $client->flows->count();
        $activeFlows = $client->flows->where('status', 'active')->count();
        $completedFlows = $client->flows->where('status', 'completed')->count();

        // Estadísticas de tareas
        $allTasks = $client->flows->flatMap->tasks;
        $totalTasks = $allTasks->count();
        $completedTasks = $allTasks->where('status', 'completed')->count();
        $pendingTasks = $allTasks->whereIn('status', ['pending', 'in_progress'])->count();

        return response()->json([
            'client' => $client,
            'stats' => [
                'total_flows' => $totalFlows,
                'active_flows' => $activeFlows,
                'completed_flows' => $completedFlows,
                'total_tasks' => $totalTasks,
                'completed_tasks' => $completedTasks,
                'pending_tasks' => $pendingTasks,
                'completion_rate' => $totalTasks > 0 ? round(($completedTasks / $totalTasks) * 100) : 0,
            ],
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Client $client)
    {
        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:50',
            'address' => 'nullable|string|max:255',
            'industry' => 'nullable|string|max:100',
            'industry_id' => 'nullable|exists:industries,id',
            'sweetcrm_id' => 'nullable|string|max:100',
            'status' => 'sometimes|required|in:active,inactive',
        ]);

        $client->update($validated);

        return response()->json($client);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Client $client)
    {
        $client->delete();
        return response()->json(null, 204);
    }

    /**
     * Get recommended templates for a client based on industry
     * GET /api/v1/clients/{client}/recommended-templates
     */
    public function recommendedTemplates(Client $client)
    {
        // Load the client's industry relationship
        $client->load('industry');

        $templates = [];

        // If client has an industry, get templates for that industry
        if ($client->industry_id) {
            $templates = Template::with(['creator', 'industries'])
                ->where('is_active', true)
                ->forIndustry($client->industry_id)
                ->orderBy('name')
                ->get();
        }

        // Fallback: if no industry-specific templates, return all active templates
        if (empty($templates) || $templates->isEmpty()) {
            $templates = Template::with(['creator', 'industries'])
                ->where('is_active', true)
                ->orderBy('name')
                ->get();
        }

        return response()->json([
            'success' => true,
            'data' => $templates,
            'industry' => $client->industry,
            'message' => $client->industry_id ?
                "Plantillas recomendadas para {$client->industry->name}" :
                'Todas las plantillas disponibles'
        ]);
    }
}
