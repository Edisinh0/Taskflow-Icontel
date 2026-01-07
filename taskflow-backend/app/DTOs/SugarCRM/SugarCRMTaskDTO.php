<?php

namespace App\DTOs\SugarCRM;

/**
 * DTO para transformar datos de Tareas (Tasks) desde SugarCRM API v4_1
 */
class SugarCRMTaskDTO
{
    public function __construct(
        public readonly string $id,
        public readonly string $name,
        public readonly ?string $description,
        public readonly ?string $status,
        public readonly ?string $priority,
        public readonly ?string $parentId,
        public readonly ?string $parentType,
        public readonly ?string $contactId,
        public readonly ?string $assignedUserId,
        public readonly ?\DateTimeInterface $dateDue,
        public readonly ?\DateTimeInterface $dateStart,
        public readonly ?\DateTimeInterface $dateEntered,
    ) {}

    public static function fromSugarCRMResponse(array $sugarCRMData): self
    {
        $nvl = $sugarCRMData['name_value_list'] ?? [];
        $val = fn($key) => $nvl[$key]['value'] ?? null;

        return new self(
            id: $sugarCRMData['id'] ?? $val('id') ?? throw new \InvalidArgumentException('Task ID is required'),
            name: $val('name') ?? 'Sin asunto',
            description: $val('description'),
            status: $val('status'),
            priority: $val('priority'),
            parentId: $val('parent_id'),
            parentType: $val('parent_type'), // Importante para jerarquía (Cases)
            contactId: $val('contact_id'),
            assignedUserId: $val('assigned_user_id'),
            dateDue: self::parseDate($val('date_due')),
            dateStart: self::parseDate($val('date_start')),
            dateEntered: self::parseDate($val('date_entered')),
        );
    }

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
            'title' => $this->name,
            'description' => $this->description,
            'status' => $this->status,
            'priority' => $this->priority,
            'parent_id' => $this->parentId,
            'parent_type' => $this->parentType,
            'due_date' => $this->dateDue?->format('c'),
            'estimated_start_at' => $this->dateStart?->format('c'),
            'estimated_end_at' => $this->dateDue?->format('c'),
            'assigned_user_id' => $this->assignedUserId,
            'type' => 'task', // Marcador para frontend
            'is_blocked' => false, // Default, lógica visual frontend
        ];
    }
}
