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

    public function login(Request $request)
    {
        try {
            $request->validate([
                'username' => 'required|string',
                'password' => 'required|string',
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'message' => 'Error de validación',
                'errors' => $e->errors(),
                'received_data' => $request->all() // Para depurar qué está llegando
            ], 422);
        }

        // Autenticar SIEMPRE contra SweetCRM
        $result = $this->sweetCrmService->authenticate($request->username, $request->password);

        if (!$result['success']) {
            return response()->json([
                'message' => $result['error'] ?? 'Credenciales de SuiteCRM inválidas.',
                'errors' => ['username' => [$result['error'] ?? 'Credenciales inválidas']]
            ], 422);
        }

        // Login exitoso - manejar usuario local
        return $this->handleSweetCrmLogin($result['data'], $request->password, $request->username);
    }

    /**
     * Manejar login exitoso desde SweetCRM
     */
    protected function handleSweetCrmLogin(array $sweetCrmData, string $password, string $username): \Illuminate\Http\JsonResponse
    {
        $sweetCrmUser = $sweetCrmData['user'] ?? $sweetCrmData;

        $sweetcrmId = $sweetCrmUser['id'] ?? null;
        $sweetcrmEmail = $sweetCrmUser['email'] ?? null;
        $sweetcrmDepartment = $sweetCrmUser['department'] ?? $sweetCrmUser['dept'] ?? null;

        if (!$sweetcrmId) {
            Log::error('SweetCRM login failed: no ID provided', ['data' => $sweetCrmUser]);
            return response()->json(['message' => 'Error: datos incompletos de SuiteCRM'], 422);
        }

        // Mapear área/departamento
        $area = $this->mapDepartment($sweetcrmDepartment);

        // Buscar o crear usuario por sweetcrm_id
        $user = User::where('sweetcrm_id', $sweetcrmId)->first();

        $userData = [
            'name' => $sweetCrmUser['name'] ?? $sweetCrmUser['username'] ?? $username,
            'email' => $sweetcrmEmail ?? "{$username}@icontel.cl",
            'sweetcrm_id' => $sweetcrmId,
            'department' => $area,
            'sweetcrm_user_type' => $sweetCrmUser['user_type'] ?? null,
            'sweetcrm_synced_at' => now(),
            'password' => Hash::make($password), // Mantener el password local sincronizado por seguridad de sesión
        ];

        if (!$user) {
            $user = User::create($userData);
            Log::info("✅ Nuevo usuario registrado desde SuiteCRM: {$user->name} ({$area})");
        } else {
            $user->update($userData);
            Log::info("🔄 Usuario sincronizado al iniciar sesión: {$user->name} ({$area})");
        }

        // Crear token
        $token = $user->createToken('api-token')->plainTextToken;

        return response()->json([
            'message' => 'Login exitoso',
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'department' => $user->department,
                'role' => $user->role ?? 'user',
                'sweetcrm_id' => $user->sweetcrm_id,
            ],
            'token' => $token
        ], 200);
    }

    /**
     * Mapear departamento de SweetCRM a áreas de Taskflow
     */
    protected function mapDepartment(?string $sweetcrmDepartment): string
    {
        if (!$sweetcrmDepartment) return 'General';

        $dept = strtolower(trim($sweetcrmDepartment));
        
        $areaMap = [
            'ventas' => 'Ventas',
            'sales' => 'Ventas',
            'comercial' => 'Ventas',
            'operaciones' => 'Operaciones',
            'operations' => 'Operaciones',
            'ops' => 'Operaciones',
            'soporte' => 'Soporte',
            'support' => 'Soporte',
            'instalaciones' => 'Instalaciones',
            'installation' => 'Instalaciones',
        ];

        return $areaMap[$dept] ?? 'General';
    }

    /**
     * Logout
     */
    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();
        return response()->json(['message' => 'Sesión cerrada']);
    }

    /**
     * Información del usuario
     */
    public function me(Request $request)
    {
        return response()->json(['user' => $request->user()]);
    }
}