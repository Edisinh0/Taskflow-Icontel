<?php

namespace App\Adapters\SugarCRM;

use App\DTOs\SugarCRM\SugarCRMClientDTO;
use App\DTOs\SugarCRM\SugarCRMUserDTO;
use App\DTOs\SugarCRM\SugarCRMSessionDTO;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Adapter para SugarCRM REST API v4_1
 *
 * Encapsula toda la comunicación con la API de SugarCRM y convierte
 * las respuestas al formato interno usando DTOs
 */
class SugarCRMApiAdapter
{
    private string $baseUrl;
    private int $timeout;
    private string $apiEndpoint;

    public function __construct()
    {
        $this->baseUrl = rtrim(config('services.sweetcrm.url'), '/');
        $this->timeout = config('services.sweetcrm.timeout', 30);
        $this->apiEndpoint = '/service/v4_1/rest.php';
    }

    /**
     * Autenticar usuario y obtener sesión
     */
    public function authenticate(string $username, string $password): ?SugarCRMSessionDTO
    {
        try {
            $response = Http::timeout($this->timeout)
                ->asForm()
                ->post("{$this->baseUrl}{$this->apiEndpoint}", [
                    'method' => 'login',
                    'input_type' => 'JSON',
                    'response_type' => 'JSON',
                    'rest_data' => json_encode([
                        'user_auth' => [
                            'user_name' => $username,
                            'password' => md5($password),
                        ],
                        'application_name' => 'Taskflow',
                    ]),
                ]);

            if (!$response->successful()) {
                Log::warning('SugarCRM authentication failed', [
                    'username' => $username,
                    'status' => $response->status(),
                ]);
                return null;
            }

            $data = $response->json();

            if (isset($data['name']) && $data['name'] === 'Invalid Login') {
                Log::warning('SugarCRM invalid credentials', ['username' => $username]);
                return null;
            }

            return SugarCRMSessionDTO::fromLoginResponse($data);

        } catch (\Exception $e) {
            Log::error('SugarCRM authentication error', [
                'username' => $username,
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }

    /**
     * Obtener lista de clientes (Accounts)
     *
     * @return SugarCRMClientDTO[]
     */
    public function getClients(string $sessionId, int $maxResults = 100, int $offset = 0, ?string $query = null): array
    {
        try {
            $response = Http::timeout($this->timeout)
                ->asForm()
                ->post("{$this->baseUrl}{$this->apiEndpoint}", [
                    'method' => 'get_entry_list',
                    'input_type' => 'JSON',
                    'response_type' => 'JSON',
                    'rest_data' => json_encode([
                        'session' => $sessionId,
                        'module_name' => 'Accounts',
                        'query' => $query ?? '',
                        'order_by' => '',
                        'offset' => $offset,
                        'select_fields' => $this->getClientFields(),
                        'link_name_to_fields_array' => [],
                        'max_results' => $maxResults,
                        'deleted' => 0,
                    ]),
                ]);

            if (!$response->successful()) {
                Log::warning('SugarCRM getClients failed', [
                    'status' => $response->status(),
                ]);
                return [];
            }

            $data = $response->json();

            if ($this->isSessionInvalid($data)) {
                Log::warning('SugarCRM session expired when fetching clients');
                return [];
            }

            $entryList = $data['entry_list'] ?? [];

            return array_map(
                fn($entry) => SugarCRMClientDTO::fromSugarCRMResponse($entry),
                $entryList
            );

        } catch (\Exception $e) {
            Log::error('SugarCRM getClients error', [
                'error' => $e->getMessage(),
            ]);
            return [];
        }
    }

    /**
     * Obtener un cliente específico
     */
    public function getClient(string $sessionId, string $clientId): ?SugarCRMClientDTO
    {
        try {
            $response = Http::timeout($this->timeout)
                ->asForm()
                ->post("{$this->baseUrl}{$this->apiEndpoint}", [
                    'method' => 'get_entry',
                    'input_type' => 'JSON',
                    'response_type' => 'JSON',
                    'rest_data' => json_encode([
                        'session' => $sessionId,
                        'module_name' => 'Accounts',
                        'id' => $clientId,
                        'select_fields' => $this->getClientFields(),
                    ]),
                ]);

            if (!$response->successful()) {
                return null;
            }

            $data = $response->json();

            if ($this->isSessionInvalid($data) || !isset($data['entry_list'][0])) {
                return null;
            }

            return SugarCRMClientDTO::fromSugarCRMResponse($data['entry_list'][0]);

        } catch (\Exception $e) {
            Log::error('SugarCRM getClient error', [
                'client_id' => $clientId,
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }

    /**
     * Obtener lista de usuarios
     *
     * @return SugarCRMUserDTO[]
     */
    public function getUsers(string $sessionId, int $maxResults = 100, int $offset = 0): array
    {
        try {
            $response = Http::timeout($this->timeout)
                ->asForm()
                ->post("{$this->baseUrl}{$this->apiEndpoint}", [
                    'method' => 'get_entry_list',
                    'input_type' => 'JSON',
                    'response_type' => 'JSON',
                    'rest_data' => json_encode([
                        'session' => $sessionId,
                        'module_name' => 'Users',
                        'query' => '',
                        'order_by' => '',
                        'offset' => $offset,
                        'select_fields' => $this->getUserFields(),
                        'max_results' => $maxResults,
                        'deleted' => 0,
                    ]),
                ]);

            if (!$response->successful()) {
                return [];
            }

            $data = $response->json();

            if ($this->isSessionInvalid($data)) {
                return [];
            }

            $entryList = $data['entry_list'] ?? [];

            return array_map(
                fn($entry) => SugarCRMUserDTO::fromSugarCRMResponse($entry),
                $entryList
            );

        } catch (\Exception $e) {
            Log::error('SugarCRM getUsers error', [
                'error' => $e->getMessage(),
            ]);
            return [];
        }
    }

    /**
     * Obtener un usuario específico
     */
    public function getUser(string $sessionId, string $userId): ?SugarCRMUserDTO
    {
        try {
            $response = Http::timeout($this->timeout)
                ->asForm()
                ->post("{$this->baseUrl}{$this->apiEndpoint}", [
                    'method' => 'get_entry',
                    'input_type' => 'JSON',
                    'response_type' => 'JSON',
                    'rest_data' => json_encode([
                        'session' => $sessionId,
                        'module_name' => 'Users',
                        'id' => $userId,
                        'select_fields' => $this->getUserFields(),
                    ]),
                ]);

            if (!$response->successful()) {
                return null;
            }

            $data = $response->json();

            if ($this->isSessionInvalid($data) || !isset($data['entry_list'][0])) {
                return null;
            }

            return SugarCRMUserDTO::fromSugarCRMResponse($data['entry_list'][0]);

        } catch (\Exception $e) {
            Log::error('SugarCRM getUser error', [
                'user_id' => $userId,
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }

    /**
     * Método genérico para obtener entradas de cualquier módulo
     * Utilizado para dashboard unificado y queries personalizadas
     */
    public function getEntriesByModule(
        string $sessionId,
        string $moduleName,
        string $query,
        array $selectFields,
        array $options = []
    ): array {
        $maxResults = $options['max_results'] ?? 100;
        $offset = $options['offset'] ?? 0;
        $orderBy = $options['order_by'] ?? '';
        $deleted = $options['deleted'] ?? 0;

        try {
            $response = Http::timeout($this->timeout)
                ->asForm()
                ->post("{$this->baseUrl}{$this->apiEndpoint}", [
                    'method' => 'get_entry_list',
                    'input_type' => 'JSON',
                    'response_type' => 'JSON',
                    'rest_data' => json_encode([
                        'session' => $sessionId,
                        'module_name' => $moduleName,
                        'query' => $query,
                        'order_by' => $orderBy,
                        'offset' => $offset,
                        'select_fields' => $selectFields,
                        'link_name_to_fields_array' => [],
                        'max_results' => $maxResults,
                        'deleted' => $deleted,
                    ])
                ]);

            if (!$response->successful()) {
                throw new \Exception("HTTP Error {$response->status()}: {$response->body()}");
            }

            $data = $response->json();

            if ($this->isSessionInvalid($data)) {
                throw new \Exception('Invalid Session ID');
            }

            return [
                'entry_list' => $data['entry_list'] ?? [],
                'result_count' => $data['result_count'] ?? 0,
                'next_offset' => $data['next_offset'] ?? 0,
            ];

        } catch (\Exception $e) {
            Log::error("SugarCRM API Error for module {$moduleName}", [
                'error' => $e->getMessage(),
                'query' => $query
            ]);
            throw $e;
        }
    }

    /**
     * Obtener historial completo de una cuenta (Account) con todas sus relaciones
     *
     * Orquesta múltiples llamadas get_entry_list para obtener:
     * - Oportunidades (account_id = {clientId})
     * - Casos (account_id = {clientId})
     * - Cotizaciones (billing_account_id = {clientId})
     * - Contactos (account_id = {clientId})
     * - Tareas (parent_id en casos y oportunidades encontrados)
     *
     * Jerarquía SuiteCRM: Cuenta > Oportunidades/Casos > Tareas
     */
    public function getAccountFullHistory(string $sessionId, string $accountId): array
    {
        try {
            Log::info('Iniciando getAccountFullHistory', ['account_id' => $accountId]);

            // 1. Obtener Oportunidades de la Cuenta
            $opportunities = $this->getOpportunitiesByAccount($sessionId, $accountId);

            // 2. Obtener Casos de la Cuenta
            $cases = $this->getCasesByAccount($sessionId, $accountId);

            // 3. Obtener Cotizaciones de la Cuenta
            $quotes = $this->getQuotesByAccount($sessionId, $accountId);

            // 4. Obtener Contactos de la Cuenta
            $contacts = $this->getContactsByAccount($sessionId, $accountId);

            // 5. Obtener Tareas recursivamente (parent_id en casos y oportunidades)
            $opportunityIds = array_map(fn($opp) => $opp['id'], $opportunities);
            $caseIds = array_map(fn($case) => $case['id'], $cases);
            $allParentIds = array_merge($opportunityIds, $caseIds);

            $tasks = [];
            if (!empty($allParentIds)) {
                $tasks = $this->getTasksByParentIds($sessionId, $allParentIds);
            }

            $result = [
                'account_id' => $accountId,
                'opportunities' => $opportunities,
                'cases' => $cases,
                'quotes' => $quotes,
                'contacts' => $contacts,
                'tasks' => $tasks,
                'summary' => [
                    'total_opportunities' => count($opportunities),
                    'total_cases' => count($cases),
                    'total_quotes' => count($quotes),
                    'total_contacts' => count($contacts),
                    'total_tasks' => count($tasks),
                ]
            ];

            Log::info('getAccountFullHistory completado', [
                'account_id' => $accountId,
                'summary' => $result['summary']
            ]);

            return $result;

        } catch (\Exception $e) {
            Log::error('getAccountFullHistory error', [
                'account_id' => $accountId,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Obtener oportunidades de una cuenta específica
     */
    private function getOpportunitiesByAccount(string $sessionId, string $accountId): array
    {
        $query = "opportunities.account_id = '{$accountId}' AND opportunities.deleted = 0";
        $selectFields = [
            'id', 'name', 'sales_stage', 'amount', 'currency_id', 'probability',
            'date_closed', 'date_entered', 'assigned_user_name', 'account_id'
        ];

        try {
            $result = $this->getEntriesByModule($sessionId, 'Opportunities', $query, $selectFields, ['max_results' => 100]);
            return $result['entry_list'] ?? [];
        } catch (\Exception $e) {
            Log::warning('Error fetching opportunities for account', [
                'account_id' => $accountId,
                'error' => $e->getMessage()
            ]);
            return [];
        }
    }

    /**
     * Obtener casos de una cuenta específica
     */
    private function getCasesByAccount(string $sessionId, string $accountId): array
    {
        $query = "cases.account_id = '{$accountId}' AND cases.deleted = 0";
        $selectFields = [
            'id', 'name', 'case_number', 'status', 'priority', 'type',
            'date_entered', 'assigned_user_name', 'account_id'
        ];

        try {
            $result = $this->getEntriesByModule($sessionId, 'Cases', $query, $selectFields, ['max_results' => 100]);
            return $result['entry_list'] ?? [];
        } catch (\Exception $e) {
            Log::warning('Error fetching cases for account', [
                'account_id' => $accountId,
                'error' => $e->getMessage()
            ]);
            return [];
        }
    }

    /**
     * Obtener cotizaciones de una cuenta específica
     */
    private function getQuotesByAccount(string $sessionId, string $accountId): array
    {
        $query = "quotes.billing_account_id = '{$accountId}' AND quotes.deleted = 0";
        $selectFields = [
            'id', 'name', 'quote_num', 'total', 'currency_id', 'status',
            'date_entered', 'assigned_user_name', 'billing_account_id'
        ];

        try {
            $result = $this->getEntriesByModule($sessionId, 'Quotes', $query, $selectFields, ['max_results' => 100]);
            return $result['entry_list'] ?? [];
        } catch (\Exception $e) {
            Log::warning('Error fetching quotes for account', [
                'account_id' => $accountId,
                'error' => $e->getMessage()
            ]);
            return [];
        }
    }

    /**
     * Obtener contactos de una cuenta específica
     */
    private function getContactsByAccount(string $sessionId, string $accountId): array
    {
        $query = "contacts.account_id = '{$accountId}' AND contacts.deleted = 0";
        $selectFields = [
            'id', 'first_name', 'last_name', 'email1', 'phone_mobile',
            'title', 'date_entered', 'account_id'
        ];

        try {
            $result = $this->getEntriesByModule($sessionId, 'Contacts', $query, $selectFields, ['max_results' => 100]);
            return $result['entry_list'] ?? [];
        } catch (\Exception $e) {
            Log::warning('Error fetching contacts for account', [
                'account_id' => $accountId,
                'error' => $e->getMessage()
            ]);
            return [];
        }
    }

    /**
     * Obtener tareas por lista de parent_ids (casos y oportunidades)
     * Filtro: tasks.parent_id IN ({lista_de_ids})
     */
    private function getTasksByParentIds(string $sessionId, array $parentIds): array
    {
        if (empty($parentIds)) {
            return [];
        }

        // Construir query IN para parent_ids
        $idsList = "'" . implode("','", $parentIds) . "'";
        $query = "tasks.parent_id IN ({$idsList}) AND tasks.deleted = 0";

        $selectFields = [
            'id', 'name', 'status', 'priority', 'date_due', 'date_entered',
            'assigned_user_name', 'parent_type', 'parent_id', 'parent_name', 'description'
        ];

        try {
            $result = $this->getEntriesByModule($sessionId, 'Tasks', $query, $selectFields, ['max_results' => 500]);
            return $result['entry_list'] ?? [];
        } catch (\Exception $e) {
            Log::warning('Error fetching tasks for parent_ids', [
                'parent_ids_count' => count($parentIds),
                'error' => $e->getMessage()
            ]);
            return [];
        }
    }

    /**
     * Verificar si hay conexión con SugarCRM
     */
    public function ping(): bool
    {
        try {
            $response = Http::timeout(5)
                ->get($this->baseUrl);

            return $response->successful();
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Campos a solicitar para clientes
     */
    private function getClientFields(): array
    {
        return [
            'id',
            'name',
            'billing_address_street',
            'billing_address_city',
            'billing_address_country',
            'phone_office',
            'email1',
            'industry',
            'description',
            'date_entered',
            'date_modified',
            'assigned_user_id',
            'account_type',
            'estatusfinanciero_c',
        ];
    }

    /**
     * Campos a solicitar para usuarios
     */
    private function getUserFields(): array
    {
        return [
            'id',
            'user_name',
            'first_name',
            'last_name',
            'full_name',
            'email1',
            'phone_work',
            'title',
            'department',
            'status',
            'user_type',
            'is_admin',
        ];
    }

    /**
     * Verificar si la respuesta indica sesión inválida
     */
    private function isSessionInvalid(array $data): bool
    {
        return isset($data['name']) && $data['name'] === 'Invalid Session ID';
    }
}
