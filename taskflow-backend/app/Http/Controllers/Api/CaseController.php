<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CrmCase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CaseController extends Controller
{
    /**
     * Listar casos con filtros
     */
    public function index(Request $request)
    {
        $query = CrmCase::with(['client', 'assignedUser']);

        // Filtrar por cliente
        if ($request->has('client_id')) {
            $query->where('client_id', $request->client_id);
        }

        // Búsqueda
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('subject', 'like', "%$search%")
                  ->orWhere('case_number', 'like', "%$search%")
                  ->orWhere('description', 'like', "%$search%")
                  ->orWhereHas('client', function($cq) use ($search) {
                      $cq->where('name', 'like', "%$search%");
                  });
            });
        }

        // Filtro de estado
        if ($request->has('status') && $request->status !== 'all') {
            $query->where('status', $request->status);
        }

        // Filtro de prioridad
        if ($request->has('priority') && $request->priority !== 'all') {
            $query->where('priority', $request->priority);
        }

        // Filtro de área (Departamento del usuario asignado)
        if ($request->has('area') && $request->area !== 'all') {
            $query->whereHas('assignedUser', function($q) use ($request) {
                $q->where('department', $request->area);
            });
        }

        // Filtro de "Mis Casos"
        if ($request->has('assigned_to_me') && $request->assigned_to_me) {
            $user = auth()->user();
            if ($user && $user->sweetcrm_id) {
                $query->where('sweetcrm_assigned_user_id', $user->sweetcrm_id);
            } else {
                $query->where('id', 0); // No devolver nada si no tiene ID de CRM
            }
        }

        return $query->latest()->paginate($request->get('per_page', 50));
    }

    /**
     * Obtener detalle de un caso con sus tareas
     */
    public function show($id)
    {
        $case = CrmCase::with([
            'client', 
            'tasks' => function($q) {
                $q->latest();
            },
            'tasks.assignee'
        ])->findOrFail($id);

        return response()->json($case);
    }

    /**
     * Obtener estadísticas de casos
     */
    public function stats()
    {
        return response()->json([
            'total' => CrmCase::count(),
            'by_status' => CrmCase::select('status', \DB::raw('count(*) as count'))
                ->groupBy('status')
                ->get(),
            'by_priority' => CrmCase::select('priority', \DB::raw('count(*) as count'))
                ->groupBy('priority')
                ->get(),
            'by_area' => DB::table('crm_cases')
                ->join('users', 'crm_cases.sweetcrm_assigned_user_id', '=', 'users.sweetcrm_id')
                ->select('users.department as area', DB::raw('count(*) as count'))
                ->groupBy('users.department')
                ->get(),
        ]);
    }
}
