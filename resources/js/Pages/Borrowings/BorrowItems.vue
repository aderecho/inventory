<script setup>
import { ref, computed, watch } from "vue";
import { usePage, router } from "@inertiajs/vue3";
import { Head } from "@inertiajs/vue3";
import Layout from "@/Layouts/Layout.vue";
import PageHeader from "@/Components/PageHeader.vue";
import InventoryTable from "@/Components/InventoryTable.vue";
import SearchFilterBar from "@/Components/Filters/SearchFilterBar.vue";
import BorrowItemsModal from "@/Components/Modals/BorrowItemsModal.vue";
import Toast from "primevue/toast";
import { useToast } from "primevue/usetoast";
import { useLoading } from "@/Composables/useLoading";
import { usePermissions } from "@/Composables/usePermissions";

const { startLoading, stopLoading } = useLoading();
const { canBorrowItems } = usePermissions();
const page = usePage();
const toast = useToast();

const items = computed(() => page.props.items || { data: [] });
const assetConditions = computed(() => page.props.assetConditions || []);

const rooms = computed(() => page.props.rooms || []);

const search = ref("");
const cost_range = ref("");
const acknowledgement_status = ref("");
const asset_condition_id = ref("");
const room_id = ref("");

const columns = [
    { label: "", key: "select_all" },
    { label: "Item Name", key: "item_name" },
    { label: "Unit", key: "unit", format: (val) => val ?? "N/A" },
    {
        label: "Unit Cost",
        key: "unit_cost",
        format: (val) =>
            val != null
                ? "\u20B1" +
                  Number(val).toLocaleString("en-PH", {
                      minimumFractionDigits: 2,
                      maximumFractionDigits: 2,
                  })
                : "N/A",
    },
    { label: "Property Number", key: "property_number" },
    { label: "Serial Number", key: "serial_number" },
    { label: "PO Number", key: "po_number" },
    { label: "Room Name", key: "room_name" },
    {
        label: "Accountable Person",
        key: "latest_acknowledgement_item",
        format: (val) => {
            const person = val?.accountable_person;

            if (!person) {
                return '<span class="font-bold text-[#D32F2F]">Unassigned</span>';
            }

            return (
                ((person.first_name ?? "") + " " + (person.last_name ?? "")).trim() ||
                '<span class="font-bold text-[#D32F2F]">Unassigned</span>'
            );
        },
    },
    {
        label: "Acknoweldgement Receipt",
        key: "latest_acknowledgement_item.acknowledgement_receipts",
        format: (val) =>
            val?.category ?? '<span class="italic text-gray-400">No Receipt</span>',
    },
    {
        label: "Condition",
        key: "latest_inspection",
        format: (val) => {
            const condition = val?.asset_condition?.condition_name;

            return condition
                ? '<span class="px-2 py-1 font-semibold text-black">' + condition + "</span>"
                : '<span class="italic text-gray-400">Not Inspected</span>';
        },
    },
    {
        label: "Status",
        key: "status",
        format: (value) =>
            value === "borrowed"
                ? '<span class="text-[11px] font-semibold text-amber-700">Borrowed</span>'
                : '<span class="text-[11px] font-semibold text-[#2E7D32]">Available</span>',
    },
];

const unitCostOptions = [
    {
        label: "Unit Cost",
        options: [
            { label: "Select All", value: "" },
            { label: "\u20B150,000 Below", value: "0-50000" },
            { label: "\u20B150,000 Above", value: "50000-99999999" },
        ],
    },
];

const acknowledgementFilter = [
    {
        label: "Accountable Person",
        options: [
            { label: "All Items", value: "" },
            { label: "Assigned", value: "with_acknowledgement" },
            { label: "Unassigned", value: "without_acknowledgement" },
        ],
    },
];

const filterCondition = computed(() => [
    {
        label: "Condition",
        options: (assetConditions.value ?? []).map((condition) => ({
            label: condition.condition_name,
            value: condition.id,
        })),
    },
]);

const selectedMap = ref(new Map());

const selectedIds = computed(() => Array.from(selectedMap.value.keys()));
const selectedDetails = computed(() => Array.from(selectedMap.value.values()));

function handleSelectionChanged(ids, currentPageRows = []) {
    const idSet = new Set(ids);
    const next = new Map(selectedMap.value);

    currentPageRows.forEach((row) => {
        if (idSet.has(row.id)) next.set(row.id, row);
    });

    for (const key of next.keys()) {
        if (!idSet.has(key)) next.delete(key);
    }

    idSet.forEach((id) => {
        if (!next.has(id) && selectedMap.value.has(id)) {
            next.set(id, selectedMap.value.get(id));
        }
    });

    selectedMap.value = next;
}

function clearSelection() {
    selectedMap.value = new Map();
}

const showBorrowModal = ref(false);

function openBorrow() {
    showBorrowModal.value = true;
}

function lendItems(payload) {
    startLoading("Lending Items...", "Recording the borrower and updating each item.");

    router.post(route("borrowings.store"), payload, {
        preserveScroll: true,
        onSuccess: () => {
            showBorrowModal.value = false;
            clearSelection();
        },
        onError: (errors) => {
            toast.add({
                severity: "error",
                summary: "Could not lend the items",
                detail: Object.values(errors)[0] ?? "Please check the form.",
                life: 5000,
            });
        },
        onFinish: () => stopLoading(),
    });
}

watch(
    () => page.props.flash,
    (flash) => {
        if (!flash?.success) return;

        toast.add({
            severity: "success",
            summary: "Success",
            detail: flash.success,
            life: 3500,
        });
    },
    { immediate: true },
);
</script>

<template>
    <Head title="UP | Borrow Items" />

    <Layout>
        <Toast />

        <BorrowItemsModal
            :show="showBorrowModal"
            :items="selectedDetails"
            :assetConditions="assetConditions"
            @submit="lendItems"
            @close="showBorrowModal = false"
        />

        <PageHeader title="Borrow Items" />

        <div class="w-full h-full">
            <div class="mt-6 rounded-xl border border-gray-200 bg-white p-3 shadow-sm">
                <div class="flex flex-col gap-3 xl:flex-row xl:items-end xl:justify-between">
                    <div class="flex flex-wrap items-center gap-2">
                        <button
                            v-if="canBorrowItems"
                            type="button"
                            @click="openBorrow"
                            class="flex items-center gap-2 rounded-md bg-[#005740] px-3 py-2 text-xs text-white transition-colors hover:bg-[#00452f] disabled:cursor-not-allowed disabled:opacity-40 sm:text-sm"
                        >
                            <i class="fa-solid fa-hand-holding"></i>
                            <span>Borrow</span>
                            <span
                                class="inline-flex h-5 w-7 shrink-0 items-center justify-center rounded-full bg-white/25 text-[11px] tabular-nums"
                            >
                                {{ selectedIds.length }}
                            </span>
                        </button>

                        <span
                            class="rounded-full border border-[#005740]/20 bg-[#005740]/[0.06] px-3 py-1.5 text-xs font-semibold text-[#005740]"
                        >
                            {{ items.total ?? 0 }} item(s) on the shelf
                        </span>
                    </div>

                    <div class="w-full xl:max-w-xl">
                        <SearchFilterBar
                            :search="search"
                            :cost_range="cost_range"
                            :room_id="room_id"
                            :rooms="rooms"
                            :unitCostOptions="unitCostOptions"
                            :filterCondition="filterCondition"
                            :acknowledgementFilter="acknowledgementFilter"
                            @update:search="search = $event"
                            @update:asset_condition_id="
                                asset_condition_id = $event
                            "
                            @update:cost_range="cost_range = $event"
                            @update:acknowledgement_status="
                                acknowledgement_status = $event
                            "
                            :mode="'borrow-items'"
                            :collapsible-filters="true"
                        />
                    </div>
                </div>
            </div>

            <InventoryTable
                :columns="columns"
                :rows="items"
                :module="'borrow-items'"
                :actions="[]"
                :selected="selectedIds"
                @selection-changed="handleSelectionChanged"
            />
        </div>
    </Layout>
</template>

