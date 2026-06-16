<?php

namespace App\Services;

use App\Models\InventoryItem;
use App\Models\AcknowledgementItem;
use Barryvdh\DomPDF\Facade\Pdf;

class PrintService
{
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

        // Validate: every selected inventory item must have an acknowledgement item
        $withoutAcknowledgement = InventoryItem::whereIn('id', $ids)
            ->doesntHave('acknowledgementItems')
            ->pluck('property_number', 'id');

        if ($withoutAcknowledgement->isNotEmpty()) {
            $itemList = $withoutAcknowledgement->values()->implode(', ');
            throw new \Exception(
                "The following item(s) have no acknowledgement record: {$itemList}"
            );
        }

        // Fetch acknowledgement items (latest per inventory item) with their receipt
        $acknowledgementItems = AcknowledgementItem::with([
            'inventoryItems.supplier',
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

        // Validate: each acknowledgement item must have a receipt
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

        // Split into PAR and ICS
        $parItems = $acknowledgementItems->filter(
            fn($item) => ($item->inventoryItems->unit_cost ?? 0) > 50000
        );

        $icsItems = $acknowledgementItems->filter(
            fn($item) => ($item->inventoryItems->unit_cost ?? 0) <= 50000
        );

        // Group PAR items by the first segment of property_number
        // e.g. "233-2025-06-001" and "233-2025-06-002" both group under "233"
        $groupedParItems = $parItems->groupBy(function ($item) {
            $propertyNumber = $item->inventoryItems->property_number ?? '';
            return explode('-', $propertyNumber)[0] ?? 'unknown';
        });

        // Case 1: Both PAR and ICS
        if ($parItems->isNotEmpty() && $icsItems->isNotEmpty()) {
            return [
                'pdf' => Pdf::loadView('prints.merged_receipt', [
                    'groupedParItems' => $groupedParItems,
                    'icsItems'        => $icsItems,
                ]),
                'type' => 'BOTH',
            ];
        }

        // Case 2: PAR only
        if ($parItems->isNotEmpty()) {
            return [
                'pdf'  => Pdf::loadView('prints.par_receipt', [
                    'groupedParItems' => $groupedParItems,
                ]),
                'type' => 'PAR',
            ];
        }

        // Case 3: ICS only
        return [
            'pdf'  => Pdf::loadView('prints.ics_receipt', [
                'acknowledgementItems' => $icsItems,
            ]),
            'type' => 'ICS',
        ];
    }
}