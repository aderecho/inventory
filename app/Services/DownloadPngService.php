<?php

namespace App\Services;

use App\Models\InventoryItem;
use App\Services\RoomApiService;
use ZipArchive;

class DownloadPngService
{
    private string $fontRegular;
    private string $fontBold;
    private RoomApiService $roomsApi;
    private QrImageService $qrImages;

    public function __construct(RoomApiService $roomsApi, QrImageService $qrImages)
    {
        $this->fontRegular = public_path('fonts/DejaVuSans.ttf');
        $this->fontBold    = public_path('fonts/DejaVuSans-Bold.ttf');
        $this->roomsApi    = $roomsApi;
        $this->qrImages    = $qrImages;
    }

    private function wrapText(string $text, int $fontSize, string $font, int $maxWidth): array
    {
        $words = explode(' ', $text);
        $lines = [];
        $currentLine = '';

        foreach ($words as $word) {
            $testLine = $currentLine === '' ? $word : $currentLine . ' ' . $word;
            $bbox = imagettfbbox($fontSize, 0, $font, $testLine);
            $lineWidth = $bbox[2] - $bbox[0];

            if ($lineWidth > $maxWidth && $currentLine !== '') {
                $lines[] = $currentLine;
                $currentLine = $word;
            } else {
                $currentLine = $testLine;
            }
        }

        if ($currentLine !== '') {
            $lines[] = $currentLine;
        }

        return $lines;
    }

    private function generateItemQrPng(InventoryItem $item): string
    {
        $scale      = 2;
        $lineHeight = 28;
        $leftX      = 20;
        $valueX     = 210;
        $qrSize     = 220;
        $qrX        = 620;
        $maxWidth   = ($qrX - $valueX - 20) * $scale;

        $roomName = 'N/A';
        if ($item->latestHistoryLocation) {
            $roomId = $item->latestHistoryLocation->room_id;
            $roomResult = $this->roomsApi->fetchRooms();
            $room = collect($roomResult['data'])->firstWhere('id', $roomId);
            $roomName = $room['room_name'] ?? 'N/A';
        }

        $fields = [
            'Date Acquired'      => $item->date_acquired ?? 'N/A',
            'Cost'               => number_format($item->unit_cost, 2),
            'Product'            => $item->item_name,
            'Serial Model#'      => $item->serial_number ?? 'N/A',
            'Accountable Person' => $item->latestAcknowledgementItem?->accountablePerson?->full_name ?? 'N/A',
            'Supplier'           => $item->supplier?->supplier_name ?? 'N/A',
            'Location'           => $roomName,
        ];

        $totalExtraLines = 0;
        foreach ($fields as $value) {
            $lines = $this->wrapText($value, 10 * $scale, $this->fontRegular, $maxWidth);
            $totalExtraLines += count($lines) - 1;
        }

        $startY        = 155;
        $fieldsHeight  = (count($fields) + $totalExtraLines) * $lineHeight;
        $contentHeight = $startY + $fieldsHeight + 40;
        $minHeight     = 420;
        $height        = max($minHeight, $contentHeight);
        $width         = $qrX + $qrSize + 30;

        $img   = imagecreatetruecolor($width * $scale, $height * $scale);
        $white = imagecolorallocate($img, 255, 255, 255);
        $black = imagecolorallocate($img, 0, 0, 0);

        imagefilledrectangle($img, 0, 0, $width * $scale, $height * $scale, $white);

        $logoPath = public_path('images/uplogo-1.png');
        if (file_exists($logoPath)) {
            $logo = imagecreatefrompng($logoPath);
            imagecopyresampled($img, $logo, $leftX * $scale, 20 * $scale, 0, 0, 60 * $scale, 60 * $scale, imagesx($logo), imagesy($logo));
            imagedestroy($logo);
        }

        imagettftext($img, 14 * $scale, 0, 90 * $scale, 42 * $scale, $black, $this->fontBold, 'University of the Philippines CEBU');
        imagettftext($img, 11 * $scale, 0, 90 * $scale, 64 * $scale, $black, $this->fontBold, 'PROPERTY INVENTORY STICKER');
        imagettftext($img, 14 * $scale, 0, $leftX * $scale, 125 * $scale, $black, $this->fontBold, 'Property Code:');
        imagettftext($img, 28 * $scale, 0, 210 * $scale, 125 * $scale, $black, $this->fontBold, $item->property_number);

        $y = $startY;
        foreach ($fields as $label => $value) {
            imagettftext($img, 10 * $scale, 0, $leftX * $scale, $y * $scale, $black, $this->fontBold, $label . ':');

            $lines = $this->wrapText($value, 10 * $scale, $this->fontRegular, $maxWidth);
            foreach ($lines as $index => $line) {
                imagettftext($img, 10 * $scale, 0, $valueX * $scale, ($y + ($index * $lineHeight)) * $scale, $black, $this->fontRegular, $line);
            }

            $y += count($lines) * $lineHeight;
        }

        $qrCenterY = $startY - 90;
        $qrImg     = $this->qrImages->image($item->property_number, $qrSize * $scale);
        imagecopyresampled($img, $qrImg, $qrX * $scale, (int)($qrCenterY * $scale), 0, 0, $qrSize * $scale, $qrSize * $scale, imagesx($qrImg), imagesy($qrImg));
        imagedestroy($qrImg);

        ob_start();
        imagepng($img);
        $png = ob_get_clean();
        imagedestroy($img);

        return $png;
    }

    private function addItemQrToZip(ZipArchive $zip, InventoryItem $item): bool
    {
        try {
            $png = $this->generateItemQrPng($item);

            $filename = preg_replace('/[^A-Za-z0-9\-_]/', '_', $item->property_number) . '.png';

            $zip->addFromString($filename, $png);

            return true;
        } catch (\Throwable $e) {
            report($e);

            return false;
        }
    }

    public function generateQrZip(array $ids, ?callable $onProgress = null): string
    {
        $report = function (int $percent, string $step) use ($onProgress): void {
            if ($onProgress) {
                $onProgress($percent, $step);
            }
        };

        if (empty($ids)) {
            abort(422, 'No items selected.');
        }

        $zipPath = storage_path('app/qr-codes-' . now()->timestamp . '.zip');
        $zip     = new ZipArchive();

        if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            abort(500, 'Unable to create ZIP file.');
        }

        $report(3, 'Loading the selected items');

        $total = count($ids);
        $generated = 0;
        $checked = 0;

        foreach ($ids as $id) {
            $item = InventoryItem::with([
                'latestAcknowledgementItem.accountablePerson',
                'supplier',
                'latestHistoryLocation',
            ])->find($id);

            if ($item && $this->addItemQrToZip($zip, $item)) {
                $generated++;
            }

            $checked++;
            $report(3 + (int) round($checked / $total * 87), "Rendered sticker {$checked} of {$total}");
        }

        $report(95, 'Packing the ZIP file');

        $zip->close();

        if ($generated === 0) {
            abort(422, 'No stickers could be generated for the selected items.');
        }

        return $zipPath;
    }

    public function generateQrPng(int $id, ?callable $onProgress = null): string
    {
        $report = function (int $percent, string $step) use ($onProgress): void {
            if ($onProgress) {
                $onProgress($percent, $step);
            }
        };

        $report(10, 'Loading the item');

        $item = InventoryItem::with([
            'latestAcknowledgementItem.accountablePerson',
            'supplier',
            'latestHistoryLocation',
        ])->find($id);

        if (! $item) {
            abort(404, 'Item not found.');
        }

        try {
            $report(40, 'Rendering the sticker');

            $png = $this->generateItemQrPng($item);

            $report(90, 'Preparing the file');

            $filename = preg_replace('/[^A-Za-z0-9\-_]/', '_', $item->property_number) . '-' . now()->timestamp . '.png';

            $pngPath = storage_path('app/' . $filename);
            file_put_contents($pngPath, $png);

            return $pngPath;
        } catch (\Throwable $e) {
            report($e);

            abort(500, 'Failed to generate the QR sticker.');
        }
    }
}
