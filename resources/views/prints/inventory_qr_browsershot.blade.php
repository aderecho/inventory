<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <title>Property Inventory Stickers</title>
    <style>
        @page {
            size: A4 portrait;
            margin: 8mm 6mm;
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: Arial, Helvetica, sans-serif;
            color: #000000;
            background: #ffffff;
        }

        .page {
            width: 100%;
            page-break-after: always;
            page-break-inside: avoid;
        }

        .sticker-grid {
            width: 100%;
            margin: 0 auto;
            border-collapse: separate;
            border-spacing: 4mm 1mm;
            table-layout: fixed;
        }

        .sticker-grid td.cell-wrapper {
            width: 50%;
            vertical-align: top;
            padding: 0;
        }

        .sticker-box {
            width: 100%;
            height: 52mm;
            border: 1px dashed #555555;
            padding: 3mm 1mm;
            background: #ffffff;
            overflow: hidden;
        }

        /* Header */

        .header-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 2mm;
        }

        .logo-cell {
            width: 11mm;
            vertical-align: middle;
        }

        .logo-img {
            width: 10mm;
            height: 10mm;
            object-fit: contain;
        }

        .header-title-cell {
            vertical-align: middle;
            padding-left: 2mm;
        }

        .univ-name {
            font-size: 7.5pt;
            font-weight: 700;
            text-transform: uppercase;
            line-height: 1.1;
            color: #000000;
        }

        .doc-title {
            font-size: 7.5pt;
            font-weight: 700;
            text-transform: uppercase;
            line-height: 1.1;
            color: #000000;
            margin-top: 1px;
        }

        /* Body */

        .body-table {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
        }

        .details-cell {
            vertical-align: top;
            width: 65%;
        }

        .qr-cell {
            vertical-align: top;
            text-align: left;
            width: 35%;
            padding-left: 2mm;
            padding-top: 1mm;
        }

        .qr-img {
            width: 25mm;
            height: 25mm;
            object-fit: contain;
        }

        /* Details */

        .details-table {
            width: 100%;
            border-collapse: collapse;
        }

        .details-table td {
            font-size: 5.6pt;
            vertical-align: top;
            line-height: 1.25;
            padding-top: 0.5mm;
            padding-bottom: 0.5mm;
            padding-left: 0;
            padding-right: 0;
        }

        .details-table td.lbl {
            font-weight: 700;
            white-space: nowrap;
            color: #000000;
            padding-right: 1mm;
            width: 1%;
        }

        .details-table td.val {
            font-weight: 400;
            color: #111111;
            word-break: break-word;
            padding-left: 0;
        }

        /* Property Code */

        .details-table tr.code-row td {
            padding-bottom: 1.5mm;
        }

        .details-table td.code-lbl {
            font-size: 7pt;
            font-weight: 800;
            color: #000000;
            vertical-align: middle;
            padding-right: 1mm;
        }

        .details-table td.code-val {
            font-size: 14pt;
            font-weight: 800;
            color: #000000;
            text-decoration: underline;
            letter-spacing: 0.3px;
            vertical-align: middle;
            padding-left: 0;
        }
    </style>
</head>

<body>

    @foreach($items->chunk(10) as $chunk)
        <div class="page">
            <table class="sticker-grid">
                @foreach($chunk->chunk(2) as $rowItems)
                    <tr>
                        @foreach($rowItems as $item)
                            <td class="cell-wrapper">
                                <div class="sticker-box">

                                    <!-- Header -->
                                    <table class="header-table">
                                        <tr>
                                            <td class="logo-cell">
                                                <img class="logo-img" src="{{ public_path('images/uplogo-1.png') }}" alt="UP Logo">
                                            </td>
                                            <td class="header-title-cell">
                                                <div class="univ-name">University of the Philippines CEBU</div>
                                                <div class="doc-title">PROPERTY INVENTORY STICKER</div>
                                            </td>
                                        </tr>
                                    </table>

                                    <!-- Content Body -->
                                    <table class="body-table">
                                        <tr>
                                            <!-- Details Column -->
                                            <td class="details-cell">
                                                <table class="details-table">
                                                    <tr class="code-row">
                                                        <td class="lbl code-lbl">Property Code:</td>
                                                        <td class="val code-val">{{ $item->property_number ?? 'N/A' }}</td>
                                                    </tr>
                                                    <tr>
                                                        <td class="lbl">Date Acquired:</td>
                                                        <td class="val">{{ $item->date_acquired ?? 'N/A' }}</td>
                                                    </tr>
                                                    <tr>
                                                        <td class="lbl">Cost:</td>
                                                        <td class="val">{{ number_format($item->unit_cost ?? 0, 2) }}</td>
                                                    </tr>
                                                    <tr>
                                                        <td class="lbl">Product:</td>
                                                        <td class="val">{{ $item->item_name ?? 'N/A' }}</td>
                                                    </tr>
                                                    <tr>
                                                        <td class="lbl">Serial Model#:</td>
                                                        <td class="val">{{ $item->serial_number ?? 'N/A' }}</td>
                                                    </tr>
                                                    <tr>
                                                        <td class="lbl">Accountable Person:</td>
                                                        <td class="val">
                                                            {{ $item->latestAcknowledgementItem?->accountablePerson?->full_name ?? 'Unassigned' }}
                                                        </td>
                                                    </tr>
                                                    <tr>
                                                        <td class="lbl">Supplier:</td>
                                                        <td class="val">{{ $item->supplier?->supplier_name ?? 'N/A' }}</td>
                                                    </tr>
                                                    <tr>
                                                        <td class="lbl">Location:</td>
                                                        <td class="val">{{ $roomNames[$item->id] ?? 'N/A' }}</td>
                                                    </tr>
                                                </table>
                                            </td>

                                            <!-- QR Code Column -->
                                            <td class="qr-cell">
                                                <img class="qr-img"
                                                    src="data:image/png;base64,{{ base64_encode(QrCode::format('png')->size(260)->margin(0)->generate($item->property_number)) }}"
                                                    alt="QR">
                                            </td>
                                        </tr>
                                    </table>

                                </div>
                            </td>
                        @endforeach

                        <!-- Fill right cell if odd number in last row -->
                        @if($rowItems->count() < 2)
                            <td class="cell-wrapper"></td>
                        @endif
                    </tr>
                @endforeach
            </table>
        </div>
    @endforeach

</body>

</html>