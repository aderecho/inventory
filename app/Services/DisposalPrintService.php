<?php

namespace App\Services;

use App\Models\UserProfile;
use Illuminate\Support\Collection;
use setasign\Fpdi\Tcpdf\Fpdi;

class DisposalPrintService
{
    /**
     * The cut-off between the two IIRUP forms, judged on each item's own total
     * cost (qty x unit cost) and never on the total of the selection.
     */
    private const MIN_ITEM_TOTAL = 50000;

    /**
     * IIRUP for Property, Plant, and Equipment: every item is ₱50,000 and
     * above on its own.
     *
     * Expects an array shaped like:
     * [
     *   'entity_name' => ..., 'fund_cluster' => ..., 'as_at' => ...,
     *   'accountable_officer' => ..., 'designation' => ..., 'station' => ...,
     *   'items' => [
     *       ['date_acquired', 'particulars', 'property_no', 'serial_number',
     *        'qty', 'unit_measure', 'unit_cost', 'impairment_loss', 'remarks'],
     *       ...
     *   ],
     *   'requested_by_name', 'requested_by_designation',
     *   'approved_by_name', 'approved_by_designation',
     *   'inspection_officer_name', 'witness_name',
     * ]
     */
    public function generateIirupPdf(array $payload, ?callable $onProgress = null): array
    {
        return $this->generateIirup(
            $payload,
            'prints.iirup_50k_receipt',
            'IIRUP_50K_UP',
            requiresAtOrAboveThreshold: true,
            onProgress: $onProgress,
        );
    }

    /**
     * IIRUP for unserviceable semi-expendable property: every item is below
     * ₱50,000 on its own. Same form design as the ₱50,000 and above IIRUP,
     * with the semi-expendable title and inventory columns.
     */
    public function generateIirupBelowPdf(array $payload, ?callable $onProgress = null): array
    {
        return $this->generateIirup(
            $payload,
            'prints.iirup_50k_below_receipt',
            'IIRUP_50K_BELOW',
            requiresAtOrAboveThreshold: false,
            onProgress: $onProgress,
        );
    }

    /**
     * Both IIRUP forms in one file: each selected item goes to the form its own
     * total cost belongs on, so every blade keeps applying its own cut-off rule
     * and simply starts on a new page.
     */
    public function generateCombinedIirupPdf(array $payload, ?callable $onProgress = null): array
    {
        $report = function (int $percent, string $step) use ($onProgress): void {
            if ($onProgress) {
                $onProgress($percent, $step);
            }
        };

        $items = collect($payload['items'] ?? []);

        if ($items->isEmpty()) {
            throw new \Exception('No items provided for the IIRUP report');
        }

        [$above, $below] = $items->partition(function (array $item) {
            $total = (float) ($item['qty'] ?? 0) * (float) ($item['unit_cost'] ?? 0);

            return $total >= self::MIN_ITEM_TOTAL;
        });

        $documents = [];

        if ($above->isNotEmpty()) {
            $report(15, 'Preparing the ₱50,000 and above form');

            $documents[] = $this->generateIirupPdf(
                array_merge($payload, ['items' => $above->values()->all()])
            )['pdf'];
        }

        if ($below->isNotEmpty()) {
            $report(35, 'Preparing the below ₱50,000 form');

            $documents[] = $this->generateIirupBelowPdf(
                array_merge($payload, ['items' => $below->values()->all()])
            )['pdf'];
        }

        $report(55, 'Joining the forms into one file');

        $pdf = count($documents) === 1
            ? $documents[0]
            : $this->mergeDocuments($documents);

        $report(95, 'PDF rendered');

        return [
            'pdf' => $pdf,
            'type' => 'IIRUP',
        ];
    }

    /**
     * Render one of the two IIRUP forms, landscape orientation.
     */
    private function generateIirup(
        array $payload,
        string $view,
        string $type,
        bool $requiresAtOrAboveThreshold,
        ?callable $onProgress = null,
    ): array {
        /*
        |--------------------------------------------------------------------------
        | Progress reporting
        |--------------------------------------------------------------------------
        | Each checkpoint is a stage that has really finished. Percentages track
        | the pipeline, not the clock: the headless renderer below is one call
        | that accounts for nearly all of the wall time, so nothing is reported
        | while it runs and the bar lands on 97 the moment it returns.
        */
        $report = function (int $percent, string $step) use ($onProgress): void {
            if ($onProgress) {
                $onProgress($percent, $step);
            }
        };

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
            // Carrying amount prints at 10% of the total cost, as on the IIRUP form.
            $carryingAmount = round($totalCost * 0.10, 2);

            return [
                'date_acquired'    => $item['date_acquired'] ?? '',
                'particulars'      => $item['particulars'] ?? '',
                'property_no'      => $item['property_no'] ?? '',
                'serial_number'    => $item['serial_number'] ?? '',
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

        $report(4, 'Computed the row totals');

        /*
        |--------------------------------------------------------------------------
        | Every item has to clear ₱50,000 on the correct side
        |--------------------------------------------------------------------------
        | The cut-off is per item, not per selection: a ₱12,000 item never rides
        | along with ₱60,000 ones on the PPE form, and a ₱60,000 item never lands
        | on the semi-expendable form. A mixed selection is refused outright
        | rather than silently dropping the rows that belong on the other form.
        */

        $onWrongSide = $rows->filter(
            fn (array $row) => $requiresAtOrAboveThreshold
                ? $row['total_cost'] < self::MIN_ITEM_TOTAL
                : $row['total_cost'] >= self::MIN_ITEM_TOTAL
        );

        if ($onWrongSide->isNotEmpty()) {
            throw new \Exception(
                $this->thresholdMessage($onWrongSide, $rows, $requiresAtOrAboveThreshold)
            );
        }

        $report(8, 'Checked the ₱50,000 cut-off');

        $approver = $this->defaultApprover();

        $approvedByName = ($payload['approved_by_name'] ?? '') ?: $this->formatName($approver);
        // Same source as the accountable officer's designation.
        $approvedByDesignation = ($payload['approved_by_designation'] ?? '') ?: ($approver?->primary_unit_division_department ?? '');

        $report(12, 'Resolved the signatories');

        /*
        |--------------------------------------------------------------------------
        | Render Blade HTML
        |--------------------------------------------------------------------------
        */

        $data = [
            'entityName'         => $payload['entity_name'] ?? '',
            'fundCluster'        => $payload['fund_cluster'] ?? '',
            'asAt'               => ($payload['as_at'] ?? '') ?: now()->format('F d, Y'),
            'accountableOfficer' => $payload['accountable_officer'] ?? '',
            'designation'        => $payload['designation'] ?? '',
            'station'            => $payload['station'] ?? '',
            'rows'               => $rows,
            'totalCostSum'       => $totalCostSum,
            'carryingAmountSum'  => $carryingAmountSum,
            'requestedByName'         => ($payload['requested_by_name'] ?? '') ?: ($payload['accountable_officer'] ?? ''),
            'requestedByDesignation'  => ($payload['requested_by_designation'] ?? '') ?: ($payload['designation'] ?? ''),
            'approvedByName'          => $approvedByName,
            'approvedByDesignation'   => $approvedByDesignation,
            'inspectionOfficerName'   => $payload['inspection_officer_name'] ?? '',
            'witnessName'             => $payload['witness_name'] ?? '',
            'witnessDate'             => ($payload['witness_date'] ?? '') ?: now()->format('F d, Y'),
        ];

        $report(16, 'Built the report layout');

        /*
        |--------------------------------------------------------------------------
        | Generate the landscape PDF
        |--------------------------------------------------------------------------
        */

        $report(20, 'Rendering the PDF');

        $pdf = app(PdfRenderService::class)->render($view, $data);

        $report(97, 'PDF rendered');

        return [
            'pdf' => $pdf,
            'type' => $type,
        ];
    }

    /**
     * One document out of several: every source page keeps its own size and
     * layout, and the second form starts on a fresh page.
     */
    private function mergeDocuments(array $pdfs): string
    {
        $document = new Fpdi();
        $document->setPrintHeader(false);
        $document->setPrintFooter(false);
        $document->setAutoPageBreak(false);

        foreach ($pdfs as $index => $bytes) {
            $path = tempnam(sys_get_temp_dir(), 'iirup') . '.pdf';
            file_put_contents($path, $bytes);

            $pages = $document->setSourceFile($path);

            for ($page = 1; $page <= $pages; $page++) {
                $template = $document->importPage($page);
                $size = $document->getTemplateSize($template);

                $document->AddPage($size['orientation'], [$size['width'], $size['height']]);
                $document->useTemplate($template);
            }

            @unlink($path);
        }

        return $document->Output('', 'S');
    }

    /**
     * Why a selection was refused, naming the items that sit on the wrong side
     * of the ₱50,000 cut-off.
     */
    private function thresholdMessage(
        Collection $offending,
        Collection $rows,
        bool $requiresAtOrAboveThreshold,
    ): string {
        $threshold = '₱' . number_format(self::MIN_ITEM_TOTAL);

        $wanted = $requiresAtOrAboveThreshold ? "{$threshold} and above" : "below {$threshold}";
        $unwanted = $requiresAtOrAboveThreshold ? "below {$threshold}" : "{$threshold} and above";
        $otherForm = $requiresAtOrAboveThreshold
            ? "the below {$threshold} form"
            : "the {$threshold} and above form";

        $count = $offending->count();

        if ($count === $rows->count()) {
            return "Every selected item is {$unwanted}. This IIRUP form is for items "
                . "{$wanted} on their own; use {$otherForm} instead.";
        }

        $names = $offending
            ->pluck('particulars')
            ->filter()
            ->take(3)
            ->implode(', ');

        $extra = $count - 3;

        if ($extra > 0) {
            $names .= " and {$extra} more";
        }

        return $count . ' of the selected items ' . ($count === 1 ? 'is' : 'are')
            . " {$unwanted}" . ($names !== '' ? " ({$names})" : '')
            . ". This IIRUP form needs every item to be {$wanted} on its own, "
            . 'so remove ' . ($count === 1 ? 'that item' : 'those items') . ' first.';
    }

    /**
     * The authorized official who approves disposal papers: the same SPMO
     * signatory used for acknowledgements, falling back to the first SPMO profile.
     */
    private function defaultApprover(): ?UserProfile
    {
        $spmo = UserProfile::where('primary_unit_division_department', 'like', '%SUPPLY AND PROPERTY MANAGEMENT%');

        return (clone $spmo)
            ->where('last_name', 'like', '%Mendez%')
            ->orderBy('id')
            ->first()
            ?? $spmo->orderBy('id')->first();
    }

    /** "Grace Lenarez Mendez" prints as "Grace L. Mendez", like the other receipts. */
    private function formatName(?UserProfile $profile): string
    {
        if (! $profile) {
            return '';
        }

        return trim(collect([
            $profile->first_name,
            $profile->middle_name ? mb_substr($profile->middle_name, 0, 1) . '.' : null,
            $profile->last_name,
        ])->filter()->implode(' '));
    }
}
