<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Inventory Custodian Slip</title>

    <style>
        @page {
            size: A4 portrait;
            margin: 0;
        }

        * {
            box-sizing: border-box;
        }

        html,
        body {
            margin: 0;
            padding: 0;
            width: 210mm;
            min-height: 297mm;
            background: #fff;
        }

        body {
            font-family: "Times New Roman", Times, serif;
            color: #000;
            font-size: 10pt;
        }

        .page {
            width: 210mm;
            min-height: 297mm;
            padding: 12mm 10mm;
            margin: 0 auto;
        }

        /* =========================
           HEADER
        ========================= */

        .header {
            width: 100%;
            margin-bottom: 5mm;
            position: relative;
        }

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
            width: 25%;
            text-align: left;
        }

        .logo-cell img {
            width: 30mm;
            height: auto;
            object-fit: contain;
        }

        .header-center {
            width: 50%;
            text-align: center;
        }

        .header-center .republic {
            font-size: 8.5pt;
            margin-bottom: 1mm;
        }

        .header-center .university {
            font-size: 11pt;
            font-weight: bold;
            text-transform: uppercase;
        }

        .header-center .campus {
            font-size: 8.5pt;
            margin-top: 1mm;
        }

        .right-logo {
            width: 25%;
            text-align: right;
        }

        .right-logo img {
            width: 30mm;
            height: auto;
            object-fit: contain;
        }

        .title {
            text-align: center;
            margin-top: 3mm;
            margin-bottom: 5mm;
        }

        .title h1 {
            font-size: 12pt;
            font-weight: bold;
            letter-spacing: 0.2px;
        }

        /* =========================
           ENTITY / ICS INFORMATION
        ========================= */

        .meta-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 2mm;
        }

        .meta-table td {
            vertical-align: bottom;
            font-size: 10pt;
            padding: 1px 0;
        }

        .entity {
            width: 65%;
        }

        .ics-number {
            width: 35%;
            text-align: right;
            white-space: nowrap;
        }

        /* =========================
           MAIN INVENTORY TABLE
        ========================= */

        .inventory-table {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
        }

        .inventory-table th,
        .inventory-table td {
            border: 1px solid #000;
        }

        .inventory-table th {
            font-weight: bold;
            text-align: center;
            vertical-align: middle;
        }

        .inventory-table td {
            vertical-align: middle;
        }

        /* COLUMN WIDTHS */

        .col-quantity {
            width: 7%;
        }

        .col-unit {
            width: 6%;
        }

        .col-unit-cost {
            width: 9%;
        }

        .col-total-cost {
            width: 10%;
        }

        .col-description {
            width: 36%;
        }

        .col-item-no {
            width: 9%;
        }

        .col-property {
            width: 13%;
        }

        .col-life {
            width: 10%;
        }

        /* =========================
           TABLE HEADER
        ========================= */

        .header-row-1 th {
            height: 12mm;
            font-size: 9pt;
        }

        .header-row-2 th {
            height: 12mm;
            font-size: 9pt;
        }

        .quantity-header {
            font-size: 9pt;
            line-height: 1.1;
        }

        .unit-header {
            font-size: 9pt;
        }

        .amount-header {
            height: 7mm !important;
            font-size: 9pt;
        }

        .description-header {
            font-size: 9pt;
        }

        .property-header {
            line-height: 1.1;
        }

        .life-header {
            line-height: 1.1;
        }

        /* =========================
           PURCHASE REQUEST ROW
        ========================= */

        .pr-row td {
            height: 7mm;
            text-align: center;
            font-weight: bold;
            font-size: 9pt;
        }

        /* =========================
           ITEM ROWS
        ========================= */

        .item-row td {
            padding: 2mm 1.5mm;
            font-size: 9pt;
        }

        .center {
            text-align: center;
        }

        .description {
            text-align: left;
            vertical-align: top !important;
            line-height: 1.25;
            padding-left: 2mm !important;
            padding-right: 2mm !important;
        }

        .description strong {
            font-weight: bold;
        }

        .serial {
            font-weight: bold;
        }

        .cost {
            text-align: right;
            white-space: nowrap;
        }

        /* =========================
           NOTHING FOLLOWS
        ========================= */

        .nothing-row td {
            height: 7mm;
            text-align: center;
            font-size: 9pt;
        }

        /* =========================
           PURCHASE INFORMATION
        ========================= */

        .purchase-table {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
        }

        .purchase-table td {
            border: 1px solid #000;
            vertical-align: top;
        }

        .purchase-info {
            width: 55%;
            padding: 3mm;
            font-size: 9pt;
            line-height: 1.8;
        }

        .purchase-empty {
            width: 45%;
        }

        .purchase-line {
            margin-bottom: 1mm;
        }

        .label {
            font-weight: bold;
        }

        /* =========================
           REMARKS / LOCATION
        ========================= */

        .remarks-location {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
        }

        .remarks-location td {
            width: 50%;
            height: 8mm;
            border: 1px solid #000;
            padding: 1.5mm;
            font-size: 9pt;
            vertical-align: top;
        }

        /* =========================
           SIGNATURE SECTION
        ========================= */

        .signature-table {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
        }

        .signature-table td {
            width: 50%;
            height: 55mm;
            border: 1px solid #000;
            vertical-align: top;
            padding: 2mm;
        }

        .signature-label {
            font-weight: bold;
            font-size: 9pt;
        }

        .signature-content {
            text-align: center;
            margin-top: 5mm;
        }

        .signature-name {
            display: block;
            font-size: 10pt;
            font-weight: bold;
            text-decoration: underline;
            margin-top: 12mm;
            margin-bottom: 2mm;
        }

        .signature-subtext {
            display: block;
            font-size: 8pt;
            margin-bottom: 2mm;
        }

        .signature-position {
            display: block;
            font-size: 9pt;
            font-weight: bold;
            text-decoration: underline;
            margin-top: 2mm;
        }

        .signature-position-label {
            display: block;
            font-size: 8pt;
            margin-top: 1mm;
        }

        .date-line {
            display: inline-block;
            width: 35mm;
            border-bottom: 1px solid #000;
            margin-top: 7mm;
        }

        .date-label {
            display: block;
            font-size: 8pt;
            margin-top: 1mm;
        }

        /* =========================
           PRINT
        ========================= */

        @media print {

            html,
            body {
                width: 210mm;
                height: 297mm;
            }

            .page {
                page-break-after: always;
            }
        }
    </style>
</head>

<body>

    @foreach ($groupedIcsItems as $propertyGroup => $receiptItems)

        @php
            $firstAckItem = $receiptItems->first();
            $receipt = $firstAckItem->acknowledgementReceipts;
            $firstItem = $firstAckItem->inventoryItems;
        @endphp

        <div class="page">

            <!-- ================= HEADER ================= -->

            <table class="header-table">
                <tr>
                    <td class="logo-cell">
                        <img src="{{ public_path('images/UP-LOGO-PDF(2).png') }}" alt="UP Logo">
                    </td>

                    <td class="header-center">
                        <div class="republic">
                            Republic of the Philippines
                        </div>

                        <div class="university">
                            University of the Philippines
                        </div>

                        <div class="campus">
                            Cebu
                        </div>
                    </td>

                    <td class="right-logo">
                        <img src="{{ public_path('images/UPC-LOGO-PDF(1).png') }}" alt="UP Cebu Logo">
                    </td>
                </tr>
            </table>

            <div class="title">
                <h1>INVENTORY CUSTODIAN SLIP</h1>
            </div>

            <!-- ================= META ================= -->

            <table class="meta-table">
                <tr>
                    <td class="entity">
                        <strong>Entity Name :</strong>
                        University of the Philippines Cebu
                    </td>

                    <td class="ics-number">
                        <strong>ICS No :</strong>
                        {{ $receipt->category ?? 'N/A' }}
                    </td>
                </tr>

                <tr>
                    <td class="entity">
                        <strong>Fund Cluster :</strong>
                        {{ $firstItem->fund_source ?? '' }}
                    </td>

                    <td></td>
                </tr>
            </table>

            <!-- ================= INVENTORY TABLE ================= -->

            <table class="inventory-table">

                <colgroup>
                    <col class="col-quantity">
                    <col class="col-unit">
                    <col class="col-unit-cost">
                    <col class="col-total-cost">
                    <col class="col-description">
                    <col class="col-item-no">
                    <col class="col-property">
                    <col class="col-life">
                </colgroup>

                <thead>

                    <tr class="header-row-1">

                        <th rowspan="2">
                            <div class="quantity-header">
                                Quantity
                            </div>
                        </th>

                        <th rowspan="2">
                            <div class="unit-header">
                                Unit
                            </div>
                        </th>

                        <th colspan="2" class="amount-header">
                            Amount
                        </th>

                        <th rowspan="2">
                            Description
                        </th>

                        <th rowspan="2">
                            Item No.
                        </th>

                        <th rowspan="2">
                            <div class="property-header">
                                Property<br>
                                Number
                            </div>
                        </th>

                        <th rowspan="2">
                            <div class="life-header">
                                Estimated<br>
                                Useful Life
                            </div>
                        </th>

                    </tr>

                    <tr class="header-row-2">

                        <th>
                            Unit<br>
                            Cost
                        </th>

                        <th>
                            Total Cost
                        </th>

                    </tr>

                </thead>

                <tbody>

                    <!-- PR NUMBER -->

                    <tr class="pr-row">
                        <td colspan="8">
                            PR {{ $firstItem->pr_number ?? 'N/A' }}
                        </td>
                    </tr>

                    <!-- ITEMS -->

                    @foreach ($receiptItems as $index => $item)

                        @php
                            $inventory = $item->inventoryItems;
                            $quantity = $inventory->quantity ?? 1;
                            $unitCost = (float) ($inventory->unit_cost ?? 0);
                            $totalCost = $unitCost * $quantity;
                        @endphp

                        <tr class="item-row">

                            <td class="center">
                                {{ $quantity }}
                            </td>

                            <td class="center">
                                {{ $inventory->unit ?? 'unit' }}
                            </td>

                            <td class="cost">
                                {{ number_format($unitCost, 2) }}
                            </td>

                            <td class="cost">
                                {{ number_format($totalCost, 2) }}
                            </td>

                            <td class="description">

                                {{ $inventory->item_name ?? 'N/A' }}

                                @if (!empty($inventory->description))
                                    <br>
                                    {{ $inventory->description }}
                                @endif

                                @if (!empty($inventory->serial_number))
                                    <br>
                                    <strong class="serial">
                                        SN: {{ $inventory->serial_number }}
                                    </strong>
                                @endif

                            </td>

                            <td class="center">
                                {{ $index + 1 }}
                            </td>

                            <td class="center">
                                {{ $inventory->property_number ?? 'N/A' }}
                            </td>

                            <td class="center">
                                5 Years
                            </td>

                        </tr>

                    @endforeach

                </tbody>

            </table>

            <!-- ================= PURCHASE INFORMATION ================= -->

            <table class="purchase-table">

                <tr>

                    <td class="purchase-info">

                        <div class="purchase-line">
                            <span class="label">Supplier:</span>
                            {{ $firstItem->supplier->supplier_name ?? 'N/A' }}
                        </div>

                        <div class="purchase-line">
                            <span class="label">SI # / Date:</span>
                            {{ $firstItem->invoice ?? 'N/A' }}
                        </div>

                        <div class="purchase-line">
                            <span class="label">PO No. / Date:</span>
                            {{ $firstItem->po_number ?? 'N/A' }}
                        </div>

                        <div class="purchase-line">
                            <span class="label">PR No.:</span>
                            {{ $firstItem->pr_number ?? 'N/A' }}
                        </div>

                        <div class="purchase-line">
                            <span class="label">Date of Issuance:</span>
                            {{ $receipt->par_date ?? 'N/A' }}
                        </div>

                    </td>

                    <td class="purchase-empty"></td>

                </tr>

            </table>

            <!-- ================= REMARKS / LOCATION ================= -->

            <table class="remarks-location">

                <tr>

                    <td>
                        <strong>Remarks:</strong>
                        {{ $receipt->remarks ?? '' }}
                    </td>

                    <td>
                        <strong>Location:</strong>
                        {{ $roomNames[$firstItem->id] ?? 'N/A' }}
                    </td>

                </tr>

            </table>

            <!-- ================= SIGNATURES ================= -->

            <table class="signature-table">

                <tr>

                    <!-- RECEIVED FROM -->

                    <td>

                        <span class="signature-label">
                            Received from:
                        </span>

                        <div class="signature-content">

                            <span class="signature-name">
                                {{
            trim(
                ($firstAckItem->issuedBy->first_name ?? '') . ' ' .
                ($firstAckItem->issuedBy->middle_name ?? '') . ' ' .
                ($firstAckItem->issuedBy->last_name ?? '')
            ) ?: '________________________'
                                }}
                            </span>

                            <span class="signature-subtext">
                                Signature Over Printed Name
                            </span>

                            <span class="signature-position">
                                {{ $firstAckItem->issuedBy->primaryOrganization->name ?? 'N/A' }}
                            </span>

                            <span class="signature-position-label">
                                Position/Office
                            </span>

                            <span class="date-line"></span>

                            <span class="date-label">
                                Date
                            </span>

                        </div>

                    </td>

                    <!-- RECEIVED BY -->

                    <td>

                        <span class="signature-label">
                            Received by:
                        </span>

                        <div class="signature-content">

                            <span class="signature-name">
                                {{ $firstAckItem->accountablePerson->full_name ?? '________________________' }}
                            </span>

                            <span class="signature-subtext">
                                Signature Over Printed Name
                            </span>

                            <span class="signature-position">
                                {{ $firstAckItem->accountablePerson->primaryOrganization->name ?? 'N/A' }}
                            </span>

                            <span class="signature-position-label">
                                Position/Office
                            </span>

                            <span class="date-line"></span>

                            <span class="date-label">
                                Date
                            </span>

                        </div>

                    </td>

                </tr>

            </table>

        </div>

    @endforeach

</body>

</html>