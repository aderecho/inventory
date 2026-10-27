<?php

namespace App\Services;

use App\Models\InventoryItem;
use App\Services\InventoryService;
use App\Models\ItemBorrowing;
use App\Models\ItemBorrowingItem;
use App\Notifications\BorrowingCreated;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class BorrowingService
{
    /** Loans that still have something out - what the Borrowings page shows. */
    public function filterAndPaginate(array $filters = [], int $perPage = 10)
    {
        return ItemBorrowing::query()
            ->where('status', ItemBorrowing::STATUS_BORROWED)
            ->with([
                'items.inventoryItem',
                'items.conditionOut',
                'items.conditionIn',
                'borrower.userProfiles',
                'releasedBy.userProfiles',
            ])
            ->when($filters['search'] ?? null, fn ($query, $term) => $query->search($term))
            ->when($filters['status'] ?? null, fn ($query, $status) => $query->filterStatus($status))
            ->when($filters['borrower_type'] ?? null, fn ($query, $type) => $query->where('borrower_type', $type))
            ->orderByDesc('borrowed_at')
            ->paginate($perPage)
            ->withQueryString();
    }

    /**
     * Every borrow line ever recorded, returned or not, for the history page.
     */
    public function filterAndPaginateHistory(array $filters = [], int $perPage = 10)
    {
        return ItemBorrowingItem::query()
            ->with(['borrowing', 'inventoryItem', 'conditionOut', 'conditionIn'])
            ->when($filters['search'] ?? null, function ($query, $term) {
                $query->where(function ($q) use ($term) {
                    $q->whereHas('borrowing', function ($borrowing) use ($term) {
                        $borrowing->where('reference_code', 'like', '%' . $term . '%')
                            ->orWhere('borrower_name', 'like', '%' . $term . '%')
                            ->orWhere('employee_number', 'like', '%' . $term . '%')
                            ->orWhere('student_number', 'like', '%' . $term . '%')
                            ->orWhere('organization', 'like', '%' . $term . '%');
                    })->orWhereHas('inventoryItem', function ($item) use ($term) {
                        $item->where('property_number', 'like', '%' . $term . '%')
                            ->orWhere('item_name', 'like', '%' . $term . '%');
                    });
                });
            })
            ->when($filters['status'] ?? null, function ($query, $status) {
                match ($status) {
                    'out' => $query->whereNull('returned_at'),
                    'returned' => $query->whereNotNull('returned_at'),
                    'overdue' => $query->whereNull('returned_at')
                        ->whereHas('borrowing', fn ($borrowing) => $borrowing
                            ->whereNotNull('due_at')
                            ->where('due_at', '<', now())),
                    default => null,
                };
            })
            ->when($filters['borrower_type'] ?? null, fn ($query, $type) => $query
                ->whereHas('borrowing', fn ($borrowing) => $borrowing->where('borrower_type', $type)))
            ->orderByDesc('created_at')
            ->paginate($perPage)
            ->withQueryString();
    }

    /**
     * Borrow Items: the same filters and columns as the Item List, limited to
     * items still on the shelf. Delegates the filtering so the two lists can
     * never drift apart.
     */
    public function filterAndPaginateBorrowable(array $filters = [], int $perPage = 10)
    {
        return app(InventoryService::class)->filterAndPaginateInventory(
            $filters['search'] ?? null,
            $filters['cost_range'] ?? null,
            $filters['asset_condition_id'] ?? null,
            $filters['acknowledgement_status'] ?? null,
            $filters['room_id'] ?? null,
            $perPage,
            InventoryItem::STATUS_AVAILABLE,
        );
    }

    /**
     * Next reference code for this year: BRW-2026-0001.
     */
    public function nextReferenceCode(): string
    {
        $prefix = 'BRW-' . now()->year . '-';

        $last = ItemBorrowing::query()
            ->where('reference_code', 'like', $prefix . '%')
            ->orderByDesc('reference_code')
            ->value('reference_code');

        $sequence = $last ? ((int) substr($last, strlen($prefix))) + 1 : 1;

        return $prefix . str_pad((string) $sequence, 4, '0', STR_PAD_LEFT);
    }

    public function counts(): array
    {
        $open = ItemBorrowing::where('status', ItemBorrowing::STATUS_BORROWED);

        return [
            'borrowed' => (clone $open)->count(),
            'overdue' => (clone $open)
                ->whereNotNull('due_at')
                ->where('due_at', '<', now())
                ->count(),
        ];
    }

    /**
     * Creates the ticket and flips every item to "borrowed" in one transaction,
     * so an item can never end up on loan without a record of who has it.
     */
    public function create(array $data): ItemBorrowing
    {
        $lines = collect($data['items'] ?? []);

        $items = InventoryItem::whereIn('id', $lines->pluck('id')->filter())
            ->with('latestAcknowledgementItem.accountablePerson')
            ->get()
            ->keyBy('id');

        $alreadyOut = $items->filter(
            fn (InventoryItem $item) => $item->status === InventoryItem::STATUS_BORROWED
        );

        if ($alreadyOut->isNotEmpty()) {
            throw ValidationException::withMessages([
                'items' => 'Already out on loan: ' . $alreadyOut
                    ->pluck('property_number')
                    ->implode(', '),
            ]);
        }

        if ($items->isEmpty()) {
            throw ValidationException::withMessages([
                'items' => 'Choose at least one item to lend.',
            ]);
        }

        $borrowing = DB::transaction(function () use ($data, $items, $lines) {
            $noDueDate = (bool) ($data['no_due_date'] ?? false);

            // The lender is whoever is accountable for the property, not a name
            // typed into the form. Clients cannot override it.
            $holder = $items->first()?->latestAcknowledgementItem?->accountablePerson;
            $holderName = $holder
                ? ($holder->full_name ?: trim(collect([
                    $holder->first_name,
                    $holder->middle_name ? mb_substr($holder->middle_name, 0, 1) . '.' : null,
                    $holder->last_name,
                ])->filter()->implode(' ')))
                : null;

            $borrowing = ItemBorrowing::create([
                'reference_code' => $this->nextReferenceCode(),
                'borrower_type' => $data['borrower_type'],
                'borrower_name' => $data['borrower_name'],
                'borrower_user_id' => $data['borrower_user_id'] ?? null,
                'employee_number' => $data['employee_number'] ?? null,
                'student_number' => $data['student_number'] ?? null,
                'organization' => $data['organization'] ?? null,
                'contact_number' => $data['contact_number'] ?? null,
                'notes' => $data['notes'] ?? null,
                'borrowed_at' => $data['borrowed_at'] ?? now(),
                // No due date means the loan stays open until it comes back.
                'due_at' => $noDueDate ? null : ($data['due_at'] ?? null),
                'status' => ItemBorrowing::STATUS_BORROWED,
                // "Borrowed from" - the item's accountable person.
                'released_by' => $holder?->user_id ?? $data['released_by'] ?? auth()->id(),
                'borrowed_from_name' => $holderName ?: ($data['borrowed_from_name'] ?? null),
            ]);

            foreach ($items as $item) {
                $line = $lines->firstWhere('id', $item->id) ?? [];

                $borrowing->items()->create([
                    'inventory_item_id' => $item->id,
                    'quantity' => $line['quantity'] ?? 1,
                    'condition_out_id' => $line['condition_out_id'] ?? null,
                    'notes' => $line['notes'] ?? null,
                ]);

                $item->update(['status' => InventoryItem::STATUS_BORROWED]);
            }

            return $borrowing;
        });

        $this->notifyBothSides($borrowing);

        return $borrowing;
    }

    /**
     * A loan has two people on it: the borrower and the staff member who
     * released the items. Both get an in-app notification and an email.
     */
    protected function notifyBothSides(ItemBorrowing $borrowing): void
    {
        $borrowing->loadMissing([
            'items.inventoryItem',
            'borrower.userProfiles',
            'releasedBy.userProfiles',
        ]);

        $borrower = $borrowing->borrower;
        $releasedBy = $borrowing->releasedBy;

        $borrowerName = $borrowing->borrower_name
            ?: ($borrower?->userProfiles?->full_name ?? 'the borrower');

        $releasedName = $borrowing->borrowed_from_name
            ?: ($releasedBy?->userProfiles?->full_name ?? 'the releasing staff');

        try {
            // A staff member borrowing from themselves gets one message, not two.
            if ($borrower && $releasedBy && $borrower->is($releasedBy)) {
                $borrower->notify(new BorrowingCreated($borrowing, 'self', $borrowerName));

                return;
            }

            $borrower?->notify(new BorrowingCreated($borrowing, 'borrower', $releasedName));
            $releasedBy?->notify(new BorrowingCreated($borrowing, 'released_by', $borrowerName));
        } catch (\Throwable $e) {
            // The loan is already saved; a mail hiccup must not fail the borrow.
            report($e);
        }
    }

    /**
     * Returns the given lines. The item goes back to "available" when its line
     * closes, and the ticket closes once every line is back.
     */
    public function returnItems(
        ItemBorrowing $borrowing,
        array $lineIds,
        ?int $conditionInId = null,
        ?string $notes = null
    ): int {
        $lines = $borrowing->items()
            ->whereIn('id', $lineIds)
            ->whereNull('returned_at')
            ->get();

        if ($lines->isEmpty()) {
            return 0;
        }

        DB::transaction(function () use ($borrowing, $lines, $conditionInId, $notes) {
            foreach ($lines as $line) {
                $line->update([
                    'returned_at' => now(),
                    'condition_in_id' => $conditionInId,
                    'notes' => $notes ?: $line->notes,
                ]);

                // A returned item goes back on the shelf unless it has been
                // disposed of in the meantime.
                InventoryItem::whereKey($line->inventory_item_id)
                    ->where('status', '!=', InventoryItem::STATUS_DISPOSED)
                    ->update(['status' => InventoryItem::STATUS_AVAILABLE]);
            }

            $openLines = $borrowing->items()->whereNull('returned_at')->count();

            if ($openLines === 0) {
                $borrowing->update([
                    'status' => ItemBorrowing::STATUS_RETURNED,
                    'returned_at' => now(),
                    'received_by' => auth()->id(),
                ]);
            }
        });

        return $lines->count();
    }

    /**
     * Takes back specific lines, which may belong to several references at
     * once. Each ticket is settled on its own: it only closes once every line
     * on it has come back, so taking one item back keeps the reference open.
     */
    public function returnLines(
        array $lineIds,
        ?int $conditionInId = null,
        ?string $notes = null
    ): int {
        $lines = ItemBorrowingItem::query()
            ->whereIn('id', $lineIds)
            ->whereNull('returned_at')
            ->get();

        $returned = 0;

        foreach ($lines->groupBy('item_borrowing_id') as $borrowingId => $group) {
            $borrowing = ItemBorrowing::find($borrowingId);

            if (! $borrowing) {
                continue;
            }

            $returned += $this->returnItems(
                $borrowing,
                $group->pluck('id')->all(),
                $conditionInId,
                $notes,
            );
        }

        return $returned;
    }

    /** Staff picker for the modal: search the PUSO-synced profiles. */
    public function searchStaff(?string $term, int $limit = 20)
    {
        return \App\Models\UserProfile::query()
            ->when($term, function ($query, $term) {
                $query->where(function ($q) use ($term) {
                    $q->where('first_name', 'like', '%' . $term . '%')
                        ->orWhere('middle_name', 'like', '%' . $term . '%')
                        ->orWhere('last_name', 'like', '%' . $term . '%')
                        ->orWhere('employee_number', 'like', '%' . $term . '%')
                        ->orWhere('primary_unit_division_department', 'like', '%' . $term . '%');
                });
            })
            ->orderBy('last_name')
            ->limit($limit)
            ->get()
            ->map(fn ($profile) => [
                'id' => $profile->user_id,
                'profile_id' => $profile->id,
                'employee_number' => $profile->employee_number,
                'name' => trim($profile->full_name ?? (
                    ($profile->first_name ?? '') . ' ' . ($profile->last_name ?? '')
                )),
                'department' => $profile->primary_unit_division_department,
            ]);
    }
}
