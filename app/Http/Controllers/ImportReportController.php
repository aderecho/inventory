<?php

namespace App\Http\Controllers;

use App\Http\Requests\ImportReportDownloadRequest;
use App\Services\ImportReportExportService;
use Illuminate\Support\Str;

class ImportReportController extends Controller
{
    public function download(
        ImportReportDownloadRequest $request,
        ImportReportExportService $exporter,
    ) {
        $format = $request->input('format');
        $filename = Str::slug($request->input('filename', 'import-report')) ?: 'import-report';
        $path = $exporter->export($request->input('report'), $format);
        $contentType = $format === 'csv'
            ? 'text/csv; charset=UTF-8'
            : 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet';

        return response()
            ->download($path, "{$filename}.{$format}", ['Content-Type' => $contentType])
            ->deleteFileAfterSend(true);
    }
}
