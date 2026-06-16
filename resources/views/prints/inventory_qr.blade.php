<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <style>
        body {
            font-family: DejaVu Sans, sans-serif;
            margin: 0;
            font-size: 12px;
            background-color: white;
        }

        table.layout {
            width: 100%;
            border-collapse: collapse;
        }

        .label {
            width: 140px;
            font-weight: bold;
        }

        .value {
            font-weight: bold;
        }

        .underline-value {
            text-decoration: underline;
        }

        .field-table {
            width: 100%;
            border-collapse: collapse;
        }

        .field-table td {
            padding: 4px;
        }
    </style>
</head>

<body>

    <table class="layout">
        <tr>

            <!-- LEFT SIDE (70%) -->
            <td width="60%" style="vertical-align:middle;">

                <!-- HEADER -->
                <table style="margin-bottom:15px;">
                    <tr>
                       

                        <td style="vertical-align:middle;">
                            <div style="font-size:16px; font-weight:bold;">
                                University of the Philippines CEBU
                            </div>

                            <div style="font-size:16px; font-weight:bold; margin-top:5px;">
                                PROPERTY INVENTORY STICKER
                            </div>
                        </td>

                         
                    </tr>
                </table>

                <!-- DETAILS -->
                <table class="field-table">

                    <tr>
                        <td style="width:90px; vertical-align:middle;">
                            <img
                                src="{{ public_path('images/uplogo-1.png') }}"
                                width="80"
                                height="80"
                            >
                        </td>
                    </tr>
                    <tr>
                        <td class="label">Property Code:</td>
                        <td class="value">
                            <span class="underline-value">
                                {{ $item->property_number }}
                            </span>
                        </td>
                    </tr>

                    <tr>
                        <td class="label">Date Acquired:</td>
                        <td class="value">
                            <span class="underline-value">
                                {{ $item->date_acquired }}
                            </span>
                        </td>
                    </tr>

                    <tr>
                        <td class="label">Cost:</td>
                        <td class="value">
                            <span class="underline-value">
                                {{ number_format($item->unit_cost, 2) }}
                            </span>
                        </td>
                    </tr>

                    <tr>
                        <td class="label">Product:</td>
                        <td class="value">
                            <span class="underline-value">
                                {{ $item->item_name }}
                            </span>
                        </td>
                    </tr>

                    <tr>
                        <td class="label">Serial Model#:</td>
                        <td class="value">
                            <span class="underline-value">
                                {{ $item->serial_number ?? 'N/A' }}
                            </span>
                        </td>
                    </tr>

                    <tr>
                        <td class="label">Accountable Person:</td>
                        <td class="value">
                            <span class="underline-value">
                                {{ $item->latestAcknowledgementItem->accountablePerson->full_name }}
                            </span>
                        </td>
                    </tr>

                    <tr>
                        <td class="label">Supplier:</td>
                        <td class="value">
                            <span class="underline-value">
                                {{ $item->supplier->supplier_name }}
                            </span>
                        </td>
                    </tr>

                    <tr>
                        <td class="label">Location:</td>
                        <td class="value">
                            <span class="underline-value">
                                N/A
                            </span>
                        </td>
                    </tr>

                </table>

            </td>

            <!-- RIGHT SIDE (30%) -->
            <td width="40%" style="text-align:center; vertical-align:middle;">

                <img
                    src="data:image/png;base64,{{ base64_encode(
                        QrCode::format('png')
                            ->size(260)
                            ->margin(2)
                            ->generate($item->property_number)
                    ) }}"
                    width="250"
                    height="250"
                    alt="QR Code"
                >

            </td>

        </tr>
    </table>

</body>

</html>