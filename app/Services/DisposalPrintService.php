<?php

namespace App\Services;

use Spatie\Browsershot\Browsershot;

class DisposalPrintService
{
    /**
     * Generate the IIRUP (Inventory and Inspection Report of
     * Unserviceable Semi-Expendable Property) PDF, landscape orientation.
     *
     * Expects an array shaped like:
     * [
     *   'entity_name' => ..., 'fund_cluster' => ..., 'as_at' => ...,
     *   'accountable_officer' => ..., 'designation' => ..., 'station' => ...,
     *   'items' => [
     *       ['date_acquired', 'particulars', 'property_no', 'qty',
     *        'unit_measure', 'unit_cost', 'impairment_loss', 'remarks'],
     *       ...
     *   ],
     *   'requested_by_name', 'requested_by_designation',
     *   'approved_by_name', 'approved_by_designation',
     *   'inspection_officer_name', 'witness_name',
     * ]
     */
    public function generateIirupPdf(array $payload): array
    {
        $items = collect($payload['items'] ?? []);

        if ($items->isEmpty()) {
            throw new \Exception('No items provided for the IIRUP report');
        }

        /*
        |--------------------------------------------------------------------------
        | Compute per-row totals (Total Cost, Carrying Amount)
        |--------------------------------------------------------------------------
        */

        $rows = $items->map(function ($item) {
            $qty = (float) ($item['qty'] ?? 0);
            $unitCost = (float) ($item['unit_cost'] ?? 0);
            $impairmentLoss = (float) ($item['impairment_loss'] ?? 0);

            $totalCost = $qty * $unitCost;
            $carryingAmount = $totalCost - $impairmentLoss;

            return [
                'date_acquired'    => $item['date_acquired'] ?? '',
                'particulars'      => $item['particulars'] ?? '',
                'property_no'      => $item['property_no'] ?? '',
                'qty'              => $qty,
                'unit_measure'     => $item['unit_measure'] ?? '',
                'unit_cost'        => $unitCost,
                'total_cost'       => $totalCost,
                'impairment_loss'  => $impairmentLoss,
                'carrying_amount'  => $carryingAmount,
                'remarks'          => $item['remarks'] ?? '',
            ];
        });

        $totalCostSum = $rows->sum('total_cost');
        $carryingAmountSum = $rows->sum('carrying_amount');

        /*
        |--------------------------------------------------------------------------
        | Render Blade HTML
        |--------------------------------------------------------------------------
        */

        $html = view('prints.iirup_receipt', [
            'entityName'         => $payload['entity_name'] ?? '',
            'fundCluster'        => $payload['fund_cluster'] ?? '',
            'asAt'               => $payload['as_at'] ?? '',
            'accountableOfficer' => $payload['accountable_officer'] ?? '',
            'designation'        => $payload['designation'] ?? '',
            'station'            => $payload['station'] ?? '',
            'rows'               => $rows,
            'totalCostSum'       => $totalCostSum,
            'carryingAmountSum'  => $carryingAmountSum,
            'requestedByName'         => $payload['requested_by_name'] ?? '',
            'requestedByDesignation'  => $payload['requested_by_designation'] ?? '',
            'approvedByName'          => $payload['approved_by_name'] ?? '',
            'approvedByDesignation'   => $payload['approved_by_designation'] ?? '',
            'inspectionOfficerName'   => $payload['inspection_officer_name'] ?? '',
            'witnessName'             => $payload['witness_name'] ?? '',
        ])->render();

        /*
        |--------------------------------------------------------------------------
        | Generate landscape PDF using Chromium
        |--------------------------------------------------------------------------
        */

        $pdf = Browsershot::html($html)
            ->format('A4')
            ->landscape()
            ->showBackground()
            ->emulateMedia('print')
            ->addChromiumArguments([
                'no-sandbox',
                'disable-setuid-sandbox',
            ])
            ->pdf();

        return [
            'pdf' => $pdf,
            'type' => 'IIRUP',
        ];
    }
}