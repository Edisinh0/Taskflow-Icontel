<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\SweetCrmService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class QuoteController extends Controller
{
    protected SweetCrmService $sweetCrmService;

    public function __construct(SweetCrmService $sweetCrmService)
    {
        $this->sweetCrmService = $sweetCrmService;
    }

    /**
     * Listar cotizaciones desde SweetCRM
     * GET /api/v1/quotes
     */
    public function index(Request $request)
    {
        try {
            $user = $request->user();

            // Obtener session ID desde cache o autenticar
            $sessionResult = $this->getSessionForUser($user);

            if (!$sessionResult['success']) {
                return response()->json([
                    'message' => 'Error de autenticación con SweetCRM',
                    'error' => $sessionResult['error'] ?? 'No se pudo obtener sesión'
                ], 401);
            }

            $sessionId = $sessionResult['session_id'];

            // Construir filtros
            $filters = [
                'max_results' => $request->input('limit', 100),
                'offset' => $request->input('offset', 0),
            ];

            // Filtro por estado de cotización
            if ($request->has('quote_stage')) {
                $filters['query'] = "quotes.quote_stage = '{$request->input('quote_stage')}'";
            }

            // Filtro por oportunidad relacionada
            if ($request->has('opportunity_id')) {
                $existingQuery = $filters['query'] ?? '';
                $oppFilter = "quotes.opportunity_id = '{$request->input('opportunity_id')}'";
                $filters['query'] = $existingQuery ? "({$existingQuery}) AND {$oppFilter}" : $oppFilter;
            }

            // Filtro por usuario asignado (para mostrar solo las del usuario actual)
            if ($request->input('my_quotes', false) && $user->sweetcrm_id) {
                $existingQuery = $filters['query'] ?? '';
                $userFilter = "quotes.assigned_user_id = '{$user->sweetcrm_id}'";
                $filters['query'] = $existingQuery ? "({$existingQuery}) AND {$userFilter}" : $userFilter;
            }

            // Obtener cotizaciones desde SweetCRM
            $rawQuotes = $this->sweetCrmService->getQuotes($sessionId, $filters);

            // Transformar datos al formato esperado por el frontend
            $quotes = $this->transformQuotes($rawQuotes);

            return response()->json([
                'data' => $quotes,
                'meta' => [
                    'total' => count($quotes),
                    'offset' => $filters['offset'],
                    'limit' => $filters['max_results'],
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Error al obtener cotizaciones', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'message' => 'Error al obtener cotizaciones',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtener session ID para el usuario actual
     */
    private function getSessionForUser($user): array
    {
        // Por ahora usamos credenciales del sistema configuradas en .env
        $username = config('services.sweetcrm.username');
        $password = config('services.sweetcrm.password');

        if (!$username || !$password) {
            return [
                'success' => false,
                'error' => 'Credenciales de SweetCRM no configuradas'
            ];
        }

        return $this->sweetCrmService->getCachedSession($username, $password);
    }

    /**
     * Transformar datos crudos de SweetCRM al formato del frontend
     */
    private function transformQuotes(array $rawQuotes): array
    {
        return array_map(function ($entry) {
            $nvl = $entry['name_value_list'] ?? [];

            return [
                'id' => $entry['id'] ?? null,
                'name' => $nvl['name']['value'] ?? 'Sin nombre',
                'quote_num' => $nvl['quote_num']['value'] ?? null,
                'quote_stage' => $nvl['quote_stage']['value'] ?? 'Draft',
                'purchase_order_num' => $nvl['purchase_order_num']['value'] ?? null,
                'payment_terms' => $nvl['payment_terms']['value'] ?? null,
                'description' => $nvl['description']['value'] ?? null,
                'total' => $nvl['total']['value'] ?? 0,
                'subtotal' => $nvl['subtotal']['value'] ?? 0,
                'tax' => $nvl['tax']['value'] ?? 0,
                'shipping' => $nvl['shipping']['value'] ?? 0,
                'discount' => $nvl['discount']['value'] ?? 0,
                'currency_id' => $nvl['currency_id']['value'] ?? null,
                'date_quote_expected_closed' => $nvl['date_quote_expected_closed']['value'] ?? null,
                'billing_account_id' => $nvl['billing_account_id']['value'] ?? null,
                'billing_account_name' => $nvl['billing_account_name']['value'] ?? null,
                'billing_contact_id' => $nvl['billing_contact_id']['value'] ?? null,
                'billing_contact_name' => $nvl['billing_contact_name']['value'] ?? null,
                'opportunity_id' => $nvl['opportunity_id']['value'] ?? null,
                'opportunity_name' => $nvl['opportunity_name']['value'] ?? null,
                'assigned_user_id' => $nvl['assigned_user_id']['value'] ?? null,
                'assigned_user_name' => $nvl['assigned_user_name']['value'] ?? null,
                'created_by' => $nvl['created_by']['value'] ?? null,
                'date_entered' => $nvl['date_entered']['value'] ?? null,
                'date_modified' => $nvl['date_modified']['value'] ?? null,
            ];
        }, $rawQuotes);
    }
}
