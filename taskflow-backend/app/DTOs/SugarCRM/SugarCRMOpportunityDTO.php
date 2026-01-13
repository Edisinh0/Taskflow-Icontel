<?php

namespace App\DTOs\SugarCRM;

readonly class SugarCRMOpportunityDTO
{
    public function __construct(
        public string $id,
        public string $name,
        public ?string $description,
        public ?string $salesStage,
        public ?float $amount,
        public ?string $currency,
        public ?int $probability,
        public ?string $expectedCloseDate,
        public ?string $accountId,
        public ?string $accountName,
        public ?string $assignedUserId,
        public ?string $assignedUserName,
        public ?string $dateEntered,
        public ?string $dateModified
    ) {}

    public static function fromSugarCRMResponse(array $entry): self
    {
        $getValue = fn($key) => $entry['name_value_list'][$key]['value'] ?? null;

        return new self(
            id: $entry['id'],
            name: $getValue('name') ?? '',
            description: $getValue('description'),
            salesStage: $getValue('sales_stage'),
            amount: $getValue('amount') ? (float) $getValue('amount') : null,
            currency: $getValue('currency_id'),
            probability: $getValue('probability') ? (int) $getValue('probability') : null,
            expectedCloseDate: $getValue('date_closed'),
            accountId: $getValue('account_id'),
            accountName: $getValue('account_name'),
            assignedUserId: $getValue('assigned_user_id'),
            assignedUserName: $getValue('assigned_user_name'),
            dateEntered: $getValue('date_entered'),
            dateModified: $getValue('date_modified')
        );
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'description' => $this->description,
            'sales_stage' => $this->salesStage,
            'amount' => $this->amount,
            'currency' => $this->currency,
            'probability' => $this->probability,
            'expected_close_date' => $this->expectedCloseDate,
            'account_id' => $this->accountId,
            'account_name' => $this->accountName,
            'assigned_user_id' => $this->assignedUserId,
            'assigned_user_name' => $this->assignedUserName,
            'date_entered' => $this->dateEntered,
            'date_modified' => $this->dateModified
        ];
    }
}
