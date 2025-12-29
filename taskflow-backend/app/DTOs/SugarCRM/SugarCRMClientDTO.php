<?php

namespace App\DTOs\SugarCRM;

/**
 * DTO para transformar datos de clientes (Accounts) desde SugarCRM API v4_1
 * al formato interno de Taskflow
 */
class SugarCRMClientDTO
{
    public function __construct(
        public readonly string $id,
        public readonly string $name,
        public readonly ?string $email,
        public readonly ?string $phone,
        public readonly ?string $address,
        public readonly ?string $industry,
        public readonly ?string $accountType,
        public readonly ?string $financialStatus,
        public readonly ?string $description,
        public readonly ?string $assignedUserId,
        public readonly ?\DateTimeInterface $dateEntered,
        public readonly ?\DateTimeInterface $dateModified,
    ) {}

    /**
     * Crear DTO desde respuesta de SugarCRM v4_1 (formato name_value_list)
     */
    public static function fromSugarCRMResponse(array $sugarCRMData): self
    {
        $nvl = $sugarCRMData['name_value_list'] ?? [];

        return new self(
            id: $sugarCRMData['id'] ?? throw new \InvalidArgumentException('Client ID is required'),
            name: $nvl['name']['value'] ?? 'Sin nombre',
            email: $nvl['email1']['value'] ?? null,
            phone: $nvl['phone_office']['value'] ?? null,
            address: self::formatAddress($nvl),
            industry: $nvl['industry']['value'] ?? null,
            accountType: $nvl['account_type']['value'] ?? null,
            financialStatus: $nvl['estatusfinanciero_c']['value'] ?? null,
            description: $nvl['description']['value'] ?? null,
            assignedUserId: $nvl['assigned_user_id']['value'] ?? null,
            dateEntered: self::parseDate($nvl['date_entered']['value'] ?? null),
            dateModified: self::parseDate($nvl['date_modified']['value'] ?? null),
        );
    }

    /**
     * Convertir a array para crear/actualizar modelo Client de Taskflow
     */
    public function toClientArray(?int $industryId = null): array
    {
        return [
            'name' => $this->name,
            'contact_email' => $this->email,
            'contact_phone' => $this->phone,
            'address' => $this->address,
            'industry_id' => $industryId,
            'notes' => $this->description,
            'sweetcrm_id' => $this->id,
            'sweetcrm_assigned_user_id' => $this->assignedUserId,
            'account_type' => $this->accountType,
            'status' => $this->mapFinancialStatusToClientStatus($this->financialStatus),
            'sweetcrm_synced_at' => now(),
        ];
    }

    /**
     * Formatear dirección desde campos de SugarCRM
     */
    private static function formatAddress(array $nameValueList): ?string
    {
        $parts = [];

        if (!empty($nameValueList['billing_address_street']['value'])) {
            $parts[] = $nameValueList['billing_address_street']['value'];
        }
        if (!empty($nameValueList['billing_address_city']['value'])) {
            $parts[] = $nameValueList['billing_address_city']['value'];
        }
        if (!empty($nameValueList['billing_address_country']['value'])) {
            $parts[] = $nameValueList['billing_address_country']['value'];
        }

        return !empty($parts) ? implode(', ', $parts) : null;
    }

    /**
     * Parsear fecha de SugarCRM a objeto DateTime
     */
    private static function parseDate(?string $date): ?\DateTimeInterface
    {
        if (!$date) {
            return null;
        }

        try {
            return new \DateTime($date);
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Mapear estado financiero de SugarCRM a estado de cliente en Taskflow
     */
    private function mapFinancialStatusToClientStatus(?string $financialStatus): string
    {
        return match ($financialStatus) {
            'Activo', 'anticipo' => 'active',
            'Baja', 'Prospecto' => 'inactive',
            default => 'active',
        };
    }
}
