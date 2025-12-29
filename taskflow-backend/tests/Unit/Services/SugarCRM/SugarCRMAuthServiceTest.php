<?php

namespace Tests\Unit\Services\SugarCRM;

use Tests\TestCase;
use App\Services\SugarCRM\SugarCRMAuthService;
use App\Adapters\SugarCRM\SugarCRMApiAdapter;
use App\DTOs\SugarCRM\SugarCRMSessionDTO;
use App\DTOs\SugarCRM\SugarCRMUserDTO;
use Mockery;

class SugarCRMAuthServiceTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_authenticate_success()
    {
        // Arrange
        $adapterMock = Mockery::mock(SugarCRMApiAdapter::class);
        $sessionDTO = new SugarCRMSessionDTO(
            sessionId: 'test-session-id',
            userId: 'user-123',
            username: 'testuser'
        );

        $userDTO = new SugarCRMUserDTO(
            id: 'user-123',
            username: 'testuser',
            firstName: 'Test',
            lastName: 'User',
            fullName: 'Test User',
            email: 'test@example.com',
            phone: null,
            title: null,
            department: null,
            status: 'Active',
            userType: 'regular',
            isAdmin: false
        );

        $adapterMock->shouldReceive('authenticate')
            ->once()
            ->with('testuser', 'password123')
            ->andReturn($sessionDTO);

        $adapterMock->shouldReceive('getUser')
            ->once()
            ->with('test-session-id', 'user-123')
            ->andReturn($userDTO);

        $service = new SugarCRMAuthService($adapterMock);

        // Act
        $result = $service->authenticate('testuser', 'password123');

        // Assert
        $this->assertTrue($result['success']);
        $this->assertEquals('test-session-id', $result['data']['session_id']);
        $this->assertEquals('Test User', $result['data']['user']['name']);
    }

    public function test_authenticate_invalid_credentials()
    {
        // Arrange
        $adapterMock = Mockery::mock(SugarCRMApiAdapter::class);
        $adapterMock->shouldReceive('authenticate')
            ->once()
            ->with('testuser', 'wrongpassword')
            ->andReturn(null);

        $service = new SugarCRMAuthService($adapterMock);

        // Act
        $result = $service->authenticate('testuser', 'wrongpassword');

        // Assert
        $this->assertFalse($result['success']);
        $this->assertEquals('Credenciales inválidas', $result['message']);
    }

    public function test_get_session_id_success()
    {
        // Arrange
        $adapterMock = Mockery::mock(SugarCRMApiAdapter::class);
        $sessionDTO = new SugarCRMSessionDTO(
            sessionId: 'test-session-id',
            userId: 'user-123'
        );

        $adapterMock->shouldReceive('authenticate')
            ->once()
            ->andReturn($sessionDTO);

        $adapterMock->shouldReceive('getUser')
            ->once()
            ->andReturn(null);

        $service = new SugarCRMAuthService($adapterMock);

        // Act
        $sessionId = $service->getSessionId('testuser', 'password123');

        // Assert
        $this->assertEquals('test-session-id', $sessionId);
    }

    public function test_get_session_id_failure()
    {
        // Arrange
        $adapterMock = Mockery::mock(SugarCRMApiAdapter::class);
        $adapterMock->shouldReceive('authenticate')
            ->once()
            ->andReturn(null);

        $service = new SugarCRMAuthService($adapterMock);

        // Act
        $sessionId = $service->getSessionId('testuser', 'wrongpassword');

        // Assert
        $this->assertNull($sessionId);
    }

    public function test_validate_session_valid()
    {
        // Arrange
        $adapterMock = Mockery::mock(SugarCRMApiAdapter::class);
        $adapterMock->shouldReceive('getClients')
            ->once()
            ->with('valid-session', 1, 0)
            ->andReturn([]);

        $service = new SugarCRMAuthService($adapterMock);

        // Act
        $isValid = $service->validateSession('valid-session');

        // Assert
        $this->assertTrue($isValid);
    }
}
