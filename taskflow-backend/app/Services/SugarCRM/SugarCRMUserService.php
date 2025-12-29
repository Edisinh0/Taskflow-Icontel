<?php

namespace App\Services\SugarCRM;

use App\Adapters\SugarCRM\SugarCRMApiAdapter;
use App\DTOs\SugarCRM\SugarCRMUserDTO;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Servicio especializado en gestión de usuarios desde SugarCRM
 */
class SugarCRMUserService
{
    public function __construct(
        private SugarCRMApiAdapter $adapter
    ) {}

    /**
     * Obtener lista de usuarios desde SugarCRM
     *
     * @return SugarCRMUserDTO[]
     */
    public function getUsers(string $sessionId, int $maxResults = 100, int $offset = 0): array
    {
        return $this->adapter->getUsers($sessionId, $maxResults, $offset);
    }

    /**
     * Obtener un usuario específico
     */
    public function getUser(string $sessionId, string $userId): ?SugarCRMUserDTO
    {
        return $this->adapter->getUser($sessionId, $userId);
    }

    /**
     * Sincronizar un usuario de SugarCRM a Taskflow
     */
    public function syncUser(SugarCRMUserDTO $sugarUser, ?string $password = null): User
    {
        // Buscar usuario por SugarCRM ID
        $user = User::where('sweetcrm_id', $sugarUser->id)->first();

        // Si no existe por ID, buscar por email
        if (!$user && $sugarUser->email) {
            $user = User::where('email', $sugarUser->email)->first();

            // Verificar que no haya conflicto de IDs
            if ($user && $user->sweetcrm_id && $user->sweetcrm_id !== $sugarUser->id) {
                Log::warning('User email conflict with different SugarCRM ID', [
                    'email' => $sugarUser->email,
                    'existing_id' => $user->sweetcrm_id,
                    'new_id' => $sugarUser->id,
                ]);
                throw new \RuntimeException("Conflict: User with email {$sugarUser->email} has different SugarCRM ID");
            }
        }

        $userData = $sugarUser->toUserArray();

        if ($user) {
            // Actualizar usuario existente (sin cambiar email si ya existe)
            unset($userData['email']);
            $user->update($userData);
            Log::debug('User updated from SugarCRM', ['user_id' => $user->id]);
        } else {
            // Crear nuevo usuario
            $userData['password'] = $password ? Hash::make($password) : Hash::make(Str::random(16));
            $user = User::create($userData);
            Log::info('User created from SugarCRM', ['user_id' => $user->id]);
        }

        return $user;
    }

    /**
     * Sincronizar múltiples usuarios
     *
     * @param SugarCRMUserDTO[] $sugarUsers
     * @return array{synced: int, created: int, updated: int, errors: array}
     */
    public function syncMultipleUsers(array $sugarUsers): array
    {
        $synced = 0;
        $created = 0;
        $updated = 0;
        $errors = [];

        foreach ($sugarUsers as $sugarUser) {
            try {
                $existingUser = User::where('sweetcrm_id', $sugarUser->id)->exists();

                $this->syncUser($sugarUser);

                $existingUser ? $updated++ : $created++;
                $synced++;

            } catch (\Exception $e) {
                $errors[] = [
                    'user' => $sugarUser->getDisplayName(),
                    'id' => $sugarUser->id,
                    'error' => $e->getMessage(),
                ];

                Log::error('Error syncing user from SugarCRM', [
                    'sweetcrm_id' => $sugarUser->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return [
            'synced' => $synced,
            'created' => $created,
            'updated' => $updated,
            'errors' => $errors,
        ];
    }
}
