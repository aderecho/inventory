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
            'accountablePerson.primaryOrganization',
            'issuedBy.primaryOrganization',
            'acknowledgementReceipts',
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

        if ($isPar) {
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

        $pdf = Browsershot::html($html)
            ->format('A4')
            ->showBackground()
            ->emulateMedia('print')
            ->pdf();

        return [
            'pdf' => $pdf,
            'type' => $type,
        ];
    }
}
