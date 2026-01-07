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

        // Filtro de "Mis Casos"
        if ($request->has('assigned_to_me') && $request->assigned_to_me) {
            $user = auth()->user();
            if ($user && $user->sweetcrm_id) {
                $query->where('sweetcrm_assigned_user_id', $user->sweetcrm_id);
            } else {
                $query->where('id', 0); // No devolver nada si no tiene ID de CRM
            }
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
    public function requestClosure(Request $request, $id)
    {
        $case = CrmCase::findOrFail($id);
        $user = auth()->user();

        // Verificar si ya está solicitado
        if ($case->closure_requested) {
            return response()->json(['message' => 'El cierre ya ha sido solicitado'], 400);
        }

        // Registrar solicitud
        $case->update([
            'closure_requested' => true,
            'closure_requested_at' => now(),
            'closure_requested_by' => $user->id
        ]);

        // Registrar avance automático
        $case->updates()->create([
            'user_id' => $user->id,
            'content' => 'Ha solicitado el cierre del caso. Pendiente de aprobación por el creador.',
            'type' => CaseUpdate::TYPE_CLOSURE_REQUEST
        ]);

        return response()->json(['message' => 'Solicitud de cierre enviada correctamente']);
    }

    /**
     * Aprobar cierre del caso (por el creador)
     */
    public function approveClosure(Request $request, $id)
    {
        $case = CrmCase::findOrFail($id);
        $user = auth()->user();

        $case->update([
            'status' => 'Cerrado', // O el estado final que corresponda
            'closure_requested' => false,
            'closure_rejection_reason' => null
        ]);

        // Registrar avance
        $case->updates()->create([
            'user_id' => $user->id,
            'content' => 'Ha aprobado el cierre del caso. El caso ha sido finalizado.',
            'type' => CaseUpdate::TYPE_CLOSURE_APPROVED
        ]);

        return response()->json(['message' => 'Caso cerrado correctamente']);
    }

    /**
     * Rechazar cierre del caso
     */
    public function rejectClosure(Request $request, $id)
    {
        $request->validate([
            'reason' => 'required|string|min:5'
        ]);

        $case = CrmCase::findOrFail($id);
        $user = auth()->user();

        $case->update([
            'closure_requested' => false,
            'closure_rejection_reason' => $request->reason
        ]);

        // Registrar avance con la razón
        $case->updates()->create([
            'user_id' => $user->id,
            'content' => "Solicitud de cierre rechazada. Razón: {$request->reason}",
            'type' => CaseUpdate::TYPE_CLOSURE_REJECTED
        ]);

        return response()->json(['message' => 'Solicitud de cierre rechazada']);
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
