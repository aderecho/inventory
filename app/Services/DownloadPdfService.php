<?php

namespace App\Services;

use App\Models\InventoryItem;
use Barryvdh\DomPDF\Facade\Pdf;
use Exception;
use Illuminate\Support\Facades\Log;
use Spatie\Browsershot\Browsershot;

class DownloadPdfService
{
    private RoomApiService $roomsApi;

    public function __construct(RoomApiService $roomsApi)
    {
        $this->roomsApi = $roomsApi;
    }

    public function generateQrPdf(array $ids): string
    {
        if (empty($ids)) {
            abort(422, 'No items selected.');
        }

        $items = InventoryItem::with([
            'latestAcknowledgementItem.accountablePerson',
            'supplier',
            'latestHistoryLocation',
        ])->whereIn('id', $ids)->get();

        if ($items->isEmpty()) {
            abort(422, 'No valid items found.');
        }

        $roomResult = $this->roomsApi->fetchRooms();
        $rooms = collect($roomResult['data'])->keyBy('id');

        $roomNames = [];

        foreach ($items as $item) {
            $roomId = $item->latestHistoryLocation?->room_id;
            $roomNames[$item->id] = $rooms[$roomId]['room_name'] ?? 'N/A';
        }

        try {
            return $this->generateWithBrowsershot($items, $roomNames);
        } catch (Exception $e) {
            Log::error('Browsershot PDF generation failed: ' . $e->getMessage());

            try {
                return $this->generateWithDomPdf($items, $roomNames);
            } catch (Exception $e2) {
                Log::error('DOMPDF fallback also failed: ' . $e2->getMessage());

                throw $e;
            }
        }
    }

    private function generateWithBrowsershot($items, array $roomNames): string
    {
        $html = view('prints.inventory_qr_browsershot', [
            'items' => $items,
            'roomNames' => $roomNames,
        ])->render();

        $filename = 'qr-codes-' . now()->timestamp . '-bs.pdf';
        $path = storage_path('app/' . $filename);

        $shot = Browsershot::html($html)
            ->showBackground()
            ->addChromiumArgument([
                'no-sandbox',
                'disable-setuid-sandbox',
            ])
            ->format('A4')
            ->printBackground();

        if (!empty(env('BROWSERSHOT_CHROME_PATH'))) {
            $shot->setChromePath(env('BROWSERSHOT_CHROME_PATH'));
        }

        if (!empty(env('BROWSERSHOT_NODE_PATH'))) {
            $shot->setNodeBinary(env('BROWSERSHOT_NODE_PATH'));
        }

        $shot->save($path);

        return $path;
    }

    private function generateWithDomPdf($items, array $roomNames): string
    {
        $pdf = Pdf::loadView('prints.inventory_qr_browsershot', [
            'items' => $items,
            'roomNames' => $roomNames,
        ]);

        $filename = 'qr-codes-' . now()->timestamp . '.pdf';
        $path = storage_path('app/' . $filename);

        file_put_contents($path, $pdf->output());

        return $path;
    }
}
