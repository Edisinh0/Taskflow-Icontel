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
     * Listar todas las oportunidades con filtros y paginación optimizada
     * Usa Eager Loading para evitar N+1
     */
    public function index(Request $request)
    {
        $perPage = min(50, max(10, (int) $request->get('per_page', 20)));
        
        // Eager loading con campos específicos
        $query = CrmOpportunity::with([
            'client:id,name',
            'quotes:id,opportunity_id,status'
        ])
        ->withCount('tasks'); // Conteo eficiente

        // Búsqueda
        if ($request->has('search') && $request->search) {
            $search = trim($request->input('search'));
            if (!empty($search)) {
                $query->where(function($q) use ($search) {
                    $q->where('name', 'like', '%' . addslashes($search) . '%')
                      ->orWhere('description', 'like', '%' . addslashes($search) . '%')
                      ->orWhereHas('client', function($cq) use ($search) {
                          $cq->where('name', 'like', '%' . addslashes($search) . '%');
                      });
                });
            }
        }

        // Filtro de etapa
        if ($request->has('sales_stage') && $request->sales_stage !== 'all') {
            $query->where('sales_stage', $request->sales_stage);
        }

        // Filtro de cliente
        if ($request->has('client_id')) {
            $query->where('client_id', $request->client_id);
        }

        // Ordenamiento
        $orderBy = $request->get('order_by', 'latest'); // latest, oldest, amount_high, amount_low
        switch ($orderBy) {
            case 'oldest':
                $query->oldest();
                break;
            case 'amount_high':
                $query->orderBy('amount', 'desc');
                break;
            case 'amount_low':
                $query->orderBy('amount', 'asc');
                break;
            default: // latest
                $query->latest();
        }

        $opportunities = $query->paginate($perPage);

        return response()->json([
            'data' => $opportunities->items(),
            'pagination' => [
                'total' => $opportunities->total(),
                'per_page' => $opportunities->perPage(),
                'current_page' => $opportunities->currentPage(),
                'last_page' => $opportunities->lastPage(),
                'from' => $opportunities->firstItem(),
                'to' => $opportunities->lastItem(),
            ]
        ]);
    }

    /**
     * Ver detalles de una oportunidad
     * Incluye tareas con información de asignados
     */
    public function show($id)
    {
        $opportunity = CrmOpportunity::with([
            'client',
            'quotes:id,opportunity_id,status,total_amount,currency',
            'tasks' => function($q) {
                // Cargar tareas con información del asignado
                $q->select('id', 'opportunity_id', 'title', 'status', 'priority', 'assignee_id', 'description', 'progress', 'created_at')
                  ->with(['assignee:id,name']); // ← Cargar datos del asignado
            }
        ])->findOrFail($id);

        // Cargar actualizaciones de la oportunidad
        $opportunity->updates = \App\Models\CaseUpdate::where('opportunity_id', $id)
            ->with(['user:id,name', 'attachments'])
            ->orderBy('created_at', 'desc')
            ->get();

        // Cargar actualizaciones de las tareas
        if ($opportunity->tasks) {
            foreach ($opportunity->tasks as $task) {
                $task->updates = \App\Models\CaseUpdate::where('task_id', $task->id)
                    ->with(['user:id,name', 'attachments'])
                    ->orderBy('created_at', 'desc')
                    ->get();
            }
        }

        return response()->json([
            'data' => $opportunity
        ]);
    }

    /**
     * Obtener estadísticas de oportunidades
     */
    public function stats()
    {
        $stats = [
            'total_opportunities' => CrmOpportunity::count(),
            'total_pipeline' => CrmOpportunity::sum('amount'),
            'by_stage' => CrmOpportunity::groupBy('sales_stage')
                ->selectRaw('sales_stage, COUNT(*) as count, SUM(amount) as total')
                ->get(),
            'open_opportunities' => CrmOpportunity::whereNotIn('sales_stage', ['Closed Won', 'Closed Lost'])
                ->count(),
            'closed_won' => CrmOpportunity::where('sales_stage', 'Closed Won')
                ->count(),
            'closed_lost' => CrmOpportunity::where('sales_stage', 'Closed Lost')
                ->count(),
        ];

        return response()->json(['data' => $stats]);
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
            'task' => $task,
            'opportunity' => $opportunity
        ]);
    }

    /**
     * Agregar actualización/avance a una oportunidad
     */
    public function addUpdate(Request $request, $id)
    {
        $opportunity = CrmOpportunity::findOrFail($id);
        $user = auth()->user();

        $validated = $request->validate([
            'content' => 'required|string|min:3',
        ]);

        $update = \App\Models\CaseUpdate::create([
            'opportunity_id' => $id,
            'user_id' => $user->id,
            'content' => $validated['content'],
            'type' => 'update',
        ]);

        $update->load(['user:id,name']);

        return response()->json([
            'data' => $update,
            'message' => 'Avance registrado exitosamente'
        ], 201);
    }
}
