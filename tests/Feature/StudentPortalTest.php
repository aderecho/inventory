<?php

namespace Tests\Feature;

use App\Models\InventoryItem;
use App\Models\ItemBorrowing;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class StudentPortalTest extends TestCase
{
    use RefreshDatabase;

    public function test_students_see_their_own_loans_and_return_history(): void
    {
        $student = $this->student();
        $overdue = $this->lend($student, dueAt: now()->subDay());
        $this->lend($student, returnedAt: now());
        $this->lend(User::factory()->create());

        $this->actingAs($student)
            ->get(route('student.dashboard'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Students/Dashboard')
                ->has('borrowedItems', 1)
                ->where('borrowedItems.0.reference_code', $overdue->reference_code)
                ->where('borrowedItems.0.is_overdue', true)
                ->has('returnedItems.data', 1)
                ->where('returnedItems.data.0.is_overdue', false)
                ->where('stats', [
                    'borrowed_items' => 1,
                    'overdue_borrowings' => 1,
                    'returned_items' => 1,
                ]));
    }

    public function test_only_students_can_open_the_student_portal(): void
    {
        $this->actingAs(User::factory()->create())
            ->get(route('student.dashboard'))
            ->assertForbidden();
    }

    public function test_the_borrow_modal_only_offers_student_accounts(): void
    {
        $student = $this->student();
        User::factory()->withProfile()->create();

        $lender = User::factory()->create();
        $lender->givePermissionTo('borrow items');

        $this->actingAs($lender)
            ->getJson(route('borrowings.students'))
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $student->id);
    }

    private function student(): User
    {
        $student = User::factory()->withProfile()->create();
        $student->assignRole(User::ROLE_STUDENT);

        return $student;
    }

    private function lend(User $borrower, $returnedAt = null, $dueAt = null): ItemBorrowing
    {
        $borrowing = ItemBorrowing::create([
            'reference_code' => 'BR-' . Str::random(8),
            'borrower_type' => ItemBorrowing::TYPE_STUDENT,
            'borrower_name' => 'Borrower',
            'borrower_user_id' => $borrower->id,
            'borrowed_at' => now()->subDays(5),
            'due_at' => $dueAt,
            'status' => $returnedAt ? ItemBorrowing::STATUS_RETURNED : ItemBorrowing::STATUS_BORROWED,
        ]);

        $borrowing->items()->create([
            'inventory_item_id' => InventoryItem::factory()->create()->id,
            'returned_at' => $returnedAt,
        ]);

        return $borrowing;
    }
}
