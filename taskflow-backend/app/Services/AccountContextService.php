<?php

namespace App\Services;

use App\Adapters\SugarCRM\SugarCRMApiAdapter;
use Illuminate\Support\Facades\Log;

/**
 * Servicio para orquestar la obtención del contexto completo de una Cuenta (Account)
 * incluyendo todas sus relaciones jerárquicas en SuiteCRM
 *
 * Jerarquía: Cuenta > Oportunidades/Casos/Cotizaciones/Contactos > Tareas
 */
class AccountContextService
{
    public function __construct(
        private SugarCRMApiAdapter $adapter,
        private SweetCrmService $sweetCrmService
    ) {}

    /**
     * Obtener contexto completo de una cuenta
     * Incluye: Oportunidades, Casos, Cotizaciones, Contactos y sus Tareas relacionadas
     */
    public function getFullAccountContext(string $accountId): array
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

            // 2. Obtener historial completo de la cuenta
            $accountHistory = $this->adapter->getAccountFullHistory($sessionId, $accountId);

            // 3. Normalizar y estructurar la respuesta
            $context = $this->normalizeAccountContext($accountHistory);

            Log::info('Account context loaded successfully', [
                'account_id' => $accountId,
                'context_summary' => $context['summary']
            ]);

            return $context;

        } catch (\Exception $e) {
            Log::error('Error getting account context', [
                'account_id' => $accountId,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Normaliza y estructura los datos del contexto de la cuenta
     * para presentación en el frontend
     */
    private function normalizeAccountContext(array $rawData): array
    {
        $getValue = fn($key, $record) => $record['name_value_list'][$key]['value'] ?? null;

        return [
            'account_id' => $rawData['account_id'],
            'opportunities' => array_map(function($opp) use ($getValue) {
                return [
                    'id' => $opp['id'],
                    'name' => $getValue('name', $opp),
                    'sales_stage' => $getValue('sales_stage', $opp),
                    'amount' => $getValue('amount', $opp),
                    'currency' => $getValue('currency_id', $opp),
                    'probability' => $getValue('probability', $opp),
                    'expected_close_date' => $getValue('date_closed', $opp),
                    'assigned_user' => $getValue('assigned_user_name', $opp),
                ];
            }, $rawData['opportunities']),

            'cases' => array_map(function($case) use ($getValue) {
                return [
                    'id' => $case['id'],
                    'name' => $getValue('name', $case),
                    'case_number' => $getValue('case_number', $case),
                    'status' => $getValue('status', $case),
                    'priority' => $getValue('priority', $case),
                    'type' => $getValue('type', $case),
                    'assigned_user' => $getValue('assigned_user_name', $case),
                ];
            }, $rawData['cases']),

            'quotes' => array_map(function($quote) use ($getValue) {
                return [
                    'id' => $quote['id'],
                    'name' => $getValue('name', $quote),
                    'number' => $getValue('quote_num', $quote),
                    'total' => $getValue('total', $quote),
                    'currency' => $getValue('currency_id', $quote),
                    'status' => $getValue('status', $quote),
                    'assigned_user' => $getValue('assigned_user_name', $quote),
                ];
            }, $rawData['quotes']),

            'contacts' => array_map(function($contact) use ($getValue) {
                $firstName = $getValue('first_name', $contact);
                $lastName = $getValue('last_name', $contact);
                return [
                    'id' => $contact['id'],
                    'name' => trim("{$firstName} {$lastName}"),
                    'email' => $getValue('email1', $contact),
                    'phone' => $getValue('phone_mobile', $contact),
                    'title' => $getValue('title', $contact),
                ];
            }, $rawData['contacts']),

            'tasks' => array_map(function($task) use ($getValue) {
                return [
                    'id' => $task['id'],
                    'name' => $getValue('name', $task),
                    'status' => $getValue('status', $task),
                    'priority' => $getValue('priority', $task),
                    'due_date' => $getValue('date_due', $task),
                    'assigned_user' => $getValue('assigned_user_name', $task),
                    'parent_type' => $getValue('parent_type', $task),
                    'parent_id' => $getValue('parent_id', $task),
                    'parent_name' => $getValue('parent_name', $task),
                ];
            }, $rawData['tasks']),

            'summary' => [
                'total_opportunities' => $rawData['summary']['total_opportunities'],
                'total_cases' => $rawData['summary']['total_cases'],
                'total_quotes' => $rawData['summary']['total_quotes'],
                'total_contacts' => $rawData['summary']['total_contacts'],
                'total_tasks' => $rawData['summary']['total_tasks'],
                'total_items' => array_sum([
                    $rawData['summary']['total_opportunities'],
                    $rawData['summary']['total_cases'],
                    $rawData['summary']['total_quotes'],
                    $rawData['summary']['total_contacts'],
                    $rawData['summary']['total_tasks'],
                ])
            ]
        ];
    }
}
