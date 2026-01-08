<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class SweetCrmService
{
    private string $baseUrl;
    private ?string $apiToken;
    private int $timeout = 30;
    private int $sessionCacheTTL = 3600; // 1 hour session cache

    public function __construct()
    {
        $this->baseUrl = rtrim(config('services.sweetcrm.url') ?? '', '/');
        $this->apiToken = config('services.sweetcrm.api_token');
    }

    /**
     * Get or create cached session for a user
     * Reduces authentication overhead by caching session IDs for 1 hour
     *
     * @param string $username
     * @param string $password
     * @return array ['success' => bool, 'session_id' => string|null, 'error' => string|null]
     */
    public function getCachedSession(string $username, string $password): array
    {
        $cacheKey = 'sweetcrm_session_' . md5($username);

        // Try to get cached session
        $cachedSessionId = Cache::get($cacheKey);

        if ($cachedSessionId) {
            // Validate session is still active by making a lightweight API call
            if ($this->validateSession($cachedSessionId)) {
                Log::info('Using cached SweetCRM session', ['username' => $username]);
                return [
                    'success' => true,
                    'session_id' => $cachedSessionId,
                    'cached' => true,
                ];
            } else {
                // Session expired, remove from cache
                Cache::forget($cacheKey);
                Log::info('Cached SweetCRM session expired', ['username' => $username]);
            }
        }

        // No valid cached session, authenticate
        $authResult = $this->authenticate($username, $password);

        if ($authResult['success']) {
            $sessionId = $authResult['data']['session_id'];

            // Cache the session ID for 1 hour
            Cache::put($cacheKey, $sessionId, $this->sessionCacheTTL);

            Log::info('Created new SweetCRM session and cached it', ['username' => $username]);

            return [
                'success' => true,
                'session_id' => $sessionId,
                'cached' => false,
            ];
        }

        return [
            'success' => false,
            'error' => $authResult['error'] ?? 'Authentication failed',
        ];
    }

    /**
     * Validate if a session ID is still active
     *
     * @param string $sessionId
     * @return bool
     */
    private function validateSession(string $sessionId): bool
    {
        try {
            $response = Http::timeout(5) // Short timeout for validation
                ->asForm()
                ->post("{$this->baseUrl}/service/v4_1/rest.php", [
                    'method' => 'get_user_id',
                    'input_type' => 'JSON',
                    'response_type' => 'JSON',
                    'rest_data' => json_encode([
                        'session' => $sessionId,
                    ]),
                ]);

            if ($response->successful()) {
                $userId = $response->json();
                // If we get a valid user ID, session is active
                return !empty($userId) && !isset($userId['name']) || $userId['name'] !== 'Invalid Session ID';
            }

            return false;
        } catch (\Exception $e) {
            Log::warning('Session validation failed', ['error' => $e->getMessage()]);
            return false;
        }
    }

    /**
     * Autenticar usuario con SugarCRM usando API REST v4_1
     *
     * IMPORTANTE: Esta instalación usa la API REST v4_1 de SugarCRM
     * Endpoint: POST /service/v4_1/rest.php
     */
    public function authenticate(string $username, string $password): array
    {
        try {
            // SugarCRM v4_1 REST API authentication
            Log::info('SweetCRM Auth Request', ['username' => $username, 'url' => "{$this->baseUrl}/service/v4_1/rest.php"]);
            $response = Http::timeout($this->timeout)
                ->asForm()
                ->post("{$this->baseUrl}/service/v4_1/rest.php", [
                    'method' => 'login',
                    'input_type' => 'JSON',
                    'response_type' => 'JSON',
                    'rest_data' => json_encode([
                        'user_auth' => [
                            'user_name' => $username,
                            'password' => md5($password), // v4_1 requiere MD5
                        ],
                        'application_name' => 'Taskflow',
                    ]),
                ]);

            if ($response->successful()) {
                $data = $response->json();
                Log::info('SweetCRM Auth Response', ['status' => $response->status(), 'data' => $data]);

                // Verificar si hubo error en la respuesta
                if (isset($data['name']) && $data['name'] === 'Invalid Login') {
                    Log::warning('SugarCRM v4_1 authentication failed', [
                        'username' => $username,
                        'error' => $data['description'] ?? 'Invalid Login',
                    ]);

                    return [
                        'success' => false,
                        'error' => 'Credenciales inválidas',
                    ];
                }

                // Login exitoso - obtener información del usuario
                $sessionId = $data['id'] ?? null;

                if (!$sessionId) {
                    return [
                        'success' => false,
                        'error' => 'No se recibió session ID',
                    ];
                }

                // Obtener información del usuario
                $userData = $this->getCurrentUserV4_1($sessionId);

                return [
                    'success' => true,
                    'data' => [
                        'user' => $userData,
                        'session_id' => $sessionId,
                    ],
                ];
            }

            Log::warning('SugarCRM v4_1 authentication request failed', [
                'username' => $username,
                'status' => $response->status(),
            ]);

            return [
                'success' => false,
                'error' => 'Error de conexión con SugarCRM',
            ];
        } catch (\Exception $e) {
            Log::error('SugarCRM authentication error', [
                'username' => $username,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'error' => 'No se pudo conectar con SugarCRM: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Obtener información del usuario actual usando session ID (API v4_1)
     */
    private function getCurrentUserV4_1(string $sessionId): ?array
    {
        try {
            $response = Http::timeout($this->timeout)
                ->asForm()
                ->post("{$this->baseUrl}/service/v4_1/rest.php", [
                    'method' => 'get_user_id',
                    'input_type' => 'JSON',
                    'response_type' => 'JSON',
                    'rest_data' => json_encode([
                        'session' => $sessionId,
                    ]),
                ]);

            if ($response->successful()) {
                $userId = $response->json();

                // Obtener detalles completos del usuario
                $userDetailsResponse = Http::timeout($this->timeout)
                    ->asForm()
                    ->post("{$this->baseUrl}/service/v4_1/rest.php", [
                        'method' => 'get_user_team_id',
                        'input_type' => 'JSON',
                        'response_type' => 'JSON',
                        'rest_data' => json_encode([
                            'session' => $sessionId,
                        ]),
                    ]);

                return [
                    'id' => $userId,
                    'username' => null, // v4_1 no retorna username fácilmente
                    'name' => null, // Necesitaríamos otro llamado para esto
                    'email' => null,
                    'role' => 'user',
                    'user_type' => 'Regular',
                ];
            }

            return [
                'id' => $sessionId, // Usar session como ID temporal
                'username' => null,
                'name' => null,
                'email' => null,
                'role' => 'user',
                'user_type' => 'Regular',
            ];
        } catch (\Exception $e) {
            Log::error('Failed to get current user from SugarCRM v4_1', [
                'error' => $e->getMessage(),
            ]);

            return [
                'id' => $sessionId,
                'username' => null,
                'name' => null,
                'email' => null,
                'role' => 'user',
                'user_type' => 'Regular',
            ];
        }
    }

    /**
     * Mapear tipos de usuario de SugarCRM a roles
     */
    private function mapSugarRole(string $sugarType): string
    {
        $roleMap = [
            'admin' => 'admin',
            'administrator' => 'admin',
            'regular' => 'user',
            'user' => 'user',
        ];

        return $roleMap[strtolower($sugarType)] ?? 'user';
    }

    /**
     * Verificar qué endpoints de SugarCRM están disponibles (para diagnóstico)
     */
    public function testEndpoints(): array
    {
        $results = [];
        $endpoints = [
            'GET /rest/v11_24/ping',
            'POST /rest/v11_24/oauth2/token',
            'GET /rest/v11_24/me',
            'GET /rest/v11_24/Accounts',
            'GET /rest/v11_24/Users',
        ];

        foreach ($endpoints as $endpoint) {
            [$method, $path] = explode(' ', $endpoint);

            try {
                if ($method === 'GET') {
                    // Ping no requiere autenticación
                    if (str_contains($path, 'ping')) {
                        $response = Http::timeout(5)->get("{$this->baseUrl}{$path}");
                    } else {
                        // Otros GET requieren token
                        $response = Http::timeout(5)
                            ->withToken($this->apiToken)
                            ->get("{$this->baseUrl}{$path}");
                    }
                } else {
                    // POST para OAuth2 (test con datos vacíos solo para verificar endpoint)
                    $response = Http::timeout(5)
                        ->asJson()
                        ->post("{$this->baseUrl}{$path}", [
                            'grant_type' => 'password',
                            'client_id' => 'sugar',
                            'client_secret' => '',
                            'username' => 'test',
                            'password' => 'test',
                            'platform' => 'taskflow',
                        ]);
                }

                $results[$endpoint] = [
                    'status' => $response->status(),
                    'accessible' => $response->successful() || $response->status() === 401,
                    'error' => !$response->successful() && $response->status() !== 401 ? substr($response->body(), 0, 200) : null,
                ];
            } catch (\Exception $e) {
                $results[$endpoint] = [
                    'status' => null,
                    'accessible' => false,
                    'error' => $e->getMessage(),
                ];
            }
        }

        return $results;
    }

    /**
     * Obtener datos de usuario desde SugarCRM v4_1
     */
    public function getUser(string $sessionId, string $sweetcrmId): ?array
    {
        try {
            $response = Http::timeout($this->timeout)
                ->asForm()
                ->post("{$this->baseUrl}/service/v4_1/rest.php", [
                    'method' => 'get_entry',
                    'input_type' => 'JSON',
                    'response_type' => 'JSON',
                    'rest_data' => json_encode([
                        'session' => $sessionId,
                        'module_name' => 'Users',
                        'id' => $sweetcrmId,
                        'select_fields' => [
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
                        ],
                    ]),
                ]);

            if (!$response->successful()) {
                Log::warning('SugarCRM v4_1 user fetch failed', [
                    'sweetcrm_id' => $sweetcrmId,
                    'status' => $response->status(),
                ]);
                return null;
            }

            $data = $response->json();

            // Verificar si hay error de sesión
            if (isset($data['name']) && $data['name'] === 'Invalid Session ID') {
                Log::warning('SugarCRM session expired when fetching user');
                return null;
            }

            // Parsear respuesta v4_1 (formato name_value_list)
            if (isset($data['entry_list'][0])) {
                $nvl = $data['entry_list'][0]['name_value_list'] ?? [];

                return [
                    'id' => $data['entry_list'][0]['id'] ?? $sweetcrmId,
                    'user_name' => $nvl['user_name']['value'] ?? null,
                    'first_name' => $nvl['first_name']['value'] ?? null,
                    'last_name' => $nvl['last_name']['value'] ?? null,
                    'full_name' => $nvl['full_name']['value'] ?? null,
                    'email' => $nvl['email1']['value'] ?? null,
                    'phone' => $nvl['phone_work']['value'] ?? null,
                    'title' => $nvl['title']['value'] ?? null,
                    'department' => $nvl['department']['value'] ?? null,
                    'status' => $nvl['status']['value'] ?? null,
                ];
            }

            return null;
        } catch (\Exception $e) {
            Log::error('SugarCRM v4_1 user fetch error', [
                'sweetcrm_id' => $sweetcrmId,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * Obtener listado de clientes (Accounts) desde SugarCRM v4_1
     *
     * Requiere autenticarse primero para obtener un session ID
     */
    public function getClients(string $sessionId, array $filters = []): array
    {
        try {
            // Usar get_entry_list para obtener Accounts
            $response = Http::timeout($this->timeout)
                ->asForm()
                ->post("{$this->baseUrl}/service/v4_1/rest.php", [
                    'method' => 'get_entry_list',
                    'input_type' => 'JSON',
                    'response_type' => 'JSON',
                    'rest_data' => json_encode([
                        'session' => $sessionId,
                        'module_name' => 'Accounts',
                        'query' => $filters['query'] ?? '', // Filtro SQL opcional
                        'order_by' => $filters['order_by'] ?? '',
                        'offset' => $filters['offset'] ?? 0,
                        'select_fields' => [
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
                        ],
                        'link_name_to_fields_array' => [],
                        'max_results' => $filters['max_results'] ?? 100,
                        'deleted' => 0,
                    ]),
                ]);

            if ($response->successful()) {
                $data = $response->json();

                // Verificar si hay error
                if (isset($data['name']) && $data['name'] === 'Invalid Session ID') {
                    Log::warning('SugarCRM session expired', [
                        'method' => 'getClients',
                    ]);
                    return [];
                }

                // Retornar la lista de accounts
                return $data['entry_list'] ?? [];
            }

            Log::warning('SugarCRM v4_1 clients (Accounts) fetch failed', [
                'status' => $response->status(),
            ]);

            return [];
        } catch (\Exception $e) {
            Log::error('SugarCRM v4_1 clients fetch error', [
                'error' => $e->getMessage(),
            ]);

            return [];
        }
    }

    /**
     * Autenticar y obtener session ID para operaciones de sincronización
     */
    public function getSessionId(string $username, string $password): ?string
    {
        $result = $this->authenticate($username, $password);

        if ($result['success']) {
            return $result['data']['session_id'] ?? null;
        }

        return null;
    }

    /**
     * Obtener un cliente (Account) específico desde SugarCRM v4_1
     */
    public function getClient(string $sessionId, string $sweetcrmId): ?array
    {
        try {
            $response = Http::timeout($this->timeout)
                ->asForm()
                ->post("{$this->baseUrl}/service/v4_1/rest.php", [
                    'method' => 'get_entry',
                    'input_type' => 'JSON',
                    'response_type' => 'JSON',
                    'rest_data' => json_encode([
                        'session' => $sessionId,
                        'module_name' => 'Accounts',
                        'id' => $sweetcrmId,
                        'select_fields' => [
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
                        ],
                    ]),
                ]);

            if ($response->successful()) {
                $data = $response->json();

                if (isset($data['entry_list'][0])) {
                    return $data['entry_list'][0];
                }
            }

            Log::warning('SugarCRM v4_1 client (Account) fetch failed', [
                'sweetcrm_id' => $sweetcrmId,
                'status' => $response->status(),
            ]);

            return null;
        } catch (\Exception $e) {
            Log::error('SugarCRM v4_1 client fetch error', [
                'sweetcrm_id' => $sweetcrmId,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * Sincronizar cliente desde SweetCRM a Taskflow
     */
    public function syncClient(string $sessionId, string $sweetcrmId): ?array
    {
        $clientData = $this->getClient($sessionId, $sweetcrmId);

        if (!$clientData) {
            return null;
        }

        // Invalidar cache
        Cache::forget("sweetcrm_client_{$sweetcrmId}");

        return $clientData;
    }

    /**
     * Verificar conexión con SugarCRM
     */
    public function ping(): bool
    {
        try {
            // SugarCRM usa /rest/v11_24/ping
            $response = Http::timeout(5)
                ->get("{$this->baseUrl}/rest/v11_24/ping");

            return $response->successful();
        } catch (\Exception $e) {
            Log::error('SugarCRM ping failed', [
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Obtener todos los usuarios de SweetCRM v4_1
     */
    public function getUsers(string $sessionId): array
    {
        try {
            $response = Http::timeout($this->timeout)
                ->asForm()
                ->post("{$this->baseUrl}/service/v4_1/rest.php", [
                    'method' => 'get_entry_list',
                    'input_type' => 'JSON',
                    'response_type' => 'JSON',
                    'rest_data' => json_encode([
                        'session' => $sessionId,
                        'module_name' => 'Users',
                        'query' => '',
                        'order_by' => 'user_name',
                        'offset' => 0,
                        'select_fields' => [
                            'id',
                            'user_name',
                            'first_name',
                            'last_name',
                            'email1',
                            'is_admin',
                            'status',
                            'department',
                        ],
                        'link_name_to_fields_array' => [],
                        'max_results' => 1000, // Alto límite para usuarios
                        'deleted' => 0,
                    ]),
                ]);

            if ($response->successful()) {
                $data = $response->json();
                return $data['entry_list'] ?? [];
            }

            return [];
        } catch (\Exception $e) {
            Log::error('SugarCRM v4_1 users fetch error', ['error' => $e->getMessage()]);
            return [];
        }
    }

    /**
     * Invalidar cache de un recurso
     */
    public function invalidateCache(string $resource, string $id): void
    {
        $cacheKey = "sweetcrm_{$resource}_{$id}";
        Cache::forget($cacheKey);
    }

    /**
     * Obtener casos (Cases) desde SugarCRM v4_1
     */
    public function getCases(string $sessionId, array $filters = []): array
    {
        try {
            $response = Http::timeout($this->timeout)
                ->asForm()
                ->post("{$this->baseUrl}/service/v4_1/rest.php", [
                    'method' => 'get_entry_list',
                    'input_type' => 'JSON',
                    'response_type' => 'JSON',
                    'rest_data' => json_encode([
                        'session' => $sessionId,
                        'module_name' => 'Cases',
                        'query' => $filters['query'] ?? '',
                        'order_by' => $filters['order_by'] ?? 'case_number DESC',
                        'offset' => $filters['offset'] ?? 0,
                        'select_fields' => [
                            'id',
                            'case_number',
                            'name',
                            'account_id',
                            'description',
                            'state', // Campo real de estado en SweetCRM (Open, Closed, etc.)
                            'status', // Campo legacy (generalmente vacío)
                            'priority',
                            'type',
                            'area_c', // Campo personalizado de área en SweetCRM
                            'assigned_user_id',
                            'assigned_user_name', // Nombre del usuario asignado
                            'created_by',
                            'created_by_name', // Nombre del creador
                            'date_entered',
                            'date_modified',
                            'avances_1_c',
                            'avances_2_c',
                            'avances_3_c',
                            'avances_4_c',
                        ],
                        'link_name_to_fields_array' => [],
                        'max_results' => $filters['max_results'] ?? 100,
                        'deleted' => 0,
                    ]),
                ]);

            if ($response->successful()) {
                $data = $response->json();
                return $data['entry_list'] ?? [];
            }

            return [];
        } catch (\Exception $e) {
            Log::error('SugarCRM v4_1 cases fetch error', ['error' => $e->getMessage()]);
            return [];
        }
    }

    /**
     * Obtener tareas (Tasks) desde SugarCRM v4_1
     */
    public function getTasks(string $sessionId, array $filters = []): array
    {
        try {
            $query = $filters['query'] ?? '';

            Log::info('🔍 SweetCRM getTasks Request', [
                'query' => $query,
                'max_results' => $filters['max_results'] ?? 100
            ]);

            $response = Http::timeout($this->timeout)
                ->asForm()
                ->post("{$this->baseUrl}/service/v4_1/rest.php", [
                    'method' => 'get_entry_list',
                    'input_type' => 'JSON',
                    'response_type' => 'JSON',
                    'rest_data' => json_encode([
                        'session' => $sessionId,
                        'module_name' => 'Tasks',
                        'query' => $query,
                        'order_by' => $filters['order_by'] ?? 'date_entered DESC',
                        'offset' => $filters['offset'] ?? 0,
                        'select_fields' => [
                            'id',
                            'name',
                            'description',
                            'status',
                            'priority',
                            'parent_type',
                            'parent_id',
                            'contact_id',
                            'date_start',
                            'date_due',
                            'assigned_user_id',
                            'assigned_user_name',
                            'created_by',
                            'created_by_name',
                            'date_entered',
                            'date_modified',
                            'avance_c',
                        ],
                        'link_name_to_fields_array' => [],
                        'max_results' => $filters['max_results'] ?? 100,
                        'deleted' => 0,
                    ]),
                ]);

            if ($response->successful()) {
                $data = $response->json();
                $resultCount = count($data['entry_list'] ?? []);

                Log::info('✅ SweetCRM getTasks Response', [
                    'count' => $resultCount,
                    'result_count_from_api' => $data['result_count'] ?? 'N/A'
                ]);

                return $data['entry_list'] ?? [];
            }

            Log::warning('❌ SweetCRM getTasks HTTP Error', [
                'status' => $response->status(),
                'body' => $response->body()
            ]);

            return [];
        } catch (\Exception $e) {
            Log::error('SugarCRM v4_1 tasks fetch error', ['error' => $e->getMessage()]);
            return [];
        }
    }

    /**
     * Obtener oportunidades (Opportunities) desde SugarCRM v4_1
     */
    public function getOpportunities(string $sessionId, array $filters = []): array
    {
        try {
            $query = $filters['query'] ?? '';

            Log::info('🔍 SweetCRM getOpportunities Request', [
                'query' => $query,
                'max_results' => $filters['max_results'] ?? 100
            ]);

            $response = Http::timeout($this->timeout)
                ->asForm()
                ->post("{$this->baseUrl}/service/v4_1/rest.php", [
                    'method' => 'get_entry_list',
                    'input_type' => 'JSON',
                    'response_type' => 'JSON',
                    'rest_data' => json_encode([
                        'session' => $sessionId,
                        'module_name' => 'Opportunities',
                        'query' => $query,
                        'order_by' => $filters['order_by'] ?? 'date_entered DESC',
                        'offset' => $filters['offset'] ?? 0,
                        'select_fields' => [
                            'id',
                            'name',
                            'description',
                            'amount',
                            'amount_usdollar',
                            'currency_id',
                            'sales_stage',
                            'probability',
                            'date_closed',
                            'next_step',
                            'lead_source',
                            'opportunity_type',
                            'account_id',
                            'account_name',
                            'assigned_user_id',
                            'assigned_user_name',
                            'created_by',
                            'date_entered',
                            'date_modified',
                        ],
                        'link_name_to_fields_array' => [],
                        'max_results' => $filters['max_results'] ?? 100,
                        'deleted' => 0,
                    ]),
                ]);

            if ($response->successful()) {
                $data = $response->json();
                $resultCount = count($data['entry_list'] ?? []);

                Log::info('✅ SweetCRM getOpportunities Response', [
                    'count' => $resultCount,
                    'result_count_from_api' => $data['result_count'] ?? 'N/A'
                ]);

                return $data['entry_list'] ?? [];
            }

            Log::warning('❌ SweetCRM getOpportunities HTTP Error', [
                'status' => $response->status(),
                'body' => $response->body()
            ]);

            return [];
        } catch (\Exception $e) {
            Log::error('SugarCRM v4_1 opportunities fetch error', ['error' => $e->getMessage()]);
            return [];
        }
    }

    /**
     * Obtener cotizaciones (Quotes) desde SugarCRM v4_1
     */
    public function getQuotes(string $sessionId, array $filters = []): array
    {
        try {
            $query = $filters['query'] ?? '';

            Log::info('🔍 SweetCRM getQuotes Request', [
                'query' => $query,
                'max_results' => $filters['max_results'] ?? 100
            ]);

            $response = Http::timeout($this->timeout)
                ->asForm()
                ->post("{$this->baseUrl}/service/v4_1/rest.php", [
                    'method' => 'get_entry_list',
                    'input_type' => 'JSON',
                    'response_type' => 'JSON',
                    'rest_data' => json_encode([
                        'session' => $sessionId,
                        'module_name' => 'Quotes',
                        'query' => $query,
                        'order_by' => $filters['order_by'] ?? 'date_entered DESC',
                        'offset' => $filters['offset'] ?? 0,
                        'select_fields' => [
                            'id',
                            'name',
                            'quote_num',
                            'quote_stage',
                            'purchase_order_num',
                            'payment_terms',
                            'description',
                            'total',
                            'subtotal',
                            'tax',
                            'shipping',
                            'discount',
                            'currency_id',
                            'date_quote_expected_closed',
                            'billing_account_id',
                            'billing_account_name',
                            'billing_contact_id',
                            'billing_contact_name',
                            'opportunity_id',
                            'opportunity_name',
                            'assigned_user_id',
                            'assigned_user_name',
                            'created_by',
                            'date_entered',
                            'date_modified',
                        ],
                        'link_name_to_fields_array' => [],
                        'max_results' => $filters['max_results'] ?? 100,
                        'deleted' => 0,
                    ]),
                ]);

            if ($response->successful()) {
                $data = $response->json();
                $resultCount = count($data['entry_list'] ?? []);

                Log::info('✅ SweetCRM getQuotes Response', [
                    'count' => $resultCount,
                    'result_count_from_api' => $data['result_count'] ?? 'N/A'
                ]);

                return $data['entry_list'] ?? [];
            }

            Log::warning('❌ SweetCRM getQuotes HTTP Error', [
                'status' => $response->status(),
                'body' => $response->body()
            ]);

            return [];
        } catch (\Exception $e) {
            Log::error('SugarCRM v4_1 quotes fetch error', ['error' => $e->getMessage()]);
            return [];
        }
    }
}
