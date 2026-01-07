<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\SweetCrmService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;

/**
 * SalesTaskController
 *
 * Maneja la lógica de triggers Ventas → Operaciones:
 * - Tarea de Levantamiento: Sin cotización, para mediciones/factibilidad
 * - Tarea de Ejecución: Con cotización aprobada, para implementación
 */
class SalesTaskController extends Controller
{
    protected SweetCrmService $sweetCrmService;

    public function __construct(SweetCrmService $sweetCrmService)
    {
        $this->sweetCrmService = $sweetCrmService;
    }

    /**
     * Crear Tarea de Levantamiento (Survey Task)
     * POST /api/v1/sales/create-survey-task
     *
     * Se usa cuando NO hay cotización aprobada.
     * Crea una tarea para que Operaciones realice:
     * - Mediciones
     * - Cálculos
     * - Estudios de factibilidad
     */
    public function createSurveyTask(Request $request)
    {
        $validated = $request->validate([
            'opportunity_id' => 'required|string',
            'opportunity_name' => 'required|string',
            'account_id' => 'nullable|string',
            'account_name' => 'nullable|string',
            'description' => 'nullable|string',
            'priority' => 'nullable|in:High,Medium,Low',
            'date_due' => 'nullable|date',
            'assigned_user_id' => 'nullable|string', // Usuario de Operaciones
        ]);

        try {
            $user = $request->user();

            // Obtener session de SweetCRM
            $sessionResult = $this->getSessionForUser($user);

            if (!$sessionResult['success']) {
                return response()->json([
                    'success' => false,
                    'message' => 'Error de autenticación con SweetCRM',
                    'error' => $sessionResult['error']
                ], 401);
            }

            $sessionId = $sessionResult['session_id'];

            // Construir datos de la tarea de levantamiento
            $taskData = [
                'name' => "[LEVANTAMIENTO] {$validated['opportunity_name']}",
                'description' => $this->buildSurveyDescription($validated, $user),
                'status' => 'Not Started',
                'priority' => $validated['priority'] ?? 'High', // Levantamientos son urgentes por defecto
                'parent_type' => 'Opportunities',
                'parent_id' => $validated['opportunity_id'],
                'date_due' => $validated['date_due'] ?? date('Y-m-d', strtotime('+3 days')),
                'assigned_user_id' => $validated['assigned_user_id'] ?? null,
            ];

            // Crear tarea en SweetCRM
            $result = $this->createTaskInSweetCrm($sessionId, $taskData);

            if (!$result['success']) {
                return response()->json([
                    'success' => false,
                    'message' => 'Error al crear tarea en SweetCRM',
                    'error' => $result['error']
                ], 500);
            }

            Log::info('✅ Tarea de Levantamiento creada', [
                'task_id' => $result['task_id'],
                'opportunity_id' => $validated['opportunity_id'],
                'created_by' => $user->id,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Tarea de Levantamiento creada exitosamente',
                'data' => [
                    'task_id' => $result['task_id'],
                    'task_name' => $taskData['name'],
                    'task_type' => 'survey',
                    'opportunity_id' => $validated['opportunity_id'],
                ]
            ], 201);

        } catch (\Exception $e) {
            Log::error('Error al crear tarea de levantamiento', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error al crear tarea de levantamiento',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Crear Tarea de Ejecución (Execution Task)
     * POST /api/v1/sales/create-execution-task
     *
     * Se usa cuando HAY cotización aprobada.
     * Crea una tarea para que Operaciones ejecute:
     * - Instalación
     * - Implementación
     * - Entrega del servicio
     */
    public function createExecutionTask(Request $request)
    {
        $validated = $request->validate([
            'opportunity_id' => 'required|string',
            'opportunity_name' => 'required|string',
            'quote_id' => 'required|string',
            'quote_name' => 'nullable|string',
            'quote_total' => 'nullable|numeric',
            'account_id' => 'nullable|string',
            'account_name' => 'nullable|string',
            'description' => 'nullable|string',
            'priority' => 'nullable|in:High,Medium,Low',
            'date_due' => 'nullable|date',
            'assigned_user_id' => 'nullable|string', // Usuario de Operaciones
        ]);

        try {
            $user = $request->user();

            // Obtener session de SweetCRM
            $sessionResult = $this->getSessionForUser($user);

            if (!$sessionResult['success']) {
                return response()->json([
                    'success' => false,
                    'message' => 'Error de autenticación con SweetCRM',
                    'error' => $sessionResult['error']
                ], 401);
            }

            $sessionId = $sessionResult['session_id'];

            // Construir datos de la tarea de ejecución
            $taskData = [
                'name' => "[EJECUCIÓN] {$validated['opportunity_name']}",
                'description' => $this->buildExecutionDescription($validated, $user),
                'status' => 'Not Started',
                'priority' => $validated['priority'] ?? 'Medium',
                'parent_type' => 'Opportunities',
                'parent_id' => $validated['opportunity_id'],
                'date_due' => $validated['date_due'] ?? date('Y-m-d', strtotime('+7 days')),
                'assigned_user_id' => $validated['assigned_user_id'] ?? null,
            ];

            // Crear tarea en SweetCRM
            $result = $this->createTaskInSweetCrm($sessionId, $taskData);

            if (!$result['success']) {
                return response()->json([
                    'success' => false,
                    'message' => 'Error al crear tarea en SweetCRM',
                    'error' => $result['error']
                ], 500);
            }

            Log::info('✅ Tarea de Ejecución creada', [
                'task_id' => $result['task_id'],
                'opportunity_id' => $validated['opportunity_id'],
                'quote_id' => $validated['quote_id'],
                'created_by' => $user->id,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Tarea de Ejecución creada exitosamente',
                'data' => [
                    'task_id' => $result['task_id'],
                    'task_name' => $taskData['name'],
                    'task_type' => 'execution',
                    'opportunity_id' => $validated['opportunity_id'],
                    'quote_id' => $validated['quote_id'],
                ]
            ], 201);

        } catch (\Exception $e) {
            Log::error('Error al crear tarea de ejecución', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error al crear tarea de ejecución',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtener usuarios de Operaciones disponibles para asignar tareas
     * GET /api/v1/sales/operations-users
     */
    public function getOperationsUsers(Request $request)
    {
        try {
            $user = $request->user();

            $sessionResult = $this->getSessionForUser($user);

            if (!$sessionResult['success']) {
                return response()->json([
                    'success' => false,
                    'message' => 'Error de autenticación con SweetCRM'
                ], 401);
            }

            $sessionId = $sessionResult['session_id'];

            // Obtener usuarios desde SweetCRM
            $rawUsers = $this->sweetCrmService->getUsers($sessionId);

            // Filtrar usuarios que pertenezcan a departamentos de Operaciones
            $operationsUsers = array_filter($rawUsers, function ($entry) {
                $nvl = $entry['name_value_list'] ?? [];
                $department = strtolower($nvl['department']['value'] ?? '');

                return in_array($department, [
                    'operaciones', 'operations', 'ops',
                    'soporte', 'support', 'tecnico', 'technical',
                    'instalaciones', 'installation', 'terreno'
                ]);
            });

            // Transformar datos
            $users = array_map(function ($entry) {
                $nvl = $entry['name_value_list'] ?? [];
                return [
                    'id' => $entry['id'] ?? null,
                    'user_name' => $nvl['user_name']['value'] ?? null,
                    'first_name' => $nvl['first_name']['value'] ?? null,
                    'last_name' => $nvl['last_name']['value'] ?? null,
                    'full_name' => trim(($nvl['first_name']['value'] ?? '') . ' ' . ($nvl['last_name']['value'] ?? '')),
                    'department' => $nvl['department']['value'] ?? null,
                    'email' => $nvl['email1']['value'] ?? null,
                ];
            }, array_values($operationsUsers));

            return response()->json([
                'success' => true,
                'data' => $users
            ]);

        } catch (\Exception $e) {
            Log::error('Error al obtener usuarios de operaciones', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error al obtener usuarios',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Crear tarea en SweetCRM usando API v4_1
     */
    private function createTaskInSweetCrm(string $sessionId, array $taskData): array
    {
        try {
            $baseUrl = rtrim(config('services.sweetcrm.url'), '/');

            // Preparar name_value_list para v4_1
            $nameValueList = [];
            foreach ($taskData as $key => $value) {
                if ($value !== null) {
                    $nameValueList[] = [
                        'name' => $key,
                        'value' => $value
                    ];
                }
            }

            $response = Http::timeout(30)
                ->asForm()
                ->post("{$baseUrl}/service/v4_1/rest.php", [
                    'method' => 'set_entry',
                    'input_type' => 'JSON',
                    'response_type' => 'JSON',
                    'rest_data' => json_encode([
                        'session' => $sessionId,
                        'module_name' => 'Tasks',
                        'name_value_list' => $nameValueList,
                    ]),
                ]);

            if ($response->successful()) {
                $data = $response->json();

                // Verificar error de sesión
                if (isset($data['name']) && $data['name'] === 'Invalid Session ID') {
                    return [
                        'success' => false,
                        'error' => 'Sesión de SweetCRM expirada'
                    ];
                }

                // set_entry retorna el ID del registro creado
                $taskId = $data['id'] ?? null;

                if ($taskId) {
                    return [
                        'success' => true,
                        'task_id' => $taskId
                    ];
                }

                return [
                    'success' => false,
                    'error' => 'No se recibió ID de tarea'
                ];
            }

            return [
                'success' => false,
                'error' => 'Error HTTP: ' . $response->status()
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Construir descripción para tarea de levantamiento
     */
    private function buildSurveyDescription(array $data, $user): string
    {
        $description = "=== TAREA DE LEVANTAMIENTO ===\n\n";
        $description .= "Tipo: Levantamiento / Medición / Factibilidad\n";
        $description .= "Prioridad: URGENTE\n\n";

        $description .= "--- INFORMACIÓN DE LA OPORTUNIDAD ---\n";
        $description .= "Oportunidad: {$data['opportunity_name']}\n";

        if (!empty($data['account_name'])) {
            $description .= "Cliente: {$data['account_name']}\n";
        }

        $description .= "\n--- INSTRUCCIONES ---\n";
        $description .= $data['description'] ?? "Realizar levantamiento técnico y reportar hallazgos.";

        $description .= "\n\n--- CREADO POR ---\n";
        $description .= "Usuario: {$user->name}\n";
        $description .= "Área: Ventas\n";
        $description .= "Fecha: " . now()->format('Y-m-d H:i:s') . "\n";

        return $description;
    }

    /**
     * Construir descripción para tarea de ejecución
     */
    private function buildExecutionDescription(array $data, $user): string
    {
        $description = "=== TAREA DE EJECUCIÓN ===\n\n";
        $description .= "Tipo: Implementación / Instalación\n";
        $description .= "Estado: Cotización Aprobada\n\n";

        $description .= "--- INFORMACIÓN DE LA OPORTUNIDAD ---\n";
        $description .= "Oportunidad: {$data['opportunity_name']}\n";

        if (!empty($data['account_name'])) {
            $description .= "Cliente: {$data['account_name']}\n";
        }

        $description .= "\n--- COTIZACIÓN APROBADA ---\n";
        if (!empty($data['quote_name'])) {
            $description .= "Cotización: {$data['quote_name']}\n";
        }
        if (!empty($data['quote_total'])) {
            $description .= "Monto Total: $" . number_format($data['quote_total'], 2) . "\n";
        }

        $description .= "\n--- INSTRUCCIONES ---\n";
        $description .= $data['description'] ?? "Proceder con la ejecución según cotización aprobada.";

        $description .= "\n\n--- CREADO POR ---\n";
        $description .= "Usuario: {$user->name}\n";
        $description .= "Área: Ventas\n";
        $description .= "Fecha: " . now()->format('Y-m-d H:i:s') . "\n";

        return $description;
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
}
