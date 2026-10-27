<?php

namespace App\Services;

use Barryvdh\DomPDF\Facade\Pdf;

/**
 * Every printed form renders through DomPDF, which runs inside PHP and needs no
 * browser on the server. Page size and margins come from each template's @page rule.
 */
class PdfRenderService
{
    public function render(string $view, array $data): string
    {
        return Pdf::loadView($view, $data)->output();
    }
}
