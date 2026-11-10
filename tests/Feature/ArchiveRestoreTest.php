<?php

namespace Tests\Feature;

use App\Models\InventoryItem;
use App\Models\ItemClassification;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class ArchiveRestoreTest extends TestCase
{
    use RefreshDatabase;

    public function test_item_batch_restore_reports_an_error_when_every_item_conflicts(): void
    {
        InventoryItem::factory()->create(['property_number' => 'DUP-001']);
        $archived = InventoryItem::factory()->create(['property_number' => 'DUP-001']);
        $archived->delete();

        $this->actingAs($this->userWith('restore archive_item'))
            ->patch(route('items.batch-restore'), ['ids' => [$archived->id]])
            ->assertSessionHas('error')
            ->assertSessionMissing('success');

        $this->assertSoftDeleted($archived);
    }

    public function test_supplier_batch_restore_reports_an_error_when_nothing_is_archived(): void
    {
        $supplier = Supplier::factory()->create();

        $this->actingAs($this->userWith('restore archive_supplier'))
            ->patch(route('suppliers.batch-restore'), ['ids' => [$supplier->id]])
            ->assertSessionHas('error')
            ->assertSessionMissing('success');
    }

    public function test_category_batch_restore_reports_an_error_when_nothing_is_archived(): void
    {
        $category = ItemClassification::factory()->create();

        $this->actingAs(User::factory()->create())
            ->patch(route('categories.batch-restore'), ['ids' => [$category->id]])
            ->assertSessionHas('error')
            ->assertSessionMissing('success');
    }

    public function test_user_batch_restore_reports_an_error_when_nothing_is_archived(): void
    {
        $target = User::factory()->create();

        $this->actingAs($this->userWith('restore archive_users'))
            ->patch(route('user_management.batch-restore'), ['ids' => [$target->id]])
            ->assertSessionHas('error')
            ->assertSessionMissing('success');
    }

    private function userWith(string $permission): User
    {
        $user = User::factory()->create();
        $user->givePermissionTo(Permission::findOrCreate($permission, 'web'));

        return $user;
    }
}
