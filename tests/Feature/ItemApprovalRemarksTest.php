<?php

namespace Tests\Feature;

use App\Models\AcknowledgementItem;
use App\Models\InventoryItem;
use App\Models\User;
use App\Models\UserProfile;
use App\Services\InventoryService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ItemApprovalRemarksTest extends TestCase
{
    use RefreshDatabase;

    public function test_rejecting_needs_remarks(): void
    {
        [$holder, $item] = $this->assignedItem();

        $this->actingAs($holder)
            ->patch(route('inventory_items.approval', $item), ['approval_status' => 'rejected', 'approval_remarks' => '   '])
            ->assertSessionHasErrors('approval_remarks');

        $this->assertSame('pending', $item->fresh()->approval_status);
    }

    public function test_the_remarks_are_saved_with_the_rejection(): void
    {
        [$holder, $item] = $this->assignedItem();

        $this->actingAs($holder)
            ->patch(route('inventory_items.approval', $item), [
                'approval_status' => 'rejected',
                'approval_remarks' => '  Wrong serial number on the sticker.  ',
            ])
            ->assertSessionHasNoErrors();

        $item->refresh();
        $this->assertSame('rejected', $item->approval_status);
        $this->assertSame('Wrong serial number on the sticker.', $item->approval_remarks);
        // The item's own remarks are a different field and stay as they were.
        $this->assertSame('Original item remarks', $item->remarks);
    }

    public function test_approving_later_clears_the_old_remarks(): void
    {
        [$holder, $item] = $this->assignedItem();
        $item->update(['approval_status' => 'rejected', 'approval_remarks' => 'Not received yet.']);

        $this->actingAs($holder)
            ->patch(route('inventory_items.approval', $item), [
                'approval_status' => 'approved',
                'approval_remarks' => 'ignored',
            ])
            ->assertSessionHasNoErrors();

        $this->assertNull($item->fresh()->approval_remarks);
    }

    public function test_only_the_accountable_person_can_reject(): void
    {
        [, $item] = $this->assignedItem();

        $this->actingAs(User::factory()->create())
            ->patch(route('inventory_items.approval', $item), [
                'approval_status' => 'rejected',
                'approval_remarks' => 'Not mine.',
            ])
            ->assertForbidden();

        $this->assertNull($item->fresh()->approval_remarks);
    }

    public function test_the_item_approval_list_carries_the_remarks(): void
    {
        [, $item] = $this->assignedItem();
        $item->update(['approval_status' => 'rejected', 'approval_remarks' => 'Damaged on arrival.']);

        $row = app(InventoryService::class)->filterAndPaginateApprovals()->items()[0];

        $this->assertSame('Damaged on arrival.', $row->approval_remarks);
    }

    /** @return array{User, InventoryItem} */
    private function assignedItem(): array
    {
        $holder = User::factory()->create();
        UserProfile::factory()->create(['id' => $holder->id, 'user_id' => $holder->id]);

        $item = InventoryItem::factory()->create([
            'approval_status' => 'pending',
            'remarks' => 'Original item remarks',
        ]);

        AcknowledgementItem::factory()->create([
            'inventory_item_id' => $item->id,
            'accountable_person_id' => $holder->id,
            'issued_by_id' => $holder->id,
        ]);

        return [$holder, $item];
    }
}
