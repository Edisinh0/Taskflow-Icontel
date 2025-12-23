<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Models\User;
use App\Services\SweetCrmService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SweetCrmController extends Controller
{
    protected SweetCrmService $sweetCrmService;

    public function __construct(SweetCrmService $sweetCrmService)
    {
        $this->sweetCrmService = $sweetCrmService;
    }

    /**
     * Verificar conexión con SweetCRM
     * GET /api/v1/sweetcrm/ping
     */
    public function ping()
    {
        $isConnected = $this->sweetCrmService->ping();

        return response()->json([
            'connected' => $isConnected,
            'service' => 'SweetCRM',
            'url' => config('services.sweetcrm.url'),
        ]);
    }

    /**
     * Sincronizar clientes desde SugarCRM v4_1
     * POST /api/v1/sweetcrm/sync-clients
     *
     * Requiere credenciales de SugarCRM en el request
     */
    public function syncClients(Request $request)
    {
        $request->validate([
            'username' => 'required|string',
            'password' => 'required|string',
            'filters' => 'sometimes|array',
        ]);

        try {
            // Autenticar con SugarCRM para obtener session ID
            $sessionId = $this->sweetCrmService->getSessionId(
                $request->username,
                $request->password
            );

            if (!$sessionId) {
                return response()->json([
                    'message' => 'Error de autenticación con SugarCRM',
                    'error' => 'No se pudo obtener session ID. Verifica las credenciales.',
                ], 401);
            }

            // Obtener clientes de SugarCRM
            $sweetCrmClients = $this->sweetCrmService->getClients(
                $sessionId,
                $request->input('filters', [])
            );

            $synced = 0;
            $created = 0;
            $updated = 0;
            $errors = [];

            DB::transaction(function () use ($sweetCrmClients, &$synced, &$created, &$updated, &$errors) {
                foreach ($sweetCrmClients as $sweetCrmClient) {
                    try {
                        // Los datos vienen en formato name_value_list
                        $clientId = $sweetCrmClient['id'] ?? null;
                        $nameValueList = $sweetCrmClient['name_value_list'] ?? [];

                        if (!$clientId) {
                            continue;
                        }

                        $client = Client::where('sweetcrm_id', $clientId)->first();

                        $clientData = [
                            'name' => $nameValueList['name']['value'] ?? 'Sin nombre',
                            'industry_id' => $this->findOrCreateIndustry($nameValueList['industry']['value'] ?? null),
                            'contact_email' => $nameValueList['email1']['value'] ?? null,
                            'contact_phone' => $nameValueList['phone_office']['value'] ?? null,
                            'website' => null, // SugarCRM v4_1 puede no tener este campo en Accounts
                            'address' => $this->formatAddress($nameValueList),
                            'notes' => $nameValueList['description']['value'] ?? null,
                            'sweetcrm_id' => $clientId,
                            'sweetcrm_synced_at' => now(),
                        ];

                        if ($client) {
                            $client->update($clientData);
                            $updated++;
                        } else {
                            Client::create($clientData);
                            $created++;
                        }

                        $synced++;
                    } catch (\Exception $e) {
                        $errors[] = [
                            'client' => $nameValueList['name']['value'] ?? 'Unknown',
                            'error' => $e->getMessage(),
                        ];

                        Log::error('Error syncing client from SugarCRM', [
                            'sweetcrm_id' => $clientId ?? 'unknown',
                            'error' => $e->getMessage(),
                        ]);
                    }
                }
            });

            return response()->json([
                'message' => 'Sincronización completada',
                'total' => count($sweetCrmClients),
                'synced' => $synced,
                'created' => $created,
                'updated' => $updated,
                'errors' => $errors,
            ]);
        } catch (\Exception $e) {
            Log::error('Error fetching clients from SugarCRM', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'message' => 'Error al sincronizar clientes',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Formatear dirección desde SugarCRM
     */
    protected function formatAddress(array $nameValueList): ?string
    {
        $addressParts = [];

        if (!empty($nameValueList['billing_address_street']['value'])) {
            $addressParts[] = $nameValueList['billing_address_street']['value'];
        }

        if (!empty($nameValueList['billing_address_city']['value'])) {
            $addressParts[] = $nameValueList['billing_address_city']['value'];
        }

        if (!empty($nameValueList['billing_address_country']['value'])) {
            $addressParts[] = $nameValueList['billing_address_country']['value'];
        }

        return !empty($addressParts) ? implode(', ', $addressParts) : null;
    }

    /**
     * Sincronizar un cliente específico
     * POST /api/v1/sweetcrm/sync-client/{sweetcrmId}
     */
    public function syncClient(string $sweetcrmId)
    {
        try {
            $sweetCrmClient = $this->sweetCrmService->syncClient($sweetcrmId);

            if (!$sweetCrmClient) {
                return response()->json([
                    'message' => 'Cliente no encontrado en SweetCRM',
                ], 404);
            }

            $client = Client::where('sweetcrm_id', $sweetcrmId)->first();

            $clientData = [
                'name' => $sweetCrmClient['name'],
                'industry_id' => $this->findOrCreateIndustry($sweetCrmClient['industry'] ?? null),
                'contact_email' => $sweetCrmClient['email'] ?? null,
                'contact_phone' => $sweetCrmClient['phone'] ?? null,
                'website' => $sweetCrmClient['website'] ?? null,
                'address' => $sweetCrmClient['address'] ?? null,
                'notes' => $sweetCrmClient['notes'] ?? null,
                'sweetcrm_id' => $sweetcrmId,
                'sweetcrm_synced_at' => now(),
            ];

            if ($client) {
                $client->update($clientData);
                $action = 'updated';
            } else {
                $client = Client::create($clientData);
                $action = 'created';
            }

            return response()->json([
                'message' => "Cliente {$action} exitosamente",
                'client' => $client,
                'action' => $action,
            ]);
        } catch (\Exception $e) {
            Log::error('Error syncing client from SweetCRM', [
                'sweetcrm_id' => $sweetcrmId,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'message' => 'Error al sincronizar cliente',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Obtener datos de usuario desde SweetCRM
     * GET /api/v1/sweetcrm/user/{sweetcrmId}
     */
    public function getUser(string $sweetcrmId)
    {
        $userData = $this->sweetCrmService->getUser($sweetcrmId);

        if (!$userData) {
            return response()->json([
                'message' => 'Usuario no encontrado en SweetCRM',
            ], 404);
        }

        return response()->json($userData);
    }

    /**
     * Sincronizar usuario actual con SweetCRM
     * POST /api/v1/sweetcrm/sync-me
     */
    public function syncCurrentUser(Request $request)
    {
        $user = $request->user();

        if (!$user->isSyncedWithSweetCrm()) {
            return response()->json([
                'message' => 'Usuario no está vinculado con SweetCRM',
            ], 400);
        }

        $userData = $this->sweetCrmService->getUser($user->sweetcrm_id);

        if (!$userData) {
            return response()->json([
                'message' => 'Usuario no encontrado en SweetCRM',
            ], 404);
        }

        $user->update([
            'name' => $userData['name'],
            'email' => $userData['email'],
            'sweetcrm_user_type' => $userData['user_type'] ?? null,
            'sweetcrm_synced_at' => now(),
        ]);

        return response()->json([
            'message' => 'Usuario sincronizado exitosamente',
            'user' => $user,
        ]);
    }

    /**
     * Buscar o crear industria
     */
    protected function findOrCreateIndustry(?string $industryName): ?int
    {
        if (!$industryName) {
            return null;
        }

        $industry = \App\Models\Industry::firstOrCreate(
            ['name' => $industryName],
            ['name' => $industryName]
        );

        return $industry->id;
    }
}
