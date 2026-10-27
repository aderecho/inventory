<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Inventory Transfer Report</title>

    <style>
        @page {
            size: A4 landscape;
            margin: 8mm 14mm;
        }

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            padding: 0;
            background: #fff;
        }

        body {
            font-family: "Times New Roman", Times, serif;
            color: #000;
            font-size: 10pt;
        }

        .page + .page {
            page-break-before: always;
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
            width: 25%;
            text-align: left;
        }

        .right-logo {
            width: 25%;
            text-align: right;
        }

        .logo-cell img,
        .right-logo img {
            width: 36mm;
            height: auto;
            object-fit: contain;
        }

        .header-center {
            width: 50%;
            text-align: center;
        }

        .republic {
            font-size: 9pt;
            margin-bottom: 1mm;
        }

        .university {
            font-size: 12pt;
            font-weight: bold;
            text-transform: uppercase;
        }

        .campus {
            font-size: 12pt;
            font-weight: bold;
            text-transform: uppercase;
        }

        .title {
            text-align: center;
            font-size: 12pt;
            font-weight: bold;
            margin: 1mm 0 2mm;
        }

        /* ========================= ENTITY / FUND ========================= */

        .meta-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 2mm;
        }

        .meta-table td {
            padding: 0 1mm;
            font-size: 10pt;
        }

        .fill {
            display: inline-block;
            min-width: 35mm;
            border-bottom: 1px solid #000;
            padding: 0 1mm;
        }

        /* ========================= MAIN FORM ========================= */

        .itr-table {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
            border: 1px solid #000;
        }

        .itr-table td,
        .itr-table th {
            padding: 0.6mm 1.5mm;
            font-size: 9.5pt;
            vertical-align: top;
        }

        .col-date {
            width: 10%;
        }

        .col-no {
            width: 6%;
        }

        .col-ics {
            width: 13%;
        }

        .col-qty {
            width: 7%;
        }

        .col-description {
            width: 38%;
        }

        .col-amount {
            width: 10%;
        }

        .col-condition {
            width: 16%;
        }

        .person-name {
            font-weight: bold;
            text-transform: uppercase;
        }

        .itr-box {
            border-left: 1px solid #000;
            text-align: center;
            line-height: 1.6;
        }

        .underlined {
            font-weight: bold;
            text-decoration: underline;
        }

        .section-top > td {
            border-top: 1px solid #000;
        }

        /* Transfer type check boxes */

        .types {
            width: 100%;
            border-collapse: collapse;
            margin: 0.5mm 0 1mm;
        }

        .types td {
            width: 50%;
            padding: 0.3mm 0 0.3mm 30mm;
            font-size: 9.5pt;
        }

        .types td.right {
            padding-left: 34mm;
        }

        .box {
            position: relative;
            display: inline-block;
            width: 3.4mm;
            height: 3.4mm;
            border: 1px solid #000;
            margin-right: 2mm;
            vertical-align: -0.6mm;
        }

        /* Drawn with borders so it does not depend on the PDF font having a check glyph. */
        .box.checked::after {
            content: "";
            position: absolute;
            left: 1.05mm;
            top: 0.2mm;
            width: 0.9mm;
            height: 2mm;
            border: solid #000;
            border-width: 0 0.45mm 0.45mm 0;
            transform: rotate(45deg);
        }

        /* Item rows */

        .items-head th {
            border: 1px solid #000;
            font-weight: bold;
            text-align: center;
            vertical-align: bottom;
            padding-top: 1mm;
        }

        .items-head th:first-child {
            text-align: left;
        }

        .item-row td {
            border: 1px solid #000;
            text-align: center;
            vertical-align: middle;
        }

        .item-row td.description {
            text-align: left;
            line-height: 1.25;
        }

        .item-row td.amount {
            text-align: right;
            white-space: nowrap;
        }

        .sub {
            display: block;
            font-size: 8pt;
        }

        .nothing-follows td {
            border: 1px solid #000;
            text-align: center;
        }

        .reason td {
            border-top: 1px solid #000;
            height: 9mm;
        }

        /* ========================= SIGNATURES ========================= */

        .sign-block {
            break-inside: avoid;
            page-break-inside: avoid;
        }

        /* Four or more items: the signatories start on the next page. */
        .sign-block.next-page {
            break-before: page;
            page-break-before: always;
        }

        .sign-block.next-page .sign-table {
            border-top: 1px solid #000;
        }

        .sign-table {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
            border: 1px solid #000;
            border-top: none;
        }

        .sign-table td {
            font-size: 9.5pt;
            padding: 0.6mm 1.5mm;
            text-align: center;
            vertical-align: bottom;
        }

        .sign-table td.label {
            width: 14%;
            text-align: left;
        }

        .sign-table .role td {
            padding-top: 1.5mm;
            padding-bottom: 1mm;
        }

        .sign-table .line td {
            height: 8mm;
        }

        .sign-line {
            display: inline-block;
            width: 62mm;
            border-bottom: 1px solid #000;
        }

        .sign-table .name {
            font-weight: bold;
            text-decoration: underline;
            text-transform: uppercase;
            padding-top: 3mm;
        }

        .sign-table .designation,
        .sign-table .date {
            font-weight: bold;
            text-decoration: underline;
        }

        /* DomPDF ignores <col> widths and sizes a fixed table from its first row instead. */
        tr.col-widths td {
            height: 0;
            padding: 0;
            border: 0;
        }
    </style>
</head>

<body>

    @foreach ($groupedTransferItems as $receiptId => $receiptItems)

        @php
            $firstAckItem = $receiptItems->first();
            // A group holds every receipt record sharing this receipt number.
            $groupReceipts = $receiptItems
                ->map(fn ($item) => $item->acknowledgementReceipts)
                ->filter()
                ->unique('id')
                ->values();

            $receipt = $groupReceipts->first() ?? $firstAckItem->acknowledgementReceipts;
            $firstItem = $firstAckItem->inventoryItems;

            $fullName = fn ($profile) => $profile
                ? trim(collect([$profile->first_name, $profile->middle_name ? mb_substr($profile->middle_name, 0, 1) . '.' : null, $profile->last_name])->filter()->implode(' '))
                : null;

            // Everyone who held these items before this transfer.
            $fromProfiles = $receiptItems
                ->map(fn ($item) => $previousAckItems[$item->id]?->accountablePerson)
                ->filter()
                ->unique('id');

            $fromNames = $fromProfiles->map($fullName)->filter()->implode(', ');
            $fromOffices = $fromProfiles->pluck('primary_unit_division_department')->filter()->unique()->implode(', ');

            $toProfile = $firstAckItem->accountablePerson;
            // Older reassignments have no approver recorded; fall back to the issuer.
            $approver = $firstAckItem->approvedBy ?? $firstAckItem->issuedBy;

            $transferTypes = $receiptItems
                ->flatMap(fn ($item) => $item->transferTypes->pluck('name'))
                ->map(fn ($name) => strtolower(trim($name)))
                ->unique();

            $isChecked = fn ($name) => $transferTypes->contains(strtolower($name));
            $otherTypes = $transferTypes
                ->reject(fn ($name) => in_array($name, ['donation', 'reassignment', 'relocate', 'new assignment']))
                ->map(fn ($name) => ucwords($name))
                ->merge(
                    // Free text typed in "Others (specify)" when reassigning.
                    $receiptItems->pluck('transfer_type_other')->map(fn ($text) => trim((string) $text))->filter()
                )
                ->unique()
                ->implode(', ');

            $transferDate = $receipt?->par_date ? \Carbon\Carbon::parse($receipt->par_date) : null;
            $signDate = $transferDate?->format('n/j/Y') ?: 'N/A';
            $reason = (string) $groupReceipts
                ->map(fn ($groupReceipt) => trim((string) $groupReceipt->reason_for_transfer))
                ->first(fn ($value) => $value !== '');

            // Three items fit with the signatories on one page; from four on they move to the next page.
            $signaturesOnNextPage = $receiptItems->count() >= 4;
        @endphp

        <div class="page">

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

            <div class="title">INVENTORY TRANSFER REPORT</div>

            <!-- ================= ENTITY / FUND CLUSTER ================= -->

            <table class="meta-table">
                <tr>
                    <td>Entity Name: University of the Philippines Cebu</td>
                    <td style="text-align: right;">
                        Fund Cluster: <span class="fill">{{ $firstItem->fund_source ?: 'N/A' }}</span>
                    </td>
                </tr>
            </table>

            <!-- ================= TRANSFER DETAILS AND ITEMS ================= -->

            <table class="itr-table">
                <tr class="col-widths">
                    <td class="col-date"></td>
                    <td class="col-no"></td>
                    <td class="col-ics"></td>
                    <td class="col-qty"></td>
                    <td class="col-description"></td>
                    <td class="col-amount"></td>
                    <td class="col-condition"></td>
                </tr>

                <tr>
                    <td colspan="3">From Accountable Officer/Agency/Fund Cluster:</td>
                    <td colspan="2" class="person-name">
                        {{ $fromNames ?: 'N/A' }}
                        <span class="sub" style="font-weight: normal;">{{ $fromOffices ?: 'N/A' }}</span>
                    </td>
                    <td colspan="2" rowspan="2" class="itr-box">
                        ITR No.: <span class="underlined">{{ $receipt->category ?? 'N/A' }}</span><br>
                        Date: <span class="underlined">{{ $transferDate?->format('m-d-Y') ?? 'N/A' }}</span>
                    </td>
                </tr>

                <tr>
                    <td colspan="3">To Accountable Officer/Agency/Fund Cluster:</td>
                    <td colspan="2" class="person-name">
                        {{ $fullName($toProfile) ?: 'N/A' }}
                        <span class="sub" style="font-weight: normal;">{{ $toProfile?->primary_unit_division_department ?: 'N/A' }}</span>
                    </td>
                </tr>

                <tr class="section-top">
                    <td colspan="7">
                        Transfer Type: (check only one)

                        <table class="types">
                            <tr>
                                <td><span @class(['box', 'checked' => $isChecked('donation')])></span>Donation</td>
                                <td class="right"><span @class(['box', 'checked' => $isChecked('relocate')])></span>Relocate</td>
                            </tr>
                            <tr>
                                <td><span @class(['box', 'checked' => $isChecked('reassignment')])></span>Reassignment</td>
                                <td class="right">
                                    <span @class(['box', 'checked' => (bool) $otherTypes])></span>Others (Specify)
                                    <span class="fill" style="min-width: 30mm;">{{ $otherTypes ?: 'N/A' }}</span>
                                </td>
                            </tr>
                        </table>
                    </td>
                </tr>

                <tr class="items-head">
                    <th>Date Acquired</th>
                    <th>Item No.</th>
                    <th>ICS No./Date</th>
                    <th>Quantity</th>
                    <th>Description</th>
                    <th>Amount</th>
                    <th>Condition of Inventory</th>
                </tr>

                @foreach ($receiptItems->values() as $index => $item)
                    @php
                        $inventory = $item->inventoryItems;
                        $previous = $previousAckItems[$item->id] ?? null;
                        $previousReceipt = $previous?->acknowledgementReceipts;
                        $quantity = $inventory->quantity;
                        $amount = (float) ($inventory->unit_cost ?? 0) * ($quantity ?? 1);
                    @endphp

                    <tr class="item-row">
                        <td>
                            {{ $inventory->date_acquired ? \Carbon\Carbon::parse($inventory->date_acquired)->format('n/j/Y') : 'N/A' }}
                        </td>
                        <td>{{ $index + 1 }}</td>
                        <td>
                            {{ $inventory->property_number ?? 'N/A' }}
                            <span class="sub">{{ $previousReceipt?->par_date ? \Carbon\Carbon::parse($previousReceipt->par_date)->format('n/j/Y') : 'N/A' }}</span>
                        </td>
                        <td>{{ $quantity ?? 'N/A' }}</td>
                        <td class="description">
                            {{ $inventory->item_name ?? 'N/A' }}
                            <span class="sub">Property No.: {{ $inventory->property_number ?? 'N/A' }}</span>
                            <span class="sub"><strong>SN:</strong> {{ $inventory->serial_number ?: 'N/A' }}</span>
                        </td>
                        <td class="amount">{{ number_format($amount, 2) }}</td>
                        <td>{{ $inventory->latestInspection?->assetCondition?->condition_name ?? 'N/A' }}</td>
                    </tr>
                @endforeach

                <tr class="nothing-follows">
                    <td colspan="7">Nothing follows*</td>
                </tr>

                <tr class="reason">
                    <td colspan="7">
                        <strong>Reason/s for Transfer</strong>: <u>{{ $reason ?: 'N/A' }}</u>
                    </td>
                </tr>
            </table>

            <!-- ================= SIGNATURES ================= -->

            <div @class(['sign-block', 'next-page' => $signaturesOnNextPage])>
            <table class="sign-table">
                <tr class="role">
                    <td class="label"></td>
                    <td>Approved by:</td>
                    <td>Released/Issued by:</td>
                    <td>Received by:</td>
                </tr>

                <tr class="line">
                    <td class="label">Signature:</td>
                    <td><span class="sign-line"></span></td>
                    <td><span class="sign-line"></span></td>
                    <td><span class="sign-line"></span></td>
                </tr>

                <tr>
                    <td class="label">Printed Name:</td>
                    <td class="name">{{ $fullName($approver) ?: 'N/A' }}</td>
                    <td class="name">{{ $fromNames ?: 'N/A' }}</td>
                    <td class="name">{{ $fullName($toProfile) ?: 'N/A' }}</td>
                </tr>

                <tr>
                    <td class="label">Designation:</td>
                    <td class="designation">{{ $approver->primary_unit_division_department ?? 'N/A' }}</td>
                    <td class="designation">{{ $fromOffices ?: 'N/A' }}</td>
                    <td class="designation">{{ $toProfile->primary_unit_division_department ?? 'N/A' }}</td>
                </tr>

                <tr>
                    <td class="label" style="padding-bottom: 2mm;">Date:</td>
                    <td class="date" style="padding-bottom: 2mm;">{{ $signDate }}</td>
                    <td class="date" style="padding-bottom: 2mm;">{{ $signDate }}</td>
                    <td class="date" style="padding-bottom: 2mm;">{{ $signDate }}</td>
                </tr>
            </table>
            </div>

        </div>

    @endforeach

</body>

</html>
