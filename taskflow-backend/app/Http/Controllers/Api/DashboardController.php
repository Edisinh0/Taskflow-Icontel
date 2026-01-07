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
}
