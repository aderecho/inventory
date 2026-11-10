<?php

namespace Tests\Feature;

use App\Models\AcknowledgementReceipt;
use App\Models\ApiClient;
use App\Models\AssetCondition;
use App\Models\AssetInspection;
use App\Models\InventoryItem;
use App\Models\ItemHistoryLocation;
use App\Models\User;
use App\Services\RolePermissionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Spatie\Activitylog\Models\Activity;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class AuditLogsTest extends TestCase
{
    use RefreshDatabase;

    public function test_audit_logs_can_be_filtered_by_module_event_user_and_date(): void
    {
        $admin = $this->auditViewer();
        $other = User::factory()->create();
        $today = now()->toDateString();

        Activity::create([
            'log_name' => 'inventory',
            'description' => 'Item created',
            'event' => 'created',
            'causer_type' => User::class,
            'causer_id' => $admin->id,
            'properties' => ['attributes' => ['item_name' => 'Chair'], 'old' => []],
            'created_at' => now(),
        ]);

        Activity::create([
            'log_name' => 'supplier',
            'description' => 'Supplier created',
            'event' => 'created',
            'causer_type' => User::class,
            'causer_id' => $other->id,
            'properties' => ['attributes' => ['supplier_name' => 'Example'], 'old' => []],
            'created_at' => now()->subDay(),
        ]);

        $this->actingAs($admin)
            ->get(route('audit_logs.index', [
                'log_name' => 'inventory',
                'event' => 'created',
                'user_id' => $admin->id,
                'date_from' => $today,
                'date_to' => $today,
            ]))
            ->assertInertia(fn (Assert $page) => $page
                ->component('AuditLogs')
                ->has('activities.data', 1)
                ->where('activities.data.0.log_name', 'inventory')
                ->where('filters.log_name', 'inventory')
                ->where('filters.event', 'created')
                ->where('filters.user_id', (string) $admin->id)
            );
    }

    public function test_inspection_receipt_location_and_api_client_changes_are_logged(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $item = InventoryItem::factory()->create();
        $condition = AssetCondition::create([
            'condition_name' => 'Good',
            'disposition' => AssetCondition::DISPOSITION_SERVICEABLE,
        ]);

        AssetInspection::create([
            'inventory_item_id' => $item->id,
            'asset_condition_id' => $condition->id,
            'inspected_by' => $user->id,
            'inspection_date' => now()->toDateString(),
            'remarks' => 'Initial inspection',
        ]);
        AcknowledgementReceipt::factory()->create(['created_by' => $user->id]);
        ItemHistoryLocation::create([
            'inventory_item_id' => $item->id,
            'room_id' => 7,
        ]);
        ApiClient::create([
            'name' => 'Test Integration',
            'api_key' => hash('sha256', 'test-key'),
            'user_id' => $user->id,
            'is_active' => true,
        ]);

        $this->assertTrue(Activity::where('log_name', 'asset_condition')->exists());
        $this->assertTrue(Activity::where('log_name', 'inspection')->exists());
        $this->assertTrue(Activity::where('log_name', 'receipt')->exists());
        $this->assertTrue(Activity::where('log_name', 'location')->exists());
        $this->assertTrue(Activity::where('log_name', 'api_client')->exists());
    }

    public function test_role_permission_and_user_permission_changes_are_logged(): void
    {
        $actor = User::factory()->create();
        $this->actingAs($actor);

        $service = app(RolePermissionService::class);
        $permission = $service->createPermission('manage test data');
        $service->createRole('TEST-MANAGER', [$permission->name]);
        $service->updateUserPermissions($actor, [$permission->name], []);

        $this->assertTrue(Activity::where('log_name', 'permission')->exists());
        $this->assertTrue(Activity::where('log_name', 'role')->exists());
        $this->assertTrue(Activity::where('log_name', 'user_permission')->exists());
    }

    private function auditViewer(): User
    {
        $user = User::factory()->create();
        $user->givePermissionTo(Permission::findOrCreate('view audit_logs', 'web'));

        return $user;
    }
}
