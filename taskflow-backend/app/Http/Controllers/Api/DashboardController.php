<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\SweetCrmService;
use App\Services\CrmDashboardService;
use App\DTOs\SugarCRM\SugarCRMCaseDTO;
use App\DTOs\SugarCRM\SugarCRMTaskDTO;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class DashboardController extends Controller
{
    protected SweetCrmService $sweetCrmService;
    protected CrmDashboardService $crmDashboardService;

    public function __construct(SweetCrmService $sweetCrmService, CrmDashboardService $crmDashboardService)
    {
        $this->sweetCrmService = $sweetCrmService;
        $this->crmDashboardService = $crmDashboardService;
    }

    /**
     * Obtener contenido (Casos y Tareas) para el Dashboard
     * Soporta 'my' (Personal) y 'area' (Equipo/Seguridad)
     * GET /api/v1/dashboard/my-content
     */
    public function getMyContent(Request $request)
    {
        $user = $request->user();
        
        // 1. Validar conexión SweetCRM
        // Necesitamos credenciales o un token activo. 
        // Por simplicidad, usamos las credenciales del sistema o requerimos re-auth si no hay token persistente.
        // Dado que el Service usa autenticación por request normalmente, 
        // intentaremos usar una sesión de "Sistema" o las credenciales del usuario si las tuviéramos guardadas (security risk).
        // MEJOR OPCIÓN: Usar un usuario de sistema (API User) configurado en .env para leer datos,
        // o pedir el session_id si el frontend lo tiene.
        // ASUMIMOS: Usamos la cuenta de servicio (config services.sweetcrm) para leer datos filtrados.
        
        $adminUsername = config('services.sweetcrm.username'); // Necesitamos esto en config
        // Update: SweetCrmService authenticated with configured credentials in sync methods.
        // Let's use a generic session for reading.
        
        // TODO: En producción, lo ideal es usar el token del usuario si se logueó con SweetCRM 
        // para respetar sus permisos reales de Sugar (ACL).
        // Pero el AuthController actual no guarda el session_id de Sugar en la sesión de Laravel/Sanctum explícitamente persistente 
        // más allá del login response.
        
        // Solución Pragmática: Usar credenciales maestras (.env) para leer y filtrar por ID de usuario.
        // Utilizar caché de sesión para reducir overhead de autenticación
        $sessionResult = $this->sweetCrmService->getCachedSession(
             config('services.sweetcrm.username'),
             config('services.sweetcrm.password') // Asumiendo que están en config o .env
        );

        if (!$sessionResult['success']) {
             // Fallback: intentar credenciales hardcoded o error
             return response()->json(['message' => 'No se pudo conectar a SweetCRM'], 503);
        }

        $sessionId = $sessionResult['session_id'];
        $sweetCrmUserId = $user->sweetcrm_id;

        if (!$sweetCrmUserId) {
            return response()->json([
                'cases' => [],
                'tasks' => [],
                'message' => 'Usuario no vinculado a SweetCRM'
            ]);
        }

        // 2. Construir Filtros
        $viewMode = $request->query('view', 'my'); // 'my' | 'area'

        // IMPORTANTE: Incluir casos con status vacío (activos por defecto en SweetCRM)
        // O casos que NO están cerrados. El OR con status='' y IS NULL es crítico para casos activos sin estado explícito.
        $caseStatusFilter = "(cases.status IS NULL OR cases.status = '' OR cases.status NOT IN ('Closed', 'Rejected', 'Duplicate', 'Merged', 'Cerrado', 'Cerrado_Cerrado'))";
        $taskStatusFilter = "(tasks.status IS NULL OR tasks.status = '' OR tasks.status NOT IN ('Completed', 'Deferred'))"; // Adjust as per SugarCRM standard values

        if ($viewMode === 'my') {
            // Filtrar casos por usuario Y estado activo
            // IMPORTANTE: Mostrar casos donde el usuario es:
            // - assigned_user_id: Casos asignados al usuario (aunque los haya creado otra persona)
            // - created_by: Casos creados por el usuario (aunque estén asignados a otra persona)
            $caseQuery = "(cases.assigned_user_id = '$sweetCrmUserId' OR cases.created_by = '$sweetCrmUserId') AND $caseStatusFilter";

            // Filtrar tareas por usuario Y excluir completadas/diferidas
            $taskQuery = "tasks.assigned_user_id = '$sweetCrmUserId' AND $taskStatusFilter";
        } else {
            // Lógica de Área:
            // Intentamos filtrar por SecurityGroups si es posible, o por visibilidad general pero solo activos.
            // Si la instalación de Sugar usa asignación de equipos, idealmente filtramos por team_id del usuario.
            // Como fallback seguro para 'Area': todos los casos activos visibles (limitado).
            $caseQuery = $caseStatusFilter;
            $taskQuery = ""; // Sin filtro para área, mostrar todas las tareas
        }

        // 3. Obtener Datos
        Log::info('🔍 Dashboard Query Filters', [
            'user_id' => $user->id,
            'sweetcrm_id' => $sweetCrmUserId,
            'view_mode' => $viewMode,
            'case_query' => $caseQuery,
            'task_query' => $taskQuery
        ]);

        $rawCases = $this->sweetCrmService->getCases($sessionId, ['query' => $caseQuery, 'max_results' => 50]);
        $rawTasks = $this->sweetCrmService->getTasks($sessionId, ['query' => $taskQuery, 'max_results' => 50]);

        Log::info('📊 Dashboard Data Retrieved', [
            'cases_count' => count($rawCases),
            'tasks_count' => count($rawTasks),
            'case_numbers' => array_map(function($case) {
                $nvl = $case['name_value_list'] ?? [];
                return $nvl['case_number']['value'] ?? 'N/A';
            }, $rawCases),
            'first_case_sample' => !empty($rawCases) ? [
                'id' => $rawCases[0]['id'] ?? 'N/A',
                'case_number' => $rawCases[0]['name_value_list']['case_number']['value'] ?? 'N/A',
                'name' => $rawCases[0]['name_value_list']['name']['value'] ?? 'N/A',
                'status' => $rawCases[0]['name_value_list']['status']['value'] ?? 'N/A',
                'assigned_user_id' => $rawCases[0]['name_value_list']['assigned_user_id']['value'] ?? 'N/A',
            ] : null,
            'first_task_sample' => !empty($rawTasks) ? [
                'name' => $rawTasks[0]['name_value_list']['name']['value'] ?? 'N/A',
                'status' => $rawTasks[0]['name_value_list']['status']['value'] ?? 'N/A',
            ] : null
        ]);

        // 4. Transformar a DTOs
        $cases = collect($rawCases)->map(fn($c) => SugarCRMCaseDTO::fromSugarCRMResponse($c)->toArray());
        $tasks = collect($rawTasks)->map(fn($t) => SugarCRMTaskDTO::fromSugarCRMResponse($t)->toArray());

        // 5. Estructura Jerárquica (Solo para endpoint, el frontend también puede hacerlo)
        // Agrupar tareas hijas dentro de sus casos padres si existen en la respuesta.
        
        $casesById = $cases->keyBy('id');
        $orphanTasks = [];

        foreach ($tasks as $task) {
            $parentId = $task['parent_id'];
            $parentType = $task['parent_type'];

            if ($parentType === 'Cases' && $parentId && $casesById->has($parentId)) {
                // Agregar a la lista de tareas del caso
                $case = $casesById->get($parentId);
                $case['tasks'][] = $task;
                $casesById->put($parentId, $case);
            } else {
                $orphanTasks[] = $task;
            }
        }

        return response()->json([
            'cases' => $casesById->values(), // Casos con sus tareas anidadas
            'tasks' => $orphanTasks,         // Tareas sueltas (sin caso visible o de otro tipo)
            'view_mode' => $viewMode
        ]);
    }

    /**
     * Obtener contenido del dashboard basado en el área del usuario
     * Para Ventas/Comercial: Oportunidades + Tareas
     * Para otros: Casos + Tareas
     * GET /api/v1/dashboard/area-content
     */
    public function getAreaBasedContent(Request $request)
    {
        try {
            $user = $request->user();

            if (!$user || !$user->sweetcrm_id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Usuario no autenticado o sin ID de SweetCRM'
                ], 401);
            }

            // Determinar el área/departamento del usuario
            $department = strtolower($user->department ?? '');
            $isSalesTeam = in_array($department, ['ventas', 'comercial', 'sales', 'commercial']);

            if ($isSalesTeam) {
                // Para ventas: traer Oportunidades + Tareas
                return $this->getSalesTeamContent($request, $user);
            } else {
                // Para otros: traer Casos + Tareas (comportamiento actual)
                return $this->getOperationsTeamContent($request, $user);
            }

        } catch (\Exception $e) {
            Log::error('Error en getAreaBasedContent: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener contenido del dashboard',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtener Casos + Tareas para equipo de Operaciones
     * Soporta viewMode: 'my' (mis casos) o 'area' (casos del área)
     */
    protected function getOperationsTeamContent(Request $request, $user)
    {
        try {
            $userSweetCrmId = $user->sweetcrm_id;
            $viewMode = $request->query('view', 'my'); // 'my' o 'area'

            Log::info('🔍 Dashboard Operations - Starting data fetch from LOCAL DATABASE', [
                'user_id' => $user->id,
                'user_name' => $user->name,
                'sweetcrm_id' => $userSweetCrmId,
                'view_mode' => $viewMode
            ]);

            // Consultar CASOS desde la base de datos local
            $casesQuery = \App\Models\CrmCase::with('client')->whereNotIn('status', ['Closed', 'Rejected', 'Duplicate', 'Merged']);

            if ($viewMode === 'my') {
                // Solo casos asignados al usuario actual
                $casesQuery->where('sweetcrm_assigned_user_id', $userSweetCrmId);
            }
            // Si es 'area', traer todos los casos activos (sin filtro de usuario)

            $cases = $casesQuery->orderBy('created_at', 'desc')
                ->limit(100)
                ->get();

            Log::info('📋 Cases fetched from LOCAL DB', [
                'count' => $cases->count(),
                'view_mode' => $viewMode
            ]);

            // Consultar TAREAS desde la base de datos local
            // Estados locales de Taskflow (no los de SweetCRM)
            // Incluir todos los estados activos: pending, in_progress, blocked, paused
            // Las tareas completadas/canceladas se excluyen
            $activeTaskStatuses = ['pending', 'in_progress', 'blocked', 'paused'];
            $tasksQuery = \App\Models\Task::with(['crmCase', 'crmCase.client', 'assignee'])
                ->whereIn('status', $activeTaskStatuses);

            // Las tareas SIEMPRE son personales (asignadas al usuario actual)
            $tasksQuery->where('assignee_id', $user->id);

            $tasks = $tasksQuery->orderBy('created_at', 'desc')
                ->limit(100)
                ->get();

            Log::info('📋 Tasks fetched from LOCAL DB', [
                'count' => $tasks->count()
            ]);

            // Agrupar tareas por caso
            $tasksByCase = [];
            $orphanTasks = [];
            $caseIdsFromTasks = []; // IDs de casos que tienen tareas del usuario

            foreach ($tasks as $task) {
                if ($task->case_id) {
                    if (!isset($tasksByCase[$task->case_id])) {
                        $tasksByCase[$task->case_id] = [];
                    }
                    $tasksByCase[$task->case_id][] = $task;
                    $caseIdsFromTasks[] = $task->case_id;
                } else {
                    $orphanTasks[] = $task;
                }
            }

            // Si hay casos que contienen tareas del usuario pero no están en los casos obtenidos,
            // agregarlos (Ej: tarea asignada a jramirez pero el caso está asignado a otro usuario)
            // También incluir tareas de casos cerrados si la tarea está activa
            if ($viewMode === 'my' && !empty($caseIdsFromTasks)) {
                $caseIdsFromTasks = array_unique($caseIdsFromTasks);
                $casesFromTasks = \App\Models\CrmCase::whereIn('id', $caseIdsFromTasks)
                    ->whereNotIn('id', $cases->pluck('id')->toArray())
                    ->get();
                
                $cases = $cases->merge($casesFromTasks);
                
                Log::info('📋 Added cases from user tasks (including closed cases)', [
                    'added_count' => $casesFromTasks->count(),
                    'total_cases' => $cases->count()
                ]);
            }

            // Formatear casos para el frontend CON sus tareas anidadas
            $casesData = $cases->map(function ($case) use ($tasksByCase) {
                $caseData = [
                    'id' => $case->id, // ID local (integer) para navegación
                    'sweetcrm_id' => $case->sweetcrm_id, // ID de SweetCRM (UUID)
                    'type' => 'case',
                    'title' => $case->subject,
                    'case_number' => $case->case_number,
                    'subject' => $case->subject,
                    'status' => $case->status,
                    'priority' => $case->priority ?? 'Normal',
                    'assigned_user_name' => $case->assigned_user_name ?? 'Sin asignar',
                    'created_by_name' => $case->original_creator_name,
                    'date_entered' => $case->sweetcrm_created_at ?? $case->created_at,
                    'tasks' => [], // Inicializar tareas vacío
                    'client' => null,
                    'client_name' => null,
                ];

                // Incluir información del cliente si existe
                if ($case->client) {
                    $caseData['client'] = [
                        'id' => $case->client->id,
                        'name' => $case->client->name,
                        'sweetcrm_id' => $case->client->sweetcrm_id,
                    ];
                    $caseData['client_name'] = $case->client->name;
                }

                // Agregar tareas de este caso si existen
                if (isset($tasksByCase[$case->id])) {
                    $caseData['tasks'] = collect($tasksByCase[$case->id])->map(function ($task) use ($case) {
                        $taskData = [
                            'id' => $task->id,
                            'sweetcrm_id' => $task->sweetcrm_id,
                            'type' => 'task',
                            'title' => $task->title,
                            'description' => $task->description,
                            'status' => $task->status,
                            'priority' => $task->priority ?? 'Medium',
                            'assigned_user_name' => $task->assignee->name ?? 'Sin asignar',
                            'date_due' => $task->estimated_end_at,
                            'due_date' => $task->estimated_end_at,
                            'estimated_end_at' => $task->estimated_end_at,
                            'estimated_start_at' => $task->estimated_start_at,
                            'date_entered' => $task->created_at,
                            'case_id' => $task->case_id,
                            'client' => null,
                            'client_name' => null,
                        ];

                        // Incluir información del cliente desde el caso
                        if ($case->client) {
                            $taskData['client'] = [
                                'id' => $case->client->id,
                                'name' => $case->client->name,
                                'sweetcrm_id' => $case->client->sweetcrm_id,
                            ];
                            $taskData['client_name'] = $case->client->name;
                        }

                        return $taskData;
                    })->toArray();
                }

                return $caseData;
            })->toArray();

            // Formatear tareas huérfanas para el frontend
            $tasksData = collect($orphanTasks)->map(function ($task) {
                $taskData = [
                    'id' => $task->id, // ID local (integer) para navegación
                    'sweetcrm_id' => $task->sweetcrm_id, // ID de SweetCRM (UUID)
                    'type' => 'task',
                    'title' => $task->title,
                    'status' => $task->status,
                    'priority' => $task->priority ?? 'Medium',
                    'assigned_user_name' => $task->assignee->name ?? 'Sin asignar',
                    'date_due' => $task->estimated_end_at,
                    'date_entered' => $task->created_at,
                    'case_id' => $task->case_id, // ID local del caso
                    'client' => null,
                    'client_name' => null,
                ];

                // Si la tarea tiene un caso asociado, incluir información del caso y cliente
                if ($task->case_id && $task->crmCase) {
                    $taskData['crm_case'] = [
                        'id' => $task->crmCase->id, // ID local (integer) para navegación
                        'sweetcrm_id' => $task->crmCase->sweetcrm_id, // ID de SweetCRM (UUID)
                        'case_number' => $task->crmCase->case_number,
                        'subject' => $task->crmCase->subject,
                    ];

                    // Incluir información del cliente si existe
                    if ($task->crmCase->client) {
                        $taskData['client'] = [
                            'id' => $task->crmCase->client->id,
                            'name' => $task->crmCase->client->name,
                            'sweetcrm_id' => $task->crmCase->client->sweetcrm_id,
                        ];
                        $taskData['client_name'] = $task->crmCase->client->name;
                    }
                }

                return $taskData;
            })->toArray();

            Log::info('✅ Operations team content loaded from LOCAL DB:', [
                'view_mode' => $viewMode,
                'cases_count' => count($casesData),
                'tasks_count' => count($tasksData),
                'total' => count($casesData) + count($tasksData)
            ]);

            return response()->json([
                'success' => true,
                'user_area' => null,
                'view_mode' => $viewMode,
                'data' => [
                    'cases' => $casesData,
                    'tasks' => $tasksData,
                    'total' => count($casesData) + count($tasksData),
                    'total_cases' => count($casesData),
                    'total_tasks' => count($tasksData),
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Error en getOperationsTeamContent: ' . $e->getMessage());
            Log::error('Stack trace: ' . $e->getTraceAsString());
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener contenido de operaciones',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtener Oportunidades + Tareas para equipo de Ventas
     * GET /api/v1/dashboard/sales-content
     */
    protected function getSalesTeamContent(Request $request, $user)
    {
        try {
            $username = config('services.sweetcrm.username');
            $password = config('services.sweetcrm.password');

            if (!$username || !$password) {
                return response()->json([
                    'success' => false,
                    'message' => 'Credenciales de SweetCRM no configuradas'
                ], 500);
            }

            // Obtener sesión de SweetCRM
            $sessionResult = $this->sweetCrmService->getCachedSession($username, $password);

            if (!$sessionResult['success']) {
                return response()->json([
                    'success' => false,
                    'message' => 'Error de autenticación con SweetCRM'
                ], 500);
            }

            $sessionId = $sessionResult['session_id'];
            $userSweetCrmId = $user->sweetcrm_id;

            // Oportunidades asignadas al usuario
            $opportunitiesData = [];
            $activeOpportunityStatuses = ['Prospecting', 'Qualification', 'Needs Analysis', 'Value Proposition', 'Id. Decision Makers', 'Perception Analysis', 'Proposal/Price Quote', 'Negotiation/Review', 'Verbal Agreement', 'Closed Won'];

            $opportunitiesFilters = [
                'query' => "opportunities.assigned_user_id = '{$userSweetCrmId}'",
                'max_results' => 100,
            ];

            $opportunitiesFromCrm = $this->sweetCrmService->getOpportunities($sessionId, $opportunitiesFilters);

            foreach ($opportunitiesFromCrm as $opp) {
                $nvl = $opp['name_value_list'];
                $opportunitiesData[] = [
                    'id' => $opp['id'],
                    'type' => 'opportunity',
                    'title' => $nvl['name']['value'] ?? 'Sin nombre',
                    'sales_stage' => $nvl['sales_stage']['value'] ?? 'Prospecting',
                    'amount' => $nvl['amount']['value'] ?? 0,
                    'currency' => $nvl['currency_id']['value'] ?? 'CLP',
                    'probability' => $nvl['probability']['value'] ?? 0,
                    'date_closed' => $nvl['date_closed']['value'] ?? null,
                    'assigned_user_id' => $userSweetCrmId,
                    'assigned_user_name' => $nvl['assigned_user_name']['value'] ?? 'Sin asignar',
                ];
            }

            // Tareas asignadas al usuario (de cualquier tipo)
            $tasksData = [];
            $activeTaskStatuses = ['Open', 'Reassigned', 'In Progress', 'Not Started'];

            $tasksFilters = [
                'query' => "tasks.assigned_user_id = '{$userSweetCrmId}' AND tasks.status IN ('" . implode("','", $activeTaskStatuses) . "')",
                'max_results' => 100,
            ];

            $tasksFromCrm = $this->sweetCrmService->getTasks($sessionId, $tasksFilters);

            foreach ($tasksFromCrm as $task) {
                $nvl = $task['name_value_list'];
                $taskStatus = $nvl['status']['value'] ?? 'Not Started';

                if (in_array($taskStatus, $activeTaskStatuses)) {
                    $tasksData[] = [
                        'id' => $task['id'],
                        'type' => 'task',
                        'title' => $nvl['name']['value'] ?? 'Sin nombre',
                        'status' => $taskStatus,
                        'priority' => $nvl['priority']['value'] ?? 'Medium',
                        'assigned_user_name' => $nvl['assigned_user_name']['value'] ?? 'Sin asignar',
                        'date_due' => $nvl['date_due']['value'] ?? null,
                        'date_entered' => $nvl['date_entered']['value'] ?? null,
                    ];
                }
            }

            return response()->json([
                'success' => true,
                'user_area' => 'sales',
                'data' => [
                    'opportunities' => $opportunitiesData,
                    'tasks' => $tasksData,
                    'total' => count($opportunitiesData) + count($tasksData),
                    'total_opportunities' => count($opportunitiesData),
                    'total_tasks' => count($tasksData),
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Error en getSalesTeamContent: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener contenido de ventas',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtener oportunidades y tareas delegadas para equipo de Ventas
     * (creadas por el usuario y asignadas a otros)
     * GET /api/v1/dashboard/delegated-sales
     */
    public function getDelegatedSales(Request $request)
    {
        try {
            $user = $request->user();

            if (!$user || !$user->sweetcrm_id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Usuario no autenticado o sin ID de SweetCRM'
                ], 401);
            }

            $username = config('services.sweetcrm.username');
            $password = config('services.sweetcrm.password');

            if (!$username || !$password) {
                return response()->json([
                    'success' => false,
                    'message' => 'Credenciales de SweetCRM no configuradas'
                ], 500);
            }

            // Obtener sesión de SweetCRM
            $sessionResult = $this->sweetCrmService->getCachedSession($username, $password);

            if (!$sessionResult['success']) {
                return response()->json([
                    'success' => false,
                    'message' => 'Error de autenticación con SweetCRM'
                ], 500);
            }

            $sessionId = $sessionResult['session_id'];
            $userSweetCrmId = $user->sweetcrm_id;

            $delegatedData = [
                'opportunities' => [],
                'tasks' => [],
                'total' => 0,
                'pending' => 0
            ];

            // Tareas creadas por el usuario y asignadas a otros
            $activeTaskStatuses = ['Open', 'Reassigned', 'In Progress', 'Not Started'];

            $tasksFilters = [
                'query' => "tasks.created_by = '{$userSweetCrmId}' AND tasks.assigned_user_id IS NOT NULL AND tasks.assigned_user_id != '{$userSweetCrmId}' AND tasks.status IN ('" . implode("','", $activeTaskStatuses) . "')",
                'max_results' => 100,
            ];

            $tasksFromCrm = $this->sweetCrmService->getTasks($sessionId, $tasksFilters);

            foreach ($tasksFromCrm as $task) {
                $nvl = $task['name_value_list'];
                $taskStatus = $nvl['status']['value'] ?? 'Not Started';

                if (in_array($taskStatus, $activeTaskStatuses)) {
                    $delegatedData['tasks'][] = [
                        'id' => $task['id'],
                        'type' => 'task',
                        'title' => $nvl['name']['value'] ?? 'Sin nombre',
                        'status' => $taskStatus,
                        'priority' => $nvl['priority']['value'] ?? 'Medium',
                        'assigned_user_name' => $nvl['assigned_user_name']['value'] ?? 'Sin asignar',
                        'created_by_name' => $nvl['created_by_name']['value'] ?? null,
                        'date_due' => $nvl['date_due']['value'] ?? null,
                        'date_entered' => $nvl['date_entered']['value'] ?? null,
                    ];

                    $delegatedData['total']++;
                    $delegatedData['pending']++;
                }
            }

            // Oportunidades creadas por el usuario y asignadas a otros
            $opportunitiesFilters = [
                'query' => "opportunities.created_by = '{$userSweetCrmId}' AND opportunities.assigned_user_id IS NOT NULL AND opportunities.assigned_user_id != '{$userSweetCrmId}'",
                'max_results' => 100,
            ];

            $opportunitiesFromCrm = $this->sweetCrmService->getOpportunities($sessionId, $opportunitiesFilters);

            foreach ($opportunitiesFromCrm as $opp) {
                $nvl = $opp['name_value_list'];

                $delegatedData['opportunities'][] = [
                    'id' => $opp['id'],
                    'type' => 'opportunity',
                    'title' => $nvl['name']['value'] ?? 'Sin nombre',
                    'sales_stage' => $nvl['sales_stage']['value'] ?? 'Prospecting',
                    'amount' => $nvl['amount']['value'] ?? 0,
                    'currency' => $nvl['currency_id']['value'] ?? 'CLP',
                    'assigned_user_name' => $nvl['assigned_user_name']['value'] ?? 'Sin asignar',
                    'created_by_name' => $nvl['created_by_name']['value'] ?? null,
                    'date_closed' => $nvl['date_closed']['value'] ?? null,
                ];

                $delegatedData['total']++;
                $delegatedData['pending']++;
            }

            return response()->json([
                'success' => true,
                'data' => $delegatedData
            ]);

        } catch (\Exception $e) {
            Log::error('Error en getDelegatedSales: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener oportunidades y tareas delegadas',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtener casos y tareas delegados (creados por el usuario y asignados a otros)
     * Usa BD LOCAL en lugar de SweetCRM API para obtener IDs consistentes
     * GET /api/v1/dashboard/delegated
     */
    public function getDelegated(Request $request)
    {
        try {
            $user = $request->user();

            if (!$user || !$user->sweetcrm_id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Usuario no autenticado o sin ID de SweetCRM'
                ], 401);
            }

            $delegatedData = [
                'cases' => [],
                'tasks' => [],
                'total' => 0,
                'pending' => 0
            ];

            // Obtener CASOS delegados desde BD LOCAL
            // Casos donde el usuario actual es el creador y están asignados a otros
            $delegatedCases = \App\Models\CrmCase::where('created_by', $user->id)
                ->where(function($query) use ($user) {
                    $query->whereNotNull('sweetcrm_assigned_user_id')
                          ->where('sweetcrm_assigned_user_id', '!=', $user->sweetcrm_id);
                })
                ->whereNotIn('status', ['Closed', 'Rejected', 'Merged', 'Cerrado'])
                ->orderBy('created_at', 'desc')
                ->limit(100)
                ->get();

            foreach ($delegatedCases as $case) {
                $delegatedData['cases'][] = [
                    'id' => $case->id, // ID LOCAL (integer)
                    'type' => 'case',
                    'title' => $case->subject,
                    'case_number' => $case->case_number,
                    'status' => $case->status,
                    'priority' => $case->priority ?? 'Normal',
                    'assigned_user_name' => $case->assigned_user_name ?? 'Sin asignar',
                    'created_by_name' => $case->original_creator_name,
                    'date_entered' => $case->sweetcrm_created_at ?? $case->created_at,
                ];

                $delegatedData['total']++;
                if ($case->status !== 'Cerrado') {
                    $delegatedData['pending']++;
                }
            }

            // Obtener TAREAS delegadas desde BD LOCAL
            // Tareas donde el usuario actual es el creador y están asignadas a otros
            $delegatedTasks = \App\Models\Task::where('created_by', $user->id)
                ->where(function($query) use ($user) {
                    $query->whereNotNull('assignee_id')
                          ->where('assignee_id', '!=', $user->id);
                })
                ->whereIn('status', ['pending', 'in_progress'])
                ->orderBy('created_at', 'desc')
                ->limit(100)
                ->get();

            foreach ($delegatedTasks as $task) {
                $taskData = [
                    'id' => $task->id, // ID LOCAL (integer)
                    'type' => 'task',
                    'title' => $task->title,
                    'status' => $task->status,
                    'priority' => $task->priority ?? 'Medium',
                    'assigned_user_name' => $task->assignee->name ?? 'Sin asignar',
                    'date_due' => $task->estimated_end_at,
                    'date_entered' => $task->created_at,
                    'case_id' => $task->case_id,
                ];

                // Si la tarea tiene un caso asociado, incluir info del caso
                if ($task->case_id && $task->crmCase) {
                    $taskData['crm_case'] = [
                        'id' => $task->crmCase->id,
                        'case_number' => $task->crmCase->case_number,
                        'subject' => $task->crmCase->subject,
                    ];
                }

                $delegatedData['tasks'][] = $taskData;

                $delegatedData['total']++;
                if ($task->status !== 'completed') {
                    $delegatedData['pending']++;
                }
            }

            return response()->json([
                'success' => true,
                'data' => $delegatedData
            ]);

        } catch (\Exception $e) {
            Log::error('Error en getDelegated: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener tareas delegadas',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Dashboard unificado CRM: Casos, Tareas y Oportunidades
     * Vista consolidada de todos los elementos CRM asignados al usuario desde SuiteCRM API v4.1
     *
     * Responde con:
     * - Tareas: nombre, estado, fecha_vencimiento, relacionado_con_nombre (Caso o Cuenta)
     * - Casos: nombre, estado, case_number, relacionado_con_nombre (Cuenta)
     * - Oportunidades: nombre, estado, monto, probabilidad, relacionado_con_nombre (Cuenta)
     *
     * GET /api/v1/dashboard/crm-overview
     */
    public function getCrmOverview(Request $request)
    {
        try {
            $user = $request->user();

            if (!$user || !$user->sweetcrm_id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Usuario no vinculado a SuiteCRM'
                ], 403);
            }

            Log::info('Dashboard CRM Overview - Usuario solicitado', [
                'user_id' => $user->id,
                'user_name' => $user->name,
                'sweetcrm_id' => $user->sweetcrm_id
            ]);

            $dashboard = $this->crmDashboardService->getUnifiedDashboard($user->sweetcrm_id);

            Log::info('Dashboard CRM Overview - Datos cargados', [
                'user_id' => $user->id,
                'items' => count($dashboard['items']),
                'summary' => $dashboard['summary']
            ]);

            return response()->json([
                'success' => true,
                'data' => $dashboard['items'],
                'summary' => $dashboard['summary']
            ]);

        } catch (\Exception $e) {
            Log::error('Dashboard CRM Overview Error', [
                'user_id' => $request->user()?->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error al cargar dashboard CRM',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }
}
