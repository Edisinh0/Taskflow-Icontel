<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

class UserTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test isAdmin() returns true for admin users
     */
    public function test_is_admin_returns_true_for_admin_role()
    {
        $admin = User::factory()->create([
            'role' => 'admin',
            'department' => 'SAC',
        ]);

        $this->assertTrue($admin->isAdmin());
    }

    /**
     * Test isAdmin() returns false for non-admin users
     */
    public function test_is_admin_returns_false_for_non_admin_role()
    {
        $user = User::factory()->create([
            'role' => 'user',
            'department' => 'SAC',
        ]);

        $this->assertFalse($user->isAdmin());
    }

    /**
     * Test isSACDepartment() returns true for SAC users
     */
    public function test_is_sac_department_returns_true_for_sac()
    {
        $sacUser = User::factory()->create([
            'department' => 'SAC',
        ]);

        $this->assertTrue($sacUser->isSACDepartment());
    }

    /**
     * Test isSACDepartment() returns false for non-SAC users
     */
    public function test_is_sac_department_returns_false_for_non_sac()
    {
        $user = User::factory()->create([
            'department' => 'Operations',
        ]);

        $this->assertFalse($user->isSACDepartment());
    }

    /**
     * Test canApproveClosures() returns true for SAC users
     */
    public function test_can_approve_closures_returns_true_for_sac_user()
    {
        $sacUser = User::factory()->create([
            'role' => 'user',
            'department' => 'SAC',
        ]);

        $this->assertTrue($sacUser->canApproveClosures());
    }

    /**
     * Test canApproveClosures() returns true for admin users
     */
    public function test_can_approve_closures_returns_true_for_admin()
    {
        $admin = User::factory()->create([
            'role' => 'admin',
            'department' => 'Operations',
        ]);

        $this->assertTrue($admin->canApproveClosures());
    }

    /**
     * Test canApproveClosures() returns false for regular users
     */
    public function test_can_approve_closures_returns_false_for_regular_user()
    {
        $user = User::factory()->create([
            'role' => 'user',
            'department' => 'Operations',
        ]);

        $this->assertFalse($user->canApproveClosures());
    }

    /**
     * Test isDepartmentHead() returns true for admin
     */
    public function test_is_department_head_returns_true_for_admin()
    {
        $admin = User::factory()->create([
            'role' => 'admin',
        ]);

        $this->assertTrue($admin->isDepartmentHead());
    }

    /**
     * Test isDepartmentHead() returns true for project_manager
     */
    public function test_is_department_head_returns_true_for_project_manager()
    {
        $pm = User::factory()->create([
            'role' => 'project_manager',
        ]);

        $this->assertTrue($pm->isDepartmentHead());
    }

    /**
     * Test isDepartmentHead() returns true for pm
     */
    public function test_is_department_head_returns_true_for_pm()
    {
        $pm = User::factory()->create([
            'role' => 'pm',
        ]);

        $this->assertTrue($pm->isDepartmentHead());
    }

    /**
     * Test isDepartmentHead() returns false for regular users
     */
    public function test_is_department_head_returns_false_for_regular_user()
    {
        $user = User::factory()->create([
            'role' => 'user',
        ]);

        $this->assertFalse($user->isDepartmentHead());
    }

    /**
     * Test getDepartmentHead() prioritizes admin role
     */
    public function test_get_department_head_prioritizes_admin_role()
    {
        // Create users in same department with different roles
        User::factory()->create([
            'name' => 'Project Manager',
            'role' => 'project_manager',
            'department' => 'SAC',
        ]);

        $admin = User::factory()->create([
            'name' => 'Admin User',
            'role' => 'admin',
            'department' => 'SAC',
        ]);

        User::factory()->create([
            'name' => 'PM User',
            'role' => 'pm',
            'department' => 'SAC',
        ]);

        $head = User::getDepartmentHead('SAC');

        $this->assertNotNull($head);
        $this->assertEquals($admin->id, $head->id);
        $this->assertEquals('admin', $head->role);
    }

    /**
     * Test getDepartmentHead() prioritizes project_manager over pm
     */
    public function test_get_department_head_prioritizes_project_manager_over_pm()
    {
        User::factory()->create([
            'name' => 'PM User',
            'role' => 'pm',
            'department' => 'SAC',
        ]);

        $projectManager = User::factory()->create([
            'name' => 'Project Manager',
            'role' => 'project_manager',
            'department' => 'SAC',
        ]);

        $head = User::getDepartmentHead('SAC');

        $this->assertNotNull($head);
        $this->assertEquals($projectManager->id, $head->id);
        $this->assertEquals('project_manager', $head->role);
    }

    /**
     * Test getDepartmentHead() returns pm when no admin or project_manager exists
     */
    public function test_get_department_head_returns_pm_when_no_higher_role()
    {
        $pm = User::factory()->create([
            'name' => 'PM User',
            'role' => 'pm',
            'department' => 'Operations',
        ]);

        $head = User::getDepartmentHead('Operations');

        $this->assertNotNull($head);
        $this->assertEquals($pm->id, $head->id);
        $this->assertEquals('pm', $head->role);
    }

    /**
     * Test getDepartmentHead() returns null when no head exists in department
     */
    public function test_get_department_head_returns_null_when_no_head_exists()
    {
        User::factory()->create([
            'role' => 'user',
            'department' => 'Marketing',
        ]);

        $head = User::getDepartmentHead('Marketing');

        $this->assertNull($head);
    }

    /**
     * Test getDepartmentHead() returns null for non-existent department
     */
    public function test_get_department_head_returns_null_for_non_existent_department()
    {
        $head = User::getDepartmentHead('NonExistentDept');

        $this->assertNull($head);
    }

    /**
     * Test getDepartmentHead() only returns heads from specified department
     */
    public function test_get_department_head_only_returns_from_specified_department()
    {
        // Create admin in SAC
        $sacAdmin = User::factory()->create([
            'name' => 'SAC Admin',
            'role' => 'admin',
            'department' => 'SAC',
        ]);

        // Create admin in different department
        User::factory()->create([
            'name' => 'Operations Admin',
            'role' => 'admin',
            'department' => 'Operations',
        ]);

        $head = User::getDepartmentHead('SAC');

        $this->assertEquals($sacAdmin->id, $head->id);
        $this->assertEquals('SAC', $head->department);
    }
}
