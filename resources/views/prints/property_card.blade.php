<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Property Card — {{ $card['item']['property_number'] ?: $card['item']['item_name'] }}</title>

    <style>
        @page {
            size: Letter landscape;
            margin: 8mm 10mm;
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
            font-size: 8pt;
        }

        .header-table {
            width: 100%;
            border-collapse: collapse;
        }

        .header-table td {
            border: none;
            padding: 0;
            vertical-align: middle;
        }

        .logo-cell {
            width: 18%;
            text-align: left;
        }

        .logo-cell.right {
            text-align: right;
        }

        .logo-cell img {
            width: 19mm;
            height: auto;
        }

        .header-center {
            width: 64%;
            text-align: center;
        }

        .republic {
            font-size: 8.5pt;
        }

        .university,
        .campus {
            font-size: 11pt;
            font-weight: bold;
            text-transform: uppercase;
        }

        .title {
            font-size: 14pt;
            font-weight: bold;
            letter-spacing: 0.6pt;
            margin: 2.5mm 0 1mm;
            text-align: center;
        }

        .office-line {
            margin-top: 1.5mm;
            font-size: 8pt;
            line-height: 1.35;
        }

        .office-line strong {
            font-size: 8.5pt;
        }

        .fields {
            width: 100%;
            border-collapse: collapse;
            margin-top: 3mm;
            font-size: 8.5pt;
        }

        .fields td {
            padding: 0 2mm 2mm 0;
            vertical-align: bottom;
        }

        .label {
            font-weight: bold;
        }

        .fill {
            display: inline-block;
            min-width: 55mm;
            border-bottom: 1px solid #000;
            padding: 0 1mm;
            font-weight: bold;
            text-transform: uppercase;
        }

        .desc {
            width: 100%;
            border-collapse: collapse;
            margin-top: 1mm;
        }

        .desc td {
            border: 0.6pt solid #000;
            padding: 1.5mm 2mm;
            vertical-align: top;
            font-size: 8pt;
        }

        .desc .body {
            margin-top: 1.5mm;
            min-height: 12mm;
            line-height: 1.35;
        }

        .desc .property-number {
            display: inline-block;
            margin-top: 2mm;
            font-weight: bold;
            font-size: 9pt;
            letter-spacing: 0.2pt;
            border-bottom: 1px solid #000;
            padding: 0 4mm 0.5mm;
        }

        table.main {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
            margin-top: 1.5mm;
        }

        table.main th,
        table.main td {
            border: 0.6pt solid #000;
        }

        table.main th {
            font-size: 7.4pt;
            font-weight: bold;
            text-align: center;
            vertical-align: middle;
            padding: 0.9mm 0.6mm;
            line-height: 1.15;
        }

        table.main td {
            font-size: 7.6pt;
            padding: 1mm 0.9mm;
            vertical-align: top;
        }

        table.main thead {
            display: table-header-group;
        }

        .group th {
            font-size: 8pt;
        }

        .center {
            text-align: center;
        }

        .num {
            text-align: right;
            white-space: nowrap;
        }

        .balance td {
            font-weight: bold;
            padding: 1.4mm 0.9mm;
        }

        .foot-note {
            margin-top: 2mm;
            font-size: 7pt;
            color: #333;
            display: flex;
            justify-content: space-between;
        }
    </style>
</head>

<body>
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

            <td class="logo-cell right">
                <img src="{{ public_path('images/UPC-LOGO-PDF(1).png') }}" alt="UP Cebu Logo">
            </td>
        </tr>
    </table>

    <div class="title">PROPERTY CARD</div>

    <div class="office-line">
        <strong>UNIVERSITY OF THE PHILIPPINES CEBU</strong><br>
        Supply &amp; Property Management Office
    </div>

    <table class="fields">
        <tr>
            <td style="width: 52%;">
                <span class="label">Office / College:</span>
                <span class="fill">{{ $card['office'] }}</span>
            </td>
            <td style="width: 48%; text-align: right;">
                <span class="label">Fund Cluster:</span>
                <span class="fill" style="min-width: 28mm;">{{ $card['item']['fund_cluster'] }}</span>
            </td>
        </tr>
        <tr>
            <td colspan="2">
                <span class="label">Name of Accountable Person:</span>
                <span class="fill" style="min-width: 80mm;">{{ $card['item']['accountable_person'] }}</span>
            </td>
        </tr>
    </table>

    <table class="desc">
        <tr>
            <td style="width: 70%;">
                <div class="label">Property, Plant and Equipment</div>
                <div class="label" style="margin-top: 1.5mm;">
                    Description (brand, size, color, SN):
                </div>
                <div class="body">
                    {{ $card['item']['item_name'] }}
                    @if ($card['item']['brand'])
                        — {{ $card['item']['brand'] }}
                    @endif
                    @if ($card['item']['model'])
                        {{ $card['item']['model'] }}
                    @endif
                    @if ($card['item']['serial_number'])
                        (SN: {{ $card['item']['serial_number'] }})
                    @endif
                    @if ($card['item']['description'])
                        <br>{{ $card['item']['description'] }}
                    @endif
                </div>
            </td>
            <td style="width: 30%; text-align: center;">
                <div class="label">Property Number:</div>
                <div class="property-number">
                    {{ $card['item']['property_number'] ?: 'N/A' }}
                </div>
            </td>
        </tr>
    </table>

    <table class="main">
        <colgroup>
            <col style="width: 10%;">
            <col style="width: 14%;">
            <col style="width: 6%;">
            <col style="width: 6%;">
            <col style="width: 22%;">
            <col style="width: 7%;">
            <col style="width: 10%;">
            <col style="width: 25%;">
        </colgroup>

        <thead>
            <tr class="group">
                <th rowspan="2">Date</th>
                <th rowspan="2">Reference / PAR No.</th>
                <th>Receipt</th>
                <th colspan="2">Issue / Transfer / Disposal</th>
                <th>Balance</th>
                <th rowspan="2">Amount</th>
                <th rowspan="2">Remarks</th>
            </tr>
            <tr>
                <th>Qty.</th>
                <th>Qty.</th>
                <th>Office / Officer</th>
                <th>Qty.</th>
            </tr>
        </thead>

        <tbody>
            @forelse ($card['rows'] as $row)
                <tr>
                    <td class="center">{{ $row['date'] }}</td>
                    <td>{{ $row['reference'] }}</td>
                    <td class="center">{{ $row['receipt_qty'] !== null ? number_format((float) $row['receipt_qty'], 0) : '' }}</td>
                    <td class="center">{{ $row['issue_qty'] !== null ? number_format((float) $row['issue_qty'], 0) : '' }}</td>
                    <td>{{ $row['office_officer'] }}</td>
                    <td class="center">{{ $row['balance_qty'] !== null ? number_format((float) $row['balance_qty'], 0) : '' }}</td>
                    <td class="num">{{ $row['amount'] !== null ? number_format((float) $row['amount'], 2) : '' }}</td>
                    <td>{{ $row['remarks'] }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="8" class="center" style="padding: 6mm;">
                        No recorded history for this article yet.
                    </td>
                </tr>
            @endforelse
        </tbody>

        <tfoot>
            <tr class="balance">
                <td colspan="5" style="text-align: left;">Balance Carried Forward</td>
                <td class="center">{{ number_format((float) $card['balance'], 0) }}</td>
                <td></td>
                <td></td>
            </tr>
        </tfoot>
    </table>

    <div class="foot-note">
        <span>Generated {{ now()->format('F j, Y g:i A') }}</span>
        <span>{{ $card['item']['condition'] ? 'Current condition: ' . $card['item']['condition'] : '' }}</span>
    </div>
</body>

</html>

