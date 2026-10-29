<?php

namespace Tests\Feature;

use App\Models\AcknowledgementItem;
use App\Models\InventoryItem;
use App\Models\User;
use App\Models\UserProfile;
use App\Services\RoomApiService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class UserDashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_dashboard_counts_assigned_items_by_approval_status(): void
    {
        $this->mock(RoomApiService::class, function ($mock) {
            $mock->shouldReceive('fetchRooms')->andReturn(['data' => []]);
        });

        $user = User::factory()->create();
        UserProfile::factory()->create(['id' => $user->id, 'user_id' => $user->id]);

        $this->assign($user, 'pending', 1000);
        $this->assign($user, 'pending', 2500.50);
        $this->assign($user, 'approved', 60000);
        $this->assign($user, 'rejected', 300);
        $this->assign($user, 'pending', 4000, InventoryItem::STATUS_DISPOSED);

        // Someone else's item stays out of every number.
        $other = User::factory()->create();
        UserProfile::factory()->create(['id' => $other->id, 'user_id' => $other->id]);
        $this->assign($other, 'pending', 99999);

        $this->actingAs($user)
            ->get(route('user.dashboard'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Users/Dashboard')
                ->where('stats.assigned_items', 4)
                ->where('stats.pending_items', 2)
                ->where('stats.approved_items', 1)
                ->where('stats.rejected_items', 1)
                ->where('stats.disposed_items', 1)
                ->missing('stats.property_value'));
    }

    public function test_disposed_items_have_their_own_tab_and_stay_out_of_the_others(): void
    {
        $this->mock(RoomApiService::class, function ($mock) {
            $mock->shouldReceive('fetchRooms')->andReturn(['data' => []]);
        });

        $user = User::factory()->create();
        UserProfile::factory()->create(['id' => $user->id, 'user_id' => $user->id]);

        $held = $this->assign($user, 'pending', 1000);
        $disposed = $this->assign($user, 'pending', 1000, InventoryItem::STATUS_DISPOSED);

        $this->actingAs($user)
            ->get(route('user.dashboard'))
            ->assertInertia(fn (Assert $page) => $page
                ->has('items.data', 1)
                ->where('items.data.0.id', $held->id));

        // An approval filter never pulls disposed property back in.
        $this->actingAs($user)
            ->get(route('user.dashboard', ['status' => 'disposed', 'approval_status' => 'approved']))
            ->assertInertia(fn (Assert $page) => $page
                ->has('items.data', 1)
                ->where('items.data.0.id', $disposed->id)
                ->where('filters.status', 'disposed'));
    }

    private function assign(
        User $holder,
        string $approval,
        float $cost,
        string $status = InventoryItem::STATUS_AVAILABLE,
    ): InventoryItem {
        $item = InventoryItem::factory()->create([
            'approval_status' => $approval,
            'status' => $status,
            'unit_cost' => $cost,
            'total_amount' => $cost,
        ]);

        AcknowledgementItem::factory()->create([
            'inventory_item_id' => $item->id,
            'accountable_person_id' => $holder->id,
            'issued_by_id' => $holder->id,
        ]);

        return $item;
    }
}
