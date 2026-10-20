<script setup>
defineProps({
    card: { type: Object, required: true },
});

const money = (value) =>
    value === null || value === undefined
        ? ""
        : Number(value).toLocaleString("en-PH", {
              minimumFractionDigits: 2,
              maximumFractionDigits: 2,
          });

const qty = (value) => (value === null || value === undefined ? "" : value);
</script>

<template>
    <div
        class="mx-auto min-w-[760px] max-w-[1040px] rounded-lg border border-gray-300 bg-white px-7 py-6 font-serif text-[#111] shadow-sm"
    >
        <!-- Letterhead -->
        <div class="flex items-start justify-between">
            <img
                src="/images/UP-LOGO-PDF(2).png"
                alt="University of the Philippines"
                class="h-16 w-16 object-contain"
            />
            <div class="text-center leading-tight">
                <p class="text-[11px]">Republic of the Philippines</p>
                <p class="text-[15px] font-bold uppercase tracking-wide">
                    University of the Philippines
                </p>
                <p class="text-[15px] font-bold uppercase tracking-wide">
                    Cebu
                </p>
            </div>
            <img
                src="/images/UPC-LOGO-PDF(1).png"
                alt="UP Cebu"
                class="h-16 w-16 object-contain"
            />
        </div>

        <p class="mt-3 text-center text-xl font-bold tracking-[0.08em]">
            PROPERTY CARD
        </p>

        <p class="mt-1 text-[11px] leading-snug">
            <span class="font-bold">UNIVERSITY OF THE PHILIPPINES CEBU</span
            ><br />
            Supply &amp; Property Management Office
        </p>

        <!-- Fields -->
        <div class="mt-4 grid grid-cols-2 gap-x-8 gap-y-3 text-[11px]">
            <p>
                <span class="font-bold">Office / College:</span>
                <span class="ml-1 border-b border-black px-1 font-bold uppercase">{{
                    card.office || "—"
                }}</span>
            </p>
            <p class="text-right">
                <span class="font-bold">Fund Cluster:</span>
                <span class="ml-1 border-b border-black px-1 font-bold uppercase">{{
                    card.item.fund_cluster || "—"
                }}</span>
            </p>
            <p class="col-span-2">
                <span class="font-bold">Name of Accountable Person:</span>
                <span class="ml-1 border-b border-black px-1 font-bold uppercase">{{
                    card.item.accountable_person || "—"
                }}</span>
            </p>
        </div>

        <!-- Description -->
        <div
            class="mt-4 grid grid-cols-[1fr_260px] border border-black text-[11px]"
        >
            <div class="border-r border-black px-3 py-2">
                <p class="font-bold">Property, Plant and Equipment</p>
                <p class="mt-2 font-bold">
                    Description (brand, size, color, SN):
                </p>
                <p class="mt-1 min-h-[34px] leading-snug">
                    {{ card.item.item_name }}
                    <template v-if="card.item.brand">
                        — {{ card.item.brand }}
                    </template>
                    <template v-if="card.item.model">
                        {{ card.item.model }}
                    </template>
                    <template v-if="card.item.serial_number">
                        (SN: {{ card.item.serial_number }})
                    </template>
                    <template v-if="card.item.description">
                        <br />{{ card.item.description }}
                    </template>
                </p>
            </div>
            <div class="px-3 py-2 text-center">
                <p class="font-bold">Property Number:</p>
                <p
                    class="mx-auto mt-2 w-fit border-b border-black px-3 pb-0.5 text-[13px] font-bold"
                >
                    {{ card.item.property_number || "N/A" }}
                </p>
            </div>
        </div>

        <!-- Ledger -->
        <table class="mt-2 w-full table-fixed border-collapse text-[10px]">
            <colgroup>
                <col style="width: 10%" />
                <col style="width: 14%" />
                <col style="width: 6%" />
                <col style="width: 6%" />
                <col style="width: 22%" />
                <col style="width: 7%" />
                <col style="width: 10%" />
                <col style="width: 25%" />
            </colgroup>
            <thead>
                <tr class="font-bold">
                    <th rowspan="2" class="border border-black px-1 py-1">
                        Date
                    </th>
                    <th rowspan="2" class="border border-black px-1 py-1">
                        Reference / PAR No.
                    </th>
                    <th class="border border-black px-1 py-0.5">Receipt</th>
                    <th colspan="2" class="border border-black px-1 py-0.5">
                        Issue / Transfer / Disposal
                    </th>
                    <th class="border border-black px-1 py-0.5">Balance</th>
                    <th rowspan="2" class="border border-black px-1 py-1">
                        Amount
                    </th>
                    <th rowspan="2" class="border border-black px-1 py-1">
                        Remarks
                    </th>
                </tr>
                <tr class="font-bold">
                    <th class="border border-black px-1 py-0.5">Qty.</th>
                    <th class="border border-black px-1 py-0.5">Qty.</th>
                    <th class="border border-black px-1 py-0.5">
                        Office / Officer
                    </th>
                    <th class="border border-black px-1 py-0.5">Qty.</th>
                </tr>
            </thead>
            <tbody>
                <tr
                    v-for="(row, index) in card.rows"
                    :key="index"
                    :class="{
                        'bg-[#005740]/[0.04]': row.kind === 'receipt',
                    }"
                >
                    <td class="border border-black px-1 py-1 text-center">
                        {{ row.date }}
                    </td>
                    <td class="border border-black px-1 py-1">
                        {{ row.reference }}
                    </td>
                    <td class="border border-black px-1 py-1 text-center">
                        {{ qty(row.receipt_qty) }}
                    </td>
                    <td class="border border-black px-1 py-1 text-center">
                        {{ qty(row.issue_qty) }}
                    </td>
                    <td class="border border-black px-1 py-1">
                        {{ row.office_officer }}
                    </td>
                    <td class="border border-black px-1 py-1 text-center">
                        {{ qty(row.balance_qty) }}
                    </td>
                    <td class="border border-black px-1 py-1 text-right">
                        {{ money(row.amount) }}
                    </td>
                    <td class="border border-black px-1 py-1">
                        {{ row.remarks }}
                    </td>
                </tr>
                <tr v-if="!card.rows.length">
                    <td
                        colspan="8"
                        class="border border-black px-2 py-6 text-center text-gray-500"
                    >
                        No recorded history for this article yet.
                    </td>
                </tr>
            </tbody>
            <tfoot>
                <tr class="font-bold">
                    <td
                        colspan="5"
                        class="border border-black px-2 py-1.5 text-left"
                    >
                        Balance Carried Forward
                    </td>
                    <td class="border border-black px-1 py-1.5 text-center">
                        {{ card.balance }}
                    </td>
                    <td class="border border-black px-1 py-1.5"></td>
                    <td class="border border-black px-1 py-1.5"></td>
                </tr>
            </tfoot>
        </table>

        <div class="mt-2 flex items-center justify-between text-[9px] text-gray-500">
            <span>
                Rows fill as PAR, ICS or ITR documents, room moves and
                inspections are recorded.
            </span>
            <span v-if="card.item.condition">
                Current condition: <strong>{{ card.item.condition }}</strong>
            </span>
        </div>
    </div>
</template>

