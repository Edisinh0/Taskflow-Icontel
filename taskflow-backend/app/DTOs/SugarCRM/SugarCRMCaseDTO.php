<?php

namespace App\DTOs\SugarCRM;

/**
 * DTO para transformar datos de Casos (Cases) desde SugarCRM API v4_1
 */
class SugarCRMCaseDTO
{
    public function __construct(
        public readonly string $id,
        public readonly string $caseNumber,
        public readonly string $name,
        public readonly ?string $description,
        public readonly ?string $status,
        public readonly ?string $priority,
        public readonly ?string $type,
        public readonly ?string $accountId,
        public readonly ?string $assignedUserId,
        public readonly ?string $assignedUserName, // New
        public readonly ?string $createdByName, // New
        public readonly ?string $area,
        public readonly ?\DateTimeInterface $dateEntered,
        public readonly ?\DateTimeInterface $dateModified,
    ) {}

    public static function fromSugarCRMResponse(array $sugarCRMData): self
    {
        $nvl = $sugarCRMData['name_value_list'] ?? [];
        $val = fn($key) => $nvl[$key]['value'] ?? null;

        return new self(
            id: $sugarCRMData['id'] ?? $val('id') ?? throw new \InvalidArgumentException('Case ID is required'),
            caseNumber: $val('case_number') ?? '',
            name: $val('name') ?? 'Sin asunto',
            description: $val('description'),
            status: $val('status'),
            priority: $val('priority'),
            type: $val('type'),
            accountId: $val('account_id'),
            assignedUserId: $val('assigned_user_id'),
            assignedUserName: $val('assigned_user_name'),
            createdByName: $val('created_by_name'),
            area: $val('area_c'),
            dateEntered: self::parseDate($val('date_entered')),
            dateModified: self::parseDate($val('date_modified')),
        );
    }
    
    // ... parseDate function remains the same ...

    private static function parseDate(?string $date): ?\DateTimeInterface
    {
        if (!$date) return null;
        try {
            return new \DateTime($date);
        } catch (\Exception $e) {
            return null;
        }
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'case_number' => $this->caseNumber,
            'title' => $this->name,
            'description' => $this->description,
            'status' => $this->status,
            'priority' => $this->priority,
            'client_id' => $this->accountId,
            'assigned_user_id' => $this->assignedUserId,
            'assigned_user_name' => $this->assignedUserName, // New field for frontend
            'created_by_name' => $this->createdByName, // New field for frontend
            'area' => $this->area,
            'created_at' => $this->dateEntered?->format('c'),
            'type' => 'case',
            'tasks' => [],
        ];
    }
}
