<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\DashboardAccessService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class StudentRoleTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_student_role_exists_without_permissions(): void
    {
        $role = Role::findByName('STUDENT', 'web');

        $this->assertCount(0, $role->permissions);
    }

    public function test_students_land_on_the_student_portal_and_cannot_open_admin_pages(): void
    {
        $student = User::factory()->create();
        $student->assignRole('STUDENT');

        $this->assertSame('student.dashboard', app(DashboardAccessService::class)->defaultRouteFor($student));

        $this->actingAs($student)->get('/roles')->assertForbidden();
        $this->actingAs($student)->get('/user-management')->assertForbidden();
    }
}
