<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class UserCacheService
{
    private const CACHE_TTL = 3600; // 1 hora
    private const CACHE_PREFIX = 'users_by_dept_';

    /**
     * Obtener usuarios por departamento (con caché)
     *
     * @param string $department Nombre del departamento (ej: 'Operaciones', 'Ventas')
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getUsersByDepartment(string $department)
    {
        $cacheKey = self::CACHE_PREFIX . strtolower($department);

        // Intentar obtener del caché
        $users = Cache::remember(
            $cacheKey,
            self::CACHE_TTL,
            function () use ($department) {
                Log::info('Cache MISS: Loading users for department', ['department' => $department]);

                return User::where('department', $department)
                    ->select('id', 'name', 'email', 'sweetcrm_id', 'department')
                    ->orderBy('name')
                    ->get();
            }
        );

        Log::debug('Retrieved users from cache', [
            'department' => $department,
            'count' => $users->count(),
            'cache_key' => $cacheKey
        ]);

        return $users;
    }

    /**
     * Obtener usuario de Operaciones por ID (optimizado)
     *
     * @param int $userId ID del usuario local
     * @return User|null
     */
    public function getOperationsUser(int $userId): ?User
    {
        // Primero intentar caché de usuario individual
        $cacheKey = 'user_' . $userId;

        $user = Cache::remember(
            $cacheKey,
            self::CACHE_TTL,
            function () use ($userId) {
                return User::where('id', $userId)
                    ->where('department', 'Operaciones')
                    ->select('id', 'name', 'email', 'sweetcrm_id', 'department')
                    ->first();
            }
        );

        return $user;
    }

    /**
     * Obtener usuario de Ventas por ID (optimizado)
     *
     * @param int $userId ID del usuario local
     * @return User|null
     */
    public function getSalesUser(int $userId): ?User
    {
        $cacheKey = 'user_' . $userId;

        $user = Cache::remember(
            $cacheKey,
            self::CACHE_TTL,
            function () use ($userId) {
                return User::where('id', $userId)
                    ->where('department', 'Ventas')
                    ->select('id', 'name', 'email', 'sweetcrm_id', 'department')
                    ->first();
            }
        );

        return $user;
    }

    /**
     * Obtener mapeo de sweetcrm_id → usuario por departamento
     * Útil para búsquedas rápidas por SuiteCRM ID
     *
     * @param string $department
     * @return array ['sweetcrm_id' => User, ...]
     */
    public function getSweetCrmIdMap(string $department): array
    {
        $cacheKey = 'sweetcrm_map_' . strtolower($department);

        $map = Cache::remember(
            $cacheKey,
            self::CACHE_TTL,
            function () use ($department) {
                Log::info('Building SweetCRM ID map', ['department' => $department]);

                return User::where('department', $department)
                    ->whereNotNull('sweetcrm_id')
                    ->get()
                    ->keyBy('sweetcrm_id')
                    ->map(function ($user) {
                        return [
                            'id' => $user->id,
                            'name' => $user->name,
                            'email' => $user->email,
                            'sweetcrm_id' => $user->sweetcrm_id
                        ];
                    })
                    ->toArray();
            }
        );

        return $map;
    }

    /**
     * Invalidar caché de un usuario
     *
     * @param int $userId
     * @return void
     */
    public function invalidateUserCache(int $userId): void
    {
        $user = User::find($userId);
        if (!$user) {
            return;
        }

        // Eliminar caché individual
        Cache::forget('user_' . $userId);

        // Eliminar caché del departamento
        Cache::forget(self::CACHE_PREFIX . strtolower($user->department));

        // Eliminar mapeo de SuiteCRM
        Cache::forget('sweetcrm_map_' . strtolower($user->department));

        Log::info('User cache invalidated', [
            'user_id' => $userId,
            'department' => $user->department
        ]);
    }

    /**
     * Invalidar todo el caché de usuarios por departamento
     *
     * @param string $department
     * @return void
     */
    public function invalidateDepartmentCache(string $department): void
    {
        Cache::forget(self::CACHE_PREFIX . strtolower($department));
        Cache::forget('sweetcrm_map_' . strtolower($department));

        Log::info('Department user cache invalidated', ['department' => $department]);
    }

    /**
     * Invalidar TODOS los cachés de usuarios
     *
     * @return void
     */
    public function invalidateAllUserCaches(): void
    {
        // Obtener todos los departamentos únicos
        $departments = User::distinct()
            ->pluck('department')
            ->filter()
            ->unique();

        foreach ($departments as $dept) {
            $this->invalidateDepartmentCache($dept);
        }

        // También limpiar patrón general
        Cache::flush();

        Log::warning('All user caches have been invalidated');
    }

    /**
     * Obtener estadísticas de caché
     *
     * @return array
     */
    public function getCacheStats(): array
    {
        $departments = User::distinct()
            ->pluck('department')
            ->filter()
            ->unique()
            ->toArray();

        $stats = [
            'cached_departments' => $departments,
            'cache_ttl' => self::CACHE_TTL,
            'per_department' => []
        ];

        foreach ($departments as $dept) {
            $cacheKey = self::CACHE_PREFIX . strtolower($dept);
            $users = Cache::get($cacheKey);
            $stats['per_department'][$dept] = [
                'count' => $users ? count($users) : 0,
                'cached' => $users !== null
            ];
        }

        return $stats;
    }
}
