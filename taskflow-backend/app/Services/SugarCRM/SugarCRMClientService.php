<?php

namespace App\Services\SugarCRM;

use App\Adapters\SugarCRM\SugarCRMApiAdapter;
use App\DTOs\SugarCRM\SugarCRMClientDTO;
use App\Models\Client;
use App\Models\Industry;
use Illuminate\Support\Facades\Log;

/**
 * Servicio especializado en gestión de clientes desde SugarCRM
 */
class SugarCRMClientService
{
    public function __construct(
        private SugarCRMApiAdapter $adapter
    ) {}

    /**
     * Obtener lista de clientes desde SugarCRM
     *
     * @return SugarCRMClientDTO[]
     */
    public function getClients(string $sessionId, int $maxResults = 100, int $offset = 0): array
    {
        return $this->adapter->getClients($sessionId, $maxResults, $offset);
    }

    /**
     * Obtener un cliente específico
     */
    public function getClient(string $sessionId, string $clientId): ?SugarCRMClientDTO
    {
        return $this->adapter->getClient($sessionId, $clientId);
    }

    /**
     * Sincronizar un cliente de SugarCRM a Taskflow
     */
    public function syncClient(SugarCRMClientDTO $sugarClient): Client
    {
        // Buscar industria o crearla
        $industryId = null;
        if ($sugarClient->industry) {
            $industryId = $this->findOrCreateIndustry($sugarClient->industry);
        }

        // Convertir DTO a array para el modelo
        $clientData = $sugarClient->toClientArray($industryId);

        // Buscar o crear cliente
        $client = Client::where('sweetcrm_id', $sugarClient->id)->first();

        if ($client) {
            $client->update($clientData);
            Log::debug('Client updated from SugarCRM', ['client_id' => $client->id]);
        } else {
            $client = Client::create($clientData);
            Log::info('Client created from SugarCRM', ['client_id' => $client->id]);
        }

        return $client;
    }

    /**
     * Sincronizar múltiples clientes
     *
     * @param SugarCRMClientDTO[] $sugarClients
     * @return array{synced: int, created: int, updated: int, errors: array}
     */
    public function syncMultipleClients(array $sugarClients): array
    {
        $synced = 0;
        $created = 0;
        $updated = 0;
        $errors = [];

        foreach ($sugarClients as $sugarClient) {
            try {
                $existingClient = Client::where('sweetcrm_id', $sugarClient->id)->exists();

                $this->syncClient($sugarClient);

                $existingClient ? $updated++ : $created++;
                $synced++;

            } catch (\Exception $e) {
                $errors[] = [
                    'client' => $sugarClient->name,
                    'id' => $sugarClient->id,
                    'error' => $e->getMessage(),
                ];

                Log::error('Error syncing client from SugarCRM', [
                    'sweetcrm_id' => $sugarClient->id,
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

    /**
     * Buscar o crear industria
     */
    private function findOrCreateIndustry(string $industryName): int
    {
        // Mapeo de industrias en inglés a español
        $industryMap = [
            'Apparel' => 'Vestuario',
            'Banking' => 'Banca',
            'Biotechnology' => 'Biotecnología',
            'Chemicals' => 'Química',
            'Communications' => 'Comunicaciones',
            'Construction' => 'Construcción',
            'Consulting' => 'Consultoría',
            'Education' => 'Educación',
            'Electronics' => 'Electrónica',
            'Energy' => 'Energía',
            'Engineering' => 'Ingeniería',
            'Entertainment' => 'Entretenimiento',
            'Environmental' => 'Medio Ambiente',
            'Finance' => 'Finanzas',
            'Food & Beverage' => 'Alimentos y Bebidas',
            'Government' => 'Gobierno',
            'Healthcare' => 'Salud',
            'Hospitality' => 'Hospitalidad',
            'Insurance' => 'Seguros',
            'Machinery' => 'Maquinaria',
            'Manufacturing' => 'Manufactura',
            'Media' => 'Medios',
            'Not For Profit' => 'Sin Fines de Lucro',
            'Other' => 'Otro',
            'Recreation' => 'Recreación',
            'Retail' => 'Retail',
            'Shipping' => 'Transporte',
            'Technology' => 'Tecnología',
            'Telecommunications' => 'Telecomunicaciones',
            'Transportation' => 'Transporte',
            'Utilities' => 'Servicios Públicos',
        ];

        $translatedName = $industryMap[$industryName] ?? $industryName;

        $industry = Industry::firstOrCreate(
            ['name' => $translatedName],
            ['description' => "Industria: $translatedName"]
        );

        return $industry->id;
    }
}
