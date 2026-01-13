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
