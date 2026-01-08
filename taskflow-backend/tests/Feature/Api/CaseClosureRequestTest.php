<?php

namespace Tests\Feature\Api;

use Tests\TestCase;
use App\Models\User;
use App\Models\CrmCase;
use App\Models\CaseClosureRequest;
use Illuminate\Foundation\Testing\RefreshDatabase;

class CaseClosureRequestTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test: Assigned user can request case closure
     */
    public function test_assigned_user_can_request_closure()
    {
        $assignedUser = User::factory()->create(['sweetcrm_id' => 'crm-123']);
        $case = CrmCase::factory()->create([
            'sweetcrm_assigned_user_id' => 'crm-123',
            'closure_status' => 'open',
        ]);

        $response = $this->actingAs($assignedUser)
            ->postJson("/api/v1/cases/{$case->id}/request-closure", [
                'reason' => 'Case is complete',
                'completion_percentage' => 100,
            ]);

        $response->assertCreated()
            ->assertJson([
                'success' => true,
                'message' => 'Solicitud de cierre enviada a Servicio al Cliente',
            ]);

        $this->assertDatabaseHas('case_closure_requests', [
            'case_id' => $case->id,
            'requested_by_user_id' => $assignedUser->id,
            'status' => 'pending',
        ]);

        $case->refresh();
        $this->assertEquals('closure_requested', $case->closure_status);
    }

    /**
     * Test: Case creator can request closure
     */
    public function test_creator_can_request_closure()
    {
        $creator = User::factory()->create();
        $case = CrmCase::factory()->create([
            'created_by' => $creator->id,
            'closure_status' => 'open',
        ]);

        $response = $this->actingAs($creator)
            ->postJson("/api/v1/cases/{$case->id}/request-closure", [
                'reason' => 'Task completed',
                'completion_percentage' => 100,
            ]);

        $response->assertCreated()
            ->assertJson(['success' => true]);

        $this->assertDatabaseHas('case_closure_requests', [
            'case_id' => $case->id,
            'requested_by_user_id' => $creator->id,
        ]);
    }

    /**
     * Test: Department head can request closure
     */
    public function test_department_head_can_request_closure()
    {
        $departmentHead = User::factory()->create([
            'role' => 'project_manager',
            'department' => 'Operations',
        ]);
        $case = CrmCase::factory()->create([
            'closure_status' => 'open',
        ]);

        $response = $this->actingAs($departmentHead)
            ->postJson("/api/v1/cases/{$case->id}/request-closure", [
                'reason' => 'Area manager approval',
                'completion_percentage' => 100,
            ]);

        $response->assertCreated()
            ->assertJson(['success' => true]);
    }

    /**
     * Test: Regular user not involved cannot request closure
     */
    public function test_unauthorized_user_cannot_request_closure()
    {
        $unauthorizedUser = User::factory()->create([
            'role' => 'user',
            'department' => 'Operations',
            'sweetcrm_id' => 'crm-unauthorized',
        ]);
        $assignedUser = User::factory()->create([
            'sweetcrm_id' => 'crm-assigned',
        ]);
        $creator = User::factory()->create();

        $case = CrmCase::factory()->create([
            'closure_status' => 'open',
            'created_by' => $creator->id,
            'sweetcrm_assigned_user_id' => 'crm-assigned',
        ]);

        $response = $this->actingAs($unauthorizedUser)
            ->postJson("/api/v1/cases/{$case->id}/request-closure", [
                'reason' => 'Unauthorized attempt',
                'completion_percentage' => 100,
            ]);

        $response->assertForbidden()
            ->assertJson([
                'success' => false,
                'message' => 'Solo el usuario asignado, creador o jefe de departamento pueden solicitar cierre',
            ]);

        $this->assertDatabaseMissing('case_closure_requests', [
            'case_id' => $case->id,
            'requested_by_user_id' => $unauthorizedUser->id,
        ]);
    }

    /**
     * Test: Cannot request closure for case with pending request
     */
    public function test_cannot_request_closure_with_pending_request()
    {
        $user = User::factory()->create();
        $case = CrmCase::factory()->create([
            'created_by' => $user->id,
            'closure_status' => 'closure_requested',
        ]);

        // Create existing pending request
        CaseClosureRequest::factory()->create([
            'case_id' => $case->id,
            'status' => 'pending',
        ]);

        $response = $this->actingAs($user)
            ->postJson("/api/v1/cases/{$case->id}/request-closure", [
                'reason' => 'Another request',
                'completion_percentage' => 100,
            ]);

        $response->assertUnprocessable()
            ->assertJson([
                'success' => false,
                'message' => 'Este caso ya tiene una solicitud de cierre pendiente o está cerrado',
            ]);
    }

    /**
     * Test: Closure request auto-assigns to SAC department head
     */
    public function test_closure_request_assigns_to_sac_head()
    {
        $requester = User::factory()->create();
        $sacHead = User::factory()->create([
            'role' => 'admin',
            'department' => 'SAC',
        ]);
        $case = CrmCase::factory()->create([
            'created_by' => $requester->id,
            'closure_status' => 'open',
        ]);

        $response = $this->actingAs($requester)
            ->postJson("/api/v1/cases/{$case->id}/request-closure", [
                'reason' => 'Complete',
                'completion_percentage' => 100,
            ]);

        $response->assertCreated();

        $closureRequest = CaseClosureRequest::where('case_id', $case->id)->first();
        $this->assertNotNull($closureRequest);
        $this->assertEquals($sacHead->id, $closureRequest->assigned_to_user_id);
    }

    /**
     * Test: SAC user can approve closure request
     */
    public function test_sac_user_can_approve_closure()
    {
        $requester = User::factory()->create();
        $sacUser = User::factory()->create([
            'department' => 'SAC',
        ]);
        $case = CrmCase::factory()->create([
            'created_by' => $requester->id,
            'closure_status' => 'closure_requested',
        ]);
        $closureRequest = CaseClosureRequest::factory()->create([
            'case_id' => $case->id,
            'requested_by_user_id' => $requester->id,
            'assigned_to_user_id' => $sacUser->id,
            'status' => 'pending',
        ]);

        $response = $this->actingAs($sacUser)
            ->postJson("/api/v1/closure-requests/{$closureRequest->id}/approve");

        $response->assertOk()
            ->assertJson([
                'success' => true,
                'message' => 'Caso cerrado exitosamente',
            ]);

        $closureRequest->refresh();
        $this->assertEquals('approved', $closureRequest->status);
        $this->assertEquals($sacUser->id, $closureRequest->reviewed_by_user_id);
        $this->assertNotNull($closureRequest->reviewed_at);

        $case->refresh();
        $this->assertEquals('closed', $case->closure_status);
        $this->assertEquals('Closed', $case->status);
        $this->assertEquals($sacUser->id, $case->closure_approved_by_id);
        $this->assertNotNull($case->closure_approved_at);
    }

    /**
     * Test: Only assigned SAC user can approve
     */
    public function test_unassigned_sac_user_cannot_approve()
    {
        $requester = User::factory()->create();
        $assignedSacUser = User::factory()->create(['department' => 'SAC']);
        $unassignedSacUser = User::factory()->create(['department' => 'SAC']);

        $case = CrmCase::factory()->create([
            'created_by' => $requester->id,
            'closure_status' => 'closure_requested',
        ]);
        $closureRequest = CaseClosureRequest::factory()->create([
            'case_id' => $case->id,
            'requested_by_user_id' => $requester->id,
            'assigned_to_user_id' => $assignedSacUser->id,
            'status' => 'pending',
        ]);

        $response = $this->actingAs($unassignedSacUser)
            ->postJson("/api/v1/closure-requests/{$closureRequest->id}/approve");

        $response->assertForbidden()
            ->assertJson([
                'success' => false,
                'message' => 'Solo puedes aprobar solicitudes asignadas a ti',
            ]);

        $closureRequest->refresh();
        $this->assertEquals('pending', $closureRequest->status);
    }

    /**
     * Test: Non-SAC user cannot approve
     */
    public function test_non_sac_user_cannot_approve()
    {
        $requester = User::factory()->create();
        $operationsUser = User::factory()->create(['department' => 'Operations']);

        $case = CrmCase::factory()->create([
            'created_by' => $requester->id,
            'closure_status' => 'closure_requested',
        ]);
        $closureRequest = CaseClosureRequest::factory()->create([
            'case_id' => $case->id,
            'requested_by_user_id' => $requester->id,
            'assigned_to_user_id' => $operationsUser->id,
            'status' => 'pending',
        ]);

        $response = $this->actingAs($operationsUser)
            ->postJson("/api/v1/closure-requests/{$closureRequest->id}/approve");

        $response->assertForbidden()
            ->assertJson([
                'success' => false,
                'message' => 'Solo usuarios de SAC pueden aprobar solicitudes de cierre',
            ]);
    }

    /**
     * Test: SAC user can reject closure request
     */
    public function test_sac_user_can_reject_closure()
    {
        $requester = User::factory()->create();
        $sacUser = User::factory()->create(['department' => 'SAC']);

        $case = CrmCase::factory()->create([
            'created_by' => $requester->id,
            'closure_status' => 'closure_requested',
        ]);
        $closureRequest = CaseClosureRequest::factory()->create([
            'case_id' => $case->id,
            'requested_by_user_id' => $requester->id,
            'assigned_to_user_id' => $sacUser->id,
            'status' => 'pending',
        ]);

        $response = $this->actingAs($sacUser)
            ->postJson("/api/v1/closure-requests/{$closureRequest->id}/reject", [
                'rejection_reason' => 'Incomplete documentation',
            ]);

        $response->assertOk()
            ->assertJson([
                'success' => true,
                'message' => 'Solicitud de cierre rechazada',
            ]);

        $closureRequest->refresh();
        $this->assertEquals('rejected', $closureRequest->status);
        $this->assertEquals('Incomplete documentation', $closureRequest->rejection_reason);
        $this->assertEquals($sacUser->id, $closureRequest->reviewed_by_user_id);

        $case->refresh();
        $this->assertEquals('open', $case->closure_status);
        $this->assertNull($case->closure_requested_by_id);
    }

    /**
     * Test: Cannot approve already processed request
     */
    public function test_cannot_approve_already_processed_request()
    {
        $requester = User::factory()->create();
        $sacUser = User::factory()->create(['department' => 'SAC']);

        $case = CrmCase::factory()->create([
            'created_by' => $requester->id,
        ]);
        $closureRequest = CaseClosureRequest::factory()->create([
            'case_id' => $case->id,
            'requested_by_user_id' => $requester->id,
            'assigned_to_user_id' => $sacUser->id,
            'status' => 'approved',
        ]);

        $response = $this->actingAs($sacUser)
            ->postJson("/api/v1/closure-requests/{$closureRequest->id}/approve");

        $response->assertUnprocessable()
            ->assertJson([
                'success' => false,
                'message' => 'Esta solicitud ya fue procesada',
            ]);
    }

    /**
     * Test: Get case closure status
     */
    public function test_get_case_closure_status()
    {
        $requester = User::factory()->create();
        $case = CrmCase::factory()->create([
            'created_by' => $requester->id,
            'closure_status' => 'closure_requested',
        ]);
        $closureRequest = CaseClosureRequest::factory()->create([
            'case_id' => $case->id,
            'requested_by_user_id' => $requester->id,
            'status' => 'pending',
        ]);

        $response = $this->actingAs($requester)
            ->getJson("/api/v1/cases/{$case->id}/closure-request");

        $response->assertOk()
            ->assertJson([
                'success' => true,
                'closure_status' => 'closure_requested',
            ])
            ->assertJsonStructure([
                'closure_request' => [
                    'id',
                    'case_id',
                    'status',
                ],
            ]);
    }

    /**
     * Test: Pagination in closure requests list
     */
    public function test_closure_requests_list_is_paginated()
    {
        $sacHead = User::factory()->create([
            'role' => 'admin',
            'department' => 'SAC',
        ]);

        // Create multiple requests
        CaseClosureRequest::factory(25)->create([
            'assigned_to_user_id' => $sacHead->id,
        ]);

        $response = $this->actingAs($sacHead)
            ->getJson('/api/v1/closure-requests');

        $response->assertOk()
            ->assertJson(['success' => true])
            ->assertJsonStructure([
                'data' => [],
                'links',
                'meta',
            ]);

        // Should have 20 items per page
        $this->assertCount(20, $response->json('data'));
    }

    /**
     * Test: Filter closure requests by status
     */
    public function test_filter_closure_requests_by_status()
    {
        $sacHead = User::factory()->create([
            'role' => 'admin',
            'department' => 'SAC',
        ]);

        CaseClosureRequest::factory(5)->create([
            'assigned_to_user_id' => $sacHead->id,
            'status' => 'pending',
        ]);

        CaseClosureRequest::factory(3)->create([
            'assigned_to_user_id' => $sacHead->id,
            'status' => 'approved',
        ]);

        $response = $this->actingAs($sacHead)
            ->getJson('/api/v1/closure-requests?status=pending');

        $response->assertOk()
            ->assertJson(['success' => true]);

        $this->assertCount(5, $response->json('data'));
    }

    /**
     * Test: Admin can see all closure requests
     */
    public function test_admin_can_see_all_closure_requests()
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $sacUser1 = User::factory()->create(['department' => 'SAC']);
        $sacUser2 = User::factory()->create(['department' => 'SAC']);

        $requester1 = User::factory()->create();
        $requester2 = User::factory()->create();

        CaseClosureRequest::factory()->create([
            'assigned_to_user_id' => $sacUser1->id,
            'requested_by_user_id' => $requester1->id,
        ]);

        CaseClosureRequest::factory()->create([
            'assigned_to_user_id' => $sacUser2->id,
            'requested_by_user_id' => $requester2->id,
        ]);

        $response = $this->actingAs($admin)
            ->getJson('/api/v1/closure-requests');

        $response->assertOk()
            ->assertJson(['success' => true]);

        // Admin should see both requests
        $this->assertCount(2, $response->json('data'));
    }

    /**
     * Test: Non-SAC user cannot view closure requests
     */
    public function test_non_sac_user_cannot_view_closure_requests()
    {
        $operationsUser = User::factory()->create(['department' => 'Operations']);

        $response = $this->actingAs($operationsUser)
            ->getJson('/api/v1/closure-requests');

        $response->assertForbidden()
            ->assertJson([
                'success' => false,
                'message' => 'No tienes permiso para ver solicitudes de cierre',
            ]);
    }

    /**
     * Test: Complete closure workflow - request to approval
     */
    public function test_complete_closure_workflow_request_to_approval()
    {
        // Setup
        $requester = User::factory()->create();
        $sacHead = User::factory()->create([
            'role' => 'admin',
            'department' => 'SAC',
        ]);
        $case = CrmCase::factory()->create([
            'created_by' => $requester->id,
            'closure_status' => 'open',
            'status' => 'Open',
        ]);

        // Step 1: Request closure
        $requestResponse = $this->actingAs($requester)
            ->postJson("/api/v1/cases/{$case->id}/request-closure", [
                'reason' => 'Case completed',
                'completion_percentage' => 100,
            ]);

        $requestResponse->assertCreated();
        $closureRequest = CaseClosureRequest::where('case_id', $case->id)->first();
        $this->assertNotNull($closureRequest);

        // Verify case status
        $case->refresh();
        $this->assertEquals('closure_requested', $case->closure_status);

        // Step 2: SAC approves
        $approveResponse = $this->actingAs($sacHead)
            ->postJson("/api/v1/closure-requests/{$closureRequest->id}/approve");

        $approveResponse->assertOk();

        // Verify final state
        $closureRequest->refresh();
        $this->assertEquals('approved', $closureRequest->status);
        $this->assertEquals($sacHead->id, $closureRequest->reviewed_by_user_id);

        $case->refresh();
        $this->assertEquals('closed', $case->closure_status);
        $this->assertEquals('Closed', $case->status);
        $this->assertEquals($sacHead->id, $case->closure_approved_by_id);
    }

    /**
     * Test: Complete closure workflow - request to rejection
     */
    public function test_complete_closure_workflow_request_to_rejection()
    {
        // Setup
        $requester = User::factory()->create();
        $sacHead = User::factory()->create([
            'role' => 'admin',
            'department' => 'SAC',
        ]);
        $case = CrmCase::factory()->create([
            'created_by' => $requester->id,
            'closure_status' => 'open',
            'status' => 'Open',
        ]);

        // Step 1: Request closure
        $requestResponse = $this->actingAs($requester)
            ->postJson("/api/v1/cases/{$case->id}/request-closure", [
                'reason' => 'Case completed',
                'completion_percentage' => 80,
            ]);

        $requestResponse->assertCreated();
        $closureRequest = CaseClosureRequest::where('case_id', $case->id)->first();

        // Step 2: SAC rejects
        $rejectResponse = $this->actingAs($sacHead)
            ->postJson("/api/v1/closure-requests/{$closureRequest->id}/reject", [
                'rejection_reason' => 'Completion percentage too low. Needs 100% completion.',
            ]);

        $rejectResponse->assertOk();

        // Verify final state
        $closureRequest->refresh();
        $this->assertEquals('rejected', $closureRequest->status);
        $this->assertEquals('Completion percentage too low. Needs 100% completion.', $closureRequest->rejection_reason);

        $case->refresh();
        $this->assertEquals('open', $case->closure_status);
        $this->assertNull($case->closure_requested_by_id);
    }
}
