<script setup>
import { ref, computed, watch } from "vue";
import { usePage, router } from "@inertiajs/vue3";
import SideBar from "@/Components/SideBar.vue";
import InventoryTable from "@/Components/InventoryTable.vue";
import PageHeader from "@/Components/PageHeader.vue";
import InventoryFormModal from "@/Components/Modals/InventoryFormModal.vue";
import InspectionModal from "@/Components/Modals/InspectionModal.vue";
import SearchFilterBar from "@/Components/Filters/SearchFilterBar.vue";
import Toast from "primevue/toast";
import LoadingOverlay from "@/Components/LoadingOverlay.vue";
import { usePermissions } from "@/Composables/usePermissions";
import { useLoading } from "@/Composables/useLoading";
import NavHeader from "@/Components/NavHeader.vue";
import { useToast } from "primevue/usetoast";
import { ChevronDown, X } from "lucide-vue-next";
import { useSidebar } from "@/Composables/useSidebar";
import SessionTimeoutWarning from "@/Components/SessionTimeoutWarning.vue";

const { isSidebarOpen, toggleSidebar } = useSidebar();

const { isLoading, loadingTitle, loadingMessage, startLoading, stopLoading } =
    useLoading();

const { inspectionActions } = usePermissions();

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
    {
        label: "Last Inspected",
        key: "latest_inspection.inspection_date",
        format: (val) =>
            val
                ? new Date(val).toLocaleDateString("en-PH", {
                      year: "numeric",
                      month: "long",
                      day: "numeric",
                  })
                : "N/A",
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
        label: "Latest Condition",
        key: "latest_inspection.asset_condition.condition_name",
        format: (val) => val ?? "Not Inspected",
    },
    {
        label: "Last Inspected",
        key: "latest_inspection.inspection_date",
        format: (val) =>
            val
                ? new Date(val).toLocaleDateString("en-PH", {
                      year: "numeric",
                      month: "long",
                      day: "numeric",
                  })
                : "Not Inspected",
    },
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
const toast = useToast();

const items = computed(() => page.props.items || { data: [] });
const rooms = computed(() => page.props.rooms || []);
const assetConditions = computed(() => page.props.assetConditions || []);

const itemClassifications = computed(
    () => page.props.itemClassifications || [],
);
const suppliers = computed(() => page.props.suppliers || []);

// INVENTORY FILTER
let search = ref("");
let status = ref(null);
let cost_range = ref(null);
let acknowledgement_status = ref("");
let room_id = ref("");

// MODAL STATE
let formMode = ref("create");
let showFormModal = ref(false);
let currentItem = ref({});

// INSPECTION MODAL STATE
let showInspectionModal = ref(false);

function handleView(item) {
    formMode.value = "view";
    currentItem.value = item;
    showFormModal.value = true;
}

const selectedItemsMap = ref(new Map());

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

        if (flash.error) {
            toast.add({
                severity: "error",
                summary: "Error",
                detail: flash.error,
                life: 4000,
            });
        }
    },
    { immediate: true },
);

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

// INSPECTION MODAL HANDLERS
function openInspectionModal() {
    if (selectedItemsDetails.value.length === 0) {
        toast.add({
            severity: "warn",
            summary: "No Items Selected",
            detail: "Please select at least one item for inspection.",
            life: 3000,
        });

        return;
    }

    showInspectionModal.value = true;
}

function handleInspectionSubmit(inspectionData) {
    startLoading("Submitting", "Processing asset inspections...");

    router.post(route("inspection.store"), inspectionData, {
        preserveScroll: true,

        onSuccess: () => {
            stopLoading();

            showInspectionModal.value = false;

            clearSelection();
        },

        onError: () => {
            stopLoading();

            toast.add({
                severity: "error",
                summary: "Submission Failed",
                detail: "Please check the form for validation errors.",
                life: 4000,
            });
        },

        onFinish: () => {
            stopLoading();
        },
    });
}

function handleAddCondition(conditionData) {
    startLoading("Creating", "Adding new asset condition...");

    router.post(route("asset-conditions.store"), conditionData, {
        preserveScroll: true,

        onSuccess: () => {
            stopLoading();
        },

        onError: () => {
            stopLoading();

            toast.add({
                severity: "error",
                summary: "Creation Failed",
                detail: "Unable to create asset condition.",
                life: 4000,
            });
        },

        onFinish: () => {
            stopLoading();
        },
    });
}

function handleDeleteCondition(condition) {
    router.delete(route("asset-conditions.destroy", condition.id), {
        preserveScroll: true,

        onError: () => {
            toast.add({
                severity: "error",
                summary: "Delete Failed",
                detail: "Unable to delete asset condition.",
                life: 4000,
            });
        },
    });
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
                        <PageHeader title="Records with history" />

                        <div class="w-full h-full">
                            <div
                                class="mt-5 bg-white border border-gray-200 rounded-2xl shadow-sm overflow-hidden"
                            >
                                <!-- HEADER -->
                                <div
                                    class="bg-gradient-to-r from-[#003d2c] via-[#005740] to-[#00795a] px-6 py-5"
                                >
                                    <div
                                        class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-5"
                                    >
                                        <div>
                                            <h2
                                                class="text-xl font-bold text-white"
                                            >
                                                Asset Inspection
                                            </h2>

                                            <p
                                                class="text-sm text-white/80 mt-1"
                                            >
                                                Select inventory items then
                                                create an inspection record.
                                            </p>
                                        </div>

                                        <!-- INSPECT BUTTON -->
                                        <button
                                            @click="openInspectionModal"
                                            :disabled="
                                                selectedItemsDetails.length ===
                                                0
                                            "
                                            :class="[
                                                'flex items-center justify-center gap-2 rounded-xl px-6 py-3 font-semibold transition-all duration-200',
                                                selectedItemsDetails.length
                                                    ? 'bg-white text-[#005740] hover:shadow-lg'
                                                    : 'bg-white/30 text-white/70 cursor-not-allowed',
                                            ]"
                                        >
                                            <i
                                                class="fa-solid fa-clipboard-check"
                                            ></i>

                                            Inspect Selected

                                            <span
                                                v-if="
                                                    selectedItemsDetails.length
                                                "
                                                class="bg-[#005740] text-white rounded-full px-2 py-0.5 text-xs"
                                            >
                                                {{
                                                    selectedItemsDetails.length
                                                }}
                                            </span>
                                        </button>
                                    </div>
                                </div>

                                <div class="bg-white">
                                    <!-- HEADER + TOOLBAR -->
                                    <div
                                        class="flex flex-col xl:flex-row xl:items-center xl:justify-between gap-5 px-6 py-5 border-b border-gray-200"
                                    >
                                        <!-- LEFT FILTERS -->
                                        <div
                                            class="flex items-center gap-6 flex-1"
                                        >
                                            <SearchFilterBar
                                                :search="search"
                                                :cost_range="cost_range"
                                                :status="status"
                                                :acknowledgement_status="
                                                    acknowledgement_status
                                                "
                                                :room_id="room_id"
                                                :rooms="rooms"
                                                :unitCostOptions="
                                                    unitCostOptions
                                                "
                                                :filterStatus="filterStatus"
                                                :acknowledgementFilter="
                                                    acknowledgementFilter
                                                "
                                                :mode="'inspection'"
                                                @update:status="status = $event"
                                                @update:cost_range="
                                                    cost_range = $event
                                                "
                                                @update:acknowledgement_status="
                                                    acknowledgement_status =
                                                        $event
                                                "
                                                @update:room_id="
                                                    room_id = $event
                                                "
                                            />
                                        </div>
                                    </div>

                                    <!-- TABLE -->
                                    <InventoryTable
                                        :columns="columns"
                                        :rows="items"
                                        :rooms="rooms"
                                        :module="'inventory'"
                                        :actions="inspectionActions"
                                        :selected="tempSelectedIds"
                                        @selection-changed="
                                            handleSelectionChanged
                                        "
                                        @view="handleView"
                                    />
                                </div>
                            </div>

                            <InspectionModal
                                :show="showInspectionModal"
                                :selectedItems="selectedItemsDetails"
                                :assetConditions="assetConditions"
                                @close="showInspectionModal = false"
                                @submit="handleInspectionSubmit"
                                @add-condition="handleAddCondition"
                                @delete-condition="handleDeleteCondition"
                            />

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
                                @close="showFormModal = false"
                            />
                        </div>
                    </div>
                </main>
            </div>
        </div>
    </div>
</template>
