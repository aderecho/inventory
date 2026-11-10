<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class AuditLogsAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_view_audit_logs_permission_can_open_the_page(): void
    {
        $user = User::factory()->create();
        $user->givePermissionTo(Permission::findOrCreate('view audit_logs', 'web'));

        $this->actingAs($user)
            ->get(route('audit_logs.index'))
            ->assertOk();
    }

    public function test_view_inventory_alone_cannot_open_the_audit_logs_page(): void
    {
        $user = User::factory()->create();
        $user->givePermissionTo(Permission::findOrCreate('view inventory', 'web'));

        $this->actingAs($user)
            ->get(route('audit_logs.index'))
            ->assertForbidden();
    }
}
