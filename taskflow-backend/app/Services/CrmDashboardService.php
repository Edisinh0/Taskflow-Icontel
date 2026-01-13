<?php

namespace App\Services;

use App\Adapters\SugarCRM\SugarCRMApiAdapter;
use Illuminate\Support\Facades\Log;

class CrmDashboardService
{
    public function __construct(
        private SugarCRMApiAdapter $adapter,
        private SweetCrmService $sweetCrmService
    ) {}

    /**
     * Obtiene dashboard unificado: Tareas, Casos y Oportunidades
     * Utiliza queries SQL específicas para SuiteCRM API v4.1
     */
    public function getUnifiedDashboard(string $userSweetCrmId): array
    {
        try {
            // 1. Obtener sesión cacheada
            $sessionResult = $this->sweetCrmService->getCachedSession(
                config('sweetcrm.username'),
                config('sweetcrm.password')
            );

            if (!$sessionResult['success']) {
                throw new \Exception('Failed to authenticate with SuiteCRM');
            }

            $sessionId = $sessionResult['session_id'];

            // 2. Ejecutar 3 consultas (secuencial para evitar timeout)
            $tasksRaw = $this->getTasks($sessionId, $userSweetCrmId);
            $casesRaw = $this->getCases($sessionId, $userSweetCrmId);
            $opportunitiesRaw = $this->getOpportunities($sessionId, $userSweetCrmId);

            // 3. Normalizar datos con formatToDashboard()
            $tasks = array_map(fn($t) => $this->formatToDashboard($t, 'Task'), $tasksRaw);
            $cases = array_map(fn($c) => $this->formatToDashboard($c, 'Case'), $casesRaw);
            $opportunities = array_map(fn($o) => $this->formatToDashboard($o, 'Opportunity'), $opportunitiesRaw);

            // 4. Unir todos los items
            $unifiedItems = array_merge($tasks, $cases, $opportunities);

            Log::info('Dashboard unificado cargado', [
                'user_id' => $userSweetCrmId,
                'tasks' => count($tasks),
                'cases' => count($cases),
                'opportunities' => count($opportunities),
                'total' => count($unifiedItems)
            ]);

            return [
                'items' => $unifiedItems,
                'summary' => [
                    'total_tasks' => count($tasks),
                    'total_cases' => count($cases),
                    'total_opportunities' => count($opportunities),
                    'total_items' => count($unifiedItems)
                ]
            ];

        } catch (\Exception $e) {
            Log::error('CrmDashboardService Error', [
                'error' => $e->getMessage(),
                'user_id' => $userSweetCrmId
            ]);
            throw $e;
        }
    }

    /**
     * Obtener tareas del usuario desde SuiteCRM
     * Query SQL Legacy para Tasks (obligatorio: parent_type, parent_id, parent_name)
     */
    private function getTasks(string $sessionId, string $userId): array
    {
        // Query SQL Legacy: tasks.assigned_user_id = '{userId}' AND tasks.deleted = 0
        $query = "tasks.assigned_user_id = '{$userId}' AND tasks.deleted = 0";

        // Campos requeridos: incluir parent_type, parent_id, parent_name obligatoriamente
        $selectFields = [
            'id',
            'name',
            'status',
            'priority',
            'date_due',
            'date_entered',
            'assigned_user_name',
            'description',
            'parent_type',      // Obligatorio: tipo del padre (Cases, Accounts, etc)
            'parent_id',        // Obligatorio: ID del padre
            'parent_name',      // Obligatorio: nombre del padre
        ];

        $result = $this->adapter->getEntriesByModule($sessionId, 'Tasks', $query, $selectFields, ['max_results' => 50]);
        return $result['entry_list'] ?? [];
    }

    /**
     * Obtener casos del usuario desde SuiteCRM
     * Query SQL Legacy para Cases
     */
    private function getCases(string $sessionId, string $userId): array
    {
        // Query SQL Legacy: cases.assigned_user_id = '{userId}' AND cases.deleted = 0
        $query = "cases.assigned_user_id = '{$userId}' AND cases.deleted = 0";

        $selectFields = [
            'id',
            'name',
            'case_number',
            'status',
            'priority',
            'type',
            'account_id',
            'account_name',
            'assigned_user_name',
            'date_entered',
            'date_modified',
        ];

        $result = $this->adapter->getEntriesByModule($sessionId, 'Cases', $query, $selectFields, ['max_results' => 50]);
        return $result['entry_list'] ?? [];
    }

    /**
     * Obtener oportunidades del usuario desde SuiteCRM
     * Query SQL Legacy para Opportunities
     */
    private function getOpportunities(string $sessionId, string $userId): array
    {
        // Query SQL Legacy: opportunities.assigned_user_id = '{userId}' AND opportunities.deleted = 0
        $query = "opportunities.assigned_user_id = '{$userId}' AND opportunities.deleted = 0";

        $selectFields = [
            'id',
            'name',
            'sales_stage',
            'amount',
            'currency_id',
            'probability',
            'date_closed',
            'account_id',
            'account_name',
            'assigned_user_name',
            'date_entered',
            'date_modified',
        ];

        $result = $this->adapter->getEntriesByModule($sessionId, 'Opportunities', $query, $selectFields, ['max_results' => 50]);
        return $result['entry_list'] ?? [];
    }

    /**
     * Normaliza datos de SuiteCRM a formato estándar para Dashboard
     * Convierte name_value_list a objeto simple
     *
     * @param array $record - Registro crudo de SuiteCRM con name_value_list
     * @param string $type - Tipo de módulo: 'Task', 'Case', 'Opportunity'
     * @return array - Objeto normalizado
     */
    private function formatToDashboard(array $record, string $type): array
    {
        // Helper para extraer valores de name_value_list
        $getValue = fn($key) => $record['name_value_list'][$key]['value'] ?? null;

        $baseItem = [
            'id' => $record['id'] ?? null,
            'modulo_origen' => $type,
            'fecha' => $getValue('date_entered') ?? $getValue('date_modified'),
            'asignado_a' => $getValue('assigned_user_name'),
        ];

        return match($type) {
            'Task' => [
                ...$baseItem,
                'nombre' => $getValue('name') ?? 'Sin título',
                'estado' => $getValue('status') ?? 'No iniciada',
                'prioridad' => $getValue('priority'),
                'descripcion' => $getValue('description'),
                'fecha_vencimiento' => $getValue('date_due'),
                // Crítico para Jorge: información del padre (Caso o Cuenta)
                'relacionado_con_tipo' => $getValue('parent_type'),
                'relacionado_con_id' => $getValue('parent_id'),
                'relacionado_con_nombre' => $getValue('parent_name'),
            ],
            'Case' => [
                ...$baseItem,
                'nombre' => $getValue('name') ?? 'Sin título',
                'estado' => $getValue('status') ?? 'Abierto',
                'prioridad' => $getValue('priority'),
                'case_number' => $getValue('case_number'),
                'tipo' => $getValue('type'),
                // Relacionado con la Cuenta
                'relacionado_con_tipo' => 'Account',
                'relacionado_con_id' => $getValue('account_id'),
                'relacionado_con_nombre' => $getValue('account_name'),
            ],
            'Opportunity' => [
                ...$baseItem,
                'nombre' => $getValue('name') ?? 'Sin título',
                'estado' => $getValue('sales_stage') ?? 'Prospecting',
                'monto' => $getValue('amount'),
                'moneda' => $getValue('currency_id'),
                'probabilidad' => $getValue('probability'),
                'fecha_cierre' => $getValue('date_closed'),
                // Relacionado con la Cuenta
                'relacionado_con_tipo' => 'Account',
                'relacionado_con_id' => $getValue('account_id'),
                'relacionado_con_nombre' => $getValue('account_name'),
            ],
            default => $baseItem,
        };
    }
}
