<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\PrintService;

class PrintController extends Controller
{
    public function __construct(
        protected PrintService $printService,
    ) {}

    public function printReceipt(Request $request)
    {
        try {
            $ids = $request->input('ids');

            $result = $this->printService->generateReceiptPdf($ids);

            $fileName = $result['type']
                . '_'
                . now()->format('Y_m_d_His')
                . '.pdf';

            return response($result['pdf'])
                ->header('Content-Type', 'application/pdf')
                ->header(
                    'Content-Disposition',
                    'attachment; filename="' . $fileName . '"'
                );

        } catch (\Exception $e) {
            return response()->json([
                'message' => $e->getMessage()
            ], 422);
        }
    }
}