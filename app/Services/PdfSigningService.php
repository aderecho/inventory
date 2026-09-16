<?php

namespace App\Services;

use Illuminate\Support\Facades\Storage;
use RuntimeException;
use setasign\Fpdi\Tcpdf\Fpdi;

class PdfSigningService
{
    public function sign(
        string $originalPdfPath,
        string $signaturePath,
        string $outputPdfPath,
        int $page = 1,
        float $x = 150,
        float $y = 240,
        float $width = 40,
        float $height = 20,
    ): string {
        $originalPdf = Storage::disk('public')->path(
            $originalPdfPath
        );

        $signature = Storage::disk('public')->path(
            $signaturePath
        );

        $outputPdf = Storage::disk('public')->path(
            $outputPdfPath
        );

        if (!file_exists($originalPdf)) {
            throw new RuntimeException(
                "Original PDF not found."
            );
        }

        if (!file_exists($signature)) {
            throw new RuntimeException(
                "Signature image not found."
            );
        }

        $pdf = new Fpdi();

        $pageCount = $pdf->setSourceFile(
            $originalPdf
        );

        for ($pageNumber = 1; $pageNumber <= $pageCount; $pageNumber++) {

            $templateId = $pdf->importPage(
                $pageNumber
            );

            $size = $pdf->getTemplateSize(
                $templateId
            );

            $orientation = $size['width'] > $size['height']
                ? 'L'
                : 'P';

            $pdf->AddPage(
                $orientation,
                [
                    $size['width'],
                    $size['height'],
                ]
            );

            $pdf->useTemplate(
                $templateId
            );

            /*
             * Add signature only to selected page.
             */
            if ($pageNumber === $page) {
                $pdf->Image(
                    $signature,
                    $x,
                    $y,
                    $width,
                    $height,
                    'PNG'
                );
            }
        }

        /*
         * Make sure the directory exists.
         */
        $directory = dirname($outputPdf);

        if (!is_dir($directory)) {
            mkdir(
                $directory,
                0755,
                true
            );
        }

        $pdf->Output(
            $outputPdf,
            'F'
        );

        return $outputPdfPath;
    }
}