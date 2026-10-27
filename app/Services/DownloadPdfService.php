<?php

namespace App\Services;

use App\Models\InventoryItem;

class DownloadPdfService
{
    private RoomApiService $roomsApi;
    private QrImageService $qrImages;
    private PdfRenderService $pdfs;

    public function __construct(RoomApiService $roomsApi, QrImageService $qrImages, PdfRenderService $pdfs)
    {
        $this->roomsApi = $roomsApi;
        $this->qrImages = $qrImages;
        $this->pdfs = $pdfs;
    }

    public function generateQrPdf(array $ids, ?callable $onProgress = null): string
    {
        $report = function (int $percent, string $step) use ($onProgress): void {
            if ($onProgress) {
                $onProgress($percent, $step);
            }
        };

        if (empty($ids)) {
            abort(422, 'No items selected.');
        }

        $report(10, 'Loading the selected items');

        $items = InventoryItem::with([
            'latestAcknowledgementItem.accountablePerson',
            'supplier',
            'latestHistoryLocation',
        ])->whereIn('id', $ids)->get();

        if ($items->isEmpty()) {
            abort(422, 'No valid items found.');
        }

        $report(25, 'Resolving room names');

        $roomResult = $this->roomsApi->fetchRooms();
        $rooms = collect($roomResult['data'])->keyBy('id');

        $roomNames = [];

        foreach ($items as $item) {
            $roomId = $item->latestHistoryLocation?->room_id;
            $roomNames[$item->id] = $rooms[$roomId]['room_name'] ?? 'N/A';
        }

        $qrImages = [];

        foreach ($items as $item) {
            $qrImages[$item->id] = $this->qrImages->dataUri($item->property_number, 260, 0);
        }

        $report(45, 'Rendering the sticker sheet');

        $path = storage_path('app/qr-codes-' . now()->timestamp . '.pdf');

        file_put_contents($path, $this->pdfs->render('prints.inventory_qr_stickers', [
            'items' => $items,
            'roomNames' => $roomNames,
            'qrImages' => $qrImages,
        ]));

        return $path;
    }
}
