<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\SweetCrmService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    protected SweetCrmService $sweetCrmService;

    public function __construct(SweetCrmService $sweetCrmService)
    {
        $this->sweetCrmService = $sweetCrmService;
    }

    /**
     * Login - Autenticar usuario y devolver token
     * POST /api/v1/auth/login
     */
    public function login(Request $request)
    {
        // Validar datos de entrada
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
            'use_sweetcrm' => 'sometimes|boolean',
        ]);

        $useSweetCrm = $request->boolean('use_sweetcrm', config('services.sweetcrm.enabled'));

        // Si está habilitado SweetCRM, intentar autenticación primero
        if ($useSweetCrm) {
            $sweetCrmAuth = $this->sweetCrmService->authenticate($request->email, $request->password);

            if ($sweetCrmAuth['success']) {
                return $this->handleSweetCrmLogin($sweetCrmAuth['data'], $request->password);
            }

            // Si falla SweetCRM, continuar con autenticación local
            Log::info('SweetCRM authentication failed, trying local auth', [
                'email' => $request->email,
            ]);
        }

        // Autenticación local
        return $this->handleLocalLogin($request->email, $request->password);
    }

    /**
     * Login con autenticación de SweetCRM
     * POST /api/v1/auth/sweetcrm-login
     */
    public function sweetCrmLogin(Request $request)
    {
        $request->validate([
            'username' => 'required|string',
            'password' => 'required',
        ]);

        $result = $this->sweetCrmService->authenticate($request->username, $request->password);

        if (!$result['success']) {
            throw ValidationException::withMessages([
                'username' => [$result['error'] ?? 'Error de autenticación con SweetCRM'],
            ]);
        }

        return $this->handleSweetCrmLogin($result['data'], $request->password, $request->username);
    }

    /**
     * Manejar login exitoso desde SweetCRM
     */
    protected function handleSweetCrmLogin(array $sweetCrmData, string $password, ?string $username = null): \Illuminate\Http\JsonResponse
    {
        $sweetCrmUser = $sweetCrmData['user'] ?? $sweetCrmData;

        // Validar que tengamos al menos ID o email para buscar
        $sweetcrmId = $sweetCrmUser['id'] ?? null;
        $sweetcrmEmail = $sweetCrmUser['email'] ?? null;

        if (!$sweetcrmId && !$sweetcrmEmail) {
            Log::error('SweetCRM login failed: no ID or email provided', [
                'data' => $sweetCrmUser,
            ]);

            return response()->json([
                'message' => 'Error: datos incompletos de SweetCRM',
            ], 422);
        }

        // Buscar usuario - solo buscar por los campos que existen
        $query = User::query();

        if ($sweetcrmId) {
            $query->where('sweetcrm_id', $sweetcrmId);
        }

        if ($sweetcrmEmail && !$sweetcrmId) {
            $query->orWhere('email', $sweetcrmEmail);
        }

        $user = $query->first();

        $userData = [
            'name' => $sweetCrmUser['name'] ?? $sweetCrmUser['username'] ?? $username,
            'sweetcrm_id' => $sweetcrmId,
            'sweetcrm_user_type' => $sweetCrmUser['user_type'] ?? null,
            'sweetcrm_synced_at' => now(),
        ];

        // Si el usuario de SweetCRM tiene email, lo usamos, si no generamos uno temporal único
        $email = $sweetcrmEmail ?? ($sweetcrmId ? "{$sweetcrmId}@sweetcrm.local" : $username . '@sweetcrm.local');

        if (!$user) {
            // Crear nuevo usuario desde datos de SweetCRM
            $user = User::create([
                ...$userData,
                'email' => $email,
                'password' => Hash::make($password),
                'role' => $this->mapSweetCrmRole($sweetCrmUser['role'] ?? 'user'),
            ]);

            Log::info('New user created from SweetCRM', [
                'user_id' => $user->id,
                'sweetcrm_id' => $sweetCrmUser['id'],
                'username' => $username,
            ]);
        } else {
            // Actualizar datos desde SweetCRM
            $user->update($userData);
        }

        // Crear token de acceso
        $token = $user->createToken('api-token')->plainTextToken;

        return response()->json([
            'message' => 'Login exitoso con SweetCRM',
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role,
                'sweetcrm_id' => $user->sweetcrm_id,
            ],
            'token' => $token,
            'expires_in' => 3600,
            'auth_source' => 'sweetcrm',
        ], 200);
    }

    /**
     * Manejar login local (sin SweetCRM)
     */
    protected function handleLocalLogin(string $email, string $password): \Illuminate\Http\JsonResponse
    {
        // Buscar usuario por email
        $user = User::where('email', $email)->first();

        // Verificar si existe y la contraseña es correcta
        if (!$user || !Hash::check($password, $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['Las credenciales son incorrectas.'],
            ]);
        }

        // Crear token de acceso
        $token = $user->createToken('api-token')->plainTextToken;

        return response()->json([
            'message' => 'Login exitoso',
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role,
            ],
            'token' => $token,
            'expires_in' => 3600,
            'auth_source' => 'local',
        ], 200);
    }

    /**
     * Mapear roles de SweetCRM a roles de Taskflow
     */
    protected function mapSweetCrmRole(string $sweetCrmRole): string
    {
        $roleMap = [
            'admin' => 'admin',
            'manager' => 'project_manager',
            'user' => 'user',
            'client' => 'user',
        ];

        return $roleMap[strtolower($sweetCrmRole)] ?? 'user';
    }

    /**
     * Logout - Revocar token actual
     * POST /api/v1/auth/logout
     */
    public function logout(Request $request)
    {
        // Eliminar el token actual del usuario autenticado
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'message' => 'Logout exitoso',
        ], 200);
    }

    /**
     * Obtener información del usuario autenticado
     * GET /api/v1/auth/me
     */
    public function me(Request $request)
    {
        return response()->json([
            'user' => $request->user(),
        ], 200);
    }

    /**
     * Registro de nuevo usuario (opcional)
     * POST /api/v1/auth/register
     */
    public function register(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|min:8|confirmed',
        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
        ]);

        $token = $user->createToken('api-token')->plainTextToken;

        return response()->json([
            'message' => 'Usuario registrado exitosamente',
            'user' => $user,
            'token' => $token,
        ], 201);
    }
}