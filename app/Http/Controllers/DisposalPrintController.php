<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\DisposalPrintService;
use App\Http\Controllers\Concerns\StreamsProgress;
use Illuminate\Validation\ValidationException;

class DisposalPrintController extends Controller
{
    use StreamsProgress;

    public function __construct(
        protected DisposalPrintService $disposalPrintService,
    ) {}

    /**
     * IIRUP (Inventory and Inspection Report of Unserviceable Property, Plant,
     * and Equipment) for items ₱50,000 and above, landscape PDF.
     */
    public function printIirup(Request $request)
    {
        return $this->printIirupForm(
            $request,
            fn (array $payload) => $this->disposalPrintService->generateIirupPdf($payload),
        );
    }

    /**
     * IIRUP (Inventory and Inspection Report of Unserviceable Semi-Expendable
     * Property) for items below ₱50,000, landscape PDF.
     */
    public function printIirupBelow(Request $request)
    {
        return $this->printIirupForm(
            $request,
            fn (array $payload) => $this->disposalPrintService->generateIirupBelowPdf($payload),
        );
    }

    /**
     * Same two forms, reported as a stream of ndjson progress events followed
     * by the finished PDF, so the download card can show what the server is
     * really doing instead of a made-up timer.
     */
    public function streamIirup(Request $request)
    {
        return $this->streamProgress($request, $this->rules(), function (array $payload, ?callable $onProgress) {
            $result = $this->disposalPrintService->generateIirupPdf($payload, $onProgress);

            return [
                'filename' => $this->fileName($result['type']),
                'contents' => $result['pdf'],
            ];
        });
    }

    public function streamIirupBelow(Request $request)
    {
        return $this->streamProgress($request, $this->rules(), function (array $payload, ?callable $onProgress) {
            $result = $this->disposalPrintService->generateIirupBelowPdf($payload, $onProgress);

            return [
                'filename' => $this->fileName($result['type']),
                'contents' => $result['pdf'],
            ];
        });
    }

    /**
     * Both IIRUP forms at once: the items above the cut-off on their form and
     * the rest on theirs, each starting on its own page in one file.
     */
    public function streamCombinedIirup(Request $request)
    {
        return $this->streamProgress($request, $this->rules(), function (array $payload, ?callable $onProgress) {
            $result = $this->disposalPrintService->generateCombinedIirupPdf($payload, $onProgress);

            return [
                'filename' => $this->fileName($result['type']),
                'contents' => $result['pdf'],
            ];
        });
    }

    /**
     * Both IIRUP forms take the same payload; only the generator differs.
     */
    private function printIirupForm(Request $request, callable $generate)
    {
        try {
            $validated = $request->validate($this->rules());

            $result = $generate($validated);

            $fileName = $this->fileName($result['type']);

            return response($result['pdf'])
                ->header('Content-Type', 'application/pdf')
                ->header(
                    'Content-Disposition',
                    'attachment; filename="' . $fileName . '"'
                );

        } catch (ValidationException $e) {
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

    /**
     * Both IIRUP forms take the same payload.
     */
    private function rules(): array
    {
        return [
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
            'items.*.serial_number'           => 'nullable|string|max:255',
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
            'witness_date'             => 'nullable|string|max:100',
        ];
    }

    private function fileName(string $type): string
    {
        return $type . '_' . now()->format('Y_m_d_His') . '.pdf';
    }
}
