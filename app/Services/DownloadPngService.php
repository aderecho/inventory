<?php

namespace App\Services;

use SimpleSoftwareIO\QrCode\Facades\QrCode;
use App\Models\InventoryItem;
use ZipArchive;

class DownloadPngService
{
    private string $fontRegular;
    private string $fontBold;

    public function __construct()
    {
        $this->fontRegular = public_path('fonts/DejaVuSans.ttf');
        $this->fontBold    = public_path('fonts/DejaVuSans-Bold.ttf');
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

        $fields = [
            'Date Acquired'      => $item->date_acquired ?? 'N/A',
            'Cost'               => 'P' . number_format($item->unit_cost, 2),
            'Product'            => $item->item_name,
            'Serial Model#'      => $item->serial_number ?? 'N/A',
            'Accountable Person' => $item->latestAcknowledgementItem?->accountablePerson?->full_name ?? 'N/A',
            'Supplier'           => $item->supplier?->supplier_name ?? 'N/A',
            'Location'           => 'N/A',
        ];

        // Calculate total height
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
        $width         = $qrX + $qrSize + 30; // 870

        $img   = imagecreatetruecolor($width * $scale, $height * $scale);
        $white = imagecolorallocate($img, 255, 255, 255);
        $black = imagecolorallocate($img, 0, 0, 0);

        imagefilledrectangle($img, 0, 0, $width * $scale, $height * $scale, $white);

        // Logo (top left)
        $logoPath = public_path('images/uplogo-1.png');
        if (file_exists($logoPath)) {
            $logo = imagecreatefrompng($logoPath);
            imagecopyresampled(
                $img,
                $logo,
                $leftX * $scale,
                20 * $scale,
                0,
                0,
                60 * $scale,
                60 * $scale,
                imagesx($logo),
                imagesy($logo)
            );
            imagedestroy($logo);
        }

        // Header (beside logo)
        imagettftext(
            $img,
            14 * $scale,
            0,
            90 * $scale,
            42 * $scale,
            $black,
            $this->fontBold,
            'University of the Philippines CEBU'
        );
        imagettftext(
            $img,
            11 * $scale,
            0,
            90 * $scale,
            64 * $scale,
            $black,
            $this->fontBold,
            'PROPERTY INVENTORY STICKER'
        );

        // Property Code label + value on same line ✅
        imagettftext(
            $img,
            14 * $scale,
            0,
            $leftX * $scale,
            125 * $scale,
            $black,
            $this->fontBold,
            'Property Code:'
        );
        imagettftext(
            $img,
            28 * $scale,
            0,
            210 * $scale,
            125 * $scale,
            $black,
            $this->fontBold,
            $item->property_number
        );

        // Fields
        $y = $startY;
        foreach ($fields as $label => $value) {
            imagettftext(
                $img,
                10 * $scale,
                0,
                $leftX * $scale,
                $y * $scale,
                $black,
                $this->fontBold,
                $label . ':'
            );

            $lines = $this->wrapText($value, 10 * $scale, $this->fontRegular, $maxWidth);
            foreach ($lines as $index => $line) {
                imagettftext(
                    $img,
                    10 * $scale,
                    0,
                    $valueX * $scale,
                    ($y + ($index * $lineHeight)) * $scale,
                    $black,
                    $this->fontRegular,
                    $line
                );
            }

            $y += count($lines) * $lineHeight;
        }

        // QR Code — vertically centered ✅
        $qrCenterY = $startY - 90;
        $qrData    = QrCode::format('png')->size($qrSize * $scale)->margin(2)->generate($item->property_number);
        $qrImg     = imagecreatefromstring($qrData);
        imagecopyresampled(
            $img,
            $qrImg,
            $qrX * $scale,
            (int)($qrCenterY * $scale),
            0,
            0,
            $qrSize * $scale,
            $qrSize * $scale,
            imagesx($qrImg),
            imagesy($qrImg)
        );
        imagedestroy($qrImg);

        // Output
        ob_start();
        imagepng($img);
        $png = ob_get_clean();
        imagedestroy($img);

        return $png;
    }

    private function addItemQrToZip(ZipArchive $zip, InventoryItem $item): void
    {
        try {
            $png = $this->generateItemQrPng($item);

            $filename = preg_replace(
                '/[^A-Za-z0-9\-_]/',
                '_',
                $item->property_number
            ) . '.png';

            $zip->addFromString($filename, $png);
        } catch (\Exception $e) {
            // log if needed
        }
    }

    public function generateQrZip(array $ids): string
    {
        if (empty($ids)) {
            abort(422, 'No items selected.');
        }

        $zipPath = storage_path('app/qr-codes-' . now()->timestamp . '.zip');
        $zip     = new ZipArchive();

        if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            abort(500, 'Unable to create ZIP file.');
        }

        $errors = [];

        foreach ($ids as $id) {
            $item = InventoryItem::with('latestAcknowledgementItem.accountablePerson', 'supplier')->find($id);

            if (!$item || !$item->latestAcknowledgementItem || !$item->latestAcknowledgementItem->accountablePerson) {
                $errors[] = $item->property_number ?? $item->item_name ?? $id;
                continue;
            }

            $this->addItemQrToZip($zip, $item);
        }

        $zip->close();

        if (!empty($errors)) {
            abort(422, 'Some items have no accountable person assigned: ' . implode(', ', $errors));
        }

        return $zipPath;
    }

    public function generateQrPng(int $id): string
    {
        $item = InventoryItem::with('latestAcknowledgementItem.accountablePerson', 'supplier')->find($id);

        if (!$item || !$item->latestAcknowledgementItem || !$item->latestAcknowledgementItem->accountablePerson) {
            abort(422, 'This item has no accountable person assigned: ' . ($item->property_number ?? $item->item_name ?? $id));
        }

        try {
            $png = $this->generateItemQrPng($item);

            $filename = preg_replace(
                '/[^A-Za-z0-9\-_]/',
                '_',
                $item->property_number
            ) . '-' . now()->timestamp . '.png';

            $pngPath = storage_path('app/' . $filename);
            file_put_contents($pngPath, $png);

            return $pngPath;
        } catch (\Exception $e) {
            abort(500, 'Failed to generate QR code.');
        }
    }
}
