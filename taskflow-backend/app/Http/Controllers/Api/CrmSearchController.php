<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\SweetCrmService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class CrmSearchController extends Controller
{
    protected SweetCrmService $sweetCrmService;

    public function __construct(SweetCrmService $sweetCrmService)
    {
        $this->sweetCrmService = $sweetCrmService;
    }

    /**
     * Search for Cases or Opportunities in SuiteCRM
     * GET /api/v1/crm/search-entities
     *
     * @param Request $request
     * Query params:
     *   - module: 'Cases' or 'Opportunities' (required)
     *   - query: Search term (required, min 2 chars)
     *   - limit: Max results (optional, default 10, max 50)
     * @return \Illuminate\Http\JsonResponse
     */
    public function searchEntities(Request $request)
    {
        $validated = $request->validate([
            'module' => 'required|in:Cases,Opportunities',
            'query' => 'required|string|min:2',
            'limit' => 'nullable|integer|min:1|max:50'
        ]);

        try {
            // Get cached session
            $username = config('services.sweetcrm.username');
            $password = config('services.sweetcrm.password');

            if (!$username || !$password) {
                return response()->json([
                    'success' => false,
                    'message' => 'Credenciales de SweetCRM no configuradas'
                ], 500);
            }

            $sessionResult = $this->sweetCrmService->getCachedSession($username, $password);

            if (!$sessionResult['success']) {
                return response()->json([
                    'success' => false,
                    'message' => 'No se pudo conectar con SweetCRM',
                    'error' => $sessionResult['error']
                ], 500);
            }

            $sessionId = $sessionResult['session_id'];
            $results = $this->sweetCrmService->searchEntities(
                $sessionId,
                $validated['module'],
                $validated['query'],
                $validated['limit'] ?? 10
            );

            return response()->json([
                'success' => true,
                'data' => $results,
                'meta' => [
                    'module' => $validated['module'],
                    'query' => $validated['query'],
                    'count' => count($results)
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Error searching CRM entities', [
                'module' => $validated['module'] ?? null,
                'query' => $validated['query'] ?? null,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error al buscar en SweetCRM',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
