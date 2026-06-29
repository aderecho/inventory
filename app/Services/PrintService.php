<?php

namespace App\Services;

use App\Models\InventoryItem;
use App\Models\AcknowledgementItem;
use App\Services\RoomApiService;
use Barryvdh\DomPDF\Facade\Pdf;

class PrintService
{
    private RoomApiService $roomsApi;

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
            $roomNames[$inventoryItem->id ?? 0] = $room['room_name'] ?? 'N/A';
        }

        return $roomNames;
    }

    public function generateReceiptPdf(int|array $ids): array
    {
        if (!is_array($ids)) {
            $ids = [$ids];
        }

        $ids = array_filter($ids, fn($id) => !is_null($id) && $id !== '');
        $ids = array_map('intval', $ids);

        if (empty($ids)) {
            throw new \Exception('No valid ID provided');
        }

        $withoutAcknowledgement = InventoryItem::whereIn('id', $ids)
            ->doesntHave('acknowledgementItems')
            ->pluck('property_number', 'id');

        if ($withoutAcknowledgement->isNotEmpty()) {
            $itemList = $withoutAcknowledgement->values()->implode(', ');
            throw new \Exception(
                "The following item(s) have no acknowledgement record: {$itemList}"
            );
        }

        $acknowledgementItems = AcknowledgementItem::with([
            'inventoryItems.supplier',
            'inventoryItems.latestHistoryLocation',
            'accountablePerson',
            'issuedBy',
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

        $withoutReceipt = $acknowledgementItems->filter(
            fn($item) => is_null($item->acknowledgementReceipts)
        );

        if ($withoutReceipt->isNotEmpty()) {
            $itemList = $withoutReceipt
                ->map(fn($item) => $item->inventoryItems->property_number ?? "ID #{$item->inventory_item_id}")
                ->implode(', ');

            throw new \Exception(
                "The following item(s) have no acknowledgement receipt: {$itemList}"
            );
        }

        // Resolve room names for all items
        $roomNames = $this->resolveRoomNames($acknowledgementItems);

        $parItems = $acknowledgementItems->filter(
            fn($item) => ($item->inventoryItems->unit_cost ?? 0) > 50000
        );

        $icsItems = $acknowledgementItems->filter(
            fn($item) => ($item->inventoryItems->unit_cost ?? 0) <= 50000
        );

        // Group PAR items by prefix (classification) + PO number
        $groupedParItems = $parItems->groupBy(function ($item) {
            return $item->acknowledgement_id;
        });

        // Group ICS items by acknowledgement_id
        $groupedIcsItems = $icsItems->groupBy(function ($item) {
            return $item->acknowledgement_id;
        });

        if ($parItems->isNotEmpty() && $icsItems->isNotEmpty()) {
            return [
                'pdf' => Pdf::loadView('prints.merged_receipt', [
                    'groupedParItems' => $groupedParItems,
                    'groupedIcsItems' => $groupedIcsItems,
                    'roomNames'       => $roomNames,
                    'acknowledgementItems' => $acknowledgementItems,
                ]),
                'type' => 'BOTH',
            ];
        }

        if ($parItems->isNotEmpty()) {
            return [
                'pdf'  => Pdf::loadView('prints.par_receipt', [
                    'groupedParItems' => $groupedParItems,
                    'roomNames'       => $roomNames,
                    'acknowledgementItems' => $parItems,
                ]),
                'type' => 'PAR',
            ];
        }

        return [
            'pdf'  => Pdf::loadView('prints.ics_receipt', [
                'groupedIcsItems' => $groupedIcsItems,
                'roomNames'       => $roomNames,
                'acknowledgementItems' => $icsItems,
            ]),
            'type' => 'ICS',
        ];
    }
}
