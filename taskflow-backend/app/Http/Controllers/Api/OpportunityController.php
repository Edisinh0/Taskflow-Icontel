<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CrmOpportunity;
use App\Models\Task;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class OpportunityController extends Controller
{
    /**
     * Listar oportunidades
     */
    public function index(Request $request)
    {
        $query = CrmOpportunity::with('client:id,name');
        
        if ($request->has('search')) {
            $query->where('name', 'like', '%' . $request->search . '%');
        }
        
        return response()->json($query->latest()->paginate(20));
    }

    /**
     * Disparador de Flujo Operativo: Ventas -> Operaciones
     * Genera una tarea basada en la existencia de cotizaciones
     */
    public function sendToOperations(Request $request, $id)
    {
        $opportunity = CrmOpportunity::with('quotes')->findOrFail($id);
        $user = auth()->user();

        // Verificar si hay cotizaciones confirmadas/aprobadas
        $hasApprovedQuote = $opportunity->quotes()->where('status', 'Confirmed')->exists();
        
        $taskType = $hasApprovedQuote ? 'Tarea de Ejecución' : 'Tarea de Levantamiento';
        $priority = $hasApprovedQuote ? 'high' : 'urgent'; // Levantamiento es urgente

        $task = DB::transaction(function () use ($opportunity, $taskType, $priority, $user) {
            return Task::create([
                'title' => "[OPERACIONES] {$taskType}: {$opportunity->name}",
                'description' => "Enviado desde Ventas por {$user->name}. Oportunidad vinculada: {$opportunity->name}.",
                'opportunity_id' => $opportunity->id,
                'client_id' => $opportunity->client_id,
                'status' => 'pending',
                'priority' => $priority,
                'department_target' => 'Operaciones', // Campo informativo del área destino
                'created_by' => $user->id,
            ]);
        });

        return response()->json([
            'message' => "Flujo enviado a Operaciones como: {$taskType}",
            'task' => $task
        ]);
    }
}
