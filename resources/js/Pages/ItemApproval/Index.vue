<script setup>
import { computed, ref } from "vue";
import { usePage } from "@inertiajs/vue3";
import { Head } from "@inertiajs/vue3";
import Layout from "@/Layouts/Layout.vue";
import PageHeader from "@/Components/PageHeader.vue";
import InventoryTable from "@/Components/InventoryTable.vue";
import SearchFilterBar from "@/Components/Filters/SearchFilterBar.vue";

const page = usePage();

const items = computed(() => page.props.items || { data: [] });

const search = ref("");
const approvalStatus = ref("");

const approvalFilter = [
    {
        label: "Approval Status",
        options: [
            { label: "All items", value: "" },
            { label: "Pending", value: "pending" },
            { label: "Approved", value: "approved" },
            { label: "Rejected", value: "rejected" },
        ],
    },
];

// Same wording and colours the accountable person sees on their dashboard.
const approvalLabel = (status) => {
    if (status === "approved") return "Approved";
    if (status === "rejected") return "Rejected";

    return "Pending";
};

const approvalColours = (status) => {
    if (status === "approved") return "bg-[#D4F8D4] text-[#2E7D32]";
    if (status === "rejected") return "bg-red-100 text-red-700";

    return "bg-amber-100 text-amber-700";
};

// What the item is, where it sits, who holds it, and what they answered.
// Read-only - there are no actions to take here.
const columns = [
    { label: "Item Name", key: "item_name" },
    { label: "Property Number", key: "property_number" },
    {
        label: "Unit Cost",
        key: "unit_cost",
        format: (value) =>
            value != null
                ? `₱${Number(value).toLocaleString("en-PH", {
                      minimumFractionDigits: 2,
                      maximumFractionDigits: 2,
                  })}`
                : "N/A",
    },
    { label: "Room Name", key: "room_name" },
    {
        label: "Accountable Person",
        key: "latest_acknowledgement_item",
        format: (value) => {
            const person = value?.accountable_person;
            const fullName =
                `${person?.first_name ?? ""} ${person?.last_name ?? ""}`.trim();

            if (!fullName) {
                return '<span class="text-[#D32F2F] font-bold">Unassigned</span>';
            }

            return fullName;
        },
    },
    {
        label: "PAR/ICS",
        key: "latest_acknowledgement_item.acknowledgement_receipts",
        format: (value) =>
            value?.category ??
            '<span class="text-gray-400 italic">No Receipt</span>',
    },
    {
        label: "Approval Status",
        key: "approval_status",
        format: (value) =>
            '<span class="rounded-full px-2 py-1 text-[11px] font-semibold ' +
            approvalColours(value) +
            '">' +
            approvalLabel(value) +
            "</span>",
    },
];
</script>

<template>
    <Head title="UP | Item Approval" />

    <Layout>
        <PageHeader title="Item Approval" />

        <div class="w-full h-full">
            <div class="mt-6 rounded-xl border border-gray-200 bg-white p-3 shadow-sm">
                <div class="flex flex-col gap-3 xl:flex-row xl:items-end xl:justify-between">
                    <span
                        class="rounded-full border border-[#005740]/20 bg-[#005740]/[0.06] px-3 py-1.5 text-xs font-semibold text-[#005740]"
                    >
                        {{ items.total ?? 0 }} item(s)
                    </span>

                    <div class="w-full xl:max-w-xl">
                        <SearchFilterBar
                            :search="search"
                            :acknowledgement_status="approvalStatus"
                            :acknowledgementFilter="approvalFilter"
                            @update:search="search = $event"
                            @update:acknowledgement_status="
                                approvalStatus = $event
                            "
                            :mode="'item-approvals'"
                        />
                    </div>
                </div>
            </div>

            <InventoryTable
                :columns="columns"
                :rows="items"
                :module="'item-approvals'"
            />
        </div>
    </Layout>
</template>
