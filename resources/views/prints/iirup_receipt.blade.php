<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<title>IIRUP Report</title>
<style>
    @page {
        size: A4 landscape;
        margin: 8mm;
    }

    * {
        box-sizing: border-box;
    }

    body {
        font-family: 'Times New Roman', Times, serif;
        font-size: 9px;
        color: #000;
        margin: 0;
        padding: 0;
        background-color: #fff;
    }

    .title {
        text-align: center;
        font-weight: bold;
        font-size: 13px;
        letter-spacing: 0.5px;
        text-transform: uppercase;
    }

    .subtitle {
        text-align: center;
        margin-bottom: 8px;
        font-size: 9.5px;
    }

    /* Meta Table Header */
    .meta-table {
        width: 100%;
        margin-bottom: 6px;
        border-collapse: collapse;
    }

    .meta-table td {
        vertical-align: bottom;
        padding: 1px 0;
    }

    .line-field {
        border-bottom: 1px solid #000;
        display: inline-block;
        min-width: 220px;
    }

    /* Officer Row Table */
    .officer-table {
        width: 100%;
        margin-bottom: 8px;
        border-collapse: collapse;
        text-align: center;
    }

    .officer-table td {
        width: 33.33%;
        vertical-align: top;
    }

    .officer-caption {
        font-size: 8.5px;
        font-style: italic;
    }

    /* Main Table Grid */
    table.main {
        width: 100%;
        border-collapse: collapse;
        margin-bottom: 0;
    }

    table.main th,
    table.main td {
        border: 1px solid #000;
        padding: 2px 1px;
        text-align: center;
        vertical-align: middle;
        font-size: 8.5px;
        line-height: 1.05;
    }

    table.main thead th {
        font-weight: bold;
    }

    .group-header {
        font-weight: bold;
        font-size: 9.5px;
        text-transform: uppercase;
    }

    .thick-right {
        border-right: 2px solid #000 !important;
    }

    .data-row td {
        height: 18px;
    }

    .data-row td.text-left {
        text-align: left;
    }

    .data-row td.number {
        text-align: right;
        padding-right: 3px;
    }

    /* Bottom Box Frame for Certifications & Signatures */
    .bottom-box {
        width: 100%;
        border-left: 2px solid #000;
        border-right: 2px solid #000;
        border-bottom: 2px solid #000;
        display: table;
        table-layout: fixed;
    }

    .bottom-col {
        display: table-cell;
        vertical-align: top;
        padding: 5px;
    }

    .col-left {
        width: 61.5%;
        border-right: 2px solid #000;
    }

    .col-mid {
        width: 23.5%;
        border-right: 1px solid #000;
    }

    .col-right {
        width: 15%;
    }

    .cert-text {
        text-align: justify;
        line-height: 1.15;
        font-size: 8.5px;
    }

    .sig-row {
        width: 100%;
        display: table;
        margin-top: 20px;
    }

    .sig-cell {
        display: table-cell;
        width: 50%;
        text-align: center;
        vertical-align: top;
        padding: 0 4px;
    }

    .sig-label {
        text-align: left;
        font-weight: normal;
        font-size: 8.5px;
    }

    .sig-name {
        font-weight: bold;
        text-decoration: underline;
        text-transform: uppercase;
        font-size: 9px;
    }

    .sig-caption {
        font-size: 8px;
        margin-top: 1px;
    }
</style>
</head>
<body>

    <!-- Header -->
    <div class="title">INVENTORY AND INSPECTION REPORT OF UNSERVICEABLE PROPERTY, PLANT, AND EQUIPMENT</div>
    <div class="subtitle">As of {{ $asAt ?? '' }}</div>

    <!-- Entity & Fund Cluster -->
    <table class="meta-table">
        <tr>
            <td style="width: 70%;">
                <strong>Entity Name:</strong>
                <span class="line-field">{{ $entityName ?? 'University of the Philippines Cebu' }}</span>
            </td>
            <td style="width: 30%; text-align: right;">
                <strong>Fund Cluster:</strong>
                <span style="border-bottom: 1px solid #000; display: inline-block; min-width: 120px;">{{ $fundCluster ?? '' }}</span>
            </td>
        </tr>
    </table>

    <!-- Officer Row -->
    <table class="officer-table">
        <tr>
            <td>
                <div>{{ $accountableOfficer ?? '' }}</div>
                <div class="officer-caption">(Name of Accountable Officer)</div>
            </td>
            <td>
                <div>{{ $designation ?? '' }}</div>
                <div class="officer-caption">(Designation)</div>
            </td>
            <td>
                <div>{{ $station ?? '' }}</div>
                <div class="officer-caption">(Station)</div>
            </td>
        </tr>
    </table>

    <!-- Main Table Grid -->
    <table class="main">
        <thead>
            <tr>
                <th colspan="10" class="group-header thick-right">INVENTORY</th>
                <th colspan="8" class="group-header">INSPECTION and DISPOSAL</th>
            </tr>
            <tr>
                <th rowspan="2" style="width: 5%;">Date Acquired</th>
                <th rowspan="2" style="width: 15%;">Particulars/ Articles</th>
                <th rowspan="2" style="width: 7%;">Property No.</th>
                <th rowspan="2" style="width: 3%;">Qty</th>
                <th rowspan="2" style="width: 5%;">Unit Cost</th>
                <th rowspan="2" style="width: 5.5%;">Total Cost</th>
                <th rowspan="2" style="width: 7%;">Accumulated Depreciation</th>
                <th rowspan="2" style="width: 7%;">Accumulated Impairment Losses</th>
                <th rowspan="2" style="width: 6%;">Carrying Amount</th>
                <th rowspan="2" style="width: 6%;" class="thick-right">Remarks</th>
                <th colspan="4">DISPOSAL</th>
                <th rowspan="2" style="width: 3.5%;">Total</th>
                <th rowspan="2" style="width: 5%;">Appraised Value</th>
                <th colspan="2">RECORD OF SALES</th>
            </tr>
            <tr>
                <th style="width: 3%;">Sale</th>
                <th style="width: 4%;">Transfer</th>
                <th style="width: 5%;">Destruction</th>
                <th style="width: 4.5%;">Others (Specify)</th>
                <th style="width: 4.5%;">OR No.</th>
                <th style="width: 5%;">Amount</th>
            </tr>
            <!-- 1 to 18 Column Indexing -->
            <tr>
                <th>1</th>
                <th>2</th>
                <th>3</th>
                <th>4</th>
                <th>5</th>
                <th>6</th>
                <th>7</th>
                <th>8</th>
                <th>9</th>
                <th class="thick-right">10</th>
                <th>11</th>
                <th>12</th>
                <th>13</th>
                <th>14</th>
                <th>15</th>
                <th>16</th>
                <th>17</th>
                <th>18</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($rows ?? [] as $row)
                <tr class="data-row">
                    <td>{{ $row['date_acquired'] ?? '' }}</td>
                    <td class="text-left">{{ $row['particulars'] ?? '' }}</td>
                    <td>{{ $row['property_no'] ?? '' }}</td>
                    <td>{{ isset($row['qty']) ? number_format($row['qty'], 0) : '' }}</td>
                    <td class="number">{{ isset($row['unit_cost']) ? number_format($row['unit_cost'], 2) : '' }}</td>
                    <td class="number">{{ isset($row['total_cost']) ? number_format($row['total_cost'], 2) : '' }}</td>
                    <td class="number">{{ isset($row['accumulated_depreciation']) ? number_format($row['accumulated_depreciation'], 2) : '0.00' }}</td>
                    <td class="number">{{ isset($row['impairment_loss']) ? number_format($row['impairment_loss'], 2) : '' }}</td>
                    <td class="number">{{ isset($row['carrying_amount']) ? number_format($row['carrying_amount'], 2) : '0.00' }}</td>
                    <td class="text-left thick-right">{{ $row['remarks'] ?? '' }}</td>
                    <td>{{ !empty($row['disposal_sale']) ? '✓' : '' }}</td>
                    <td>{{ !empty($row['disposal_transfer']) ? '✓' : '' }}</td>
                    <td>{{ !empty($row['disposal_destruction']) ? '✓' : '' }}</td>
                    <td>{{ $row['disposal_others'] ?? '' }}</td>
                    <td class="number">{{ isset($row['disposal_total']) ? number_format($row['disposal_total'], 2) : '' }}</td>
                    <td class="number">{{ isset($row['appraised_value']) ? number_format($row['appraised_value'], 2) : '' }}</td>
                    <td>{{ $row['or_no'] ?? '' }}</td>
                    <td class="number">{{ isset($row['sale_amount']) ? number_format($row['sale_amount'], 2) : '' }}</td>
                </tr>
            @empty
                @for ($i = 0; $i < 8; $i++)
                    <tr class="data-row">
                        <td>&nbsp;</td>
                        <td>&nbsp;</td>
                        <td>&nbsp;</td>
                        <td>&nbsp;</td>
                        <td>&nbsp;</td>
                        <td>&nbsp;</td>
                        <td class="number">0.00</td>
                        <td>&nbsp;</td>
                        <td class="number">0.00</td>
                        <td class="thick-right">&nbsp;</td>
                        <td>&nbsp;</td>
                        <td>&nbsp;</td>
                        <td>&nbsp;</td>
                        <td>&nbsp;</td>
                        <td>&nbsp;</td>
                        <td>&nbsp;</td>
                        <td>&nbsp;</td>
                        <td>&nbsp;</td>
                    </tr>
                @endfor
            @endforelse
        </tbody>
    </table>

    <!-- Bottom Outer Border Box -->
    <div class="bottom-box">
        <!-- Left Section: Request & Approval Signatures -->
        <div class="bottom-col col-left">
            <div class="cert-text">
                I HEREBY request inspection and disposition, pursuant to Section 79 of PD 1445, of the property enumerated above.
            </div>

            <div class="sig-row" style="margin-top: 20px;">
                <div class="sig-cell">
                    <div class="sig-label">Requested by:</div>
                    <div style="height: 20px;"></div>
                    <div class="sig-name">{{ $requestedByName ?? '' }}</div>
                    <div class="sig-caption">(Signature over Printed Name of Accountable Officer)</div>
                    <div style="height: 15px;"></div>
                    <div class="sig-caption">Designation of Accountable Officer({{ $requestedByDesignation ?? 'Designation of Accountable Officer' }})</div>
                </div>
                <div class="sig-cell">
                    <div class="sig-label">Approved by:</div>
                    <div style="height: 20px;"></div>
                    <div class="sig-name">{{ $approvedByName ?? 'GRACE L. MENDEZ' }}</div>
                    <div class="sig-caption">(Signature over Printed Name of Authorized Official)</div>
                    <div style="height: 5px;"></div>
                    <div class="sig-name" style="text-decoration: none; font-size: 8.5px;">{{ $approvedByDesignation ?? 'Head, SPMO' }}</div>
                    <div class="sig-caption">(Designation of Authorized Official)</div>
                </div>
            </div>
        </div>

        <!-- Middle Section: Inspection Officer Certificate -->
        <div class="bottom-col col-mid">
            <div class="cert-text">
                I CERTIFY that I have inspected each and every article enumerated in this report, and that the disposition made thereof was, in my judgement, the best for the public interest.
            </div>
            <div style="height: 45px;"></div>
            <div style="text-align: center;">
                <div style="border-bottom: 1px solid #000; width: 80%; margin: 0 auto;"></div>
                <div class="sig-caption">(Signature over Printed Name of Inspection Officer)</div>
            </div>
        </div>

        <!-- Right Section: Witness Certificate -->
        <div class="bottom-col col-right">
            <div class="cert-text">
                I CERTIFY that I have witnessed the disposition of the articles enumertd on this report this ______
            </div>
            <div style="height: 35px;"></div>
            <div style="text-align: center;">
                <div class="sig-name">{{ $witnessName ?? 'JESSIE BRIONES' }}</div>
                <div class="sig-caption">(Signature over Printed Name of Witness)</div>
            </div>
        </div>
    </div>

</body>
</html>