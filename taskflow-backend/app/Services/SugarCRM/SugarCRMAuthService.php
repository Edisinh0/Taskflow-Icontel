<?php

namespace App\Services\SugarCRM;

use App\Adapters\SugarCRM\SugarCRMApiAdapter;
use App\DTOs\SugarCRM\SugarCRMSessionDTO;
use App\DTOs\SugarCRM\SugarCRMUserDTO;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * Servicio especializado en autenticación con SugarCRM
 */
class SugarCRMAuthService
{
    public function __construct(
        private SugarCRMApiAdapter $adapter
    ) {}

    /**
     * Autenticar usuario en SugarCRM
     */
    public function authenticate(string $username, string $password): array
    {
        try {
            $session = $this->adapter->authenticate($username, $password);

            if (!$session) {
                return [
                    'success' => false,
                    'message' => 'Credenciales inválidas',
                ];
            }

            // Obtener información del usuario
            $user = $session->userId
                ? $this->adapter->getUser($session->sessionId, $session->userId)
                : null;

            Log::info('SugarCRM authentication successful', [
                'username' => $username,
                'user_id' => $session->userId,
            ]);

            return [
                'success' => true,
                'data' => [
                    'session_id' => $session->sessionId,
                    'user' => $user ? [
                        'id' => $user->id,
                        'name' => $user->getDisplayName(),
                        'username' => $user->username,
                        'email' => $user->email,
                        'role' => $user->isAdmin ? 'admin' : 'user',
                        'user_type' => $user->userType,
                    ] : null,
                ],
            ];

        } catch (\Exception $e) {
            Log::error('SugarCRM authentication error', [
                'username' => $username,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'message' => 'Error de conexión con SugarCRM',
            ];
        }
    }

    /**
     * Obtener session ID válido (simplificado para comandos)
     */
    public function getSessionId(string $username, string $password): ?string
    {
        $result = $this->authenticate($username, $password);

        return $result['success'] ? $result['data']['session_id'] : null;
    }

    /**
     * Validar si una sesión está activa
     */
    public function validateSession(string $sessionId): bool
    {
        // Intentar hacer una petición simple para validar la sesión
        $clients = $this->adapter->getClients($sessionId, 1, 0);

        return !empty($clients) || is_array($clients);
    }

    /**
     * Obtener o refrescar session ID (con caché)
     */
    public function getCachedSession(string $username, string $password, int $ttl = 3000): ?string
    {
        $cacheKey = "sugarcrm_session_{$username}";

        return Cache::remember($cacheKey, $ttl, function () use ($username, $password) {
            return $this->getSessionId($username, $password);
        });
    }

    /**
     * Invalidar sesión en caché
     */
    public function invalidateSession(string $username): void
    {
        Cache::forget("sugarcrm_session_{$username}");
    }
}
