<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\TaskRequest;
use App\Models\Task;
use App\Models\CrmCase;
use App\Models\Opportunity;
use App\Events\TaskUpdated;
use App\Services\SweetCrmService;
use App\Adapters\SugarCRM\SugarCRMApiAdapter;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class TaskController extends Controller
{
    protected SweetCrmService $sweetCrmService;
    protected SugarCRMApiAdapter $sugarCRMAdapter;

    public function __construct(SweetCrmService $sweetCrmService, SugarCRMApiAdapter $sugarCRMAdapter)
    {
        $this->sweetCrmService = $sweetCrmService;
        $this->sugarCRMAdapter = $sugarCRMAdapter;
    }

    /**
     * Listar tareas (con filtros opcionales)
     * GET /api/v1/tasks
     */
    public function index(Request $request)
    {
        $query = Task::with(['flow', 'assignee', 'creator', 'parentTask', 'subtasks', 'crmCase', 'crmCase.client', 'opportunity']);

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
     * Crear nueva tarea (compatible con SuiteCRM v4.1)
     * POST /api/v1/tasks
     */
    public function store(TaskRequest $request)
    {
        try {
            $user = auth()->user();
            
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Usuario no autenticado'
                ], 401);
            }

            $validated = $request->validated();

            // 1. Validar que el parent (Case/Opportunity) exista en BD local o SuiteCRM
            $parentRecord = $this->validateAndFindParentRecord(
                $validated['parent_type'],
                $validated['parent_id']
            );

            if (!$parentRecord) {
                return response()->json([
                    'success' => false,
                    'message' => "Caso/Oportunidad no encontrado: {$validated['parent_id']}"
                ], 404);
            }

            // Asignar según tipo
            if ($validated['parent_type'] === 'Cases') {
                $validated['case_id'] = $parentRecord->id;
            } else {
                $validated['opportunity_id'] = $parentRecord->id;
            }

            // 2. Preparar datos para la tarea local
            $localTaskData = [
                'title' => $validated['title'],
                'description' => $validated['description'] ?? null,
                'priority' => $validated['priority'],
                'status' => $validated['status'] ?? 'Not Started',
                'case_id' => $validated['case_id'] ?? null,
                'opportunity_id' => $validated['opportunity_id'] ?? null,
                'assigned_user_id' => $validated['assigned_user_id'] ?? $user->id,
                'created_by' => $user->id,
                'estimated_start_at' => $validated['date_start'],
                'estimated_end_at' => $validated['date_due'],
                'completion_percentage' => $validated['completion_percentage'] ?? 0,
                'flow_id' => $validated['flow_id'] ?? null,
                'parent_task_id' => $validated['parent_task_id'] ?? null,
                // Campos SuiteCRM
                'sweetcrm_parent_type' => $validated['parent_type'],
                'sweetcrm_parent_id' => $validated['parent_id'],
                'date_entered' => now(),
                'date_modified' => now(),
                'created_by_id' => $user->id,
            ];

            // 3. Crear tarea en BD local
            $localTask = Task::create($localTaskData);
            Log::info('Local task created', ['task_id' => $localTask->id, 'user' => $user->email]);

            // 4. Preparar datos para SuiteCRM (name_value_list)
            $nameValueList = [
                // Campos requeridos
                'name' => ['name' => 'name', 'value' => $validated['title']],
                'priority' => ['name' => 'priority', 'value' => $validated['priority']],
                'status' => ['name' => 'status', 'value' => $validated['status'] ?? 'Not Started'],
                'date_start' => ['name' => 'date_start', 'value' => $validated['date_start']],
                'date_due' => ['name' => 'date_due', 'value' => $validated['date_due']],
                'parent_type' => ['name' => 'parent_type', 'value' => $validated['parent_type']],
                'parent_id' => ['name' => 'parent_id', 'value' => $validated['parent_id']],

                // Campos opcionales
                'description' => ['name' => 'description', 'value' => $validated['description'] ?? ''],
                'parent_name' => ['name' => 'parent_name', 'value' => $parentRecord->subject ?? $parentRecord->name ?? ''],
            ];

            // Agregar completion_percentage si se proporciona
            if (isset($validated['completion_percentage']) && $validated['completion_percentage'] !== null) {
                $nameValueList['completion_percentage'] = [
                    'name' => 'completion_percentage',
                    'value' => (int) $validated['completion_percentage']
                ];
            }

            // Asignado al usuario actual en SuiteCRM o específico en request
            if (isset($validated['sweetcrm_assigned_user_id']) && $validated['sweetcrm_assigned_user_id']) {
                $nameValueList['assigned_user_id'] = [
                    'name' => 'assigned_user_id',
                    'value' => $validated['sweetcrm_assigned_user_id']
                ];
            } elseif ($user->sweetcrm_id) {
                $nameValueList['assigned_user_id'] = [
                    'name' => 'assigned_user_id',
                    'value' => $user->sweetcrm_id
                ];
                $nameValueList['assigned_user_name'] = [
                    'name' => 'assigned_user_name',
                    'value' => $user->name
                ];
            }

            Log::info('Task name_value_list prepared', [
                'fields_count' => count($nameValueList),
                'required_fields' => ['name', 'priority', 'status', 'date_start', 'date_due', 'parent_type', 'parent_id'],
                'has_dates' => isset($nameValueList['date_start']) && isset($nameValueList['date_due'])
            ]);

            // 5. Intentar crear en SuiteCRM
            $suiteTaskId = null;
            try {
                // Obtener sesión SuiteCRM
                $sessionResult = $this->getSessionForUser($user);
                if (!$sessionResult['success']) {
                    Log::warning('Could not get SuiteCRM session for task creation', ['user' => $user->email]);
                } else {
                    // Llamar set_entry en SuiteCRM
                    $suiteTaskId = $this->createTaskInSuiteCRM(
                        $sessionResult['session_id'],
                        $nameValueList
                    );

                    if ($suiteTaskId) {
                        $localTask->update([
                            'sweetcrm_id' => $suiteTaskId,
                            'sweetcrm_synced_at' => now(),
                        ]);
                        Log::info('Task synced to SuiteCRM', ['local_id' => $localTask->id, 'sweetcrm_id' => $suiteTaskId]);
                    }
                }
            } catch (\Exception $e) {
                Log::warning('Error creating task in SuiteCRM', ['error' => $e->getMessage()]);
                // No lanzar error - la tarea local ya existe
            }

            return response()->json([
                'success' => true,
                'message' => 'Tarea creada exitosamente',
                'data' => $localTask->fresh()->load(['assignee', 'crmCase', 'crmCase.client', 'opportunity']),
            ], 201);

        } catch (\Exception $e) {
            Log::error('Error creating task', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Error al crear la tarea: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Crear tarea en SuiteCRM con validación de fechas y reintentos
     *
     * @param string $sessionId Session ID válida para SuiteCRM
     * @param array $nameValueList Datos de la tarea en formato name_value_list
     * @param int $attempts Número de intento actual (para reintentos)
     * @return string|null ID de la tarea creada en SuiteCRM o null si falló
     */
    private function createTaskInSuiteCRM(string $sessionId, array $nameValueList, int $attempts = 0): ?string
    {
        try {
            // Validar y formatear fechas para SuiteCRM v4.1
            if (isset($nameValueList['date_start']['value'])) {
                $nameValueList['date_start']['value'] = $this->validateAndFormatDate(
                    $nameValueList['date_start']['value'],
                    'date_start'
                );
            }

            if (isset($nameValueList['date_due']['value'])) {
                $nameValueList['date_due']['value'] = $this->validateAndFormatDate(
                    $nameValueList['date_due']['value'],
                    'date_due'
                );
            }

            Log::info('Sending task to SuiteCRM', [
                'attempt' => $attempts + 1,
                'date_start' => $nameValueList['date_start']['value'] ?? null,
                'date_due' => $nameValueList['date_due']['value'] ?? null,
                'parent_type' => $nameValueList['parent_type']['value'] ?? null,
                'parent_id' => $nameValueList['parent_id']['value'] ?? null,
            ]);

            $response = Http::timeout(30)
                ->asForm()
                ->post(rtrim(config('services.sweetcrm.url'), '/') . '/service/v4_1/rest.php', [
                    'method' => 'set_entry',
                    'input_type' => 'JSON',
                    'response_type' => 'JSON',
                    'rest_data' => json_encode([
                        'session' => $sessionId,
                        'module' => 'Tasks',
                        'name_value_list' => $nameValueList,
                    ]),
                ]);

            if (!$response->successful()) {
                Log::warning('SuiteCRM set_entry HTTP error', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                    'attempt' => $attempts + 1
                ]);

                // Reintentar automáticamente si no es el último intento
                if ($attempts < 2) {
                    Log::info('Retrying SuiteCRM task creation', [
                        'attempt' => $attempts + 1,
                        'next_attempt' => $attempts + 2
                    ]);
                    sleep(2); // Esperar 2 segundos antes de reintentar
                    return $this->createTaskInSuiteCRM($sessionId, $nameValueList, $attempts + 1);
                }

                return null;
            }

            $data = $response->json();

            // Verificar si hay error en la respuesta JSON
            if (isset($data['name']) && $data['name'] === 'invalid_session') {
                Log::error('SuiteCRM session invalid', ['attempt' => $attempts + 1]);
                return null;
            }

            // Extraer ID de la tarea creada
            if (isset($data['id']) && !empty($data['id'])) {
                Log::info('Task created in SuiteCRM successfully', [
                    'sweetcrm_id' => $data['id'],
                    'attempt' => $attempts + 1
                ]);
                return $data['id'];
            }

            Log::warning('SuiteCRM set_entry returned no ID', [
                'response' => $data,
                'attempt' => $attempts + 1
            ]);
            return null;

        } catch (\Exception $e) {
            Log::error('Exception creating task in SuiteCRM', [
                'error' => $e->getMessage(),
                'attempt' => $attempts + 1,
                'trace' => $e->getTraceAsString()
            ]);

            // Reintentar en caso de exception de red
            if ($attempts < 2 && strpos($e->getMessage(), 'cURL') !== false) {
                Log::info('Retrying after network error', ['attempt' => $attempts + 1]);
                sleep(2);
                return $this->createTaskInSuiteCRM($sessionId, $nameValueList, $attempts + 1);
            }

            return null;
        }
    }

    /**
     * Validar y formatear fecha al formato requerido por SuiteCRM v4.1 (Y-m-d H:i:s)
     *
     * @param string $dateString Fecha en cualquier formato
     * @param string $fieldName Nombre del campo (para logging)
     * @return string Fecha en formato Y-m-d H:i:s
     */
    private function validateAndFormatDate(string $dateString, string $fieldName = 'date'): string
    {
        try {
            // Intentar con formatos comunes primero
            $formats = [
                'Y-m-d H:i:s',      // Ya en formato SuiteCRM
                'Y-m-d\TH:i',       // ISO datetime-local
                'Y-m-d H:i',        // Datetime sin segundos
                'Y-m-d',            // Solo fecha
            ];

            $dateObj = null;
            foreach ($formats as $format) {
                $dateObj = \DateTime::createFromFormat($format, $dateString);
                if ($dateObj) {
                    break;
                }
            }

            // Si ningún formato coincide, intentar parseado automático
            if (!$dateObj) {
                $dateObj = new \DateTime($dateString);
            }

            $formatted = $dateObj->format('Y-m-d H:i:s');

            if ($formatted !== $dateString) {
                Log::info('Date formatted for SuiteCRM', [
                    'field' => $fieldName,
                    'original' => $dateString,
                    'formatted' => $formatted
                ]);
            }

            return $formatted;

        } catch (\Exception $e) {
            Log::error('Error formatting date for SuiteCRM', [
                'field' => $fieldName,
                'date' => $dateString,
                'error' => $e->getMessage()
            ]);

            // Devolver tal cual si no se puede parsear
            return $dateString;
        }
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

    /**
     * Delegar tarea a Operaciones
     * POST /api/v1/tasks/{id}/delegate
     */
    public function delegate(Request $request, Task $task)
    {
        // Solo usuarios de Ventas pueden delegar
        $user = auth()->user();
        if (!$user || $user->department !== 'Ventas') {
            return response()->json([
                'message' => 'Solo usuarios de Ventas pueden delegar tareas'
            ], 403);
        }

        $validated = $request->validate([
            'delegated_to_user_id' => 'required|exists:users,id',
            'reason' => 'required|string|max:500'
        ]);

        try {
            $delegatedTo = \App\Models\User::findOrFail($validated['delegated_to_user_id']);

            // Obtener sesión de SugarCRM
            $username = config('services.sweetcrm.username');
            $password = config('services.sweetcrm.password');
            $sessionResult = $this->sweetCrmService->getCachedSession($username, $password);

            if (!$sessionResult['success']) {
                return response()->json([
                    'message' => 'No se pudo conectar con SugarCRM'
                ], 500);
            }

            // Usar el servicio de workflow
            $workflowService = app(\App\Services\SugarCRMWorkflowService::class);
            $result = $workflowService->delegateTaskToOperations(
                $task,
                $delegatedTo,
                $sessionResult['session_id'],
                $validated['reason']
            );

            if (!$result['success']) {
                return response()->json([
                    'message' => $result['message']
                ], 400);
            }

            // Recargar la tarea
            $task->refresh();

            return response()->json([
                'success' => true,
                'message' => $result['message'],
                'data' => $task->load(['assignee', 'delegatedToUser', 'originalSalesUser'])
            ]);

        } catch (\Exception $e) {
            Log::error('Error delegating task', [
                'task_id' => $task->id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'message' => 'Error al delegar tarea: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtener tareas delegadas pendientes para el usuario actual
     * GET /api/v1/tasks/delegated
     */
    public function getDelegatedTasks(Request $request)
    {
        $user = auth()->user();

        if (!$user) {
            return response()->json([
                'message' => 'No autenticado'
            ], 401);
        }

        // Solo Operaciones ve tareas delegadas
        if ($user->department !== 'Operaciones') {
            return response()->json([
                'data' => [],
                'total' => 0
            ]);
        }

        $workflowService = app(\App\Services\SugarCRMWorkflowService::class);
        $tasks = $workflowService->getPendingDelegatedTasks($user);

        return response()->json([
            'data' => $tasks->map(function ($task) {
                return [
                    'id' => $task->id,
                    'title' => $task->title,
                    'description' => $task->description,
                    'status' => $task->status,
                    'priority' => $task->priority,
                    'delegation_reason' => $task->delegation_reason,
                    'delegated_at' => $task->delegated_to_ops_at,
                    'case' => $task->crmCase ? [
                        'id' => $task->crmCase->id,
                        'case_number' => $task->crmCase->case_number,
                        'subject' => $task->crmCase->subject,
                    ] : null,
                    'original_sales_user' => $task->originalSalesUser ? [
                        'id' => $task->originalSalesUser->id,
                        'name' => $task->originalSalesUser->name,
                        'email' => $task->originalSalesUser->email,
                    ] : null,
                ];
            }),
            'total' => $tasks->count()
        ]);
    }

    /**
     * Marcar tarea delegada como completada
     * POST /api/v1/tasks/{id}/complete-delegation
     */
    public function completeDelegation(Task $task)
    {
        $user = auth()->user();

        if (!$user || $user->department !== 'Operaciones') {
            return response()->json([
                'message' => 'Solo usuarios de Operaciones pueden completar tareas delegadas'
            ], 403);
        }

        if ($task->delegation_status !== 'delegated') {
            return response()->json([
                'message' => 'La tarea no está delegada o ya fue completada'
            ], 400);
        }

        try {
            $workflowService = app(\App\Services\SugarCRMWorkflowService::class);
            $result = $workflowService->completeDelegatedTask($task);

            if (!$result['success']) {
                return response()->json([
                    'message' => $result['message']
                ], 400);
            }

            $task->refresh();

            return response()->json([
                'success' => true,
                'message' => $result['message'],
                'data' => $task
            ]);

        } catch (\Exception $e) {
            Log::error('Error completing delegated task', [
                'task_id' => $task->id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'message' => 'Error al completar tarea: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Validar y encontrar registro parent (Case u Opportunity)
     * Soporta búsqueda por ID local o sweetcrm_id para máxima compatibilidad
     *
     * @param string $parentType Tipo de parent: 'Cases' o 'Opportunities'
     * @param string $parentId ID del parent (local o SuiteCRM)
     * @return Model|null Modelo encontrado o null si no existe
     */
    private function validateAndFindParentRecord(string $parentType, string $parentId)
    {
        try {
            if ($parentType === 'Cases') {
                $record = CrmCase::where('id', $parentId)
                    ->orWhere('sweetcrm_id', $parentId)
                    ->first();

                if ($record) {
                    Log::info('Parent Case found', [
                        'parent_id' => $parentId,
                        'local_id' => $record->id,
                        'sweetcrm_id' => $record->sweetcrm_id
                    ]);
                    return $record;
                }
            } else {
                $record = Opportunity::where('id', $parentId)
                    ->orWhere('sweetcrm_id', $parentId)
                    ->first();

                if ($record) {
                    Log::info('Parent Opportunity found', [
                        'parent_id' => $parentId,
                        'local_id' => $record->id,
                        'sweetcrm_id' => $record->sweetcrm_id
                    ]);
                    return $record;
                }
            }

            Log::warning('Parent record not found', [
                'parent_type' => $parentType,
                'parent_id' => $parentId
            ]);
            return null;

        } catch (\Exception $e) {
            Log::error('Error validating parent record', [
                'parent_type' => $parentType,
                'parent_id' => $parentId,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }
}