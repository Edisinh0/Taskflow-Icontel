<?php

namespace Tests\Feature\Api;

use Tests\TestCase;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;

class AuthApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_login_with_valid_credentials()
    {
        // Arrange
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => Hash::make('password123'),
        ]);

        // Act
        $response = $this->postJson('/api/v1/auth/login', [
            'email' => 'test@example.com',
            'password' => 'password123',
        ]);

        // Assert
        $response->assertOk()
            ->assertJsonStructure([
                'message',
                'user' => ['id', 'name', 'email', 'role'],
                'token',
            ]);
    }

    public function test_user_cannot_login_with_invalid_credentials()
    {
        // Arrange
        User::factory()->create([
            'email' => 'test@example.com',
            'password' => Hash::make('password123'),
        ]);

        // Act
        $response = $this->postJson('/api/v1/auth/login', [
            'email' => 'test@example.com',
            'password' => 'wrongpassword',
        ]);

        // Assert
        $response->assertUnauthorized()
            ->assertJson([
                'message' => 'Credenciales inválidas',
            ]);
    }

    public function test_login_requires_email_and_password()
    {
        // Act
        $response = $this->postJson('/api/v1/auth/login', []);

        // Assert
        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email', 'password']);
    }

    public function test_user_can_logout()
    {
        // Arrange
        $user = User::factory()->create();
        $token = $user->createToken('test-token')->plainTextToken;

        // Act
        $response = $this->withHeader('Authorization', "Bearer $token")
            ->postJson('/api/v1/auth/logout');

        // Assert
        $response->assertOk()
            ->assertJson([
                'message' => 'Logout exitoso',
            ]);

        // Verificar que el token fue revocado
        $this->assertEquals(0, $user->tokens()->count());
    }

    public function test_authenticated_user_can_get_profile()
    {
        // Arrange
        $user = User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);
        $token = $user->createToken('test-token')->plainTextToken;

        // Act
        $response = $this->withHeader('Authorization', "Bearer $token")
            ->getJson('/api/v1/auth/user');

        // Assert
        $response->assertOk()
            ->assertJson([
                'id' => $user->id,
                'name' => 'Test User',
                'email' => 'test@example.com',
            ]);
    }

    public function test_guest_cannot_get_profile()
    {
        // Act
        $response = $this->getJson('/api/v1/auth/user');

        // Assert
        $response->assertUnauthorized();
    }
}
