<?php

namespace App\Services;

use BaconQrCode\Common\ErrorCorrectionLevel;
use BaconQrCode\Encoder\Encoder;

/**
 * QR rendering on GD. simple-qrcode renders PNG through the imagick extension,
 * which this project does not install, so the encoded matrix is drawn directly
 * and handed to callers as a GD image, raw PNG bytes, or a data URI.
 */
class QrImageService
{
    public function image(string $content, int $targetSize, int $margin = 2): \GdImage
    {
        $matrix = Encoder::encode($content, ErrorCorrectionLevel::M())->getMatrix();

        $modules = $matrix->getWidth() + ($margin * 2);
        $moduleSize = max(1, (int) floor($targetSize / $modules));
        $size = $modules * $moduleSize;

        $image = imagecreatetruecolor($size, $size);
        $white = imagecolorallocate($image, 255, 255, 255);
        $black = imagecolorallocate($image, 0, 0, 0);
        imagefilledrectangle($image, 0, 0, $size, $size, $white);

        for ($y = 0; $y < $matrix->getHeight(); $y++) {
            for ($x = 0; $x < $matrix->getWidth(); $x++) {
                if ($matrix->get($x, $y) === 1) {
                    $left = ($x + $margin) * $moduleSize;
                    $top = ($y + $margin) * $moduleSize;

                    imagefilledrectangle(
                        $image,
                        $left,
                        $top,
                        $left + $moduleSize - 1,
                        $top + $moduleSize - 1,
                        $black
                    );
                }
            }
        }

        return $image;
    }

    public function pngData(string $content, int $targetSize, int $margin = 2): string
    {
        $image = $this->image($content, $targetSize, $margin);

        ob_start();
        imagepng($image);
        $png = ob_get_clean();
        imagedestroy($image);

        return $png;
    }

    public function dataUri(string $content, int $targetSize, int $margin = 2): string
    {
        return 'data:image/png;base64,' . base64_encode(
            $this->pngData($content, $targetSize, $margin)
        );
    }
}
