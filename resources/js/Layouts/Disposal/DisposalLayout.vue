<script setup>
import { ref, computed } from "vue";
import { usePage, router } from "@inertiajs/vue3";
import SideBar from "@/Components/SideBar.vue";
import InventoryTable from "@/Components/InventoryTable.vue";
import PageHeader from "@/Components/PageHeader.vue";
import InventoryFormModal from "@/Components/Modals/InventoryFormModal.vue";
import SearchFilterBar from "@/Components/Filters/SearchFilterBar.vue";
import Toast from "primevue/toast";
import LoadingOverlay from "@/Components/LoadingOverlay.vue";
import { usePermissions } from "@/Composables/usePermissions";
import { useLoading } from "@/Composables/useLoading";
import NavHeader from "@/Components/NavHeader.vue";
import SessionTimeoutWarning from "@/Components/SessionTimeoutWarning.vue";

import {
    ChevronDown,
    X,
} from "lucide-vue-next";
import { useSidebar } from "@/Composables/useSidebar";
const { isSidebarOpen, toggleSidebar } = useSidebar();

const { isLoading, loadingTitle, loadingMessage, startLoading, stopLoading } =
    useLoading();

const {
    disposalActions,
} = usePermissions();

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
    { label: "PO Number", key: "po_number" },
    { label: "Room Name", key: "room_name" },
    {
        label: "Accountable Person",
        key: "latest_acknowledgement_item",
        format: (val) => {
            const person = val?.accountable_person;

            if (!person) {
                return `<span class="text-[#D32F2F] font-bold">Unassigned</span>`;
            }

            const fullName = `${person.first_name ?? ""} ${person.last_name ?? ""}`.trim();

            return fullName || `<span class="text-[#D32F2F] font-bold">Unassigned</span>`;
        },
    },
    {
        label: "Status",
        key: "status",
        format: (status) => {
            let label = "Unknown",
                cls = "text-gray-500",
                icon = "";
            if (status === 0) {
                label = "Unserviceable";
                cls =
                    "text-[#D32F2F] font-bold bg-[#F8D4D4] py-1 px-2 rounded-md";
            } else if (status === 1) {
                label = "Serviceable";
                cls =
                    "text-[#2E7D32] font-bold bg-[#D4F8D4] py-2 px-2 rounded-md";
            }
            return `<span class="${cls}">${icon} ${label}</span>`;
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
        label: "Status",
        key: "status",
        format: (status) => {
            let label = "Unknown",
                cls = "text-gray-500",
                icon = "";
            if (status === 0) {
                label = "Unservicable";
                cls =
                    "text-[#D32F2F] font-bold bg-[#F8D4D4] py-1 px-2 rounded-md";
            } else if (status === 1) {
                label = "Serviceable";
                cls =
                    "text-[#2E7D32] font-bold bg-[#D4F8D4] py-1 px-2 rounded-md";
            }
            return `<span class="${cls}">${icon} ${label}</span>`;
        },
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
    {
        label: "Quantity",
        model: "quantity",
        placeholder: "0",
        type: "number",
    },
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

const filterStatus = [
    {
        label: "Status",
        options: [
            { label: "Serviceable", value: 1 },
            { label: "Unserviceable", value: 0 },
        ],
    },
];

const acknowledgementFilter = [
    {
        label: "Accountable Person",
        options: [
            { label: "All Items", value: "" },
            { label: "Assigned", value: "with_acknowledgement" },
            {
                label: "Unassigned",
                value: "without_acknowledgement",
            },
        ],
    },
];

const page = usePage();

const items = computed(() => page.props.items || { data: [] });
const rooms = computed(() => page.props.rooms || []);

const itemClassifications = computed(
    () => page.props.itemClassifications || [],
);
const suppliers = computed(() => page.props.suppliers || []);

// INVENTORY FILTER
let search = ref("");
let status = ref(null);
let cost_range = ref(null);
let acknowledgement_status = ref("");

// MODAL STATE
let formMode = ref("create");
let showFormModal = ref(false);
let currentItem = ref({});

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

function handleSelectionChanged(ids, currentPageRows = []) {
    const idSet = new Set(ids);
    const newMap = new Map(selectedItemsMap.value);

    currentPageRows.forEach((row) => {
        if (idSet.has(row.id)) {
            newMap.set(row.id, row);
        }
    });

    for (const key of newMap.keys()) {
        if (!idSet.has(key)) {
            newMap.delete(key);
        }
    }

    idSet.forEach((id) => {
        if (!newMap.has(id)) {
            const existing = selectedItemsMap.value.get(id);
            if (existing) {
                newMap.set(id, existing);
            }
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
</script>

<template>
    <SessionTimeoutWarning />
    <Toast />
    <LoadingOverlay
        :show="isLoading"
        :title="loadingTitle"
        :message="loadingMessage"
    />

    <div class="h-screen flex flex-col bg-gray-100">
        <div class="flex flex-1 overflow-hidden">
            <aside
                class="h-full transition-all duration-300 ease-in-out flex-shrink-0"
            >
                <SideBar
                    :isOpen="isSidebarOpen"
                    @toggleSidebar="toggleSidebar"
                />
            </aside>

            <div class="flex flex-col flex-1 overflow-hidden">
                <NavHeader
                    :isSidebarOpen="isSidebarOpen"
                    @toggleSidebar="toggleSidebar"
                >
                </NavHeader>

                <main class="flex-1 sm:p-5 md:p-6 md:mx-0 overflow-y-auto">
                    <div class="m-2">
                        <PageHeader title="Disposal" />
                        <div class="w-full h-full">

                            <div
                                class="flex flex-col md:flex-row md:items-center justify-between gap-3 mt-5"
                            >
                                <SearchFilterBar
                                    :search="search"
                                    :cost_range="cost_range"
                                    :status="status"
                                    :acknowledgement_status="
                                        acknowledgement_status
                                    "
                                    :unitCostOptions="unitCostOptions"
                                    :filterStatus="filterStatus"
                                    :acknowledgementFilter="
                                        acknowledgementFilter
                                    "
                                    @update:search="search = $event"
                                    @update:status="status = $event"
                                    @update:cost_range="cost_range = $event"
                                    @update:acknowledgement_status="
                                        acknowledgement_status = $event
                                    "
                                    :mode="'disposal'"
                                />

                                <!-- SELECTED ITEMS BADGE -->
                                <div class="relative flex-shrink-0 self-start md:self-auto">
                                    <button
                                        type="button"
                                        @click="toggleSelectedPanel"
                                        class="flex items-center gap-2 bg-white border border-gray-300 rounded-full pl-1 pr-3 py-1 shadow-sm hover:bg-gray-50 transition-colors"
                                    >
                                        <span
                                            class="flex items-center justify-center w-7 h-7 rounded-full bg-[#0E6021] text-white text-xs font-bold flex-shrink-0"
                                        >
                                            {{ tempSelectedIds.length }}
                                        </span>
                                        <span
                                            class="text-xs font-medium text-gray-600 whitespace-nowrap"
                                        >
                                            Selected
                                        </span>
                                        <ChevronDown
                                            class="w-4 h-4 text-gray-500 transition-transform duration-200"
                                            :class="{
                                                'rotate-180':
                                                    showSelectedPanel,
                                            }"
                                        />
                                    </button>

                                    <div
                                        v-if="showSelectedPanel"
                                        class="absolute right-0 mt-2 w-72 bg-white rounded-lg shadow-lg border border-gray-200 z-30 max-h-80 overflow-y-auto"
                                    >
                                        <div
                                            class="px-4 py-2 border-b border-gray-100 flex items-center justify-between sticky top-0 bg-white"
                                        >
                                            <p
                                                class="text-xs font-semibold text-gray-500 uppercase tracking-wide"
                                            >
                                                Selected ({{
                                                    tempSelectedIds.length
                                                }})
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
                                                class="px-4 py-2 flex items-center gap-2 text-sm"
                                            >
                                                <button
                                                    type="button"
                                                    @click="
                                                        removeFromSelection(
                                                            item.id,
                                                        )
                                                    "
                                                    class="text-gray-400 hover:text-[#D71D1D] flex-shrink-0"
                                                    title="Remove from selection"
                                                >
                                                    <X class="w-3.5 h-3.5" />
                                                </button>
                                                <span
                                                    class="text-gray-700 truncate"
                                                    >{{ item.item_name }}
                                                    <template
                                                        v-if="
                                                            item.property_number
                                                        "
                                                        >- {{
                                                            item.property_number
                                                        }}</template
                                                    ></span
                                                >
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
                    </div>
                </main>
            </div>
        </div>
    </div>
</template>