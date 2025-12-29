<?php

namespace Tests\Unit\DTOs;

use Tests\TestCase;
use App\DTOs\SugarCRM\SugarCRMClientDTO;

class SugarCRMClientDTOTest extends TestCase
{
    public function test_from_sugar_crm_response()
    {
        // Arrange
        $sugarData = [
            'id' => 'client-123',
            'name_value_list' => [
                'name' => ['value' => 'Test Company'],
                'email1' => ['value' => 'contact@test.com'],
                'phone_office' => ['value' => '+1234567890'],
                'industry' => ['value' => 'Technology'],
                'account_type' => ['value' => 'Customer'],
                'estatusfinanciero_c' => ['value' => 'Activo'],
                'description' => ['value' => 'Test description'],
                'assigned_user_id' => ['value' => 'user-456'],
                'billing_address_street' => ['value' => '123 Main St'],
                'billing_address_city' => ['value' => 'Test City'],
                'billing_address_country' => ['value' => 'Test Country'],
                'date_entered' => ['value' => '2025-01-01 10:00:00'],
                'date_modified' => ['value' => '2025-01-10 15:30:00'],
            ],
        ];

        // Act
        $dto = SugarCRMClientDTO::fromSugarCRMResponse($sugarData);

        // Assert
        $this->assertEquals('client-123', $dto->id);
        $this->assertEquals('Test Company', $dto->name);
        $this->assertEquals('contact@test.com', $dto->email);
        $this->assertEquals('+1234567890', $dto->phone);
        $this->assertEquals('Technology', $dto->industry);
        $this->assertEquals('Customer', $dto->accountType);
        $this->assertEquals('Activo', $dto->financialStatus);
        $this->assertEquals('user-456', $dto->assignedUserId);
        $this->assertStringContainsString('123 Main St', $dto->address);
        $this->assertStringContainsString('Test City', $dto->address);
    }

    public function test_to_client_array()
    {
        // Arrange
        $dto = new SugarCRMClientDTO(
            id: 'client-123',
            name: 'Test Company',
            email: 'contact@test.com',
            phone: '+1234567890',
            address: '123 Main St, Test City',
            industry: 'Technology',
            accountType: 'Customer',
            financialStatus: 'Activo',
            description: 'Test description',
            assignedUserId: 'user-456',
            dateEntered: null,
            dateModified: null
        );

        // Act
        $clientArray = $dto->toClientArray(10);

        // Assert
        $this->assertEquals('Test Company', $clientArray['name']);
        $this->assertEquals('contact@test.com', $clientArray['contact_email']);
        $this->assertEquals('+1234567890', $clientArray['contact_phone']);
        $this->assertEquals('123 Main St, Test City', $clientArray['address']);
        $this->assertEquals(10, $clientArray['industry_id']);
        $this->assertEquals('client-123', $clientArray['sweetcrm_id']);
        $this->assertEquals('user-456', $clientArray['sweetcrm_assigned_user_id']);
        $this->assertEquals('active', $clientArray['status']);
    }

    public function test_financial_status_mapping()
    {
        // Test Activo -> active
        $dto1 = new SugarCRMClientDTO(
            id: '1',
            name: 'Company 1',
            email: null,
            phone: null,
            address: null,
            industry: null,
            accountType: null,
            financialStatus: 'Activo',
            description: null,
            assignedUserId: null,
            dateEntered: null,
            dateModified: null
        );
        $this->assertEquals('active', $dto1->toClientArray()['status']);

        // Test Baja -> inactive
        $dto2 = new SugarCRMClientDTO(
            id: '2',
            name: 'Company 2',
            email: null,
            phone: null,
            address: null,
            industry: null,
            accountType: null,
            financialStatus: 'Baja',
            description: null,
            assignedUserId: null,
            dateEntered: null,
            dateModified: null
        );
        $this->assertEquals('inactive', $dto2->toClientArray()['status']);

        // Test default -> active
        $dto3 = new SugarCRMClientDTO(
            id: '3',
            name: 'Company 3',
            email: null,
            phone: null,
            address: null,
            industry: null,
            accountType: null,
            financialStatus: 'Unknown',
            description: null,
            assignedUserId: null,
            dateEntered: null,
            dateModified: null
        );
        $this->assertEquals('active', $dto3->toClientArray()['status']);
    }
}
