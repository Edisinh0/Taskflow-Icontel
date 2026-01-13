<?php

namespace App\DTOs;

readonly class UnifiedDashboardItemDTO
{
    public function __construct(
        public string $id,
        public string $moduleType,      // 'Task', 'Case', 'Opportunity'
        public string $title,
        public ?string $status,
        public ?string $dueDate,
        public ?string $assignedUserName,
        public ?string $relatedTo,      // Nombre del padre (para tareas)
        public ?string $relatedId,      // ID del padre
        public ?string $priority,
        public ?float $amount,          // Para oportunidades
        public array $metadata          // Datos extra específicos
    ) {}

    public static function fromTask(array $task): self
    {
        $getValue = fn($key) => $task['name_value_list'][$key]['value'] ?? null;

        return new self(
            id: $task['id'],
            moduleType: 'Task',
            title: $getValue('name') ?? 'Sin título',
            status: $getValue('status'),
            dueDate: $getValue('date_due'),
            assignedUserName: $getValue('assigned_user_name'),
            relatedTo: $getValue('parent_name'),
            relatedId: $getValue('parent_id'),
            priority: $getValue('priority'),
            amount: null,
            metadata: [
                'parent_type' => $getValue('parent_type'),
                'description' => $getValue('description')
            ]
        );
    }

    public static function fromCase(array $case): self
    {
        $getValue = fn($key) => $case['name_value_list'][$key]['value'] ?? null;

        return new self(
            id: $case['id'],
            moduleType: 'Case',
            title: $getValue('name') ?? 'Sin título',
            status: $getValue('status'),
            dueDate: null,
            assignedUserName: $getValue('assigned_user_name'),
            relatedTo: $getValue('account_name'),
            relatedId: $getValue('account_id'),
            priority: $getValue('priority'),
            amount: null,
            metadata: [
                'case_number' => $getValue('case_number'),
                'type' => $getValue('type')
            ]
        );
    }

    public static function fromOpportunity(array $opportunity): self
    {
        $getValue = fn($key) => $opportunity['name_value_list'][$key]['value'] ?? null;

        return new self(
            id: $opportunity['id'],
            moduleType: 'Opportunity',
            title: $getValue('name') ?? 'Sin título',
            status: $getValue('sales_stage'),
            dueDate: $getValue('date_closed'),
            assignedUserName: $getValue('assigned_user_name'),
            relatedTo: $getValue('account_name'),
            relatedId: $getValue('account_id'),
            priority: null,
            amount: $getValue('amount') ? (float) $getValue('amount') : null,
            metadata: [
                'probability' => $getValue('probability'),
                'currency' => $getValue('currency_id')
            ]
        );
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'module_type' => $this->moduleType,
            'title' => $this->title,
            'status' => $this->status,
            'due_date' => $this->dueDate,
            'assigned_user_name' => $this->assignedUserName,
            'related_to' => $this->relatedTo,
            'related_id' => $this->relatedId,
            'priority' => $this->priority,
            'amount' => $this->amount,
            'metadata' => $this->metadata
        ];
    }
}
