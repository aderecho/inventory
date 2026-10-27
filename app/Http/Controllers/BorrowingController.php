<?php

namespace App\Http\Controllers;

use App\Http\Requests\ReturnBorrowingItemsRequest;
use App\Http\Requests\StoreBorrowingRequest;
use App\Models\AssetCondition;
use App\Models\ItemBorrowing;
use App\Services\BorrowingService;
use Illuminate\Http\Request;

class BorrowingController extends Controller
{
    public function __construct(
        protected BorrowingService $borrowingService,
    ) {}

    public function index(Request $request)
    {
        $borrowings = $this->borrowingService->filterAndPaginate([
                'search' => $request->input('search'),
                // The shared filter bar sends its status select as
                // acknowledgement_status; here it means the borrow status.
                'status' => $request->input('acknowledgement_status'),
                'borrower_type' => $request->input('borrower_type'),
            ]);

        // One label the table can colour directly: a loan past its due date
        // reads as Overdue even though it is still "borrowed".
        $borrowings->getCollection()->transform(function ($row) {
            // Lines can come back one at a time, so the ticket reports how many
            // are still out instead of counting every line ever lent.
            $row->returned_lines = $row->items->whereNotNull('returned_at')->count();
            $row->open_lines = $row->items->count() - $row->returned_lines;

            $row->status_label = match (true) {
                $row->is_overdue => 'Overdue',
                $row->status === ItemBorrowing::STATUS_RETURNED => 'Returned',
                $row->returned_lines > 0 => 'Partly returned',
                default => 'Borrowed',
            };

            // Prefer the live profile name, fall back to the stored snapshot.
            $row->borrowed_from_label = $row->releasedBy?->userProfiles?->full_name
                ?: $row->borrowed_from_name
                ?: 'Not recorded';

            return $row;
        });

        return inertia('Borrowings/Index', [
            'borrowings' => $borrowings,
            'assetConditions' => AssetCondition::orderBy('condition_name')->get(['id', 'condition_name']),
            'counts' => $this->borrowingService->counts(),
        ]);
    }

    /**
     * Every borrow line ever recorded, including the ones already returned.
     */
    public function history(Request $request)
    {
        $lines = $this->borrowingService->filterAndPaginateHistory([
            'search' => $request->input('search'),
            'status' => $request->input('acknowledgement_status'),
        ]);

        // Ready-made labels: the table stays dumb, the payload explains itself.
        $lines->getCollection()->transform(function ($line) {
            $line->reference_code = $line->borrowing?->reference_code;
            $line->history_borrower = $line->borrowing?->borrower_name;
            $line->borrower_type_label = match ($line->borrowing?->borrower_type) {
                ItemBorrowing::TYPE_STAFF => 'Staff (PUSO)',
                ItemBorrowing::TYPE_STUDENT => 'Student',
                default => 'Outsider',
            };
            $line->item_label = trim(
                ($line->inventoryItem?->property_number ?? '—')
                    . ' · '
                    . ($line->inventoryItem?->item_name ?? '')
            );
            $line->condition_label = ($line->conditionOut?->condition_name ?? '—')
                . ' → '
                . ($line->conditionIn?->condition_name ?? 'not recorded');
            $line->borrowed_label = $line->borrowing?->borrowed_at?->format('M j, Y') ?? '—';
            $line->due_label = $line->borrowing?->due_at?->format('M j, Y') ?? 'No due date';
            $line->returned_label = $line->returned_at?->format('M j, Y') ?? 'Still out';
            $line->status_label = match (true) {
                $line->returned_at !== null => 'Returned',
                $line->borrowing?->due_at !== null && $line->borrowing->due_at->isPast() => 'Overdue',
                default => 'Out',
            };

            return $line;
        });

        return inertia('Borrowings/History', [
            'lines' => $lines,
            'counts' => $this->borrowingService->counts(),
        ]);
    }

    /**
     * Borrow Items: the list you lend from, one row per item on the shelf.
     */
    public function borrowableItems(Request $request, \App\Services\RoomApiService $roomsApi)
    {
        $items = $this->borrowingService->filterAndPaginateBorrowable([
            'search' => $request->input('search'),
            'cost_range' => $request->input('cost_range'),
            'asset_condition_id' => $request->input('asset_condition_id'),
            'acknowledgement_status' => $request->input('acknowledgement_status'),
            'room_id' => $request->input('room_id'),
        ]);

        $rooms = collect($roomsApi->fetchRooms()['data'] ?? [])
            ->map(fn ($room) => [
                'id' => $room['id'],
                'room_name' => $room['room_name'],
                'room_code' => $room['room_code'],
                'description' => $room['description'],
                'building' => $room['building'],
                'building_name' => $room['building_name'],
                'capacity' => $room['capacity'],
            ])
            ->values();

        $lookup = $rooms->keyBy('id');

        $items->getCollection()->transform(function ($item) use ($lookup) {
            $roomId = $item->latestHistoryLocation?->room_id;
            $item->room_name = isset($lookup[$roomId]) ? $lookup[$roomId]['room_name'] : 'N/A';

            return $item;
        });

        return inertia('Borrowings/BorrowItems', [
            'items' => $items,
            'rooms' => $rooms,
            'assetConditions' => AssetCondition::orderBy('condition_name')->get(['id', 'condition_name']),
        ]);
    }

    /**
     * Record a loan and take the items off the shelf.
     */
    public function store(StoreBorrowingRequest $request)
    {
        $borrowing = $this->borrowingService->create($request->validated());

        return back()->with(
            'success',
            "Borrowing {$borrowing->reference_code} recorded."
        );
    }

    /**
     * Take back some lines of one reference.
     */
    public function returnItems(
        ReturnBorrowingItemsRequest $request,
        ItemBorrowing $borrowing,
    ) {
        $validated = $request->validated();

        $count = $this->borrowingService->returnItems(
            $borrowing,
            $validated['lines'],
            $validated['condition_in_id'] ?? null,
            $validated['notes'] ?? null,
        );

        return back()->with(
            'success',
            $count . ' item' . ($count === 1 ? '' : 's') . ' taken back.'
        );
    }

    /**
     * Take back lines across references - the Borrowings page lets you pick
     * rows from more than one ticket at once.
     */
    public function returnAny(ReturnBorrowingItemsRequest $request)
    {
        $validated = $request->validated();

        $count = $this->borrowingService->returnLines(
            $validated['lines'],
            $validated['condition_in_id'] ?? null,
            $validated['notes'] ?? null,
        );

        return back()->with(
            'success',
            $count . ' item' . ($count === 1 ? '' : 's') . ' taken back.'
        );
    }

    /** Staff picker for the borrow modal, served from the PUSO-synced profiles. */
    public function staff(Request $request)
    {
        return response()->json([
            'data' => $this->borrowingService->searchStaff($request->input('search')),
        ]);
    }

    /** Student account picker for the borrow modal. */
    public function students(Request $request)
    {
        return response()->json([
            'data' => $this->borrowingService->searchStudents($request->input('search')),
        ]);
    }
}
