<?php

namespace App\Services;

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

        $acknowledgementItems = AcknowledgementItem::with([
            'inventoryItems',
            'acknowledgementReceipts.accountablePerson',
            'acknowledgementReceipts.issuedBy.userProfiles',
        ])
            ->whereIn('id', $ids)
            ->get();

        if ($acknowledgementItems->isEmpty()) {
            throw new \Exception('Item(s) not found');
        }

        $hasParItem = $acknowledgementItems->contains(function ($item) {
            return ($item->inventoryItems->unit_cost ?? 0) > 50000;
        });

        $view = $hasParItem
            ? 'prints.par_receipt'
            : 'prints.ics_receipt';

        $pdf = Pdf::loadView($view, [
            'acknowledgementItems' => $acknowledgementItems,
        ]);

        return [
            'pdf' => $pdf,
            'type' => $hasParItem ? 'PAR' : 'ICS',
        ];
    }
}
