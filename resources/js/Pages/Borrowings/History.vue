<script setup>
import { ref, computed } from "vue";
import { usePage } from "@inertiajs/vue3";
import { Head } from "@inertiajs/vue3";
import Layout from "@/Layouts/Layout.vue";
import PageHeader from "@/Components/PageHeader.vue";
import InventoryTable from "@/Components/InventoryTable.vue";
import SearchFilterBar from "@/Components/Filters/SearchFilterBar.vue";

const page = usePage();

const lines = computed(() => page.props.lines || { data: [] });
const counts = computed(() => page.props.counts || { borrowed: 0, overdue: 0 });

const search = ref("");
const status = ref("");

const statusFilter = [
    {
        label: "Status",
        options: [
            { label: "All records", value: "" },
            { label: "Still out", value: "out" },
            { label: "Returned", value: "returned" },
            { label: "Overdue", value: "overdue" },
        ],
    },
];

const badge = (label, tone, plain = false) => {
    const tones = {
        green: "bg-[#D4F8D4] text-[#2E7D32]",
        amber: "bg-amber-100 text-amber-700",
        red: "bg-red-100 text-red-700",
        gray: "bg-gray-100 text-gray-600",
    };

    // Statuses carry colour on the text only; the borrower-type badge keeps its
    // pill so the two columns stay visually distinct.
    const plainTones = {
        green: "text-[#2E7D32]",
        amber: "text-amber-700",
        red: "text-red-700",
        gray: "text-gray-600",
    };

    return (
        '<span class="' +
        (plain ? "text-[11px] font-semibold " : "rounded-full px-2 py-1 text-[11px] font-semibold ") +
        (plain ? plainTones[tone] : tones[tone]) +
        '">' +
        label +
        "</span>"
    );
};

const columns = [
    { label: "Reference", key: "reference_code" },
    { label: "Borrower", key: "history_borrower" },
    {
        label: "Type",
        key: "borrower_type_label",
        format: (value) => badge(value, value === "Staff (PUSO)" ? "green" : "gray"),
    },
    { label: "Item", key: "item_label" },
    { label: "Borrowed", key: "borrowed_label" },
    { label: "Due", key: "due_label" },
    { label: "Returned", key: "returned_label" },
    { label: "Condition", key: "condition_label" },
    {
        label: "Status",
        key: "status_label",
        format: (value) =>
            badge(
                value,
                value === "Returned"
                    ? "gray"
                    : value === "Overdue"
                      ? "red"
                      : "amber",
                true,
            ),
    },
];
</script>

<template>
    <Head title="UP | Borrowing History" />

    <Layout>
        <PageHeader title="Borrowing History" />

        <div class="w-full h-full">
            <div class="mt-6 rounded-xl border border-gray-200 bg-white p-3 shadow-sm">
                <div class="flex flex-col gap-3 xl:flex-row xl:items-end xl:justify-between">
                    <div class="flex flex-wrap items-center gap-2">
                        <span
                            class="flex items-center gap-2 rounded-full border border-[#005740]/20 bg-[#005740]/[0.06] px-3 py-1.5 text-xs font-semibold text-[#005740]"
                        >
                            {{ counts.borrowed }} loans still open
                        </span>

                        <span
                            v-if="counts.overdue"
                            class="flex items-center gap-2 rounded-full border border-red-200 bg-red-50 px-3 py-1.5 text-xs font-semibold text-red-600"
                        >
                            {{ counts.overdue }} overdue
                        </span>
                    </div>

                    <div class="w-full xl:max-w-xl">
                        <SearchFilterBar
                            :search="search"
                            :acknowledgement_status="status"
                            :acknowledgementFilter="statusFilter"
                            @update:search="search = $event"
                            @update:acknowledgement_status="status = $event"
                            :mode="'borrowings-history'"
                        />
                    </div>
                </div>

                <p class="mt-2 text-[11px] text-gray-400">
                    One row per item lent, so a loan of three items shows three
                    rows with the same reference.
                </p>
            </div>

            <InventoryTable
                :columns="columns"
                :rows="lines"
                :module="'borrowings-history'"
                :actions="[]"
                :selected="[]"
            />
        </div>
    </Layout>
</template>

