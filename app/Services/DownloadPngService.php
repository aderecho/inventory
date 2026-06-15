<?php

namespace App\Services;

use Barryvdh\DomPDF\Facade\Pdf;
use ZipArchive;
use App\Models\InventoryItem;

class DownloadPngService
{
    private function addItemQrToZip(
        ZipArchive $zip,
        InventoryItem $item
    ): void {
        // Generate PDF in memory (no file saving)
        $pdf = Pdf::loadView(
            'prints.inventory_qr',
            compact('item')
        )->setPaper([0, 0, 600, 400]);

        $pdfOutput = $pdf->output(); // Get as string

        try {
            // Read PDF directly from memory string
            $imagick = new \Imagick();
            $imagick->setResolution(300, 300);
            $imagick->readImageBlob($pdfOutput); // Key: read from blob, not file

            $imagick->setImageFormat('png');

            $png = $imagick->getImageBlob();

            $filename = preg_replace(
                '/[^A-Za-z0-9\-_]/',
                '_',
                $item->property_number
            ) . '.png';

            $zip->addFromString(
                $filename,
                $png
            );

            $imagick->clear();
            $imagick->destroy();
        } catch (\Exception $e) {
            // Handle error silently or log if needed
        }
    }

    public function generateQrZip(array $ids): string
    {
        if (empty($ids)) {
            abort(422, 'No items selected.');
        }

        $zipFileName = 'qr-codes-' . now()->timestamp . '.zip';

        $zipPath = storage_path('app/' . $zipFileName);

        $zip = new ZipArchive();

        if ($zip->open(
            $zipPath,
            ZipArchive::CREATE | ZipArchive::OVERWRITE
        ) !== true) {
            abort(500, 'Unable to create ZIP file.');
        }

        $errors = [];

        foreach ($ids as $id) {
            $item = InventoryItem::with('latestAcknowledgementItem.accountablePerson')->find($id);

            if (!$item || !$item->latestAcknowledgementItem || !$item->latestAcknowledgementItem->accountablePerson) {
                $errors[] = $item->property_number ?? $item->item_name ?? $id;
                continue;
            }

            $this->addItemQrToZip($zip, $item);
        }

        $zip->close();

        if (!empty($errors)) {
            abort(422, 'Some items have no accountable person assigned: ' . implode(' ,', $errors));
        }

        return $zipPath;
    }
}
