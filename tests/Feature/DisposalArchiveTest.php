<?php

namespace Tests\Feature;

use App\Models\InventoryItem;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class DisposalArchiveTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_disposed_item_moves_to_the_item_archive(): void
    {
        $item = InventoryItem::factory()->create(['status' => InventoryItem::STATUS_DISPOSED]);

        $this->actingAs($this->archiver())
            ->delete(route('disposal.archive', $item))
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        $this->assertSoftDeleted($item);
    }

    public function test_archiving_needs_the_archive_disposal_permission(): void
    {
        $item = InventoryItem::factory()->create(['status' => InventoryItem::STATUS_DISPOSED]);

        $this->actingAs(User::factory()->create())
            ->delete(route('disposal.archive', $item))
            ->assertForbidden();

        $this->assertNotSoftDeleted($item);
    }

    public function test_an_item_still_in_service_cannot_be_archived_from_disposal(): void
    {
        $item = InventoryItem::factory()->create(['status' => InventoryItem::STATUS_AVAILABLE]);

        $this->actingAs($this->archiver())
            ->delete(route('disposal.archive', $item))
            ->assertSessionHasErrors('item');

        $this->assertNotSoftDeleted($item);
    }

    public function test_batch_archive_moves_only_the_disposed_items(): void
    {
        $disposed = InventoryItem::factory()->count(2)->create(['status' => InventoryItem::STATUS_DISPOSED]);
        $inService = InventoryItem::factory()->create(['status' => InventoryItem::STATUS_AVAILABLE]);

        $this->actingAs($this->archiver())
            ->delete(route('disposal.batch-archive'), [
                'ids' => [...$disposed->pluck('id')->all(), $inService->id],
            ])
            ->assertRedirect()
            ->assertSessionHas('success', '2 items archived successfully.');

        $disposed->each(fn (InventoryItem $item) => $this->assertSoftDeleted($item));
        $this->assertNotSoftDeleted($inService);
    }

    public function test_batch_archive_needs_the_archive_disposal_permission(): void
    {
        $item = InventoryItem::factory()->create(['status' => InventoryItem::STATUS_DISPOSED]);

        $this->actingAs(User::factory()->create())
            ->delete(route('disposal.batch-archive'), ['ids' => [$item->id]])
            ->assertForbidden();

        $this->assertNotSoftDeleted($item);
    }

    public function test_batch_archive_needs_a_selection(): void
    {
        $this->actingAs($this->archiver())
            ->delete(route('disposal.batch-archive'), ['ids' => []])
            ->assertSessionHasErrors('ids');
    }

    private function archiver(): User
    {
        $user = User::factory()->create();
        $user->givePermissionTo(Permission::findOrCreate('archive disposal', 'web'));

        return $user;
    }
}
