<?php

namespace Tests\Feature\Api;

use Tests\TestCase;
use App\Models\User;
use App\Models\Client;
use App\Models\Industry;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

class ClientApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Crear industria de prueba
        Industry::factory()->create(['name' => 'Tecnología']);
    }

    public function test_user_can_list_clients()
    {
        // Arrange
        $user = User::factory()->create();
        Client::factory()->count(5)->create();

        Sanctum::actingAs($user);

        // Act
        $response = $this->getJson('/api/v1/clients');

        // Assert
        $response->assertOk()
            ->assertJsonCount(5, 'data');
    }

    public function test_user_can_view_single_client()
    {
        // Arrange
        $user = User::factory()->create();
        $client = Client::factory()->create(['name' => 'Test Client']);

        Sanctum::actingAs($user);

        // Act
        $response = $this->getJson("/api/v1/clients/{$client->id}");

        // Assert
        $response->assertOk()
            ->assertJson([
                'id' => $client->id,
                'name' => 'Test Client',
            ]);
    }

    public function test_admin_can_create_client()
    {
        // Arrange
        $admin = User::factory()->create(['role' => 'admin']);
        $industry = Industry::first();

        Sanctum::actingAs($admin);

        $clientData = [
            'name' => 'New Test Client',
            'industry_id' => $industry->id,
            'contact_email' => 'contact@testclient.com',
            'contact_phone' => '+1234567890',
            'address' => '123 Test Street',
        ];

        // Act
        $response = $this->postJson('/api/v1/clients', $clientData);

        // Assert
        $response->assertCreated()
            ->assertJson([
                'client' => [
                    'name' => 'New Test Client',
                    'contact_email' => 'contact@testclient.com',
                ],
            ]);

        $this->assertDatabaseHas('clients', [
            'name' => 'New Test Client',
            'contact_email' => 'contact@testclient.com',
        ]);
    }

    public function test_regular_user_cannot_create_client()
    {
        // Arrange
        $user = User::factory()->create(['role' => 'user']);
        $industry = Industry::first();

        Sanctum::actingAs($user);

        $clientData = [
            'name' => 'New Test Client',
            'industry_id' => $industry->id,
        ];

        // Act
        $response = $this->postJson('/api/v1/clients', $clientData);

        // Assert
        $response->assertForbidden();
    }

    public function test_admin_can_update_client()
    {
        // Arrange
        $admin = User::factory()->create(['role' => 'admin']);
        $client = Client::factory()->create(['name' => 'Original Name']);

        Sanctum::actingAs($admin);

        // Act
        $response = $this->putJson("/api/v1/clients/{$client->id}", [
            'name' => 'Updated Name',
        ]);

        // Assert
        $response->assertOk();
        $this->assertDatabaseHas('clients', [
            'id' => $client->id,
            'name' => 'Updated Name',
        ]);
    }

    public function test_admin_can_delete_client()
    {
        // Arrange
        $admin = User::factory()->create(['role' => 'admin']);
        $client = Client::factory()->create();

        Sanctum::actingAs($admin);

        // Act
        $response = $this->deleteJson("/api/v1/clients/{$client->id}");

        // Assert
        $response->assertOk();
        $this->assertSoftDeleted('clients', ['id' => $client->id]);
    }

    public function test_guest_cannot_access_clients()
    {
        // Act
        $response = $this->getJson('/api/v1/clients');

        // Assert
        $response->assertUnauthorized();
    }

    public function test_clients_can_be_filtered_by_industry()
    {
        // Arrange
        $user = User::factory()->create();
        $industry1 = Industry::factory()->create(['name' => 'Tech']);
        $industry2 = Industry::factory()->create(['name' => 'Finance']);

        Client::factory()->count(3)->create(['industry_id' => $industry1->id]);
        Client::factory()->count(2)->create(['industry_id' => $industry2->id]);

        Sanctum::actingAs($user);

        // Act
        $response = $this->getJson("/api/v1/clients?industry_id={$industry1->id}");

        // Assert
        $response->assertOk()
            ->assertJsonCount(3, 'data');
    }
}
