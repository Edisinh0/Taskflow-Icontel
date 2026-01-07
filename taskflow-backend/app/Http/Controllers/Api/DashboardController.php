<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\SweetCrmService;
use App\DTOs\SugarCRM\SugarCRMCaseDTO;
use App\DTOs\SugarCRM\SugarCRMTaskDTO;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class DashboardController extends Controller
{
    protected SweetCrmService $sweetCrmService;

    public function __construct(SweetCrmService $sweetCrmService)
    {
        $this->sweetCrmService = $sweetCrmService;
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
     * Obtener casos y tareas delegados (creados por el usuario y asignados a otros)
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
                'cases' => [],
                'tasks' => [],
                'total' => 0,
                'pending' => 0
            ];

            // Estados activos para casos
            $openCaseStatuses = ['New', 'Open', 'In Progress', 'Pending', 'Reopened'];

            // Casos creados por el usuario y asignados a otros (solo estados abiertos)
            $casesFilters = [
                'query' => "cases.created_by = '{$userSweetCrmId}' AND cases.assigned_user_id IS NOT NULL AND cases.assigned_user_id != '{$userSweetCrmId}' AND (cases.status IS NULL OR cases.status = '' OR cases.status IN ('" . implode("','", $openCaseStatuses) . "'))",
                'max_results' => 100,
            ];

            $casesFromCrm = $this->sweetCrmService->getCases($sessionId, $casesFilters);

            foreach ($casesFromCrm as $crmCase) {
                $nvl = $crmCase['name_value_list'];
                $caseStatus = $nvl['status']['value'] ?? 'New';

                // Solo incluir si está en estados activos
                if (in_array($caseStatus, $openCaseStatuses) || empty($caseStatus)) {
                    $delegatedData['cases'][] = [
                        'id' => $crmCase['id'],
                        'type' => 'case',
                        'title' => $nvl['name']['value'] ?? 'Sin nombre',
                        'case_number' => $nvl['case_number']['value'] ?? null,
                        'status' => $caseStatus,
                        'priority' => $nvl['priority']['value'] ?? 'Normal',
                        'assigned_user_name' => $nvl['assigned_user_name']['value'] ?? 'Sin asignar',
                        'created_by_name' => $nvl['created_by_name']['value'] ?? null,
                        'date_entered' => $nvl['date_entered']['value'] ?? null,
                    ];

                    $delegatedData['total']++;
                    $delegatedData['pending']++;
                }
            }

            // Estados activos para tareas: Not Started, In Progress, Reassigned, Open
            $openTaskStatuses = ['Not Started', 'In Progress', 'Reassigned', 'Open', 'Pending'];

            // Tareas creadas por el usuario y asignadas a otros (solo estados activos)
            $tasksFilters = [
                'query' => "tasks.created_by = '{$userSweetCrmId}' AND tasks.assigned_user_id IS NOT NULL AND tasks.assigned_user_id != '{$userSweetCrmId}' AND tasks.parent_type = 'Cases' AND (tasks.status IS NULL OR tasks.status = '' OR tasks.status IN ('" . implode("','", $openTaskStatuses) . "'))",
                'max_results' => 100,
            ];

            $tasksFromCrm = $this->sweetCrmService->getTasks($sessionId, $tasksFilters);

            foreach ($tasksFromCrm as $task) {
                $nvl = $task['name_value_list'];
                $taskStatus = $nvl['status']['value'] ?? 'Not Started';

                // Solo incluir si está en estados activos
                if (in_array($taskStatus, $openTaskStatuses) || empty($taskStatus)) {
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
}
