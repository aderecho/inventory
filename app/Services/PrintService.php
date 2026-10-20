<?php

namespace App\Services;

use App\Models\InventoryItem;
use App\Models\AcknowledgementItem;
use App\Services\RoomApiService;
use Spatie\Browsershot\Browsershot;

class PrintService
{
    private RoomApiService $roomsApi;

    private const PAR_THRESHOLD = 50000;

    public function __construct(RoomApiService $roomsApi)
    {
        $this->roomsApi = $roomsApi;
    }

    private function resolveRoomNames(iterable $acknowledgementItems): array
    {
        $roomResult = $this->roomsApi->fetchRooms();
        $rooms = collect($roomResult['data']);

        $roomNames = [];

        foreach ($acknowledgementItems as $item) {
            $inventoryItem = $item->inventoryItems;

            $roomId = $inventoryItem?->latestHistoryLocation?->room_id;

            $room = $rooms->firstWhere('id', $roomId);

            $roomNames[$inventoryItem->id ?? 0] =
                $room['room_name'] ?? 'N/A';
        }

        return $roomNames;
    }

    private function sumUnitCost(iterable $items): float
    {
        return collect($items)->sum(
            fn($item) => (float) ($item->inventoryItems->unit_cost ?? 0)
        );
    }

    /**
     * "ITR_223-2026-10-900.pdf", "PAR_221-2022-04-261_221-2022-04-262.pdf",
     * or "ICS_223-2026-10-1_and_4_more.pdf" when many receipts are printed together.
     */
    private function receiptFileName(string $type, iterable $receiptNumbers): string
    {
        $numbers = collect($receiptNumbers)
            ->map(fn($number) => trim(preg_replace('/[^A-Za-z0-9._-]+/', '-', (string) $number), '-.'))
            ->filter()
            ->unique()
            ->values();

        if ($numbers->isEmpty()) {
            return $type . '_' . now()->format('Y_m_d_His') . '.pdf';
        }

        $joined = $numbers->implode('_');

        if ($numbers->count() > 3 || strlen($joined) > 120) {
            $joined = $numbers->first() . '_and_' . ($numbers->count() - 1) . '_more';
        }

        return "{$type}_{$joined}.pdf";
    }

    /**
     * For each latest acknowledgement item, the assignment it replaced (if any),
     * keyed by the latest acknowledgement item's id.
     */
    private function previousAcknowledgementItems($acknowledgementItems)
    {
        $history = AcknowledgementItem::with([
            'accountablePerson',
            'acknowledgementReceipts',
        ])
            ->whereIn('inventory_item_id', $acknowledgementItems->pluck('inventory_item_id'))
            ->orderByDesc('id')
            ->get()
            ->groupBy('inventory_item_id');

        return $acknowledgementItems->mapWithKeys(fn($item) => [
            $item->id => $history
                ->get($item->inventory_item_id, collect())
                ->first(fn($earlier) => $earlier->id < $item->id),
        ]);
    }

    /**
     * An item is being reassigned when someone held it before, or when the
     * assignment was recorded with the "Reassignment" transfer type.
     */
    private function isReassignment($item, $previousAckItems): bool
    {
        return $previousAckItems[$item->id] !== null
            || $item->transferTypes->contains(
                fn($type) => strtolower(trim($type->name)) === 'reassignment'
            );
    }

    public function generateReceiptPdf(int|array $ids): array
    {
        if (!is_array($ids)) {
            $ids = [$ids];
        }

        $ids = array_filter(
            $ids,
            fn($id) => !is_null($id) && $id !== ''
        );

        $ids = array_map('intval', $ids);

        if (empty($ids)) {
            throw new \Exception('No valid ID provided');
        }

        /*
        |--------------------------------------------------------------------------
        | Check acknowledgement
        |--------------------------------------------------------------------------
        */

        $withoutAcknowledgement = InventoryItem::whereIn('id', $ids)
            ->doesntHave('acknowledgementItems')
            ->pluck('property_number', 'id');

        if ($withoutAcknowledgement->isNotEmpty()) {
            $itemList = $withoutAcknowledgement
                ->values()
                ->implode(', ');

            throw new \Exception(
                "The following item(s) have no acknowledgement record: {$itemList}"
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Get latest acknowledgement items
        |--------------------------------------------------------------------------
        */

        $acknowledgementItems = AcknowledgementItem::with([
            'inventoryItems.supplier',
            'inventoryItems.latestHistoryLocation',
            'inventoryItems.latestInspection.assetCondition',
            'accountablePerson',
            'issuedBy',
            'approvedBy',
            'acknowledgementReceipts',
            'transferTypes',
        ])
            ->whereIn('inventory_item_id', $ids)
            ->whereIn('id', function ($query) use ($ids) {
                $query->selectRaw('MAX(id)')
                    ->from('acknowledgement_items')
                    ->whereIn('inventory_item_id', $ids)
                    ->groupBy('inventory_item_id');
            })
            ->get();

        if ($acknowledgementItems->isEmpty()) {
            throw new \Exception('Item(s) not found');
        }

        /*
        |--------------------------------------------------------------------------
        | Check acknowledgement receipt
        |--------------------------------------------------------------------------
        */

        $withoutReceipt = $acknowledgementItems->filter(
            fn($item) => is_null($item->acknowledgementReceipts)
        );

        if ($withoutReceipt->isNotEmpty()) {
            $itemList = $withoutReceipt
                ->map(
                    fn($item) =>
                    $item->inventoryItems->property_number
                        ?? "ID #{$item->inventory_item_id}"
                )
                ->implode(', ');

            throw new \Exception(
                "The following item(s) have no acknowledgement receipt: {$itemList}"
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Resolve rooms
        |--------------------------------------------------------------------------
        */

        $roomNames = $this->resolveRoomNames(
            $acknowledgementItems
        );

        /*
        |--------------------------------------------------------------------------
        | Calculate totals
        |--------------------------------------------------------------------------
        */

        $grandTotal = $this->sumUnitCost(
            $acknowledgementItems
        );

        $isPar = $grandTotal >= self::PAR_THRESHOLD;

        /*
        |--------------------------------------------------------------------------
        | Reassignments print as transfer reports: an Inventory Transfer Report (ITR)
        | below the PAR threshold, a Property Transfer Report (PTR) at or above it.
        |--------------------------------------------------------------------------
        */

        $previousAckItems = $this->previousAcknowledgementItems(
            $acknowledgementItems
        );

        $reassignedItems = $acknowledgementItems->filter(
            fn($item) => $this->isReassignment($item, $previousAckItems)
        );

        $isTransfer = $reassignedItems->count() === $acknowledgementItems->count();

        if (!$isTransfer && $reassignedItems->isNotEmpty()) {
            $itemList = $reassignedItems
                ->map(fn($item) => $item->inventoryItems->property_number ?? "ID #{$item->inventory_item_id}")
                ->implode(', ');

            $report = $isPar ? 'a Property Transfer Report' : 'an Inventory Transfer Report';

            throw new \Exception(
                "These item(s) were reassigned and print on {$report}, so print them separately from first assignments: {$itemList}"
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Group items
        |--------------------------------------------------------------------------
        */

        $groupedItems = $acknowledgementItems->groupBy(
            function ($item) {
                return $item->acknowledgement_id;
            }
        );

        $groupedTotals = $groupedItems->map(
            fn($group) => $this->sumUnitCost($group)
        );

        /*
        |--------------------------------------------------------------------------
        | Determine Blade view
        |--------------------------------------------------------------------------
        */

        if ($isTransfer) {
            $view = $isPar ? 'prints.ptr_receipt' : 'prints.itr_receipt';

            // One document per receipt number: reassigning the same receipt can leave
            // more than one receipt record carrying that number.
            $groupedTransferItems = $acknowledgementItems->groupBy(
                fn($item) => $item->acknowledgementReceipts?->category
                    ?: 'receipt-' . $item->acknowledgement_id
            );

            $data = [
                'groupedTransferItems' => $groupedTransferItems,
                'previousAckItems' => $previousAckItems,
                'roomNames' => $roomNames,
                'acknowledgementItems' => $acknowledgementItems,
                'transferTotal' => $grandTotal,
                'groupedTotals' => $groupedTotals,
            ];

            $type = $isPar ? 'PTR' : 'ITR';
        } elseif ($isPar) {
            $view = 'prints.par_receipt';

            $data = [
                'groupedParItems' => $groupedItems,
                'roomNames' => $roomNames,
                'acknowledgementItems' => $acknowledgementItems,
                'parTotal' => $grandTotal,
                'groupedParTotals' => $groupedTotals,
            ];

            $type = 'PAR';
        } else {
            $view = 'prints.ics_receipt';

            $data = [
                'groupedIcsItems' => $groupedItems,
                'roomNames' => $roomNames,
                'acknowledgementItems' => $acknowledgementItems,
                'icsTotal' => $grandTotal,
                'groupedTotals' => $groupedTotals,
            ];

            $type = 'ICS';
        }

        /*
        |--------------------------------------------------------------------------
        | Render Blade HTML
        |--------------------------------------------------------------------------
        */

        $html = view($view, $data)->render();

        /*
        |--------------------------------------------------------------------------
        | Generate PDF using Chromium
        |--------------------------------------------------------------------------
        */

        $shot = Browsershot::html($html)
            ->setChromePath('/usr/bin/chromium')
            ->format('A4');

        // All receipts print on A4; the transfer report forms (ITR/PTR) are laid out landscape,
        // with top/bottom margins on every page so continued pages do not start at the edge.
        if ($isTransfer) {
            $shot = $shot->landscape()->margins(8, 0, 8, 0);
        }

        $pdf = $shot
            ->showBackground()
            ->emulateMedia('print')
            ->addChromiumArguments([
                'no-sandbox',
                'disable-setuid-sandbox',
            ])
            ->pdf();

        return [
            'pdf' => $pdf,
            'type' => $type,
            'file_name' => $this->receiptFileName(
                $type,
                $groupedItems->map(fn($group) => $group->first()->acknowledgementReceipts?->category)
            ),
        ];
    }


    public function generateAndStoreReceiptPdf(int|array $ids): array
    {
        $result = $this->generateReceiptPdf($ids);

        // Stored copies keep a timestamp so printing the same receipt again
        // does not overwrite an earlier (possibly signed) file.
        $fileName = pathinfo($result['file_name'], PATHINFO_FILENAME)
            . '_'
            . now()->format('Y_m_d_His')
            . '.pdf';

        $directory = 'receipts';

        $path = $directory . '/' . $fileName;

        \Storage::disk('public')->put(
            $path,
            $result['pdf']
        );

        return [
            'path' => $path,
            'file_name' => $fileName,
            'type' => $result['type'],
            'pdf' => $result['pdf'],
        ];
    }
}
