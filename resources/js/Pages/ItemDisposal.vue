<script setup>
import { ref, computed, watch } from "vue";
import { usePage, router } from "@inertiajs/vue3";
import { Head } from "@inertiajs/vue3";
import Layout from "@/Layouts/Layout.vue";
import PageHeader from "@/Components/PageHeader.vue";
import InventoryTable from "@/Components/InventoryTable.vue";
import SearchFilterBar from "@/Components/Filters/SearchFilterBar.vue";
import BatchRestoreModal from "@/Components/Modals/BatchRestoreModal.vue";
import BatchForceDeleteModal from "@/Components/Modals/BatchForceDeleteModal.vue";
import Toast from "primevue/toast";
import { useToast } from "primevue/usetoast";
import { useLoading } from "@/Composables/useLoading";
import { usePermissions } from "@/Composables/usePermissions";

const { startLoading, stopLoading } = useLoading();
const { archiveItemActions } = usePermissions();
const page = usePage();
const toast = useToast();

const items = computed(() => page.props.items || { data: [] });
const rooms = computed(() => page.props.rooms || []);
const assetConditions = computed(() => page.props.assetConditions || []);

// Filters: the same set the Item List (inventory) module uses.
const search = ref("");
const cost_range = ref("");
const acknowledgement_status = ref("");
const asset_condition_id = ref("");
const room_id = ref("");

const unitCostOptions = [
    {
        label: "Unit Cost",
        options: [
            { label: "Select All", value: "" },
            { label: "₱50,000 Below", value: "0-50000" },
            { label: "₱50,000 Above", value: "50000-99999999" },
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
        options: assetConditions.value.map((condition) => ({
            label: condition.condition_name,
            value: condition.id,
        })),
    },
]);

const columns = [
    { label: "", key: "select_all" },
    { label: "Item Name", key: "item_name" },
    { label: "Unit", key: "unit", format: (val) => val ?? "N/A" },
    {
        label: "Unit Cost",
        key: "unit_cost",
        format: (val) =>
            val != null
                ? `₱${Number(val).toLocaleString("en-PH", {
                      minimumFractionDigits: 2,
                      maximumFractionDigits: 2,
                  })}`
                : "N/A",
    },
    { label: "Property Number", key: "property_number" },
    { label: "Serial Number", key: "serial_number" },
    { label: "Invoice", key: "invoice" },
    {
        label: "Supplier Name",
        key: "supplier",
        format: (val) => val?.supplier_name ?? "N/A",
    },
    {
        label: "Status",
        key: "status",
        format: (status) => {
            if (status === 0)
                return `<span class="text-[#D32F2F] font-bold bg-[#F8D4D4] py-1 px-2 rounded-md">Unserviceable</span>`;
            if (status === 1)
                return `<span class="text-[#2E7D32] font-bold bg-[#D4F8D4] py-1 px-2 rounded-md">Serviceable</span>`;
            return `<span class="text-gray-500">Unknown</span>`;
        },
    },
    { label: "Action", key: "action" },
];

// The table renders a button per action, so only the archive ones are passed.
const rowActions = computed(() =>
    (archiveItemActions.value ?? []).filter((action) =>
        ["restore", "force-delete"].includes(action),
    ),
);

// SELECTION
const selectedItemsMap = ref(new Map());

// Derived from the map below, so the table's selection-changed event is the
// single source of truth and selections survive pagination.
const tempSelectedIds = computed(() =>
    Array.from(selectedItemsMap.value.keys()),
);
const selectedItemsDetails = computed(() =>
    Array.from(selectedItemsMap.value.values()),
);

function handleSelectionChanged(ids, currentPageRows = []) {
    const idSet = new Set(ids);
    const newMap = new Map(selectedItemsMap.value);

    currentPageRows.forEach((row) => {
        if (idSet.has(row.id)) newMap.set(row.id, row);
    });

    for (const key of newMap.keys()) {
        if (!idSet.has(key)) newMap.delete(key);
    }

    idSet.forEach((id) => {
        if (!newMap.has(id) && selectedItemsMap.value.has(id)) {
            newMap.set(id, selectedItemsMap.value.get(id));
        }
    });

    selectedItemsMap.value = newMap;
}

function clearSelection() {
    selectedItemsMap.value = new Map();
}

// MODALS
const showRestoreModal = ref(false);
const showForceDeleteModal = ref(false);
const modalItems = ref([]);

function openRestore(rows) {
    modalItems.value = rows;
    showRestoreModal.value = true;
}

function openForceDelete(rows) {
    modalItems.value = rows;
    showForceDeleteModal.value = true;
}

// Row actions reuse the same modals with a single row.
function handleRestoreItem(item) {
    openRestore([item]);
}

function handleForceDeleteItem(item) {
    openForceDelete([item]);
}

function confirmRestore() {
    startLoading(
        "Restoring Items...",
        "Please wait while we restore the selected items.",
    );

    router.patch(
        route("items.batch-restore"),
        { ids: modalItems.value.map((item) => item.id) },
        {
            preserveScroll: true,
            onSuccess: () => {
                showRestoreModal.value = false;
                clearSelection();
            },
            onFinish: () => stopLoading(),
        },
    );
}

function confirmForceDelete() {
    startLoading(
        "Deleting Items...",
        "Please wait while we permanently delete the selected items.",
    );

    router.delete(route("items.batch-force-delete"), {
        data: { ids: modalItems.value.map((item) => item.id) },
        preserveScroll: true,
        onSuccess: () => {
            showForceDeleteModal.value = false;
            clearSelection();
        },
        onFinish: () => stopLoading(),
    });
}

// Backend flash messages (restore conflicts, counts, failures).
watch(
    () => page.props.flash,
    (flash) => {
        if (!flash) return;

        if (flash.success) {
            toast.add({
                severity: "success",
                summary: "Success",
                detail: flash.success,
                life: 3000,
            });
        }

        if (flash.warning) {
            toast.add({
                severity: "warn",
                summary: "Not Restored",
                detail: flash.warning,
                life: 6000,
            });
        }

        if (flash.error) {
            toast.add({
                severity: "error",
                summary: "Error",
                detail: flash.error,
                life: 5000,
            });
        }
    },
    { immediate: true },
);
</script>

<template>
    <Head title="UP | Item Archive" />

    <Layout>
        <Toast />

        <BatchRestoreModal
            v-if="showRestoreModal"
            :items="modalItems"
            entityLabel="item"
            @confirm="confirmRestore"
            @close="showRestoreModal = false"
        />

        <BatchForceDeleteModal
            v-if="showForceDeleteModal"
            :items="modalItems"
            entityLabel="item"
            @confirm="confirmForceDelete"
            @close="showForceDeleteModal = false"
        />

        <PageHeader title="Item Archive" />

        <div class="w-full h-full">
            <div
                class="mt-6 rounded-xl border border-gray-200 bg-white p-3 shadow-sm"
            >
                <div
                    class="flex flex-col gap-3 xl:flex-row xl:items-end xl:justify-between"
                >
                    <div class="flex flex-wrap items-center gap-2">
                        <button
                            v-if="rowActions.includes('restore')"
                            type="button"
                            :disabled="tempSelectedIds.length === 0"
                            @click="openRestore(selectedItemsDetails)"
                            class="flex items-center gap-2 rounded-md bg-[#0E6021] px-3 py-2 text-xs text-white transition-colors hover:bg-[#19703a] disabled:cursor-not-allowed disabled:opacity-40 sm:text-sm"
                        >
                            <i class="fa-solid fa-rotate-left"></i>
                            <span>Restore Selected</span>
                            <span
                                class="inline-flex h-5 w-7 shrink-0 items-center justify-center rounded-full bg-white/25 text-[11px] tabular-nums leading-5"
                            >
                                {{ tempSelectedIds.length }}
                            </span>
                        </button>

                        <button
                            v-if="rowActions.includes('force-delete')"
                            type="button"
                            :disabled="tempSelectedIds.length === 0"
                            @click="openForceDelete(selectedItemsDetails)"
                            class="flex items-center gap-2 rounded-md bg-[#D71D1D] px-3 py-2 text-xs text-white transition-colors hover:bg-[#b81616] disabled:cursor-not-allowed disabled:opacity-40 sm:text-sm"
                        >
                            <i class="fa-solid fa-trash-can"></i>
                            <span>Delete Permanently</span>
                            <span
                                class="inline-flex h-5 w-7 shrink-0 items-center justify-center rounded-full bg-white/25 text-[11px] tabular-nums leading-5"
                            >
                                {{ tempSelectedIds.length }}
                            </span>
                        </button>
                    </div>

                    <div class="w-full xl:max-w-3xl">
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
                            :mode="'item-archive'"
                            :collapsible-filters="true"
                        />
                    </div>
                </div>

                <!-- Selection summary -->
                <div
                    class="mt-3 flex flex-wrap items-center gap-2 rounded-lg border px-3 py-2 transition-colors"
                    :class="
                        tempSelectedIds.length
                            ? 'border-[#005740]/20 bg-[#005740]/[0.06]'
                            : 'border-gray-200 bg-gray-50'
                    "
                >
                    <span
                        class="flex items-center gap-2 text-xs font-semibold transition-colors"
                        :class="
                            tempSelectedIds.length
                                ? 'text-[#005740]'
                                : 'text-gray-500'
                        "
                    >
                        <i class="fa-solid fa-circle-check"></i>
                        {{ tempSelectedIds.length }} selected
                    </span>

                    <button
                        v-if="tempSelectedIds.length"
                        type="button"
                        @click="clearSelection"
                        class="ml-auto text-xs font-semibold text-[#005740] hover:underline"
                    >
                        Clear
                    </button>
                </div>
            </div>

            <InventoryTable
                :columns="columns"
                :rows="items"
                :module="'archive_item'"
                :actions="rowActions"
                :selected="tempSelectedIds"
                @selection-changed="handleSelectionChanged"
                @restore="handleRestoreItem"
                @force-delete="handleForceDeleteItem"
            />
        </div>
    </Layout>
</template>
