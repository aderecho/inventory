<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <title>Property Acknowledgement Receipt</title>

    <style>
        @page {
            size: A4 portrait;
            margin: 0;
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: "Times New Roman", Times, serif;
            font-size: 9pt;
            color: #000;
            background: #fff;
        }

        .a4-page {
            width: 210mm;
            min-height: 297mm;
            margin: 0 auto;
            padding: 14mm 18mm 15mm 18mm;
            position: relative;
            background: #fff;
            overflow: hidden;
        }

        /* =========================================================
           HEADER
        ========================================================= */

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
            width: 25mm;
            height: auto;
            object-fit: contain;
        }

        /* =========================================================
           SMALL TOP RIGHT LABEL
        ========================================================= */

        /* =========================================================
           TITLE
        ========================================================= */

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

        /* =========================================================
           META INFORMATION
        ========================================================= */

        .meta-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 2mm;
            font-size: 8.5pt;
        }

        .meta-table td {
            border: none;
            padding: 1.5px 2px;
            vertical-align: bottom;
        }

        .meta-left {
            width: 62%;
        }

        .meta-right {
            width: 38%;
        }

        .meta-line {
            display: table;
            width: 100%;
            min-height: 5mm;
        }

        .meta-label {
            display: table-cell;
            width: 15mm;
            white-space: nowrap;
            font-weight: bold;
            vertical-align: bottom;
        }

        .meta-value {
            display: table-cell;
            padding-left: 2px;
            vertical-align: bottom;
            height: 5mm;
        }

        /* =========================================================
           MAIN ITEMS TABLE
        ========================================================= */

        .items-table {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
            font-size: 8pt;
        }

        .items-table th,
        .items-table td {
            border: 1px solid #000;
        }

        .items-table th {
            height: 9mm;
            padding: 2px;
            text-align: center;
            vertical-align: middle;
            font-weight: bold;
        }

        .items-table td {
            padding: 3px 3px;
            vertical-align: middle;
            text-align: center;
        }

        .items-table .qty {
            width: 8%;
        }

        .items-table .unit {
            width: 7%;
        }

        .items-table .description {
            width: 35%;
            text-align: left;
        }

        .items-table .property {
            width: 17%;
        }

        .items-table .date-acquired {
            width: 11%;
        }

        .items-table .unit-cost {
            width: 11%;
        }

        .items-table .total-cost {
            width: 11%;
        }

        .description-content {
            line-height: 1.25;
            min-height: 17mm;
        }

        .description-title {
            font-weight: bold;
            margin-bottom: 1mm;
        }

        .description-text {
            line-height: 1.25;
        }

        .serial-number {
            margin-top: 1mm;
            font-weight: bold;
        }

        .property-number {
            line-height: 1.3;
        }

        /* =========================================================
           INFORMATION SECTION
        ========================================================= */

        .info-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 8pt;
        }

        .info-table td {
            border: 1px solid #000;
            padding: 3px 5px;
            vertical-align: top;
        }

        .info-left {
            width: 60%;
        }

        .info-right {
            width: 40%;
        }

        .info-row {
            line-height: 1.5;
            min-height: 4mm;
        }

        .info-label {
            font-weight: bold;
        }

        /* =========================================================
           NOTE / REMARKS / LOCATION
        ========================================================= */

        .note-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 8pt;
        }

        .note-table td {
            border: 1px solid #000;
            padding: 3px 5px;
            vertical-align: top;
            height: 9mm;
        }

        .remarks-cell {
            width: 60%;
        }

        .location-cell {
            width: 40%;
        }

        /* =========================================================
           SIGNATURE SECTION
        ========================================================= */

        .signature-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 8pt;
            margin-top: 0;
        }

        .signature-table td {
            width: 50%;
            border: 1px solid #000;
            height: 35mm;
            padding: 4px 6px;
            vertical-align: top;
        }

        .signature-heading {
            font-weight: bold;
            margin-bottom: 5mm;
        }

        .signature-name {
            text-align: center;
            font-weight: bold;
            min-height: 5mm;
            margin-top: 5mm;
            line-height: 1.2;
        }

        .signature-line {
            width: 75%;
            border-bottom: 1px solid #000;
            margin: 1mm auto 1mm auto;
        }

        .signature-description {
            text-align: center;
            font-size: 7pt;
            line-height: 1.2;
        }

        .signature-position {
            text-align: center;
            margin-top: 2mm;
            font-size: 9pt;
            font-weight: bold;
            text-decoration: underline;
        }

        .signature-position-label {
            text-align: center;
            font-size: 7pt;
            margin-top: 1mm;
        }

        .signature-date {
            width: 55%;
            border-bottom: 1px solid #000;
            margin: 4mm auto 0 auto;
            height: 4mm;
        }

        .date-label {
            text-align: center;
            font-size: 7pt;
            margin-top: 1mm;
        }

        /* =========================================================
           PRINT
        ========================================================= */

        @media print {

            html,
            body {
                width: 210mm;
                height: 297mm;
                margin: 0;
                padding: 0;
            }

            .a4-page {
                margin: 0;
                page-break-after: always;
            }

            .a4-page:last-child {
                page-break-after: auto;
            }
        }
    </style>
</head>

<body>

    @foreach ($groupedParItems as $propertyGroup => $receiptItems)

        @php
            $firstAckItem = $receiptItems->first();

            $receipt = $firstAckItem->acknowledgementReceipts;
            $firstItem = $firstAckItem->inventoryItems;

            $accountablePerson = $firstAckItem->accountablePerson;
            $issuedBy = $firstAckItem->issuedBy;

            $roomName = $roomNames[$firstItem->id] ?? 'N/A';

            $issuedByName = trim(
                ($issuedBy->first_name ?? '') . ' ' .
                ($issuedBy->middle_name ?? '') . ' ' .
                ($issuedBy->last_name ?? '')
            );

            $accountableName = $accountablePerson->full_name ?? '';

            $totalAmount = $groupedParTotals[$propertyGroup] ?? 0;
        @endphp

        <div class="a4-page">

            <!-- =====================================================
                         HEADER
                    ====================================================== -->
            <div class="header">

                <table class="header-table">
                    <tr>

                        <!-- LEFT LOGO -->
                        <td class="logo-cell">
                            <img src="{{ public_path('images/uplogo-2.png') }}" alt="UP Logo">
                        </td>

                        <!-- CENTER -->
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

                        <!-- RIGHT LOGO -->
                        <td class="right-logo">
                            <img src="{{ public_path('images/uplogo-1.png') }}" alt="UP Cebu Logo">
                        </td>

                    </tr>
                </table>

            </div>

            <!-- =====================================================
                         TITLE
                    ====================================================== -->
            <div class="title">
                <h1>PROPERTY ACKNOWLEDGEMENT RECEIPT</h1>
            </div>

            <!-- =====================================================
                         META
                    ====================================================== -->
            <table class="meta-table">
                <tr>

                    <td class="meta-left">

                        <div class="meta-line">
                            <span class="meta-label">
                                Entity Name:
                            </span>

                            <span class="meta-value">
                                University of the Philippines Cebu
                            </span>
                        </div>

                        <div class="meta-line">
                            <span class="meta-label">
                                Fund Cluster:
                            </span>

                            <span class="meta-value">
                                {{ $firstItem->fund_source ?? 'N/A' }}
                            </span>
                        </div>

                    </td>

                    <td class="meta-right">

                        <div class="meta-line">
                            <span class="meta-label">
                                PAR No.:
                            </span>

                            <span class="meta-value">
                                {{ $receipt->category ?? 'N/A' }}
                            </span>
                        </div>

                    </td>

                </tr>
            </table>

            <!-- =====================================================
                         ITEMS
                    ====================================================== -->
            <table class="items-table">

                <thead>
                    <tr>

                        <th class="qty">
                            Quantity
                        </th>

                        <th class="unit">
                            Unit
                        </th>

                        <th class="description">
                            Description
                        </th>

                        <th class="property">
                            Property<br>
                            Number
                        </th>

                        <th class="date-acquired">
                            Date<br>
                            Acquired
                        </th>

                        <th class="unit-cost">
                            <div>AMOUNT</div>
                            <div>Unit Cost</div>
                        </th>

                        <th class="total-cost">
                            <div>AMOUNT</div>
                            <div>TOTAL</div>
                        </th>

                    </tr>
                </thead>

                <tbody>

                    @foreach ($receiptItems as $ackItem)

                            @php
                                $inventoryItem = $ackItem->inventoryItems;

                                $quantity = $inventoryItem->quantity ?? 1;

                                $unitCost = $inventoryItem->unit_cost ?? 0;

                                $itemTotal = $unitCost * $quantity;
                            @endphp

                            <tr>

                                <!-- QUANTITY -->
                                <td>
                                    {{ $quantity }}
                                </td>

                                <!-- UNIT -->
                                <td>
                                    {{ $inventoryItem->unit ?? 'unit' }}
                                </td>

                                <!-- DESCRIPTION -->
                                <td class="description">

                                    <div class="description-content">

                                        <div class="description-title">
                                            {{ $inventoryItem->item_name ?? 'N/A' }}
                                        </div>

                                        <div class="description-text">
                                            {{ $inventoryItem->description ?? 'N/A' }}
                                        </div>

                                        @if (!empty($inventoryItem->serial_number))
                                            <div class="serial-number">
                                                SN: {{ $inventoryItem->serial_number }}
                                            </div>
                                        @endif

                                    </div>

                                </td>

                                <!-- PROPERTY NUMBER -->
                                <td>
                                    <div class="property-number">
                                        {{ $inventoryItem->property_number ?? 'N/A' }}
                                    </div>
                                </td>

                                <!-- DATE ACQUIRED -->
                                <td>
                                    {{ $inventoryItem->date_acquired
                        ? \Carbon\Carbon::parse($inventoryItem->date_acquired)->format('m/d/Y')
                        : 'N/A' }}
                                </td>

                                <!-- UNIT COST -->
                                <td>
                                    {{ number_format($unitCost, 2) }}
                                </td>

                                <!-- TOTAL -->
                                <td>
                                    {{ number_format($itemTotal, 2) }}
                                </td>

                            </tr>

                    @endforeach

                </tbody>

            </table>

            <!-- =====================================================
                         SUPPLIER / PURCHASE INFORMATION
                    ====================================================== -->
            <table class="info-table">

                <tr>

                    <td class="info-left">

                        <div class="info-row">
                            <span class="info-label">SUPPLIER:</span>
                            {{ $firstItem->supplier->supplier_name ?? 'N/A' }}
                        </div>

                        <div class="info-row">
                            <span class="info-label">SALES INVOICE NO. & DATE:</span>
                            {{ $firstItem->invoice ?? 'N/A' }}
                        </div>

                        <div class="info-row">
                            <span class="info-label">FUND CODE:</span>
                            {{ $firstItem->fund_source ?? 'N/A' }}
                        </div>

                        <div class="info-row">
                            <span class="info-label">PO NO. & DATE:</span>
                            {{ $firstItem->po_number ?? 'N/A' }}
                        </div>

                    </td>

                    <td class="info-right">

                        <div class="info-row">
                            <span class="info-label">TOTAL:</span>
                            {{ number_format($totalAmount, 2) }}
                        </div>

                        <div class="info-row">
                            <span class="info-label">PR NO.:</span>
                            {{ $firstItem->pr_number ?? 'N/A' }}
                        </div>

                        <div class="info-row">
                            <span class="info-label">DATE OF ISSUANCE:</span>
                            {{ $receipt->par_date ?? 'N/A' }}
                        </div>

                    </td>

                </tr>

            </table>

            <!-- =====================================================
                         REMARKS / LOCATION
                    ====================================================== -->
            <table class="note-table">

                <tr>

                    <td class="remarks-cell">
                        <strong>REMARKS:</strong>
                        {{ $receipt->remarks ?? '' }}
                    </td>

                    <td class="location-cell">
                        <strong>LOCATION:</strong>
                        {{ $roomName }}
                    </td>

                </tr>

            </table>

            <!-- =====================================================
                         SIGNATURES
                    ====================================================== -->
            <table class="signature-table">

                <tr>

                    <!-- RECEIVED BY -->
                    <td>

                        <div class="signature-heading">
                            Received by:
                        </div>

                        <div class="signature-name">
                            {{ $accountableName ?: '____________________________' }}
                        </div>

                        <div class="signature-line"></div>

                        <div class="signature-description">
                            Signature over Printed Name of End User
                        </div>

                        <div class="signature-position">
                            {{ $firstAckItem->accountablePerson->primaryOrganization->name ?? 'N/A' }}
                        </div>

                        <div class="signature-position-label">
                            Position/Office
                        </div>

                        <div class="signature-date"></div>

                        <div class="date-label">
                            Date
                        </div>

                    </td>

                    <!-- ISSUED BY -->
                    <td>

                        <div class="signature-heading">
                            Issued by:
                        </div>

                        <div class="signature-name">
                            {{ $issuedByName ?: '____________________________' }}
                        </div>

                        <div class="signature-line"></div>

                        <div class="signature-description">
                            Signature over Printed Name of Supply and/or Property Custodian
                        </div>

                        <div class="signature-position">
                            {{ $firstAckItem->issuedBy->primaryOrganization->name ?? 'N/A' }}
                        </div>

                        <div class="signature-position-label">
                            Position/Office
                        </div>

                        <div class="signature-date"></div>

                        <div class="date-label">
                            Date
                        </div>

                    </td>

                </tr>

            </table>

        </div>

    @endforeach

</body>

</html>