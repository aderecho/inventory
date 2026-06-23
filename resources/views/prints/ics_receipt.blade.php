<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <title>Inventory Custodian Slip</title>

    <style>
        @page {
            size: A4;
            margin: 0;
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: "Times New Roman", serif;
            font-size: 11pt;
            background: #fff;
            color: #000;
        }

        /* A4 PAGE */
        .a4-page {
            width: 210mm;
            height: 297mm;
            margin: 0 auto;
            background: #fff;
            position: relative;
            overflow: hidden;
            outline: 1px solid #000;
            /* ✅ USE OUTLINE, NOT BORDER */
        }

        /* CONTENT */
        .content-area {
            padding: 15mm 20mm 32mm 20mm;
            /* 🔽 reduced bottom */
        }

        /* HEADER */
        .header {
            display: table;
            width: 100%;
            margin-bottom: 6mm;
            /* 🔽 */
            table-layout: fixed;
        }

        .header-left,
        .header-center,
        .header-right {
            display: table-cell;
            vertical-align: middle;
            text-align: center;
        }

        .header-left,
        .header-right {
            width: 25%;
        }

        .header-center {
            width: 50%;
        }

        .header img {
            max-width: 140px;
            /* 🔽 logo size */
        }

        .header-center h1 {
            font-size: 14pt;
            font-weight: bold;
        }

        .header-center p {
            font-size: 10pt;
        }

        .header-center h2 {
            font-size: 12pt;
            font-weight: bold;
            text-transform: uppercase;
        }

        /* TITLE */
        .title-header {
            text-align: center;
            margin: 6mm 0 10mm 0;
            /* space above & below */
        }

        .title-header h1 {
            font-size: 12pt;
            font-weight: bold;
            letter-spacing: 0.5px;
        }

        /* META */
        .meta-section {
            margin-bottom: 3mm;
        }

        .meta-table {
            width: 100%;
            font-size: 12pt;
            border-collapse: collapse;
        }

        .meta-table td {
            padding: 4px 0;
            vertical-align: top;
            line-height: 1.6;
            /* 👈 spacing between Entity Name / Fund Cluster */
        }

        .meta-table td span {
            color: #363636;
        }

        /* spacing between left (Entity) and right (PAR / Date) */
        .meta-table td:first-child {
            padding-right: 10mm;
        }


        /* TABLE */
        .ics-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 10.5pt;
            /* margin-bottom: 5mm; */
            /* 🔽 */
        }

        .ics-table th,
        .ics-table td {
            border: 1px solid #000;
            padding: 3px;
            text-align: center;
        }

        .ics-table td:nth-child(5) {
            text-align: left;
            padding-left: 5px;
        }

        /* PURCHASE INFO */
        .purchase-section {
            width: 100%;
        }

        /* TABLE */
        .purchase-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 10.5pt;
        }

        .purchase-table td {
            border: 1px solid #000;
            border-top: none;
            height: 35 mm;
            padding: 6px;
            vertical-align: top;
        }

        /* FLEX INSIDE THE CELL */
        .purchase-info {
            display: flex;
            flex-direction: column;
            gap: 2mm;
        }

        .purchase-info .label {
            font-weight: bold;
        }

        .purchase-info .value {
            margin-left: 0;
        }

        .purchase-info>div {
            display: flex;
            gap: 2mm;
        }

        .purchase-info h2 {
            font-size: 10.5pt;
        }

        .purchase-second-content {
            margin-top: 1em;
        }

        .purchase-right-content {
            padding: 10px;
        }

        .second-right-content {
            margin-top: 2em;
        }

        /* SIGNATURE */
        .signature-section {
            position: absolute;
            display: flex;
            margin-top: -8.3em;
            left: 20mm;
            right: 20mm;
        }

        .signature-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 10.5pt;
        }

        .signature-table td {
            border: 1px solid #000;
            border-top: none;
            height: 26mm;
            padding-top: 15px;
            padding-left: 6px;
            vertical-align: top;
            position: relative;
        }

        .signature-table td span {
            display: block;
            text-align: center;
            margin-top: 4px;
            /* Reduced from 8px to bring it up */
            position: relative;
            top: -4px;
            /* Added to move it up slightly more */
        }

        .name-container {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            min-height: 40px;
            margin: 12px 0 8px 0;
            /* Reduced bottom margin to bring text up */
        }

        .underline {
            border-bottom: 1px solid #000;
            width: 250px;
            padding-bottom: 4px;
            text-align: center;
            margin: 0 auto;
        }

        /* PRINT SAFETY */
        @media print {
            body {
                margin: 0;
            }

            * {
                page-break-inside: avoid !important;
            }
        }
    </style>
</head>

<body>
    @php $acknowledgementItems = $acknowledgementItems ?? $parItems ?? $icsItems; @endphp
    @foreach ($acknowledgementItems->groupBy('acknowledgement_id') as $receiptItems)
        @php
            $firstAckItem = $receiptItems->first();
            $receipt = $firstAckItem->acknowledgementReceipts;
            $firstItem = $firstAckItem->inventoryItems;
            $lastItem = $receiptItems->last()->inventoryItems;
        @endphp

        <div class="a4-page">
            <div class="content-area">

                <!-- HEADER -->
                <div class="header">
                    <div class="header-left">
                        <img src="{{ public_path('images/uplogo-2.png') }}">
                    </div>
                    <div class="header-center">
                        <h1>University of the Philippines</h1>
                        <p>Region VII - Central Visayas</p>
                    </div>
                    <div class="header-right">
                        <img src="{{ public_path('images/uplogo-1.png') }}">
                    </div>
                </div>

                <!-- TITLE -->
                <div class="title-header">
                    <h1>INVENTORY CUSTODIAN SLIP</h1>
                </div>

                <!-- META -->
                <div class="meta-section">
                    <table class="meta-table">
                        <tr>
                            <td width="60%">
                                <strong>Entity Name:</strong> <span>{{ $firstItem->item_name ?? 'N/A' }}</span>
                                <br>
                                <strong>Fund Cluster:</strong> <span>{{ $firstItem->fund_source ?? 'N/A' }}</span>
                            </td>
                            <td width="40%">
                                <strong>ICS No.:</strong> <span>{{ $firstItem->property_number ?? 'N/A' }}</span>
                                <br>
                                <strong>Date:</strong> <span>{{ optional($receipt->created_at)->format('m/d/Y') }}</span>
                            </td>
                        </tr>
                    </table>
                </div>

                <!-- ITEMS TABLE -->
                <table class="ics-table">
                    <thead>
                        <tr>
                            <th rowspan="2">Quantity</th>
                            <th rowspan="2">Unit</th>
                            <th colspan="2">Amount</th>
                            <th rowspan="2">Description</th>
                            <th rowspan="2">Property No.</th>
                            <th rowspan="2">Estimated Useful Life</th>
                        </tr>
                        <tr>
                            <th>Unit Cost</th>
                            <th>Total Cost</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($receiptItems as $item)
                            <tr>
                                <td>{{ $item->inventoryItems->quantity ?? 1 }}</td>
                                <td>{{ $item->inventoryItems->unit ?? 'unit' }}</td>
                                <td>{{ number_format($item->inventoryItems->unit_cost, 2) }}</td>
                                <td>{{ number_format($item->inventoryItems->unit_cost * ($item->inventoryItems->quantity ?? 1), 2) }}
                                </td>
                                <td>{{ $item->inventoryItems->description }}</td>
                                <td>{{ $item->inventoryItems->property_number }}</td>
                                <td>{{ $item->inventoryItems->useful_life ?? '' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>

                <!-- PURCHASE INFO -->
                <div class="purchase-section">
                    <table class="purchase-table">
                        <tr>
                            <td width="60%">
                                <div class="purchase-info">
                                    <div>
                                        <span class="label">Supplier:</span>
                                        <span class="value">{{ $lastItem->supplier->supplier_name ?? 'N/A' }}</span>
                                    </div>
                                    <div class="purchase-second-content">
                                        <div>
                                            <span class="label">Invoice No.:</span>
                                            <span class="value">{{ $lastItem->invoice ?? 'N/A' }}</span>
                                        </div>
                                        <div>
                                            <span class="label">PO No.:</span>
                                            <span class="value">{{ $lastItem->po_number ?? 'N/A' }}</span>
                                        </div>
                                        <div>
                                            <span class="label">PR No.:</span>
                                            <span class="value">{{ $lastItem->pr_number ?? 'N/A' }}</span>
                                        </div>
                                    </div>
                                </div>
                            </td>
                            <td width="40%">
                                <div class="purchase-info">
                                    <div class="purchase-right-content">
                                        <div>
                                            <span class="label">Serial No.:</span>
                                            <span class="value">{{ $lastItem->serial_number ?? 'N/A' }}</span>
                                        </div>
                                        <div class="second-right-content">
                                            <span class="label">Location:</span>
                                            {{ $firstAckItem->accountablePerson->department ?? 'N/A' }}
                                        </div>
                                    </div>
                                </div>
                            </td>
                        </tr>
                    </table>
                </div>
            </div>

            <!-- SIGNATURE -->
            <div class="signature-section">
                <table class="signature-table">
                    <tr>
                        <td width="50%">
                            <strong>Received From:</strong>
                            <div class="name-container">
                                <div class="underline">
                                    {{ $firstAckItem->issuedBy->first_name ?? '' }}
                                    {{ $firstAckItem->issuedBy->middle_name ?? '' }}
                                    {{ $firstAckItem->issuedBy->last_name ?? '' }}
                                </div>
                            </div>
                            <span>Signature over Printed Name</span>
                        </td>
                        <td width="50%">
                            <strong>Received By:</strong>
                            <div class="name-container">
                                <div class="underline">
                                    {{ $firstAckItem->accountablePerson->full_name ?? ' ' }}
                                </div>
                            </div>
                            <span>Signature over Printed Name</span>
                        </td>
                    </tr>
                </table>
            </div>
        </div>
    @endforeach
</body>
</html>