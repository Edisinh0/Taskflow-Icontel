<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\CaseResource;
use App\Http\Resources\CaseDetailResource;
use App\Models\CrmCase;
use App\Models\CaseUpdate; // Nuevo
use App\Models\User;      // Nuevo
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CaseController extends Controller
{
    /**
     * Listar casos con filtros y paginación optimizada
     * Usa Eager Loading para evitar N+1 y API Resources para reducir payload
     */
    public function index(Request $request)
    {
        $perPage = min(50, max(10, (int) $request->get('per_page', 20)));

        // Eager loading con campos específicos para optimizar
        $query = CrmCase::with([
            'client:id,name', // Solo campos necesarios
            'assignedUser:id,name,department,sweetcrm_id'
        ])
        ->withCount('tasks'); // Conteo eficiente sin cargar la relación completa

        // FILTER BY AUTHENTICATED USER - Cases should show only those assigned to the current user
        $user = auth()->user();
        if ($user && $user->sweetcrm_id) {
            $query->where('sweetcrm_assigned_user_id', $user->sweetcrm_id);
        } else {
            // If user is not linked to SweetCRM, show no cases
            $query->where('id', 0);
        }

        // Filtrar por cliente
        if ($request->has('client_id')) {
            $query->where('client_id', $request->client_id);
        }

        // Búsqueda optimizada con índices
        if ($request->has('search') && $request->search) {
            $search = trim($request->input('search'));
            if (!empty($search)) {
                $query->where(function($q) use ($search) {
                    $q->where('subject', 'like', '%' . addslashes($search) . '%')
                      ->orWhere('case_number', 'like', '%' . addslashes($search) . '%')
                      ->orWhereHas('client', function($cq) use ($search) {
                          $cq->where('name', 'like', '%' . addslashes($search) . '%');
                      });
                });
            }
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

        // Optional: Explicit assigned_to_me filter (for backward compatibility)
        // This is now redundant but kept for explicit requests
        if ($request->has('assigned_to_me') && !$request->assigned_to_me) {
            // If user explicitly sets assigned_to_me=false, show all cases (admin only)
            // For now, we still enforce the filter. Remove the filter below to allow all cases.
            // $query = CrmCase::with(...); // Reset query - commented out, always filter by user
        }

        // Ordenar y paginar: Primero por creación en CRM, luego por creación local
        $paginator = $query->orderBy('sweetcrm_created_at', 'desc')
                           ->orderBy('created_at', 'desc')
                           ->paginate($perPage);

        // Retornar usando el Resource para optimizar el JSON
        return CaseResource::collection($paginator);
    }

    /**
     * Obtener casos asignados al usuario autenticado (activos)
     * GET /api/v1/my-cases
     * Ahora con paginación y optimizado
     */
    public function myCases(Request $request)
    {
        $user = auth()->user();

        if (!$user || !$user->sweetcrm_id) {
            return response()->json([
                'data' => [],
                'meta' => [
                    'current_page' => 1,
                    'last_page' => 1,
                    'per_page' => 20,
                    'total' => 0
                ],
                'message' => 'Usuario no vinculado a SweetCRM'
            ]);
        }

        $perPage = min(50, max(10, (int) $request->get('per_page', 20)));

        // Estados que se consideran cerrados/inactivos
        $closedStatuses = ['Cerrado', 'Rechazado', 'Duplicado', 'Closed', 'Rejected', 'Duplicate', 'Merged', 'Closed_Closed'];

        // Query base con eager loading optimizado
        $query = CrmCase::with([
            'client:id,name',
            'tasks' => function($q) use ($user) {
                // Solo cargar tareas asignadas AL USUARIO ACTUAL y activas
                $q->whereIn('status', ['pending', 'in_progress'])
                  ->where('assignee_id', $user->id) // Filtrar por usuario actual
                  ->select('id', 'title', 'status', 'priority', 'case_id', 'assignee_id',
                           'sla_due_date', 'estimated_start_at', 'estimated_end_at',
                           'actual_start_at', 'actual_end_at', 'progress')
                  ->with('assignee:id,name')
                  ->latest()
                  ->limit(5); // Solo las 5 más recientes por caso
            }
        ])
        ->withCount(['tasks' => function($q) use ($user) {
            // Contar solo tareas del usuario actual
            $q->whereIn('status', ['pending', 'in_progress'])
              ->where('assignee_id', $user->id);
        }])
        ->where('sweetcrm_assigned_user_id', $user->sweetcrm_id)
        // Excluir casos cerrados/inactivos (usar whereNotIn es más legible y seguro)
        ->where(function($q) use ($closedStatuses) {
            $q->whereNull('status')
              ->orWhere('status', '')
              ->orWhereNotIn('status', $closedStatuses);
        });

        // Permitir búsqueda
        if ($request->has('search') && $request->search) {
            $search = trim($request->input('search'));
            if (!empty($search)) {
                $query->where(function($q) use ($search) {
                    $q->where('subject', 'like', '%' . addslashes($search) . '%')
                      ->orWhere('case_number', 'like', '%' . addslashes($search) . '%');
                });
            }
        }

        // Paginar: Ordenar por creación en CRM de forma descendente
        $paginator = $query->orderBy('sweetcrm_created_at', 'desc')
                           ->orderBy('created_at', 'desc')
                           ->paginate($perPage);

        // Formato personalizado para myCases (incluye tareas)
        return response()->json([
            'data' => $paginator->items(),
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
                'from' => $paginator->firstItem(),
                'to' => $paginator->lastItem()
            ],
            'links' => [
                'first' => $paginator->url(1),
                'last' => $paginator->url($paginator->lastPage()),
                'prev' => $paginator->previousPageUrl(),
                'next' => $paginator->nextPageUrl()
            ]
        ]);
    }

    /**
     * Obtener detalle de un caso con sus tareas
     * Usa CaseDetailResource para formato optimizado
     */
    /**
     * Obtener detalle de un caso con sus tareas, updates y metadatos
     */
    public function show($id)
    {
        $case = CrmCase::with([
            'client:id,name,email',
            'assignedUser:id,name,email,department,sweetcrm_id',
            'closureRequester:id,name',
            'closureRequestedBy:id,name',  // Nuevo: Usuario que solicitó el cierre
            'closureApprovedBy:id,name',   // Nuevo: Usuario que aprobó el cierre
            'latestClosureRequest',        // Nuevo: Última solicitud de cierre
            'updates.user:id,name', // Cargar usuario de cada update
            'updates.attachments', // Cargar adjuntos de updates
            'tasks' => function($q) {
                $q->select('id', 'title', 'description', 'status', 'priority', 'case_id', 'assignee_id',
                           'sla_due_date', 'estimated_start_at', 'estimated_end_at',
                           'actual_start_at', 'actual_end_at', 'progress', 'created_at', 'sweetcrm_synced_at')
                  ->with('assignee:id,name')
                  ->orderBy('created_at', 'desc');
            }
        ])->findOrFail($id);

        return new CaseDetailResource($case);
    }


    public function addUpdate(Request $request, $id)
    {
        $request->validate([
            'content' => 'required|string|min:3',
            'attachments.*' => 'nullable|file|max:10240', // Max 10MB
        ]);

        $case = CrmCase::findOrFail($id);
        $user = auth()->user();

        $update = $case->updates()->create([
            'user_id' => $user->id,
            'content' => $request->content,
            'type' => CaseUpdate::TYPE_UPDATE
        ]);

        // Manejar adjuntos si existen
        if ($request->hasFile('attachments')) {
            foreach ($request->file('attachments') as $file) {
                $path = $file->store('update_attachments', 'public');
                $update->attachments()->create([
                    'user_id' => $user->id,
                    'name' => $file->getClientOriginalName(),
                    'file_path' => $path,
                    'file_type' => $file->getMimeType(),
                    'file_size' => $file->getSize(),
                ]);
            }
        }

        return response()->json([
            'message' => 'Avance registrado correctamente',
            'update' => $update->load(['user:id,name', 'attachments'])
        ]);
    }

    /**
     * Eliminar un avance
     */
    public function deleteUpdate($id)
    {
        $update = \App\Models\CaseUpdate::findOrFail($id);
        $user = auth()->user();

        // Validar permisos (Solo el autor o admin)
        if ($update->user_id !== $user->id && !in_array($user->role, ['admin', 'pm', 'project_manager'])) {
            return response()->json(['message' => 'No tienes permiso para eliminar este avance'], 403);
        }

        // Eliminar archivos físicos
        foreach ($update->attachments as $attachment) {
            if (\Illuminate\Support\Facades\Storage::disk('public')->exists($attachment->file_path)) {
                \Illuminate\Support\Facades\Storage::disk('public')->delete($attachment->file_path);
            }
        }

        $update->delete();

        return response()->json([
            'success' => true,
            'message' => 'Avance eliminado correctamente'
        ]);
    }

    /**
     * Solicitar cierre del caso (por el asignado)
     */
    /**
     * @deprecated Use CaseClosureRequestController::store instead
     * Este endpoint ha sido reemplazado por el nuevo sistema de solicitud de cierre.
     * Use: POST /api/v1/cases/{caseId}/request-closure
     */
    public function requestClosure(Request $request, $id)
    {
        \Illuminate\Support\Facades\Log::warning('DEPRECATED: Usando endpoint antiguo requestClosure', [
            'case_id' => $id,
            'user_id' => auth()->id(),
            'message' => 'Use CaseClosureRequestController::store instead'
        ]);

        return response()->json([
            'success' => false,
            'message' => 'Este endpoint está deprecado. Use POST /api/v1/cases/{caseId}/request-closure en su lugar.',
            'new_endpoint' => 'POST /api/v1/cases/{caseId}/request-closure'
        ], 410); // 410 Gone
    }

    /**
     * @deprecated Use CaseClosureRequestController::approve instead
     * Este endpoint ha sido reemplazado por el nuevo sistema de aprobación de cierre.
     * Use: POST /api/v1/closure-requests/{closureRequestId}/approve
     */
    public function approveClosure(Request $request, $id)
    {
        \Illuminate\Support\Facades\Log::warning('DEPRECATED: Usando endpoint antiguo approveClosure', [
            'case_id' => $id,
            'user_id' => auth()->id(),
            'message' => 'Use CaseClosureRequestController::approve instead'
        ]);

        return response()->json([
            'success' => false,
            'message' => 'Este endpoint está deprecado. Use POST /api/v1/closure-requests/{closureRequestId}/approve en su lugar.',
            'new_endpoint' => 'POST /api/v1/closure-requests/{closureRequestId}/approve'
        ], 410); // 410 Gone
    }

    /**
     * @deprecated Use CaseClosureRequestController::reject instead
     * Este endpoint ha sido reemplazado por el nuevo sistema de rechazo de cierre.
     * Use: POST /api/v1/closure-requests/{closureRequestId}/reject
     */
    public function rejectClosure(Request $request, $id)
    {
        \Illuminate\Support\Facades\Log::warning('DEPRECATED: Usando endpoint antiguo rejectClosure', [
            'case_id' => $id,
            'user_id' => auth()->id(),
            'message' => 'Use CaseClosureRequestController::reject instead'
        ]);

        return response()->json([
            'success' => false,
            'message' => 'Este endpoint está deprecado. Use POST /api/v1/closure-requests/{closureRequestId}/reject en su lugar.',
            'new_endpoint' => 'POST /api/v1/closure-requests/{closureRequestId}/reject'
        ], 410); // 410 Gone
    }

    /**
     * Obtener estadísticas de casos
     * Optimizado con consultas agregadas
     */
    public function stats()
    {
        // Usar caché para estadísticas si es posible (opcional)
        return response()->json([
            'total' => CrmCase::count(),
            'by_status' => CrmCase::select('status', DB::raw('count(*) as count'))
                ->groupBy('status')
                ->get(),
            'by_priority' => CrmCase::select('priority', DB::raw('count(*) as count'))
                ->groupBy('priority')
                ->get(),
            'by_area' => DB::table('crm_cases')
                ->join('users', 'crm_cases.sweetcrm_assigned_user_id', '=', 'users.sweetcrm_id')
                ->select('users.department as area', DB::raw('count(*) as count'))
                ->groupBy('users.department')
                ->get(),
            'tasks_total' => DB::table('tasks')
                ->whereNotNull('case_id')
                ->count()
        ]);
    }
}
