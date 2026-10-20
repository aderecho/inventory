<?php

namespace App\Services;

use App\Models\InventoryItem;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

/**
 * Builds the Property Card ledger for one article.
 *
 * Every event the system already records - the acquisition, every PAR/ICS/ITR
 * issue, room moves, inspections and borrowings - becomes one row of the card,
 * oldest first, with the running balance kept at the item's quantity.
 */
class PropertyCardService
{
    private ?Collection $roomNames = null;

    public function __construct(
        private readonly RoomApiService $roomsApi,
    ) {}

    public function filterAndPaginate(
        ?string $search,
        int|string|null $roomId,
        int|string|null $conditionId,
        int $perPage = 10,
    ): LengthAwarePaginator {
        return InventoryItem::with([
            'itemClassification',
            'acknowledgementItems.accountablePerson',
            'acknowledgementItems.acknowledgementReceipts',
            'acknowledgementItems.transferTypes',
            'acknowledgementItems.issuedBy',
            'historyLocations',
            'inspections.assetCondition',
            'borrowingItems.borrowing',
            'latestAcknowledgementItem.accountablePerson',
            'latestHistoryLocation',
            'latestInspection.assetCondition',
        ])
            ->search($search)
            ->when(
                filled($roomId),
                fn ($query) => $query->whereHas(
                    'latestHistoryLocation',
                    fn ($location) => $location->where('room_id', $roomId),
                )
            )
            ->when(
                filled($conditionId),
                fn ($query) => $query->whereHas(
                    'inspections',
                    fn ($inspection) => $inspection->where('asset_condition_id', $conditionId),
                )
            )
            ->orderBy('item_name')
            ->paginate($perPage)
            ->withQueryString();
    }

    /**
     * @return array{item: array<string, mixed>, office: string, rows: array<int, array<string, mixed>>, balance: float}
     */
    public function cardFor(InventoryItem $item): array
    {
        $item->loadMissing([
            'itemClassification',
            'acknowledgementItems.accountablePerson',
            'acknowledgementItems.acknowledgementReceipts',
            'acknowledgementItems.transferTypes',
            'acknowledgementItems.issuedBy',
            'historyLocations',
            'inspections.assetCondition',
            'borrowingItems.borrowing',
            'latestAcknowledgementItem.accountablePerson',
            'latestHistoryLocation',
            'latestInspection.assetCondition',
            'supplier',
        ]);

        $quantity = (float) ($item->quantity ?: 1);
        $balance = $quantity;
        $rows = [];

        // 1. The acquisition itself.
        $reference = collect([
            $item->po_number ? 'PO ' . $item->po_number : null,
            $item->pr_number ? 'PR ' . $item->pr_number : null,
            $item->invoice ? 'Invoice ' . $item->invoice : null,
        ])->filter()->first();

        if ($item->date_acquired || $reference) {
            $rows[] = $this->row(
                date: $item->date_acquired,
                reference: $reference ?: 'Acquisition',
                receiptQty: $quantity,
                officeOfficer: $this->officeFor($item),
                balanceQty: $balance,
                amount: $item->unit_cost,
                remarks: 'Received' . ($item->itemClassification?->classification_name
                    ? ' — ' . $item->itemClassification->classification_name
                    : ''),
                kind: 'receipt',
            );
        }

        // 2. Every PAR / ICS / ITR issue or reassignment.
        foreach ($item->acknowledgementItems->sortBy('created_at') as $acknowledgement) {
            $receipt = $acknowledgement->acknowledgementReceipts;
            $person = $acknowledgement->accountablePerson;

            $rows[] = $this->row(
                date: $receipt?->par_date ?? $acknowledgement->created_at,
                reference: $this->receiptReference($receipt, $acknowledgement),
                issueQty: $quantity,
                officeOfficer: $this->personWithUnit($person)
                    ?: $this->officeFor($item),
                balanceQty: $balance,
                amount: null,
                remarks: $this->transferRemarks($acknowledgement, $receipt),
                kind: 'transfer',
            );
        }

        // 3. Room moves.
        foreach ($item->historyLocations->sortBy('created_at') as $location) {
            $room = $this->roomName($location->room_id);

            $rows[] = $this->row(
                date: $location->created_at,
                reference: 'Location',
                officeOfficer: $room,
                balanceQty: $balance,
                remarks: $room !== '' ? 'Moved to ' . $room : 'Room updated',
                kind: 'location',
            );
        }

        // 4. Inspection conditions, including when an item falls unserviceable.
        foreach ($item->inspections->sortBy(fn ($inspection) => $inspection->inspection_date ?? $inspection->created_at) as $inspection) {
            $condition = $inspection->assetCondition?->condition_name ?? 'Inspected';

            $rows[] = $this->row(
                date: $inspection->inspection_date ?? $inspection->created_at,
                reference: 'Inspection',
                balanceQty: $balance,
                remarks: 'Condition: ' . $condition
                    . ($inspection->remarks ? ' — ' . $inspection->remarks : ''),
                kind: 'condition',
            );
        }

        // 5. Borrowing out and back.
        foreach ($item->borrowingItems->sortBy('created_at') as $borrowingItem) {
            $borrowing = $borrowingItem->borrowing;

            $rows[] = $this->row(
                date: $borrowing?->borrowed_at ?? $borrowingItem->created_at,
                reference: $borrowing?->reference_code ?: 'Borrowing',
                issueQty: (float) ($borrowingItem->quantity ?: 1),
                officeOfficer: $borrowing?->borrower_name ?? '',
                balanceQty: $balance,
                remarks: $borrowingItem->returned_at
                    ? 'Borrowed and returned ' . $borrowingItem->returned_at->format('M j, Y')
                    : 'Borrowed',
                kind: 'borrowing',
            );
        }

        usort($rows, function (array $a, array $b) {
            return [$a['sort_date'], $a['kind_order']] <=> [$b['sort_date'], $b['kind_order']];
        });

        return [
            'item' => $this->itemSummary($item),
            'office' => $this->officeFor($item),
            'rows' => array_map(fn (array $row) => collect($row)->except(['sort_date', 'kind_order'])->all(), $rows),
            'balance' => $balance,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function row(
        mixed $date,
        string $reference,
        ?float $receiptQty = null,
        ?float $issueQty = null,
        string $officeOfficer = '',
        ?float $balanceQty = null,
        mixed $amount = null,
        string $remarks = '',
        string $kind = 'event',
    ): array {
        $carbon = $this->carbon($date);

        return [
            'date' => $carbon?->format('M j, Y') ?? '',
            'reference' => $reference,
            'receipt_qty' => $receiptQty,
            'issue_qty' => $issueQty,
            'office_officer' => $officeOfficer,
            'balance_qty' => $balanceQty,
            'amount' => $amount !== null ? (float) $amount : null,
            'remarks' => $remarks,
            'kind' => $kind,
            'sort_date' => $carbon?->timestamp ?? 0,
            'kind_order' => array_search($kind, ['receipt', 'transfer', 'location', 'condition', 'borrowing'], true),
        ];
    }

    private function carbon(mixed $value): ?\Carbon\Carbon
    {
        if (! $value) {
            return null;
        }

        return $value instanceof \Carbon\Carbon
            ? $value
            : \Carbon\Carbon::parse($value);
    }

    /**
     * @return array<string, mixed>
     */
    private function itemSummary(InventoryItem $item): array
    {
        $person = $item->latestAcknowledgementItem?->accountablePerson;

        return [
            'id' => $item->id,
            'item_name' => $item->item_name,
            'description' => $item->description,
            'brand' => $item->brand,
            'model' => $item->model,
            'serial_number' => $item->serial_number,
            'property_number' => $item->property_number,
            'unit' => $item->unit,
            'quantity' => (float) ($item->quantity ?: 1),
            'unit_cost' => (float) ($item->unit_cost ?? 0),
            'date_acquired' => $this->carbon($item->date_acquired)?->format('M j, Y'),
            'fund_cluster' => $item->fund_source,
            'classification' => $item->itemClassification?->classification_name,
            'accountable_person' => $this->personName($person),
            'accountable_unit' => $person?->primary_unit_division_department,
            'room_name' => $this->roomName($item->latestHistoryLocation?->room_id),
            'condition' => $item->latestInspection?->assetCondition?->condition_name,
        ];
    }

    private function receiptReference($receipt, $acknowledgement): string
    {
        // The receipt category is the PAR/ICS/ITR number printed on the document.
        return (string) ($receipt?->category
            ?: str_pad((string) ($receipt?->id ?? $acknowledgement->id), 5, '0', STR_PAD_LEFT));
    }

    private function transferRemarks($acknowledgement, $receipt): string
    {
        $types = $acknowledgement->transferTypes?->pluck('name')->filter()->implode(', ');

        return collect([
            $types ?: null,
            $receipt?->reason_for_transfer,
            $receipt?->remarks,
        ])->filter()->first() ?: 'Issued to accountable person';
    }

    private function personName($person): string
    {
        if (! $person) {
            return '';
        }

        return trim(collect([
            $person->first_name,
            $person->middle_name ? mb_substr($person->middle_name, 0, 1) . '.' : null,
            $person->last_name,
        ])->filter()->implode(' '));
    }

    private function personWithUnit($person): string
    {
        $name = $this->personName($person);
        $unit = $person?->primary_unit_division_department;

        return trim($name . ($unit ? ' — ' . $unit : ''));
    }

    /** Office / College line: the holder's unit, or the item's classification. */
    private function officeFor(InventoryItem $item): string
    {
        $person = $item->latestAcknowledgementItem?->accountablePerson;

        return $person?->primary_unit_division_department
            ?? $item->itemClassification?->classification_name
            ?? '';
    }

    private function roomName(int|string|null $roomId): string
    {
        if (! $roomId) {
            return '';
        }

        if ($this->roomNames === null) {
            $result = $this->roomsApi->fetchRooms();

            $this->roomNames = collect($result['data'] ?? [])
                ->keyBy('id')
                ->map(fn ($room) => $room['room_name'] ?? '');
        }

        return (string) ($this->roomNames[$roomId] ?? '');
    }
}

