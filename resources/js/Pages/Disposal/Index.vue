<script setup>
import { computed, onBeforeUnmount, onMounted, ref } from "vue";
import { usePage } from "@inertiajs/vue3";
import { Head } from "@inertiajs/vue3";
import Layout from "@/Layouts/Layout.vue";
import Toast from "primevue/toast";
import InventoryTable from "@/Components/InventoryTable.vue";
import PageHeader from "@/Components/PageHeader.vue";
import InventoryFormModal from "@/Components/Modals/InventoryFormModal.vue";
import SearchFilterBar from "@/Components/Filters/SearchFilterBar.vue";
import { usePermissions } from "@/Composables/usePermissions";
import DownloadProgressCard from "@/Components/DownloadProgressCard.vue";
import { usePrintDownload } from "@/Composables/usePrintDownload";
import { buildIirupPayload } from "@/lib/iirupPayload";
import { useToast } from "primevue/usetoast";
import { ChevronDown, Printer, X } from "lucide-vue-next";
import { Button } from "@/Components/ui/button";

defineProps({
    rooms: Array,
});

const { disposalActions } = usePermissions();

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
    {
        label: "Date Acquired",
        key: "date_acquired",
        format: (val) => val ?? "N/A",
    },
    { label: "Quantity", key: "quantity", format: (val) => val ?? "N/A" },
    {
        label: "Total Cost",
        key: "total_amount",
        format: (val) =>
            val != null
                ? "₱" +
                  Number(val).toLocaleString("en-PH", {
                      minimumFractionDigits: 2,
                      maximumFractionDigits: 2,
                  })
                : "N/A",
    },
    { label: "PO Number", key: "po_number" },
    { label: "Room Name", key: "room_name" },
    { label: "Remarks", key: "remarks", format: (val) => val ?? "N/A" },
    {
        label: "Accountable Person",
        key: "latest_acknowledgement_item",
        format: (val) => {
            const person = val?.accountable_person;
            if (!person) {
                return `<span class="text-[#D32F2F] font-bold">Unassigned</span>`;
            }
            const fullName =
                `${person.first_name ?? ""} ${person.last_name ?? ""}`.trim();
            return (
                fullName ||
                `<span class="text-[#D32F2F] font-bold">Unassigned</span>`
            );
        },
    },
    {
        label: "Condition",
        key: "latest_inspection",
        format: (val) => {
            const condition = val?.asset_condition?.condition_name;
            if (!condition) {
                return `<span class="text-gray-400 italic">Not Inspected</span>`;
            }
            return `<span class="text-black font-semibold py-1 px-2">${condition}</span>`;
        },
    },
    { label: "Action", key: "action" },
];

const viewItem = [
    { label: "Item Name", key: "item_name" },
    { label: "Property Number", key: "property_number" },
    { label: "Unit", key: "unit", format: (val) => val ?? "N/A" },
    { label: "Serial Number", key: "serial_number" },
    {
        label: "Unit Cost",
        key: "unit_cost",
        format: (val) =>
            val !== null && val !== undefined
                ? `₱${Number(val).toLocaleString()}`
                : "N/A",
    },
    { label: "PO Number", key: "po_number" },
    { label: "Supplier", key: "supplier.supplier_name" },
    { label: "PR Number", key: "pr_number" },
    { label: "Description", key: "description" },
    { label: "Invoice", key: "invoice" },
    { label: "Remarks", key: "remarks" },
    { label: "Fund Source", key: "fund_source" },
    { label: "Date Acquired", key: "date_acquired" },
    { label: "Room Name", key: "room_name" },
    {
        label: "Condition",
        key: "latest_inspection.asset_condition.condition_name",
        format: (val) => val ?? "Not Inspected",
    },
    {
        label: "Visibility",
        key: "is_private",
        format: (val) =>
            val === 1
                ? `<span class="text-[#D32F2F] font-bold bg-[#F8D4D4] py-1 px-2 rounded-md">Private</span>`
                : `<span class="text-[#2E7D32] font-bold bg-[#D4F8D4] py-1 px-2 rounded-md">Public</span>`,
    },
];

const quantityCostFields = [
    { label: "Quantity", model: "quantity", placeholder: "0", type: "number" },
    {
        label: "Unit Cost",
        model: "unit_cost",
        placeholder: "0",
        type: "text",
        format: (val) => {
            const num = Number(val);
            return Number.isFinite(num) ? num.toLocaleString("en-PH") : "";
        },
    },
];

const inputFields = [
    {
        label: "Item Name",
        model: "item_name",
        placeholder: "Input Item name...",
        type: "text",
        required: true,
    },
    {
        label: "Brand",
        model: "brand",
        placeholder: "Input brand...",
        type: "text",
        required: false,
    },
    {
        label: "Model",
        model: "model",
        placeholder: "Input model...",
        type: "text",
        required: false,
    },
];

const inputFieldsEdit = [
    {
        label: "Serial Number",
        model: "serial_number",
        placeholder: "SER-####.",
        type: "text",
        readonly: true,
    },
    {
        label: "Item Name",
        model: "item_name",
        placeholder: "Laptops, Ceiling Fan...",
        type: "text",
        readonly: false,
    },
    {
        label: "Brand",
        model: "brand",
        placeholder: "Input brand...",
        type: "text",
        readonly: false,
    },
    {
        label: "Model",
        model: "model",
        placeholder: "Input model...",
        type: "text",
        readonly: false,
    },
];

const supplierOptions = [
    {
        label: "Supplier",
        model: "supplier_id",
        name: "suppliers",
        option: "supplier_name",
        value: "id",
    },
];

const requestFields = [
    {
        label: "Purchase Request",
        model: "pr_number",
        placeholder: "PR-###",
        type: "text",
        required: true,
    },
    {
        label: "Purchase Order",
        model: "po_number",
        placeholder: "PO-###",
        type: "text",
        required: true,
    },
    {
        label: "Remarks",
        model: "remarks",
        placeholder: "Input a remarks",
        type: "text",
        required: false,
    },
];

const invoicesFundFields = [
    {
        label: "Invoice Number",
        model: "invoice",
        placeholder: "0000",
        type: "text",
        readonly: false,
    },
    {
        label: "Fund Source",
        model: "fund_source",
        placeholder: "000",
        type: "text",
        readonly: false,
    },
];

const roomDropdown = [
    {
        label: "Room",
        model: "room_id",
        name: "rooms",
        option: "room_name",
        value: "id",
        searchKeys: [
            "room_name",
            "room_code",
            "description",
            "building",
            "building_name",
            "capacity",
        ],
    },
];

const firstDropdown = [
    {
        label: "Classifications",
        model: "item_classification_id",
        name: "itemClass",
        option: "classification_name",
        value: "id",
        searchKeys: ["classification_name", "classification_code"],
        labelFormat: (option) =>
            `${option.classification_name} - ${option.classification_code}`,
    },
];

const firstInputField = [
    {
        label: "Property Number",
        model: "property_number",
        placeholder: "PROP-####.",
        type: "text",
    },
];

const secondDropdown = [
    {
        label: "Unit",
        model: "unit",
        options: [
            { label: "unit", value: "unit" },
            { label: "pcs", value: "pcs" },
            { label: "box", value: "box" },
            { label: "lot", value: "lot" },
        ],
    },
    {
        label: "Status",
        model: "status",
        options: [
            { label: "Serviceable", value: "1" },
            { label: "Unserviceable", value: "0" },
        ],
    },
];

const totalCost = [{ label: "Total Cost" }];

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

const filterCondition = computed(() => [
    {
        label: "Condition",
        options: (page.props.assetConditions ?? []).map((condition) => ({
            label: condition.condition_name,
            value: condition.id,
        })),
    },
]);

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

const page = usePage();

const items = computed(() => page.props.items || { data: [] });
const rooms = computed(() => page.props.rooms || []);
const itemClassifications = computed(() => page.props.itemClassifications || []);
const suppliers = computed(() => page.props.suppliers || []);
const toast = useToast();

// INVENTORY FILTER
const search = ref("");
const asset_condition_id = ref("");
const cost_range = ref(null);
const acknowledgement_status = ref("");

// MODAL STATE
const formMode = ref("create");
const showFormModal = ref(false);
const currentItem = ref({});

function handleView(item) {
    formMode.value = "view";
    currentItem.value = item;
    showFormModal.value = true;
}

const selectedItemsMap = ref(new Map());

const tempSelectedIds = computed(() => Array.from(selectedItemsMap.value.keys()));
const selectedItemsDetails = computed(() =>
    Array.from(selectedItemsMap.value.values()),
);

// One print covers both IIRUP forms: the server splits the selection at
// ₱50,000 on each item's own total cost and puts every item on the form it
// belongs on, each starting a new page.
function formatMoney(value) {
    return Number(value || 0).toLocaleString("en-PH", {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2,
    });
}

// What one article is worth on the selected list: quantity x unit cost, the
// same per-item total the IIRUP uses to decide which form it belongs on.
function itemTotalCost(item) {
    const quantity = Number(item?.quantity ?? 1) || 0;
    const unitCost = Number(item?.unit_cost ?? 0) || 0;

    return quantity * unitCost;
}

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
        if (!newMap.has(id)) {
            const existing = selectedItemsMap.value.get(id);
            if (existing) newMap.set(id, existing);
        }
    });

    selectedItemsMap.value = newMap;
}

function removeFromSelection(id) {
    const newMap = new Map(selectedItemsMap.value);
    newMap.delete(id);
    selectedItemsMap.value = newMap;
}

function clearSelection() {
    selectedItemsMap.value = new Map();
}

// SELECTED ITEMS BADGE + PANEL
const showSelectedPanel = ref(false);

function toggleSelectedPanel() {
    showSelectedPanel.value = !showSelectedPanel.value;
}

async function printIirup() {
    return printIirupForm("disposal.print.iirup.combined.stream");
}

// The toolbar sticks to the top while scrolling, exactly like the item list, so
// its height is published as a CSS variable that the table header reads to pin
// itself right underneath instead of sliding under it.
const toolbarRef = ref(null);
let toolbarObserver = null;

function publishToolbarHeight() {
    if (!toolbarRef.value) return;

    document.documentElement.style.setProperty(
        "--table-sticky-top",
        toolbarRef.value.offsetHeight + "px",
    );
}

onMounted(() => {
    publishToolbarHeight();

    if (typeof ResizeObserver !== "undefined" && toolbarRef.value) {
        toolbarObserver = new ResizeObserver(publishToolbarHeight);
        toolbarObserver.observe(toolbarRef.value);
    }

    window.addEventListener("resize", publishToolbarHeight);
});

// The IIRUP download no longer blanks the screen with the loading overlay: the
// PDF streams back from the print endpoint while this card follows the stages
// the server reports, so the user can keep working while it is built.
const { job: iirupJob, download: runIirupPrint, dismiss: dismissIirupJob } =
    usePrintDownload();

onBeforeUnmount(() => {
    toolbarObserver?.disconnect();
    window.removeEventListener("resize", publishToolbarHeight);
    document.documentElement.style.removeProperty("--table-sticky-top");
});

async function printIirupForm(printRoute) {
    if (tempSelectedIds.value.length === 0) {
        toast.add({
            severity: "warn",
            summary: "No items selected",
            detail: "Select at least one item to generate the IIRUP report.",
            life: 3000,
        });
        return;
    }

    const payload = buildIirupPayload(selectedItemsDetails.value);

    await runIirupPrint({
        url: route(printRoute),
        payload,
        title: "Generating IIRUP",
        successTitle: "IIRUP report downloaded",
        errorTitle: "IIRUP was not generated",
        errorMessage: "Failed to generate the IIRUP report.",
    });
}
</script>

<template>
    <Head title="UP | Disposal" />

    <Layout>
        <Toast />

        <DownloadProgressCard :job="iirupJob" @dismiss="dismissIirupJob" />

        <PageHeader title="Disposal" />

        <div class="w-full h-full">
            <!-- TOOLBAR: print actions, selection, and filters. It sticks to the
                 top while scrolling; the table header pins itself below it. -->
            <div
                ref="toolbarRef"
                class="sticky top-0 z-30 mt-6 rounded-xl border border-gray-200 bg-white/95 p-3 shadow-sm backdrop-blur"
            >
                <div
                    class="flex flex-col gap-3 xl:flex-row xl:items-end xl:justify-between"
                >
                    <!-- PRINT ACTIONS AND THE SELECTED-ITEMS PANEL -->
                    <div
                        class="flex w-full flex-col gap-2 sm:flex-row sm:items-center xl:w-auto"
                    >
                        <div class="w-full sm:w-auto">
                            <Button
                                type="button"
                                @click="printIirup"
                                :disabled="tempSelectedIds.length === 0"
                                class="w-full justify-center bg-[#0E6021] text-xs text-white hover:bg-[#19703a] disabled:cursor-not-allowed disabled:opacity-40 disabled:hover:bg-[#0E6021] sm:text-sm"
                            >
                                <Printer class="h-4 w-4" />
                                Reprint IIRUP
                                <span
                                    class="hidden rounded-full bg-white/20 px-2 py-0.5 text-[10px] font-semibold sm:inline"
                                >
                                    Both forms, one file
                                </span>
                            </Button>
                        </div>

                        <!-- SELECTED ITEMS BADGE -->
                        <div class="relative flex-shrink-0">
                            <button
                                type="button"
                                @click="toggleSelectedPanel"
                                class="flex h-10 items-center gap-2 rounded-lg border bg-white pl-1.5 pr-3 shadow-sm transition-colors"
                                :class="
                                    showSelectedPanel
                                        ? 'border-[#005740] ring-1 ring-[#005740]/25'
                                        : 'border-gray-300 hover:bg-gray-50'
                                "
                            >
                                <span
                                    class="flex h-7 min-w-7 items-center justify-center rounded-md bg-[#0E6021] px-1.5 text-xs font-bold text-white"
                                >
                                    {{ tempSelectedIds.length }}
                                </span>
                                <span
                                    class="whitespace-nowrap text-xs font-medium text-[#3B3B3B]"
                                >
                                    Selected
                                </span>
                                <ChevronDown
                                    class="h-4 w-4 text-gray-500 transition-transform duration-200"
                                    :class="{ 'rotate-180': showSelectedPanel }"
                                />
                            </button>

                            <div
                                v-if="showSelectedPanel"
                                class="absolute right-0 z-30 mt-2 max-h-80 w-72 overflow-y-auto rounded-lg border border-gray-200 bg-white shadow-lg"
                            >
                                <div
                                    class="sticky top-0 flex items-center justify-between border-b border-gray-100 bg-white px-4 py-2"
                                >
                                    <p
                                        class="text-xs font-semibold uppercase tracking-wide text-gray-500"
                                    >
                                        Selected ({{ tempSelectedIds.length }})
                                    </p>
                                    <button
                                        v-if="tempSelectedIds.length"
                                        type="button"
                                        @click="clearSelection"
                                        class="text-xs text-[#850038] hover:underline"
                                    >
                                        Clear all
                                    </button>
                                </div>

                                <ul
                                    v-if="selectedItemsDetails.length"
                                    class="divide-y divide-gray-100"
                                >
                                    <li
                                        v-for="item in selectedItemsDetails"
                                        :key="item.id"
                                        class="flex items-center gap-2 px-4 py-2 text-sm"
                                    >
                                        <button
                                            type="button"
                                            @click="removeFromSelection(item.id)"
                                            class="flex-shrink-0 text-gray-400 hover:text-[#D71D1D]"
                                            title="Remove from selection"
                                        >
                                            <X class="h-3.5 w-3.5" />
                                        </button>
                                        <span
                                            class="min-w-0 flex-1 truncate text-gray-700"
                                        >
                                            {{ item.item_name }}
                                            <template v-if="item.property_number">
                                                - {{ item.property_number }}
                                            </template>
                                        </span>

                                        <span
                                            class="flex-shrink-0 text-[11px] font-semibold text-gray-500"
                                        >
                                            &#8369;{{ formatMoney(itemTotalCost(item)) }}
                                        </span>
                                    </li>
                                </ul>

                                <p
                                    v-else
                                    class="px-4 py-6 text-center text-xs text-gray-400"
                                >
                                    No items selected yet.
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- SEARCH; THE FILTERS SIT BEHIND THE THREE-DOT BUTTON -->
                    <div class="w-full xl:w-auto">
                        <SearchFilterBar
                            :search="search"
                            :cost_range="cost_range"
                            :asset_condition_id="asset_condition_id"
                            :acknowledgement_status="acknowledgement_status"
                            :unitCostOptions="unitCostOptions"
                            :filterCondition="filterCondition"
                            :acknowledgementFilter="acknowledgementFilter"
                            @update:search="search = $event"
                            @update:asset_condition_id="asset_condition_id = $event"
                            @update:cost_range="cost_range = $event"
                            @update:acknowledgement_status="
                                acknowledgement_status = $event
                            "
                            :mode="'disposal'"
                            :collapsible-filters="true"
                        />
                    </div>
                </div>
            </div>

            <InventoryFormModal
                v-if="showFormModal"
                :mode="formMode"
                :firstDropdown="firstDropdown"
                :firstInputField="firstInputField"
                :secondDropdown="secondDropdown"
                :quantityCostFields="quantityCostFields"
                :input-fields="inputFields"
                :invoicesFundFields="invoicesFundFields"
                :supplierOptions="supplierOptions"
                :requestFields="requestFields"
                :inputFieldsEdit="inputFieldsEdit"
                :totalCost="totalCost"
                :itemClass="itemClassifications"
                :rooms="rooms"
                :roomDropdown="roomDropdown"
                :initialValues="currentItem"
                :suppliers="suppliers"
                :item="currentItem"
                :viewItem="viewItem"
                @close="() => (showFormModal = false)"
            />

            <InventoryTable
                :columns="columns"
                :rows="items"
                :rooms="rooms"
                :module="'inventory'"
                :actions="disposalActions"
                :selected="tempSelectedIds"
                @selection-changed="handleSelectionChanged"
                @view="handleView"
            />
        </div>
    </Layout>
</template>
