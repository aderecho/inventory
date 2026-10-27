<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Inventory and Inspection Report of Unserviceable Semi-Expendable Property</title>

    <style>
        @page {
            size: A4 landscape;
            margin: 6mm 8mm;
        }

        * {
            box-sizing: border-box;
        }

        html,
        body {
            margin: 0;
            padding: 0;
            background: #fff;
        }

        body {
            font-family: "Times New Roman", Times, serif;
            color: #000;
            font-size: 7.5pt;
        }

        /* ========================= HEADER ========================= */

        .header-table {
            width: 100%;
            border-collapse: collapse;
        }

        .header-table td {
            border: none;
            vertical-align: middle;
            padding: 0;
        }

        .logo-cell {
            width: 20%;
            text-align: left;
        }

        .right-logo {
            width: 20%;
            text-align: right;
        }

        .logo-cell img,
        .right-logo img {
            width: 27mm;
            height: auto;
            object-fit: contain;
        }

        .header-center {
            width: 60%;
            text-align: center;
        }

        .republic {
            font-size: 8.5pt;
            margin-bottom: 0.5mm;
        }

        .university {
            font-size: 11pt;
            font-weight: bold;
            text-transform: uppercase;
        }

        .campus {
            font-size: 11pt;
            font-weight: bold;
            text-transform: uppercase;
        }

        .title {
            text-align: center;
            font-size: 10.5pt;
            font-weight: bold;
            margin: 2mm 0 0.5mm;
        }

        .as-of {
            text-align: center;
            font-size: 8.5pt;
            margin-bottom: 1.5mm;
        }

        /* ========================= OFFICER BLOCK ========================= */

        .officer-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 1.5mm;
        }

        .officer-table td {
            padding: 0 2mm;
            vertical-align: bottom;
        }

        .officer-table .left {
            text-align: left;
        }

        .officer-table .center {
            text-align: center;
        }

        /* Breathing room above and below the officer values, shared by all three
           so the accountable officer, designation, and station stay on one line. */
        .officer-table td.officer-value-cell {
            padding-top: 2mm;
            padding-bottom: 2.5mm;
        }

        /* Underline only as wide as the text. */
        .underlined-text {
            display: inline-block;
            font-size: 8.5pt;
            font-weight: bold;
            text-transform: uppercase;
            border-bottom: 1px solid #000;
            padding: 0 1mm;
            min-height: 4mm;
            line-height: 4mm;
        }

        .officer-caption {
            text-align: center;
            font-size: 7pt;
            font-style: italic;
        }

        .fund-cluster {
            text-align: right;
            font-size: 8.5pt;
            padding-bottom: 1mm !important;
        }

        .fill {
            display: inline-block;
            min-width: 40mm;
            border-bottom: 1px solid #000;
            padding: 0 1mm;
        }

        /* ========================= MAIN TABLE ========================= */

        .main-table {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
        }

        .main-table th,
        .main-table td {
            border: 0.5pt solid #000;
        }

        .main-table th {
            font-weight: bold;
            text-align: center;
            vertical-align: middle;
            padding: 0.6mm 0.3mm;
            font-size: 6.6pt;
            line-height: 1.15;
            overflow-wrap: anywhere;
            word-break: break-word;
        }

        .main-table td {
            padding: 0.7mm 0.8mm;
            font-size: 7pt;
            vertical-align: top;
        }

        .group-header {
            font-size: 7.5pt;
        }

        .col-number {
            font-size: 6pt;
            font-weight: normal;
        }

        .text-center {
            text-align: center;
        }

        .number {
            text-align: right;
            white-space: nowrap;
        }

        .particulars {
            text-align: left;
            line-height: 1.2;
        }

        .particulars .sub {
            display: block;
            font-size: 6.2pt;
        }

        .item-row td {
            height: 6mm;
        }

        /* ========================= CERTIFICATION AND SIGNATURES ========================= */

        .bottom-table {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
            border: 0.5pt solid #000;

            /* The top rule is drawn here instead of relying on the table above,
               so the block still reads as a closed box when it lands on a page
               of its own. When the two share a page the negative margin lands
               this rule exactly on that table's bottom rule, so the junction
               keeps the same weight as every other line on the form. */
            border-top: 0.5pt solid #000;
            margin-top: -0.5pt;
            break-inside: avoid;
            page-break-inside: avoid;
        }

        .bottom-table td {
            border: none;
            vertical-align: top;
            padding: 1.5mm;
            font-size: 7pt;
        }

        /* Articles per page, as on the printed form. */
        .main-table tbody.page-break {
            break-before: page;
            page-break-before: always;
        }

        /* Only the divider between Approved by and the I CERTIFY columns remains. */
        .bottom-table td.divider-left {
            border-left: 0.5pt solid #000;
        }

        .cert-text {
            text-align: justify;
            line-height: 1.3;
        }

        .sig-label {
            font-size: 7pt;
            text-align: left;
            padding-top: 2mm;
        }

        .sig-gap {
            height: 9mm;
        }

        .sig-underline {
            display: inline-block;
            min-width: 45mm;
            border-bottom: 1px solid #000;
            height: 3.5mm;
        }

        .sig-name {
            font-size: 8pt;
            font-weight: bold;
            text-transform: uppercase;
            border-bottom: 1px solid #000;
            display: inline-block;
            padding: 0 1mm;
        }

        .sig-caption {
            text-align: center;
            font-size: 6.4pt;
            font-style: italic;
            padding-top: 0.5mm;
        }

        .sig-center {
            text-align: center;
        }
    </style>
</head>

<body>

    @php
        $money = fn ($value) => $value === null ? '' : number_format((float) $value, 2);
    @endphp

    <!-- ================= HEADER ================= -->

    <table class="header-table">
        <tr>
            <td class="logo-cell">
                <img src="{{ public_path('images/UP-LOGO-PDF(2).png') }}" alt="UP Logo">
            </td>

            <td class="header-center">
                <div class="republic">Republic of the Philippines</div>
                <div class="university">University of the Philippines</div>
                <div class="campus">Cebu</div>
            </td>

            <td class="right-logo">
                <img src="{{ public_path('images/UPC-LOGO-PDF(1).png') }}" alt="UP Cebu Logo">
            </td>
        </tr>
    </table>

    <div class="title">
        INVENTORY AND INSPECTION REPORT OF UNSERVICEABLE SEMI-EXPENDABLE PROPERTY
    </div>

    <div class="as-of">As of {{ strtoupper($asAt ?: now()->format('F d, Y')) }}.</div>

    <!-- ================= OFFICER BLOCK ================= -->

    <table class="officer-table">
        <tr>
            <td style="width: 40%;" class="left">
                <strong>Entity Name:</strong> {{ $entityName ?: 'University of the Philippines Cebu' }}
            </td>
            <td style="width: 30%;"></td>
            <td style="width: 30%;" class="fund-cluster">
                <strong>Fund Cluster:</strong> <span class="fill">{{ $fundCluster }}</span>
            </td>
        </tr>
        <tr>
            <td class="center officer-value-cell">
                <span class="underlined-text">{{ $accountableOfficer }}</span>
            </td>
            <td class="center officer-value-cell">
                <span class="underlined-text">{{ $designation }}</span>
            </td>
            <td class="center officer-value-cell">
                <span class="underlined-text">{{ $station }}</span>
            </td>
        </tr>
        <tr>
            <td class="officer-caption">(Name of Accountable Officer)</td>
            <td class="officer-caption">(Designation)</td>
            <td class="officer-caption">(Station)</td>
        </tr>
    </table>

    <!-- ================= MAIN TABLE ================= -->

    <table class="main-table">
        <colgroup>
            <col style="width: 6.5%;">
            <col style="width: 18%;">
            <col style="width: 10.5%;">
            <col style="width: 3%;">
            <col style="width: 6%;">
            <col style="width: 7%;">
            <col style="width: 7%;">
            <col style="width: 5.5%;">
            <col style="width: 6%;">
            <col style="width: 8%;">
            <col style="width: 2.5%;">
            <col style="width: 2.5%;">
            <col style="width: 2.5%;">
            <col style="width: 3.5%;">
            <col style="width: 2.5%;">
            <col style="width: 3%;">
            <col style="width: 3%;">
            <col style="width: 3%;">
        </colgroup>

        <thead>
            <tr>
                <th colspan="10" class="group-header">INVENTORY</th>
                <th colspan="8" class="group-header">INSPECTION and DISPOSAL</th>
            </tr>
            <tr>
                <th rowspan="2">Date Acquired</th>
                <th rowspan="2">Particular / Articles</th>
                <th rowspan="2">Semi-expendable Property No.</th>
                <th rowspan="2">Qty</th>
                <th rowspan="2">Unit Measure</th>
                <th rowspan="2">Unit Cost</th>
                <th rowspan="2">Total Cost</th>
                <th rowspan="2">Accumulated Impairment Losses</th>
                <th rowspan="2">Carrying Amount</th>
                <th rowspan="2">Remarks</th>
                <th colspan="4">DISPOSAL</th>
                <th rowspan="2">Total</th>
                <th rowspan="2">Appraised Value</th>
                <th colspan="2">RECORD OF SALE</th>
            </tr>
            <tr>
                <th>Sale</th>
                <th>Transfer</th>
                <th>Destruction</th>
                <th>Others (Specify)</th>
                <th>OR No.</th>
                <th>Amount</th>
            </tr>
            <tr>
                {{-- Numbered as on the printed form: the Unit Measure column is
                     part of Qty's block and carries no number of its own. --}}
                @foreach (['-1', '-2', '-3', '-4', '', '-5', '-6', '-7', '-8', '-9', '-10', '-11', '-12', '-13', '-14', '-15', '-16', '-17'] as $columnNumber)
                    <th class="col-number">{{ $columnNumber }}</th>
                @endforeach
            </tr>
        </thead>

        @php
            // Articles per page, as on the printed form: the first page carries
            // the letterhead and the accountable-officer block, so it holds
            // fewer articles than the continuation pages.
            $firstPageArticles = 10;
            $continuationArticles = 16;

            $articlePages = [];
            $pending = collect($rows)->values();
            $pageSize = $firstPageArticles;

            while ($pending->isNotEmpty()) {
                $articlePages[] = $pending->splice(0, $pageSize);
                $pageSize = $continuationArticles;
            }
        @endphp

        @foreach ($articlePages as $articlePage)
            <tbody @class(['page-break' => ! $loop->first])>
            @foreach ($articlePage as $row)
                <tr class="item-row">
                    <td class="text-center">
                        {{ $row['date_acquired'] ? \Carbon\Carbon::parse($row['date_acquired'])->format('n/j/Y') : '' }}
                    </td>
                    <td class="particulars">
                        {{ $row['particulars'] ?: 'N/A' }}
                        @if (!empty($row['serial_number']))
                            <span class="sub">SN: {{ $row['serial_number'] }}</span>
                        @endif
                    </td>
                    <td class="text-center">{{ $row['property_no'] ?: 'N/A' }}</td>
                    <td class="text-center">{{ number_format((float) $row['qty'], 0) }}</td>
                    <td class="text-center">{{ $row['unit_measure'] }}</td>
                    <td class="number">{{ $money($row['unit_cost']) }}</td>
                    <td class="number">{{ $money($row['total_cost']) }}</td>
                    <td></td>
                    <td class="number">{{ $money($row['carrying_amount']) }}</td>
                    <td class="particulars">{{ $row['remarks'] }}</td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td></td>
                </tr>
            @endforeach
            </tbody>
        @endforeach
    </table>

    <!-- ================= CERTIFICATION AND SIGNATURES ================= -->

    <table class="bottom-table">
        <colgroup>
            <col style="width: 28%;">
            <col style="width: 22%;">
            <col style="width: 25%;">
            <col style="width: 25%;">
        </colgroup>

        <tr>
            <td colspan="2" class="cert-text">
                I HEREBY request inspection and disposition, pursuant to Section 70 of PD No. 1445, of the
                property enumerated above
            </td>
            <td class="cert-text divider-left">
                I CERTIFY that I have inspected each and every article enumerated in this report, and that
                the disposition made thereof was, in my judgement, the best for the public interest.
            </td>
            <td class="cert-text">
                I CERTIFY that I have witnessed the disposition of the articles enumerated on this report
                this <strong>{{ strtoupper($asAt ?: now()->format('F d, Y')) }}</strong>
            </td>
        </tr>

        <tr>
            <td class="sig-label">Requested by:</td>
            <td class="sig-label">Approved by:</td>
            <td class="divider-left"></td>
            <td></td>
        </tr>

        <tr>
            <td class="sig-center">
                <div class="sig-gap"></div>
                <span class="sig-name">{{ $requestedByName }}</span>
                <div class="sig-caption">(Signature over Printed Name of Accountable Officer)</div>
                <div class="sig-gap"></div>
                <span class="sig-name">{{ $requestedByDesignation }}</span>
                <div class="sig-caption">(Designation of Accountable Officer)</div>
            </td>
            <td class="sig-center">
                <div class="sig-gap"></div>
                <span class="sig-name">{{ $approvedByName }}</span>
                <div class="sig-caption">(Signature over Printed Name of Authorized Official)</div>
                <div class="sig-gap"></div>
                <span class="sig-name">{{ $approvedByDesignation }}</span>
                <div class="sig-caption">(Designation of Authorized Official)</div>
            </td>
            <td class="sig-center divider-left">
                <div class="sig-gap"></div>
                <span class="sig-underline"></span>
                <div class="sig-caption">(Signature over Printed Name of Inspection Officer)</div>
            </td>
            <td class="sig-center">
                <div class="sig-gap"></div>
                <span class="sig-underline"></span>
                <div class="sig-caption">(Signature over Printed Name of Witness)</div>
            </td>
        </tr>
    </table>

</body>

</html>
