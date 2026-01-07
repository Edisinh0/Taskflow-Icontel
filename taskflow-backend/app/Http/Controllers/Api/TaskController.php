<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Task;
use App\Events\TaskUpdated;
use App\Services\SweetCrmService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class TaskController extends Controller
{
    protected SweetCrmService $sweetCrmService;

    public function __construct(SweetCrmService $sweetCrmService)
    {
        $this->sweetCrmService = $sweetCrmService;
    }

    /**
     * Listar tareas (con filtros opcionales)
     * GET /api/v1/tasks
     */
    public function index(Request $request)
    {
        $query = Task::with(['flow', 'assignee', 'parentTask', 'subtasks', 'crmCase', 'crmCase.client']);

        // Filtrar por flujo
        if ($request->has('flow_id')) {
            $query->where('flow_id', $request->flow_id);
        }

        // Filtrar por usuario asignado
        if ($request->has('assignee_id')) {
            $query->where('assignee_id', $request->assignee_id);
        }

        // Filtrar por estado
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        // Solo milestones
        if ($request->has('milestones_only') && $request->milestones_only) {
            $query->where('is_milestone', true);
        }

        // Solo tareas raíz (sin padre)
        if ($request->has('root_only') && $request->root_only) {
            $query->whereNull('parent_task_id');
        }

        $tasks = $query->orderBy('order')->get();

        return response()->json([
            'success' => true,
            'data' => $tasks,
        ], 200);
    }

    /**
     * Obtener tareas asignadas al usuario autenticado (activas)
     * GET /api/v1/my-tasks
     */
    public function myTasks(Request $request)
    {
        $user = auth()->user();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Usuario no autenticado',
                'tasks' => []
            ], 401);
        }

        // Filtrar tareas asignadas al usuario Y que estén activas (no completadas ni canceladas)
        $query = Task::with(['flow', 'crmCase', 'crmCase.client', 'parentTask'])
            ->where('assignee_id', $user->id)
            ->whereIn('status', ['pending', 'in_progress', 'blocked', 'paused']);

        // Permitir búsqueda
        if ($request->has('search')) {
            $search = $request->input('search');
            $query->where(function($q) use ($search) {
                $q->where('title', 'like', '%' . addslashes($search) . '%')
                  ->orWhere('description', 'like', '%' . addslashes($search) . '%');
            });
        }

        // Filtrar por prioridad
        if ($request->has('priority')) {
            $query->where('priority', $request->priority);
        }

        $tasks = $query->orderBy('priority', 'desc')
            ->orderBy('estimated_end_at', 'asc')
            ->get();

        return response()->json([
            'success' => true,
            'tasks' => $tasks,
            'count' => $tasks->count()
        ]);
    }

    /**
     * Obtener tareas del usuario actual desde SweetCRM
     * GET /api/v1/tasks/my-tasks
     */
    public function mySweetCrmTasks(Request $request)
    {
        try {
            $user = $request->user();

            // Obtener session ID desde cache o autenticar
            $sessionResult = $this->getSessionForUser($user);

            if (!$sessionResult['success']) {
                return response()->json([
                    'message' => 'Error de autenticación con SweetCRM',
                    'error' => $sessionResult['error'] ?? 'No se pudo obtener sesión'
                ], 401);
            }

            $sessionId = $sessionResult['session_id'];

            // Construir filtros para tareas del usuario
            $filters = [
                'max_results' => $request->input('limit', 100),
                'offset' => $request->input('offset', 0),
            ];

            // Filtrar por usuario asignado (sweetcrm_id del usuario actual)
            if ($user->sweetcrm_id) {
                $filters['query'] = "tasks.assigned_user_id = '{$user->sweetcrm_id}'";
            }

            // Filtrar por estado si se especifica
            if ($request->has('status')) {
                $existingQuery = $filters['query'] ?? '';
                $statusFilter = "tasks.status = '{$request->input('status')}'";
                $filters['query'] = $existingQuery ? "({$existingQuery}) AND {$statusFilter}" : $statusFilter;
            }

            // Excluir completadas por defecto (a menos que se pida explícitamente)
            if (!$request->input('include_completed', false)) {
                $existingQuery = $filters['query'] ?? '';
                $activeFilter = "tasks.status NOT IN ('Completed', 'Deferred')";
                $filters['query'] = $existingQuery ? "({$existingQuery}) AND {$activeFilter}" : $activeFilter;
            }

            // Obtener tareas desde SweetCRM
            $rawTasks = $this->sweetCrmService->getTasks($sessionId, $filters);

            // Transformar datos al formato esperado por el frontend
            $tasks = $this->transformTasks($rawTasks);

            return response()->json([
                'data' => $tasks,
                'meta' => [
                    'total' => count($tasks),
                    'offset' => $filters['offset'],
                    'limit' => $filters['max_results'],
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Error al obtener tareas de SweetCRM', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'message' => 'Error al obtener tareas',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtener session ID para el usuario actual
     */
    private function getSessionForUser($user): array
    {
        $username = config('services.sweetcrm.username');
        $password = config('services.sweetcrm.password');

        if (!$username || !$password) {
            return [
                'success' => false,
                'error' => 'Credenciales de SweetCRM no configuradas'
            ];
        }

        return $this->sweetCrmService->getCachedSession($username, $password);
    }

    /**
     * Transformar datos crudos de SweetCRM al formato del frontend
     */
    private function transformTasks(array $rawTasks): array
    {
        return array_map(function ($entry) {
            $nvl = $entry['name_value_list'] ?? [];

            return [
                'id' => $entry['id'] ?? null,
                'name' => $nvl['name']['value'] ?? 'Sin nombre',
                'description' => $nvl['description']['value'] ?? null,
                'status' => $nvl['status']['value'] ?? 'Not Started',
                'priority' => $nvl['priority']['value'] ?? 'Medium',
                'parent_type' => $nvl['parent_type']['value'] ?? null,
                'parent_id' => $nvl['parent_id']['value'] ?? null,
                'parent_name' => $nvl['parent_name']['value'] ?? null,
                'contact_id' => $nvl['contact_id']['value'] ?? null,
                'date_start' => $nvl['date_start']['value'] ?? null,
                'date_due' => $nvl['date_due']['value'] ?? null,
                'assigned_user_id' => $nvl['assigned_user_id']['value'] ?? null,
                'assigned_user_name' => $nvl['assigned_user_name']['value'] ?? null,
                'date_entered' => $nvl['date_entered']['value'] ?? null,
                'date_modified' => $nvl['date_modified']['value'] ?? null,
            ];
        }, $rawTasks);
    }

    /**
     * Crear nueva tarea
     * POST /api/v1/tasks
     */
    public function store(Request $request)
    {
        // Autorización: Solo PM/Admin pueden crear tareas (modificar estructura)
        \Illuminate\Support\Facades\Gate::authorize('create', Task::class);
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'flow_id' => 'required|exists:flows,id',
            'parent_task_id' => 'nullable|exists:tasks,id',
            'assignee_id' => 'nullable|exists:users,id',
            'priority' => 'nullable|in:low,medium,high,urgent',
            'status' => 'nullable|in:pending,blocked,in_progress,paused,completed,cancelled',
            'is_milestone' => 'nullable|boolean',
            'allow_attachments' => 'nullable|boolean', // <-- Permitir adjuntos
            'estimated_start_at' => 'nullable|date',
            'estimated_end_at' => 'nullable|date',
            // ⚠️ NO permitir que el frontend controle is_blocked
            // Este campo se calcula automáticamente en el Observer
            'depends_on_task_id' => 'nullable|exists:tasks,id',
            'depends_on_milestone_id' => 'nullable|exists:tasks,id',
        ]);

        // Validar dependencias circulares y auto-referencia
        if (isset($validated['depends_on_task_id'])) {
            // Verificar que no sea la misma tarea (aunque aún no tiene ID, prevenir en updates)
            if (isset($validated['depends_on_milestone_id']) &&
                $validated['depends_on_task_id'] === $validated['depends_on_milestone_id']) {
                return response()->json([
                    'success' => false,
                    'message' => 'Una tarea no puede depender de la misma tarea como precedente y milestone.',
                ], 422);
            }
        }

        // ✅ NO establecer is_blocked aquí - el Observer lo maneja automáticamente
        // El Observer::creating() verificará las dependencias y establecerá is_blocked correctamente

        // 🔧 Lógica especial para subtareas de milestones
        if (isset($validated['parent_task_id'])) {
            // Verificar si hay otras subtareas hermanas
            $siblingSubtasks = Task::where('parent_task_id', $validated['parent_task_id'])
                ->orderBy('order', 'asc')
                ->orderBy('created_at', 'asc')
                ->get();

            // Si es la primera subtarea, debe estar en "in_progress"
            if ($siblingSubtasks->isEmpty()) {
                if (!isset($validated['status'])) {
                    $validated['status'] = 'in_progress';
                }
            } else {
                // Si no es la primera, debe depender de la última subtarea creada
                // (solo si no se especificó otra dependencia manualmente)
                if (!isset($validated['depends_on_task_id'])) {
                    $lastSubtask = $siblingSubtasks->last();
                    $validated['depends_on_task_id'] = $lastSubtask->id;
                }
                // Si no se especificó estado, dejarla en "pending" (por defecto)
            }
        }

        $task = Task::create($validated);


        return response()->json([
            'success' => true,
            'message' => 'Tarea creada exitosamente',
            'data' => $task->load(['flow', 'assignee']),
        ], 201);
    }

    /**
     * Ver una tarea específica
     * GET /api/v1/tasks/{id}
     */
    public function show($id)
    {
        try {
            $task = Task::with([
                'flow',
                'assignee',
                'lastEditor',
                'parentTask',
                'subtasks.assignee',
                'dependencies.dependsOnTask',
                'dependents.task',
                'attachments.uploader', // Cargar adjuntos
                'crmCase',
                'updates.user:id,name', // Cargar avances
                'updates.attachments' // Cargar adjuntos de avances
            ])->findOrFail($id);

            // Verificar si está bloqueada
            // Usamos try-catch interno por si hay errores de recursión o datos corruptos
            try {
                $task->is_blocked = $task->isBlocked();
            } catch (\Exception $e) {
                \Illuminate\Support\Facades\Log::error("Error calculando isBlocked para tarea $id: " . $e->getMessage());
                // Fallback seguro
                $task->is_blocked = false; 
            }

            return response()->json([
                'success' => true,
                'data' => $task,
            ], 200);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error("Error mostrando tarea $id: " . $e->getMessage() . "\n" . $e->getTraceAsString());
            
            return response()->json([
                'success' => false,
                'message' => 'Error interno al cargar la tarea.',
                'error_debug' => env('APP_DEBUG') ? $e->getMessage() : null
            ], 500);
        }
    }

    /**
     * Actualizar tarea
     * PUT /api/v1/tasks/{id}
     */
    public function update(Request $request, $id)
{
    $task = Task::findOrFail($id);
    
    // Determinar si es una actualización de estructura o de ejecución
    $isStructuralChange = $request->hasAny([
        'title', 'description', 'parent_task_id', 'is_milestone', 
        'depends_on_task_id', 'depends_on_milestone_id', 'priority'
    ]);

    // Campos que el usuario quiere cambiar y que el usuario solicitó específicamente
    $isAssigneeChange = $request->has('assignee_id');
    $isDateChange = $request->has('estimated_end_at');

    if ($isStructuralChange) {
        \Illuminate\Support\Facades\Gate::authorize('updateStructure', $task);
    } else {
        // Para cambios de ejecución (status, progress, notes, etc.)
        // También incluimos assignee_id y estimated_end_at si el usuario tiene permiso específico
        \Illuminate\Support\Facades\Gate::authorize('execute', $task);
    }
    
    $validated = $request->validate([
        'flow_id' => 'sometimes|exists:flows,id',
        'title' => 'sometimes|string|max:255',
        'description' => 'nullable|string',
        'status' => ['sometimes', 'string', Rule::in(['pending', 'in_progress', 'completed', 'paused', 'cancelled'])],
        'assignee_id' => 'nullable|exists:users,id',
        'estimated_end_at' => 'nullable|date',
        'is_milestone' => 'sometimes|boolean',
        'allow_attachments' => 'sometimes|boolean',
        'order' => 'sometimes|integer|min:0',
        'depends_on_task_id' => 'nullable|exists:tasks,id',
        'depends_on_milestone_id' => 'nullable|exists:tasks,id',
        'progress' => 'sometimes|integer|min:0|max:100',
        'priority' => ['sometimes', 'string', Rule::in(['low', 'medium', 'high', 'urgent'])],
        'notes' => 'nullable|string',
    ]);

    // 🎯 Lógica automática de progreso al completar
    if (isset($validated['status']) && $validated['status'] === 'completed') {
        $validated['progress'] = 100;
        
        // También establecer fecha real de término si no existe
        if (!$task->actual_end_at) {
            $validated['actual_end_at'] = now();
        }
    }

    // Si el estado cambia a "in_progress", establecer fecha real de inicio si no existe
    if (isset($validated['status']) && $validated['status'] === 'in_progress') {
        if (!$task->actual_start_at) {
            $validated['actual_start_at'] = now();
        }
    }

    // 🎯 MOTOR DE CONTROL DE FLUJOS (Lógica de Bloqueo y Requisitos)
    if (isset($validated['status'])) {
        $task->refresh();
        $newStatus = $validated['status'];
        
        // 2. Validar adjuntos obligatorios al completar
        if ($newStatus === 'completed' && $task->allow_attachments) {
            if ($task->attachments()->count() === 0) {
                return response()->json([
                    'success' => false,
                    'message' => "⚠️ Requisito faltante: Debes adjuntar al menos un documento para completar esta tarea.",
                ], 422);
            }
        }
    }
    
    // Continuar con la actualización normal
    try {
        if (isset($validated['assignee_id']) && !isset($task->assigned_at)) {
            $validated['assigned_at'] = now();
        }

        // Guardar los cambios realizados
        $originalAttributes = $task->getOriginal();
        $task->update($validated);

        // Calcular qué campos cambiaron
        $changes = [];
        foreach ($validated as $key => $value) {
            if (isset($originalAttributes[$key]) && $originalAttributes[$key] != $value) {
                $changes[$key] = [
                    'old' => $originalAttributes[$key],
                    'new' => $value,
                ];
            }
        }

        // Disparar evento en tiempo real
        if (!empty($changes)) {
            broadcast(new TaskUpdated($task, $changes))->toOthers();
        }

        return response()->json([
            'success' => true,
            'message' => 'Tarea actualizada exitosamente',
            'data' => $task->load(['flow', 'assignee', 'parentTask', 'subtasks']),
        ], 200);

    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'message' => 'Error al actualizar tarea: ' . $e->getMessage(),
        ], 500);
    }
}

/**
 * Registrar un avance en la tarea
 */
public function addUpdate(Request $request, $id)
{
    $request->validate([
        'content' => 'required|string|min:3',
        'attachments.*' => 'nullable|file|max:10240', // Max 10MB
    ]);

    $task = Task::findOrFail($id);
    $user = auth()->user();

    $update = $task->updates()->create([
        'user_id' => $user->id,
        'content' => $request->content,
        'type' => \App\Models\CaseUpdate::TYPE_UPDATE,
        'case_id' => $task->case_id // Mantener vínculo con el caso si existe
    ]);

    // Manejar adjuntos
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
        'success' => true,
        'message' => 'Avance registrado correctamente',
        'update' => $update->load(['user:id,name', 'attachments'])
    ]);
}

    /**
     * Eliminar tarea
     * DELETE /api/v1/tasks/{id}
     */
    public function destroy($id)
    {
        $task = Task::findOrFail($id);

        // Autorización: Solo PM/Admin pueden eliminar
        \Illuminate\Support\Facades\Gate::authorize('delete', $task);
        $task->delete();

        return response()->json([
            'success' => true,
            'message' => 'Tarea eliminada exitosamente',
        ], 200);
    }

    public function reorder(Request $request)
    {
        // Autorización: Modificar orden es estructural (PM/Admin)
        \Illuminate\Support\Facades\Gate::authorize('create', Task::class); // Usamos create o una permission genérica de estructura
        $validated = $request->validate([
            'tasks' => 'required|array',
            'tasks.*.id' => 'required|exists:tasks,id',
            'tasks.*.order' => 'required|integer|min:0',
            'tasks.*.parent_task_id' => 'nullable|exists:tasks,id',
        ]);

        try {
            DB::beginTransaction();

            foreach ($validated['tasks'] as $taskData) {
                Task::where('id', $taskData['id'])->update([
                    'order' => $taskData['order'],
                    'parent_task_id' => $taskData['parent_task_id'] ?? null,
                ]);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Tareas reordenadas exitosamente',
            ], 200);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Error al reordenar tareas: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Mover tarea a otro milestone/parent
     * POST /api/v1/tasks/{id}/move
     */
    public function move(Request $request, $id)
    {
        $task = Task::findOrFail($id);
        
        // Autorización: Mover es cambio estructural
        \Illuminate\Support\Facades\Gate::authorize('updateStructure', $task);

        $validated = $request->validate([
            'parent_task_id' => 'nullable|exists:tasks,id',
            'order' => 'nullable|integer|min:0',
        ]);

        try {
            $task->update([
                'parent_task_id' => $validated['parent_task_id'] ?? null,
                'order' => $validated['order'] ?? 0,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Tarea movida exitosamente',
                'data' => $task->load(['flow', 'assignee', 'parentTask']),
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al mover tarea: ' . $e->getMessage(),
            ], 500);
        }
    }
}