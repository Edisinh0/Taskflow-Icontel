<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\User;
use App\Models\CrmCase;
use App\Models\CaseClosureRequest;
use App\Policies\CaseClosureRequestPolicy;
use Illuminate\Foundation\Testing\RefreshDatabase;

class CaseClosureRequestPolicyTest extends TestCase
{
    use RefreshDatabase;

    private CaseClosureRequestPolicy $policy;

    protected function setUp(): void
    {
        parent::setUp();
        $this->policy = new CaseClosureRequestPolicy();
    }

    /**
     * Test viewAny: Only SAC or admin can view requests
     */
    public function test_view_any_returns_true_for_sac_user()
    {
        $sacUser = User::factory()->create([
            'role' => 'user',
            'department' => 'SAC',
        ]);

        $this->assertTrue($this->policy->viewAny($sacUser));
    }

    /**
     * Test viewAny: Admin can view requests
     */
    public function test_view_any_returns_true_for_admin()
    {
        $admin = User::factory()->create([
            'role' => 'admin',
        ]);

        $this->assertTrue($this->policy->viewAny($admin));
    }

    /**
     * Test viewAny: Regular user cannot view requests
     */
    public function test_view_any_returns_false_for_regular_user()
    {
        $user = User::factory()->create([
            'role' => 'user',
            'department' => 'Operations',
        ]);

        $this->assertFalse($this->policy->viewAny($user));
    }

    /**
     * Test view: Admin can view any closure request
     */
    public function test_view_returns_true_for_admin()
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $requester = User::factory()->create();
        $case = CrmCase::factory()->create();
        $closureRequest = CaseClosureRequest::factory()->create([
            'case_id' => $case->id,
            'requested_by_user_id' => $requester->id,
        ]);

        $this->assertTrue($this->policy->view($admin, $closureRequest));
    }

    /**
     * Test view: SAC user assigned to request can view
     */
    public function test_view_returns_true_for_sac_user_assigned()
    {
        $sacUser = User::factory()->create([
            'department' => 'SAC',
        ]);
        $requester = User::factory()->create();
        $case = CrmCase::factory()->create();
        $closureRequest = CaseClosureRequest::factory()->create([
            'case_id' => $case->id,
            'requested_by_user_id' => $requester->id,
            'assigned_to_user_id' => $sacUser->id,
        ]);

        $this->assertTrue($this->policy->view($sacUser, $closureRequest));
    }

    /**
     * Test view: SAC user NOT assigned cannot view other's requests
     */
    public function test_view_returns_false_for_sac_user_not_assigned()
    {
        $sacUser1 = User::factory()->create(['department' => 'SAC']);
        $sacUser2 = User::factory()->create(['department' => 'SAC']);
        $requester = User::factory()->create();
        $case = CrmCase::factory()->create();
        $closureRequest = CaseClosureRequest::factory()->create([
            'case_id' => $case->id,
            'requested_by_user_id' => $requester->id,
            'assigned_to_user_id' => $sacUser2->id,
        ]);

        $this->assertFalse($this->policy->view($sacUser1, $closureRequest));
    }

    /**
     * Test view: Requester can view their own request
     */
    public function test_view_returns_true_for_requester()
    {
        $requester = User::factory()->create();
        $case = CrmCase::factory()->create();
        $closureRequest = CaseClosureRequest::factory()->create([
            'case_id' => $case->id,
            'requested_by_user_id' => $requester->id,
        ]);

        $this->assertTrue($this->policy->view($requester, $closureRequest));
    }

    /**
     * Test create: Assigned user can request closure
     */
    public function test_create_returns_true_for_assigned_user()
    {
        $assignedUser = User::factory()->create(['sweetcrm_id' => 'crm-123']);
        $case = CrmCase::factory()->create([
            'sweetcrm_assigned_user_id' => 'crm-123',
        ]);

        $this->assertTrue($this->policy->create($assignedUser, $case));
    }

    /**
     * Test create: Creator can request closure
     */
    public function test_create_returns_true_for_creator()
    {
        $creator = User::factory()->create();
        $case = CrmCase::factory()->create([
            'created_by' => $creator->id,
        ]);

        $this->assertTrue($this->policy->create($creator, $case));
    }

    /**
     * Test create: Department head can request closure
     */
    public function test_create_returns_true_for_department_head()
    {
        $departmentHead = User::factory()->create([
            'role' => 'project_manager',
            'department' => 'Operations',
        ]);
        $case = CrmCase::factory()->create();

        $this->assertTrue($this->policy->create($departmentHead, $case));
    }

    /**
     * Test create: Regular user not involved cannot request closure
     */
    public function test_create_returns_false_for_uninvolved_user()
    {
        $user = User::factory()->create([
            'role' => 'user',
            'sweetcrm_id' => 'crm-different',
        ]);
        $creator = User::factory()->create();
        $assignedUser = User::factory()->create(['sweetcrm_id' => 'crm-assigned']);
        $case = CrmCase::factory()->create([
            'created_by' => $creator->id,
            'sweetcrm_assigned_user_id' => 'crm-assigned',
        ]);

        $this->assertFalse($this->policy->create($user, $case));
    }

    /**
     * Test approve: Admin can approve
     */
    public function test_approve_returns_true_for_admin()
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $requester = User::factory()->create();
        $case = CrmCase::factory()->create();
        $closureRequest = CaseClosureRequest::factory()->create([
            'case_id' => $case->id,
            'requested_by_user_id' => $requester->id,
        ]);

        $this->assertTrue($this->policy->approve($admin, $closureRequest));
    }

    /**
     * Test approve: SAC user assigned to request can approve
     */
    public function test_approve_returns_true_for_assigned_sac_user()
    {
        $sacUser = User::factory()->create(['department' => 'SAC']);
        $requester = User::factory()->create();
        $case = CrmCase::factory()->create();
        $closureRequest = CaseClosureRequest::factory()->create([
            'case_id' => $case->id,
            'requested_by_user_id' => $requester->id,
            'assigned_to_user_id' => $sacUser->id,
        ]);

        $this->assertTrue($this->policy->approve($sacUser, $closureRequest));
    }

    /**
     * Test approve: SAC user NOT assigned cannot approve
     */
    public function test_approve_returns_false_for_unassigned_sac_user()
    {
        $sacUser1 = User::factory()->create(['department' => 'SAC']);
        $sacUser2 = User::factory()->create(['department' => 'SAC']);
        $requester = User::factory()->create();
        $case = CrmCase::factory()->create();
        $closureRequest = CaseClosureRequest::factory()->create([
            'case_id' => $case->id,
            'requested_by_user_id' => $requester->id,
            'assigned_to_user_id' => $sacUser2->id,
        ]);

        $this->assertFalse($this->policy->approve($sacUser1, $closureRequest));
    }

    /**
     * Test approve: Non-SAC user cannot approve
     */
    public function test_approve_returns_false_for_non_sac_user()
    {
        $user = User::factory()->create([
            'role' => 'user',
            'department' => 'Operations',
        ]);
        $requester = User::factory()->create();
        $case = CrmCase::factory()->create();
        $closureRequest = CaseClosureRequest::factory()->create([
            'case_id' => $case->id,
            'requested_by_user_id' => $requester->id,
            'assigned_to_user_id' => $user->id,
        ]);

        $this->assertFalse($this->policy->approve($user, $closureRequest));
    }

    /**
     * Test reject: Same logic as approve
     */
    public function test_reject_returns_true_for_assigned_sac_user()
    {
        $sacUser = User::factory()->create(['department' => 'SAC']);
        $requester = User::factory()->create();
        $case = CrmCase::factory()->create();
        $closureRequest = CaseClosureRequest::factory()->create([
            'case_id' => $case->id,
            'requested_by_user_id' => $requester->id,
            'assigned_to_user_id' => $sacUser->id,
        ]);

        $this->assertTrue($this->policy->reject($sacUser, $closureRequest));
    }

    /**
     * Test reject: Admin can reject
     */
    public function test_reject_returns_true_for_admin()
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $requester = User::factory()->create();
        $case = CrmCase::factory()->create();
        $closureRequest = CaseClosureRequest::factory()->create([
            'case_id' => $case->id,
            'requested_by_user_id' => $requester->id,
        ]);

        $this->assertTrue($this->policy->reject($admin, $closureRequest));
    }

    /**
     * Test delete: Admin can delete
     */
    public function test_delete_returns_true_for_admin()
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $requester = User::factory()->create();
        $case = CrmCase::factory()->create();
        $closureRequest = CaseClosureRequest::factory()->create([
            'case_id' => $case->id,
            'requested_by_user_id' => $requester->id,
            'status' => 'pending',
        ]);

        $this->assertTrue($this->policy->delete($admin, $closureRequest));
    }

    /**
     * Test delete: Requester can delete pending request
     */
    public function test_delete_returns_true_for_requester_pending()
    {
        $requester = User::factory()->create();
        $case = CrmCase::factory()->create();
        $closureRequest = CaseClosureRequest::factory()->create([
            'case_id' => $case->id,
            'requested_by_user_id' => $requester->id,
            'status' => 'pending',
        ]);

        $this->assertTrue($this->policy->delete($requester, $closureRequest));
    }

    /**
     * Test delete: Requester cannot delete non-pending request
     */
    public function test_delete_returns_false_for_requester_non_pending()
    {
        $requester = User::factory()->create();
        $case = CrmCase::factory()->create();
        $closureRequest = CaseClosureRequest::factory()->create([
            'case_id' => $case->id,
            'requested_by_user_id' => $requester->id,
            'status' => 'approved',
        ]);

        $this->assertFalse($this->policy->delete($requester, $closureRequest));
    }

    /**
     * Test delete: Other users cannot delete
     */
    public function test_delete_returns_false_for_other_user()
    {
        $requester = User::factory()->create();
        $otherUser = User::factory()->create();
        $case = CrmCase::factory()->create();
        $closureRequest = CaseClosureRequest::factory()->create([
            'case_id' => $case->id,
            'requested_by_user_id' => $requester->id,
            'status' => 'pending',
        ]);

        $this->assertFalse($this->policy->delete($otherUser, $closureRequest));
    }
}
