<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\DisposalPrintService;

class DisposalPrintController extends Controller
{
    public function __construct(
        protected DisposalPrintService $disposalPrintService,
    ) {}

    /**
     * Generate the IIRUP (Inventory and Inspection Report of
     * Unserviceable Semi-Expendable Property) landscape PDF.
     */
    public function printIirup(Request $request)
    {
        try {
            $validated = $request->validate([
                'entity_name'          => 'nullable|string|max:255',
                'fund_cluster'         => 'nullable|string|max:100',
                'as_at'                => 'nullable|string|max:100',
                'accountable_officer'  => 'nullable|string|max:255',
                'designation'          => 'nullable|string|max:255',
                'station'              => 'nullable|string|max:255',

                'items'                          => 'required|array|min:1',
                'items.*.date_acquired'          => 'nullable|string|max:50',
                'items.*.particulars'             => 'nullable|string|max:255',
                'items.*.property_no'             => 'nullable|string|max:100',
                'items.*.qty'                      => 'nullable|numeric',
                'items.*.unit_measure'            => 'nullable|string|max:50',
                'items.*.unit_cost'               => 'nullable|numeric',
                'items.*.impairment_loss'         => 'nullable|numeric',
                'items.*.remarks'                 => 'nullable|string|max:255',

                'requested_by_name'        => 'nullable|string|max:255',
                'requested_by_designation' => 'nullable|string|max:255',
                'approved_by_name'         => 'nullable|string|max:255',
                'approved_by_designation'  => 'nullable|string|max:255',
                'inspection_officer_name'  => 'nullable|string|max:255',
                'witness_name'             => 'nullable|string|max:255',
            ]);

            $result = $this->disposalPrintService->generateIirupPdf($validated);

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

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'message' => 'Invalid input.',
                'errors'  => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'message' => $e->getMessage()
            ], 422);
        }
    }
}