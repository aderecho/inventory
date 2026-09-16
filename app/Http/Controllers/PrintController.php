<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\PrintService;
use App\Services\PdfSigningService;

class PrintController extends Controller
{
    public function __construct(
        protected PrintService $printService,
        protected PdfSigningService $pdfSigningService,
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

    public function signReceipt(Request $request)
    {
        try {
            $request->validate([
                'ids' => ['required', 'array', 'min:1'],
                'ids.*' => ['integer'],
                'signature' => [
                    'required',
                    'string',
                    'regex:/^data:image\/png;base64,/',
                ],
            ]);

            /*
         * Generate the exact same receipt PDF
         * that your current print function generates.
         */
            $result = $this->printService
                ->generateAndStoreReceiptPdf(
                    $request->input('ids')
                );

            /*
         * Decode signature.
         */
            $signatureData = $request->input(
                'signature'
            );

            $signatureData = preg_replace(
                '#^data:image/\w+;base64,#i',
                '',
                $signatureData
            );

            $signatureData = str_replace(
                ' ',
                '+',
                $signatureData
            );

            $signatureBinary = base64_decode(
                $signatureData
            );

            if ($signatureBinary === false) {
                throw new \Exception(
                    'Invalid signature.'
                );
            }

            /*
         * Save signature.
         */
            $signatureFileName =
                'signature_' .
                auth()->id() .
                '_' .
                now()->format('Y_m_d_His') .
                '.png';

            $signaturePath =
                'signatures/' .
                $signatureFileName;

            \Storage::disk('public')->put(
                $signaturePath,
                $signatureBinary
            );

            /*
         * Create signed PDF path.
         */
            $signedFileName =
                pathinfo(
                    $result['file_name'],
                    PATHINFO_FILENAME
                ) .
                '_SIGNED.pdf';

            $signedPdfPath =
                'receipts/' .
                $signedFileName;

            /*
         * Stamp signature using FPDI.
         */
            $this->pdfSigningService->sign(
                originalPdfPath: $result['path'],
                signaturePath: $signaturePath,
                outputPdfPath: $signedPdfPath,

                /*
             * Temporary values.
             * We'll adjust these to your actual
             * signature area.
             */
                page: 1,
                x: 150,
                y: 240,
                width: 40,
                height: 20,
            );

            return response()->json([
                'message' => 'Receipt signed successfully.',
                'path' => $signedPdfPath,
                'file_name' => $signedFileName,
            ]);
        } catch (\Exception $e) {

            return response()->json([
                'message' => $e->getMessage(),
            ], 422);
        }
    }
}
