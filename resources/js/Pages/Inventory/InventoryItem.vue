<script setup>
import { ref, computed } from "vue";
import { usePage, router } from "@inertiajs/vue3";
import { Head } from "@inertiajs/vue3";
import Layout from "@/Layouts/Layout.vue";
import InventoryTable from "@/Components/InventoryTable.vue";
import PageHeader from "@/Components/PageHeader.vue";
import InventoryFormModal from "@/Components/Modals/InventoryFormModal.vue";
import SearchFilterBar from "@/Components/Filters/SearchFilterBar.vue";
import ArchiveModal from "@/Components/Modals/ArchiveModal.vue";
import BatchArchiveModal from "@/Components/Modals/BatchArchiveModal.vue";
import SuccessDeleteModal from "@/Components/Modals/SuccessDeleteModal.vue";
import AcknowledgementFormModal from "@/Components/Modals/AcknowledgementFormModal.vue";
import ImportResultModal from "@/Components/Modals/ImportResultModal.vue";
import ImportItemsModal from "@/Components/Modals/ImportItemsModal.vue";
import ExportFormatModal from "@/Components/Modals/ExportFormatModal.vue";
import AssetConditionFormModal from "@/Components/Modals/AssetConditionFormModal.vue";
import DisposeItemsModal from "@/Components/Modals/DisposeItemsModal.vue";
import { useToast } from "primevue/usetoast";
import { usePermissions } from "@/Composables/usePermissions";
import { useLoading } from "@/Composables/useLoading";
import { usePrintDownload } from "@/Composables/usePrintDownload";
import { buildIirupPayload } from "@/lib/iirupPayload";
import { useStickyToolbar } from "@/Composables/useStickyToolbar";
import DownloadProgressCard from "@/Components/DownloadProgressCard.vue";
import { Button } from "@/Components/ui/button";
import {
    Archive,
    ChevronDown,
    UserPlus,
    QrCode,
    Printer,
    X,
    FileUp,
    FileDown,
    ListChecks,
    PackageX,
} from "lucide-vue-next";
import { Plus } from "lucide-vue-next";

defineProps({
    rooms: Array,
});

const { startLoading, stopLoading } = useLoading();
const showConditionModal = ref(false);
const showDisposeModal = ref(false);

const { job: stickerJob, download: runStickerDownload, dismiss: dismissStickerJob } =
    usePrintDownload();
const { job: disposeJob, download: runDisposePrint, dismiss: dismissDisposeJob } =
    usePrintDownload();

const {
    inventoryActions,
    canCreateInventory,
    canImportInventory,
    canExportInventory,
} = usePermissions();

const columns = [
    { label: "", key: "select_all" },
    { label: "Item Name", key: "item_name" },
    { label: "Description", key: "description", cellClass: "min-w-[14rem]" },
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
        label: "PAR/ICS",
        key: "latest_acknowledgement_item.acknowledgement_receipts",
        format: (val) =>
            val?.category ??
            `<span class="text-gray-400 italic">No Receipt</span>`,
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
    // {
    //     label: "Borrow Status",
    //     key: "status",
    //     format: (value) => {
    //         if (value === "borrowed") {
    //             return '<span class="text-[11px] font-semibold text-amber-700">Borrowed</span>';
    //         }

    //         if (value === "retired") {
    //             return '<span class="text-[11px] font-semibold text-gray-600">Retired</span>';
    //         }

    //         return '<span class="text-[11px] font-semibold text-[#2E7D32]">Available</span>';
    //     },
    // },
    { label: "Action", key: "action" },
];

const viewItem = [
    { label: "Item Name", key: "item_name" },
    { label: "Property Number", key: "property_number" },
    { label: "Unit", key: "unit", format: (val) => val ?? "N/A" },
    { label: "PO Number Date", key: "po_number_date" },
    {
        label: "Unit Cost",
        key: "unit_cost",
        format: (val) =>
            val !== null && val !== undefined
                ? `₱${Number(val).toLocaleString()}`
                : "N/A",
    },
    { label: "PO Number", key: "po_number" },
    { label: "Serial Number", key: "serial_number" },
    { label: "Supplier", key: "supplier.supplier_name" },
    { label: "PR Number", key: "pr_number" },
    { label: "Invoice", key: "invoice" },
    { label: "Description", key: "description" },
    { label: "Invoice Date", key: "sales_invoice_date" },
    { label: "Remarks", key: "remarks" },
    { label: "Fund Source", key: "fund_source" },
    {
        label: "Lifespan",
        key: "lifespan",
        format: (val) =>
            val != null ? `${val} year${val == 1 ? "" : "s"}` : "N/A",
    },
    { label: "Room Name", key: "room_name" },
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
        label: "Approval Status",
        key: "latest_acknowledgement_item",
        format: (val) => {
            const person = val?.accountable_person;

            if (!person) {
                return `<span class="bg-gray-100 text-gray-500 font-semibold py-1 px-2 rounded-full text-xs">Unassigned</span>`;
            }

            const map = {
                approved: {
                    label: "Approved",
                    cls: "bg-green-100 text-green-700",
                },
                rejected: { label: "Rejected", cls: "bg-red-100 text-red-700" },
                pending: {
                    label: "Pending",
                    cls: "bg-yellow-100 text-yellow-700",
                },
            };
            const status = map[val?.approval_status] ?? map.pending;
            return `<span class="${status.cls} font-semibold py-1 px-2 rounded-full text-xs">${status.label}</span>`;
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

const lifespanField = [
    {
        label: "Lifespan (years)",
        model: "lifespan",
        placeholder: "e.g. 5",
        type: "number",
        required: true,
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
        label: "Purchase Order Date",
        model: "po_number_date",
        type: "date",
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
        required: true,
    },
    {
        label: "Invoice Number Date",
        model: "sales_invoice_date",
        type: "date",
        readonly: false,
        required: true,
    },
    {
        label: "Fund Source",
        model: "fund_source",
        placeholder: "000",
        type: "text",
        readonly: false,
        required: true,
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
        allowCustom: true,
        options: [
            { label: "unit", value: "unit" },
            { label: "pcs", value: "pcs" },
            { label: "box", value: "box" },
            { label: "lot", value: "lot" },
            { label: "set", value: "set" },
        ],
    },
    {
        label: "Condition",
        model: "asset_condition_id",
        name: "assetConditions",
        option: "condition_name",
        value: "id",
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
const itemClassifications = computed(
    () => page.props.itemClassifications || [],
);
const suppliers = computed(() => page.props.suppliers || []);
const assetConditions = computed(() => page.props.assetConditions || []);

// Disposing needs a condition flagged as Disposed; the modal guides the user
// to add one when this comes back empty.
const disposedCondition = computed(
    () => assetConditions.value.find((condition) => condition.disposition === "disposed") ?? null,
);
const transferTypes = computed(() => page.props.transferTypes || []);
const biggestCategories = computed(() => page.props.biggestCategories || {});

// INVENTORY FILTER
const search = ref("");
const cost_range = ref(null);
const asset_condition_id = ref("");
const acknowledgement_status = ref("");
const room_id = ref("");

// The table header pins itself right below the sticky toolbar, so the
// toolbar's height is published as a CSS variable that the table reads.
const toolbarRef = useStickyToolbar();

// MODAL STATE
const formMode = ref("create");
const showFormModal = ref(false);
const showArchiveModal = ref(false);
const showBatchArchiveModal = ref(false);
const archiveSuccessMessage = ref("Item archived successfully.");
const showAssignModal = ref(false);
const currentItem = ref({});

const showDeleteSuccessModal = ref(false);
const toast = useToast();

// IMPORT (CSV / Excel)
const showImportModal = ref(false);
const importResult = ref(null);

// The file shown in a review, kept so it can be imported after.
const pendingImportFile = ref(null);

// Saves a receipt PDF under the name the server chose, e.g. "ITR_223-2026-10-900.pdf".
function downloadReceipt(response) {
    const disposition = response.headers["content-disposition"] || "";
    const match = disposition.match(/filename="?([^"]+)"?/);
    const fileName = match ? match[1] : `receipt_${Date.now()}.pdf`;

    const url = window.URL.createObjectURL(
        new Blob([response.data], { type: "application/pdf" }),
    );
    const link = document.createElement("a");
    link.href = url;
    link.setAttribute("download", fileName);
    document.body.appendChild(link);
    link.click();
    link.remove();
    window.URL.revokeObjectURL(url);
}

function openImportModal() {
    showImportModal.value = true;
}

// Downloads the fixed three-row sample (used inside the Batch Add window).
function downloadExportSample() {
    window.location.href = route("inventory.export.sample");
}

// Export opens a small modal so the format can be picked first.
const showExportModal = ref(false);

function confirmExport(format) {
    showExportModal.value = false;

    window.location.href = route("inventory.export", { format });
}

// Review runs the whole import without saving, so the report can be checked
// before anything is written to the database.
async function handleReviewFile(file) {
    if (!file) return;

    showImportModal.value = false;
    pendingImportFile.value = file;

    const formData = new FormData();
    formData.append("file", file);
    formData.append("dry_run", 1);

    startLoading(
        "Reviewing Items...",
        "Checking every row. Nothing is saved until you import.",
    );

    try {
        const { data } = await axios.post(route("inventory.import"), formData);
        importResult.value = data;
    } catch (error) {
        // Keep the real cause in the console; the user gets a short message.
        console.error("Review request failed:", error);

        const response = error.response;
        let message = "The file could not be reviewed. Please try again.";

        if (response?.status === 403) {
            message = "You do not have permission to import items.";
        } else if (response?.status === 413) {
            message = "The file is too large to upload.";
        } else if (response?.data?.message) {
            message = response.data.message;
        } else if (response?.status) {
            message =
                "The server returned " +
                response.status +
                ". Please try again.";
        }

        importResult.value = { fatal: message };
    } finally {
        stopLoading();
    }
}

// "Import now" on the review report saves the file for real.
function importPendingFile() {
    const file = pendingImportFile.value;

    importResult.value = null;

    if (file) {
        handleImportFile(file);
    }
}

async function handleImportFile(file) {
    if (!file) return;

    showImportModal.value = false;

    const formData = new FormData();
    formData.append("file", file);

    startLoading(
        "Importing Items...",
        "Reading the spreadsheet and checking every row. This can take a minute.",
    );

    try {
        const { data } = await axios.post(route("inventory.import"), formData);
        importResult.value = data;
        pendingImportFile.value = null;

        if (data.imported_rows > 0) {
            router.reload({ only: ["items", "biggestCategories"] });
        }
    } catch (error) {
        const response = error.response;
        let message = "The import could not be completed. Please try again.";

        if (response?.status === 403) {
            message = "You do not have permission to import items.";
        } else if (response?.status === 413) {
            message = "The file is too large to upload.";
        } else if (response?.data?.errors?.file?.[0]) {
            message = response.data.errors.file[0];
        } else if (response?.data?.message) {
            message = response.data.message;
        }

        importResult.value = { fatal: message };
    } finally {
        stopLoading();
    }
}

function openAdd(item) {
    formMode.value = "create";
    currentItem.value = item;
    showFormModal.value = true;
}

function openAss() {
    if (!tempSelectedIds.value.length) {
        toast.add({
            severity: "error",
            summary: "No Items Selected",
            detail: "Please select at least one item.",
            life: 3000,
        });
        return;
    }
    showAssignModal.value = true;
}

function handleView(item) {
    formMode.value = "view";
    currentItem.value = item;
    showFormModal.value = true;
}

function handleEdit(item) {
    formMode.value = "edit";
    currentItem.value = item;
    showFormModal.value = true;
}

function handleDelete(item) {
    currentItem.value = item;
    showArchiveModal.value = true;
}

// ------------------------------------------------------------------
// SELECTION STATE — single source of truth mapping id -> item, so
// selections survive pagination without being rebuilt from the
// current page's data.
// ------------------------------------------------------------------
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

function refreshItems() {
    router.get(
        "/inventory/items",
        {
            search: search.value,
            cost_range: cost_range.value,
            acknowledgement_status: acknowledgement_status.value,
        },
        { preserveState: true, preserveScroll: true, only: ["items"] },
    );
}

function handleSubmit() {
    stopLoading();
    showFormModal.value = false;
    clearSelection();
    refreshItems();
}

function confirmArchive(item) {
    router.delete(route("items.destroy", item.id), {
        preserveScroll: true,
        onSuccess: () => {
            stopLoading();
            showArchiveModal.value = false;
            archiveSuccessMessage.value = "Item archived successfully.";
            showDeleteSuccessModal.value = true;
            removeFromSelection(item.id);
            refreshItems();
        },
    });
}

function openBatchArchive() {
    if (!tempSelectedIds.value.length) {
        toast.add({
            severity: "error",
            summary: "No Items Selected",
            detail: "Please select at least one item to archive.",
            life: 3000,
        });
        return;
    }

    if (!canDeleteInventory.value) {
        toast.add({
            severity: "error",
            summary: "Not Allowed",
            detail: "You do not have permission to archive items.",
            life: 3000,
        });
        return;
    }

    showBatchArchiveModal.value = true;
}

function confirmBatchArchive() {
    const count = tempSelectedIds.value.length;
    const label = count === 1 ? "Item" : count + " items";

    startLoading(
        "Archiving Items...",
        "Please wait while we archive the selected items.",
    );

    router.delete(route("items.batch-archive"), {
        data: { ids: tempSelectedIds.value },
        preserveScroll: true,
        onSuccess: () => {
            stopLoading();
            showBatchArchiveModal.value = false;
            archiveSuccessMessage.value = label + " archived successfully.";
            showDeleteSuccessModal.value = true;
            clearSelection();
            refreshItems();
        },
        onError: () => {
            stopLoading();
            toast.add({
                severity: "error",
                summary: "Archive Failed",
                detail: "The selected items could not be archived.",
                life: 5000,
            });
        },
    });
}

// ICONS
const iconDelete = `
  <svg class="w-16 h-16" viewBox="0 0 50 50" fill="none" xmlns="http://www.w3.org/2000/svg">
    <rect width="50" height="50" rx="25" fill="#C8EFD4"/>
    <path d="M39.4346 21.7708C38.8917 21.2034 38.3301 20.6188 38.1184 20.1047C37.9226 19.6338 37.9111 18.8533 37.8995 18.0973C37.8779 16.6919 37.8549 15.0992 36.7475 13.9918C35.6402 12.8844 34.0475 12.8614 32.642 12.8398C31.886 12.8283 31.1055 12.8168 30.6346 12.6209C30.122 12.4092 29.5359 11.8476 28.9685 11.3047C27.9749 10.35 26.846 9.26855 25.3426 9.26855C23.8392 9.26855 22.7117 10.35 21.7166 11.3047C21.1492 11.8476 20.5646 12.4092 20.0505 12.6209C19.5825 12.8168 18.7991 12.8283 18.0431 12.8398C16.6377 12.8614 15.045 12.8844 13.9376 13.9918C12.8302 15.0992 12.8144 16.6919 12.7856 18.0973C12.7741 18.8533 12.7626 19.6338 12.5667 20.1047C12.355 20.6173 11.7934 21.2034 11.2505 21.7708C10.2958 22.7644 9.21436 23.8934 9.21436 25.3968C9.21436 26.9002 10.2958 28.0277 11.2505 29.0227C11.7934 29.5901 12.355 30.1748 12.5667 30.6888C12.7626 31.1597 12.7741 31.9402 12.7856 32.6962C12.8072 34.1017 12.8302 35.6944 13.9376 36.8017C15.045 37.9091 16.6377 37.9321 18.0431 37.9537C18.7991 37.9653 19.5796 37.9768 20.0505 38.1726C20.5632 38.3843 21.1492 38.9459 21.7166 39.4888C22.7102 40.4435 23.8392 41.525 25.3426 41.525C26.846 41.525 27.9735 40.4435 28.9685 39.4888C29.5359 38.9459 30.1206 38.3843 30.6346 38.1726C31.1055 37.9768 31.886 37.9653 32.642 37.9537C34.0475 37.9321 35.6402 37.9091 36.7475 36.8017C37.8549 35.6944 37.8779 34.1017 37.8995 32.6962C37.9111 31.9402 37.9226 31.1597 38.1184 30.6888C38.3301 30.1762 38.8917 29.5901 39.4346 29.0227C40.3893 28.0291 41.4708 26.9002 41.4708 25.3968C41.4708 23.8934 40.3893 22.7659 39.4346 21.7708ZM31.9177 22.7558L23.8536 30.8199C23.7466 30.927 23.6195 31.012 23.4797 31.0699C23.3398 31.1279 23.1899 31.1578 23.0385 31.1578C22.8872 31.1578 22.7372 31.1279 22.5974 31.0699C22.4575 31.012 22.3305 30.927 22.2235 30.8199L18.7674 27.3638C18.6604 27.2568 18.5755 27.1297 18.5176 26.9899C18.4597 26.85 18.4298 26.7002 18.4298 26.5488C18.4298 26.3974 18.4597 26.2475 18.5176 26.1077C18.5755 25.9678 18.6604 25.8408 18.7674 25.7337C18.9836 25.5176 19.2768 25.3961 19.5825 25.3961C19.7339 25.3961 19.8838 25.4259 20.0236 25.4839C20.1634 25.5418 20.2905 25.6267 20.3975 25.7337L23.0385 28.3762L30.2876 21.1257C30.3946 21.0186 30.5217 20.9337 30.6616 20.8758C30.8014 20.8179 30.9513 20.7881 31.1027 20.7881C31.254 20.7881 31.4039 20.8179 31.5438 20.8758C31.6836 20.9337 31.8107 21.0186 31.9177 21.1257C32.0247 21.2327 32.1096 21.3598 32.1676 21.4996C32.2255 21.6395 32.2553 21.7894 32.2553 21.9407C32.2553 22.0921 32.2255 22.242 32.1676 22.3818C32.1096 22.5217 32.0247 22.6487 31.9177 22.7558Z" fill="#41BD66"/>
  </svg>`;

// PRINTING / SELECTION
const isPrintingSelected = ref(false);
const showSelectedPanel = ref(false);

function toggleSelectedPanel() {
    showSelectedPanel.value = !showSelectedPanel.value;
}

const printSelected = async (idsOverride) => {
    const ids = idsOverride?.length ? idsOverride : tempSelectedIds.value;

    if (!ids.length) {
        toast.add({
            severity: "error",
            summary: "No Items Selected",
            detail: "Please select at least one item to print.",
            life: 3000,
        });
        return;
    }

    startLoading(
        "Downloading Receipt...",
        "Please wait while we prepare your document.",
    );

    try {
        const response = await axios.post(
            route("print.receipt"),
            { ids },
            { responseType: "blob" },
        );

        downloadReceipt(response);
    } catch (error) {
        toast.add({
            severity: "error",
            summary: "Error",
            detail: await responseErrorMessage(
                error,
                "Something went wrong while downloading the receipt.",
            ),
            life: 10000,
        });
    } finally {
        stopLoading();
    }
};

const handlePrint = async (id) => {
    startLoading(
        "Downloading Receipt...",
        "Please wait while we prepare your document.",
    );

    try {
        const response = await axios.post(
            route("print.receipt"),
            { ids: [id] },
            { responseType: "blob" },
        );

        downloadReceipt(response);
    } catch (error) {
        toast.add({
            severity: "error",
            summary: "Error",
            detail: await responseErrorMessage(
                error,
                "Something went wrong while downloading the receipt.",
            ),
            life: 10000,
        });
        toast.add({
            severity: "info",
            summary: "Tip",
            detail: "Use the Assigned/Unassigned filter to check accountable persons.",
            life: 5000,
        });
    } finally {
        stopLoading();
    }
};

// Print-format (PNG/PDF) modal
const showPrintModal = ref(false);
const selectedPrintFormat = ref("png");

function openPrintModal() {
    if (!tempSelectedIds.value.length) {
        toast.add({
            severity: "error",
            summary: "No Items Selected",
            detail: "Please select at least one item.",
            life: 3000,
        });
        return;
    }
    selectedPrintFormat.value = "png";
    showPrintModal.value = true;
}

async function proceedPrint() {
    showPrintModal.value = false;
    if (selectedPrintFormat.value === "png") {
        await printQrCodesAsPng();
    } else {
        await printQrCodesAsPdf();
    }
}

// A blob response hides the server's message, so read it back for the toast
// instead of always reporting a generic failure.
const responseErrorMessage = async (error, fallback) => {
    const data = error?.response?.data;

    if (data instanceof Blob) {
        try {
            const parsed = JSON.parse(await data.text());

            if (parsed?.message) {
                return parsed.message;
            }
        } catch (parseError) {
            // Not JSON: an HTML error page, so fall through to the fallback.
        }
    }

    return data?.message ?? fallback;
};

// Disposing is followed by the IIRUP for the same items.
async function onItemsDisposed(items) {
    clearSelection();

    await runDisposePrint({
        url: route("disposal.print.iirup.combined.stream"),
        payload: buildIirupPayload(items),
        title: "Generating IIRUP",
        successTitle: "IIRUP downloaded",
        errorTitle: "IIRUP was not generated",
        errorMessage:
            "The items were disposed, but the IIRUP could not be generated.",
    });
}

async function printQrCodesAsPng() {
    await runStickerDownload({
        url: route("inventory.qr.pngs.stream"),
        payload: { ids: tempSelectedIds.value },
        title: "Generating stickers",
        successTitle: "Stickers downloaded",
        errorTitle: "Stickers were not generated",
        errorMessage: "Something went wrong while generating the PNG stickers.",
    });
}

async function printQrCodesAsPdf() {
    await runStickerDownload({
        url: route("inventory.qr.pdfs.stream"),
        payload: { ids: tempSelectedIds.value },
        title: "Generating sticker PDF",
        successTitle: "Sticker PDF downloaded",
        errorTitle: "Sticker PDF was not generated",
        errorMessage: "Something went wrong while generating the sticker PDF.",
    });
}

const formatName = (name) => {
    if (!name) return "";

    return name
        .trim()
        .toLowerCase()
        .replace(/\b\w/g, (char) => char.toUpperCase());
};

const users = computed(() => {
    return (page.props.users ?? [])
        .map((user) => {
            const profile = user.user_profiles;

            return {
                ...user,
                full_name: profile
                    ? [
                          formatName(profile.first_name),
                          formatName(profile.middle_name),
                          formatName(profile.last_name),
                      ]
                          .filter(Boolean)
                          .join(" ")
                    : "N/A",
            };
        })
        .sort((a, b) => a.full_name.localeCompare(b.full_name));
});

const adminProfiles = computed(() => {
    return (page.props.adminProfiles ?? [])
        .map((u) => ({
            ...u,
            full_name: [
                formatName(u.first_name),
                formatName(u.middle_name),
                formatName(u.last_name),
            ]
                .filter(Boolean)
                .join(" "),
            roles_label: (u.roles ?? []).join(", "),
        }))
        .sort((a, b) => a.full_name.localeCompare(b.full_name));
});

const canDeleteInventory = computed(() =>
    inventoryActions.value.includes("delete"),
);

const defaultIssuedBy = computed(() => {
    const targetNames = ["grace", "lenarez", "mendez"];

    return (
        adminProfiles.value.find((person) => {
            const names = [
                person.first_name,
                person.middle_name,
                person.last_name,
            ]
                .filter(Boolean)
                .map((name) => name.trim().toLowerCase());

            return targetNames.some((target) => names.includes(target));
        })?.id ?? null
    );
});

const accountableField = [
    {
        label: "Accountable Person",
        model: "accountable_persons_id",
        name: "users",
        option: "full_name",
        value: "id",
        class: "accountable-person-select",
    },
    {
        label: "Issued By",
        model: "issued_by_id",
        name: "adminProfiles",
        option: "full_name",
        value: "id",
        class: "issued-by-select",
    },
];

const filterCondition = computed(() => [
    {
        label: "Condition",
        options: assetConditions.value.map((c) => ({
            label: c.condition_name,
            value: c.id,
        })),
    },
]);

const itemSelectedField = [{ label: "Item Selected", model: "item_name" }];

function handleAssignSubmit(ids) {
    showAssignModal.value = false;
    clearSelection();

    router.get(
        "/inventory/items",
        {
            search: search.value,
            cost_range: cost_range.value,
            acknowledgement_status: acknowledgement_status.value,
        },
        {
            preserveState: true,
            preserveScroll: true,
            only: ["items"],
            onFinish: () => printSelected(ids),
        },
    );
}
</script>

<template>
    <Head title="UP | Inventory/Items" />

    <Layout>
        <DownloadProgressCard :job="stickerJob" @dismiss="dismissStickerJob" />
        <DownloadProgressCard :job="disposeJob" @dismiss="dismissDisposeJob" />

        <PageHeader title="Items" />
        <div class="w-full h-full">
            <!-- TOOLBAR (sticks to the top while scrolling) -->
            <div
                ref="toolbarRef"
                class="sticky top-0 z-30 mt-6 rounded-xl border border-gray-200 bg-white/95 p-3 shadow-sm backdrop-blur"
            >
                <div
                    class="flex flex-col gap-3 xl:flex-row xl:items-end xl:justify-between"
                >
                    <!-- Buttons sit in two aligned rows: the batch actions on
                         top, Add Item and Export lined up underneath. -->
                    <div
                        class="grid w-full grid-cols-1 gap-2 sm:grid-cols-2 xl:w-auto"
                    >
                        <Button
                            v-if="canImportInventory"
                            @click="openImportModal"
                            class="w-full justify-center bg-[#0E6021] text-xs text-white hover:bg-[#19703a] sm:text-sm"
                        >
                            <FileUp class="w-4 h-4 mr-2" />
                            Batch Add
                        </Button>

                        <Button
                            v-if="canDeleteInventory"
                            @click="openBatchArchive"
                            :disabled="!tempSelectedIds.length"
                            class="w-full justify-center bg-[#D71D1D] text-xs text-white hover:bg-[#b81616] disabled:cursor-not-allowed disabled:opacity-40 disabled:hover:bg-[#D71D1D] sm:text-sm"
                        >
                            <Archive class="w-4 h-4 mr-2" />
                            Batch Archive
                            <span
                                class="ml-1 inline-flex h-5 w-7 shrink-0 items-center justify-center rounded-full bg-white/25 text-[11px] tabular-nums leading-5"
                            >
                                {{ tempSelectedIds.length }}
                            </span>
                        </Button>

                        <Button
                            v-if="canCreateInventory"
                            @click="openAdd"
                            class="w-full justify-center bg-[#0E6021] text-xs text-white hover:bg-[#19703a] sm:text-sm"
                        >
                            <Plus class="w-4 h-4 mr-2" />
                            Add Item
                        </Button>

                        <Button
                            v-if="canExportInventory"
                            @click="showExportModal = true"
                            variant="outline"
                            class="w-full justify-center border-[#0E6021] text-xs text-[#0E6021] hover:bg-[#0E6021]/5 sm:text-sm"
                        >
                            <FileDown class="w-4 h-4 mr-2" />
                            Export
                        </Button>

                    </div>

                    <!-- filters and search -->
                    <div class="flex w-full flex-col gap-2 xl:max-w-3xl">
                        <div class="flex justify-end">
                            <Button
                                type="button"
                                @click="showConditionModal = true"
                                variant="outline"
                                class="justify-center border-gray-300 text-xs text-gray-600 hover:bg-gray-50 sm:text-sm"
                            >
                                <ListChecks class="w-4 h-4 mr-2" />
                                Manage Conditions
                            </Button>
                        </div>

                        <SearchFilterBar
                            :search="search"
                            :cost_range="cost_range"
                            :asset_condition_id="asset_condition_id"
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
                            :mode="'inventory'"
                            :collapsible-filters="true"
                        />
                    </div>
                </div>

                <!-- SELECTION ACTIONS (always visible, enabled once rows are ticked) -->
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
                        <span
                            class="flex h-6 w-7 shrink-0 items-center justify-center rounded-full text-[11px] font-bold tabular-nums text-white transition-colors"
                            :class="
                                tempSelectedIds.length
                                    ? 'bg-[#005740]'
                                    : 'bg-gray-400'
                            "
                        >
                            {{ tempSelectedIds.length }}
                        </span>
                        selected
                    </span>

                    <span
                        class="hidden h-5 w-px sm:block"
                        :class="
                            tempSelectedIds.length
                                ? 'bg-[#005740]/20'
                                : 'bg-gray-200'
                        "
                    ></span>

                    <button
                        type="button"
                        @click="openAss"
                        :disabled="!tempSelectedIds.length"
                        class="flex items-center gap-1.5 rounded-md px-2.5 py-1.5 text-xs font-medium text-[#005740] transition-colors hover:bg-[#005740]/10 disabled:cursor-not-allowed disabled:opacity-40 disabled:hover:bg-transparent"
                    >
                        <UserPlus class="h-3.5 w-3.5" />
                        Assign
                    </button>

                    <button
                        type="button"
                        @click="openPrintModal"
                        :disabled="!tempSelectedIds.length"
                        class="flex items-center gap-1.5 rounded-md px-2.5 py-1.5 text-xs font-medium text-[#005740] transition-colors hover:bg-[#005740]/10 disabled:cursor-not-allowed disabled:opacity-40 disabled:hover:bg-transparent"
                    >
                        <QrCode class="h-3.5 w-3.5" />
                        Sticker
                    </button>

                    <button
                        type="button"
                        @click="printSelected"
                        :disabled="!tempSelectedIds.length || isPrintingSelected"
                        class="flex items-center gap-1.5 rounded-md px-2.5 py-1.5 text-xs font-medium text-[#005740] transition-colors hover:bg-[#005740]/10 disabled:cursor-not-allowed disabled:opacity-40 disabled:hover:bg-transparent"
                    >
                        <Printer class="h-3.5 w-3.5" />
                        {{ isPrintingSelected ? "Printing…" : "Print" }}
                    </button>

                    <button
                        type="button"
                        :disabled="!tempSelectedIds.length"
                        @click="showDisposeModal = true"
                        class="flex items-center gap-1.5 rounded-md px-2.5 py-1.5 text-xs font-medium text-[#D71D1D] transition-colors hover:bg-[#D71D1D]/10 disabled:cursor-not-allowed disabled:opacity-40 disabled:hover:bg-transparent"
                    >
                        <PackageX class="h-3.5 w-3.5" />
                        Dispose
                        <span
                            class="rounded-full bg-[#D71D1D]/10 px-1.5 text-[10px] font-bold tabular-nums"
                        >
                            {{ tempSelectedIds.length }}
                        </span>
                    </button>

                    <div class="ml-auto flex items-center gap-1">
                        <div class="relative">
                            <button
                                type="button"
                                @click="toggleSelectedPanel"
                                :disabled="!tempSelectedIds.length"
                                class="flex items-center gap-1 rounded-md px-2.5 py-1.5 text-xs font-medium text-gray-600 transition-colors hover:bg-white disabled:cursor-not-allowed disabled:opacity-40 disabled:hover:bg-transparent"
                            >
                                Review
                                <ChevronDown
                                    class="h-3.5 w-3.5 transition-transform duration-200"
                                    :class="{
                                        'rotate-180': showSelectedPanel,
                                    }"
                                />
                            </button>

                            <div
                                v-if="showSelectedPanel"
                                class="absolute right-0 z-40 mt-2 max-h-80 w-72 overflow-y-auto rounded-xl border border-gray-200 bg-white shadow-lg"
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
                                        type="button"
                                        @click="clearSelection"
                                        class="text-xs font-medium text-[#005740] hover:underline"
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
                                            class="flex-shrink-0 text-gray-400 transition-colors hover:text-[#D71D1D]"
                                            title="Remove from selection"
                                        >
                                            <X class="w-3.5 h-3.5" />
                                        </button>
                                        <span class="truncate text-gray-700">
                                            {{ item.item_name }}
                                            <template
                                                v-if="item.property_number"
                                                >-
                                                {{
                                                    item.property_number
                                                }}</template
                                            >
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

                        <button
                            type="button"
                            @click="clearSelection"
                            :disabled="!tempSelectedIds.length"
                            class="rounded-md px-2 py-1.5 text-xs font-medium text-gray-500 transition-colors hover:text-[#D71D1D] hover:underline disabled:cursor-not-allowed disabled:opacity-40 disabled:hover:text-gray-500 disabled:hover:no-underline"
                        >
                            Clear
                        </button>
                    </div>
                </div>
            </div>

            <AssetConditionFormModal
                v-if="showConditionModal"
                :conditions="assetConditions"
                @close="showConditionModal = false"
            />

            <DisposeItemsModal
                v-if="showDisposeModal"
                :items="selectedItemsDetails"
                :disposed-condition="disposedCondition"
                @close="showDisposeModal = false"
                @add-condition="
                    showDisposeModal = false;
                    showConditionModal = true;
                "
                @disposed="onItemsDisposed"
            />

            <ImportItemsModal
                v-if="showImportModal"
                :canDownloadSample="canImportInventory"
                @close="showImportModal = false"
                @download-sample="downloadExportSample"
                @import="handleImportFile"
                @review="handleReviewFile"
            />

            <ImportResultModal
                v-if="importResult"
                :result="importResult"
                :canImport="Boolean(pendingImportFile)"
                @import="importPendingFile"
                @close="importResult = null"
            />

            <ExportFormatModal
                :show="showExportModal"
                title="Export Items"
                subtitle="Download every item in the database. Pick the file format you want."
                @confirm="confirmExport"
                @close="showExportModal = false"
            />

            <AcknowledgementFormModal
                v-if="showAssignModal"
                :mode="'create'"
                :accountableField="accountableField"
                :itemSelectedField="itemSelectedField"
                :adminProfiles="adminProfiles"
                :selectedIDs="tempSelectedIds"
                :items="items"
                :rooms="rooms"
                :userProfiles="users"
                :defaultIssuedBy="defaultIssuedBy"
                :transferTypes="transferTypes"
                :biggestCategories="biggestCategories"
                @close="() => (showAssignModal = false)"
                @assigned="handleAssignSubmit"
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
                :assetConditions="assetConditions"
                :itemClass="itemClassifications"
                :rooms="rooms"
                :roomDropdown="roomDropdown"
                :initialValues="currentItem"
                :suppliers="suppliers"
                :item="currentItem"
                :viewItem="viewItem"
                :lifespanField="lifespanField"
                @submit="handleSubmit"
                @close="() => (showFormModal = false)"
            />

            <SuccessDeleteModal
                v-if="showDeleteSuccessModal"
                :icon="iconDelete"
                title="Archive Success"
                :message="archiveSuccessMessage"
                buttonText="Confirm"
                @close="showDeleteSuccessModal = false"
            />

            <BatchArchiveModal
                v-if="showBatchArchiveModal"
                :items="selectedItemsDetails"
                @confirm="confirmBatchArchive"
                @close="showBatchArchiveModal = false"
            />

            <ArchiveModal
                v-if="showArchiveModal"
                :item="currentItem"
                @confirm="confirmArchive"
                @close="() => (showArchiveModal = false)"
            />

            <InventoryTable
                :columns="columns"
                :rows="items"
                :rooms="rooms"
                :module="'inventory'"
                :actions="inventoryActions"
                :selected="tempSelectedIds"
                @selection-changed="handleSelectionChanged"
                @view="handleView"
                @edit="handleEdit"
                @delete="handleDelete"
                @print="handlePrint"
            />
        </div>

        <!-- Print Format Modal -->
        <div
            v-if="showPrintModal"
            class="fixed inset-0 z-50 flex items-center justify-center modal-backdrop"
        >
            <div class="bg-white rounded-lg w-full max-w-md overflow-hidden">
                <div
                    class="px-6 py-3 bg-gradient-to-r from-[#005740] to-[#00795a]"
                >
                    <h3 class="text-white text-lg font-bold">
                        Download QR Codes
                    </h3>
                </div>
                <div class="p-6">
                    <p class="text-md font-bold text-gray-600 mb-4">
                        Choose output format:
                    </p>

                    <div class="space-y-3 mb-4">
                        <label class="flex items-start gap-3">
                            <input
                                type="radio"
                                value="png"
                                v-model="selectedPrintFormat"
                                class="form-radio mt-1"
                            />
                            <div>
                                <div class="flex items-center gap-2">
                                    <i class="pi pi-image text-[#005740]"></i>
                                    <span class="text-sm font-semibold"
                                        >PNG (images)</span
                                    >
                                </div>
                                <p class="text-xs text-gray-500 mt-1">
                                    PNG files are flexible in size and will not
                                    be constrained to a fixed sticker dimension.
                                    Use this when you need images or variable
                                    sizing.
                                </p>
                            </div>
                        </label>

                        <label class="flex items-start gap-3">
                            <input
                                type="radio"
                                value="pdf"
                                v-model="selectedPrintFormat"
                                class="form-radio mt-1"
                            />
                            <div>
                                <div class="flex items-center gap-2">
                                    <i
                                        class="pi pi-file-pdf text-[#005740]"
                                    ></i>
                                    <span class="text-sm font-semibold"
                                        >PDF (document)</span
                                    >
                                </div>
                                <p class="text-xs text-gray-500 mt-1">
                                    PDF produces fixed-size pages suitable for
                                    printing standardized stickers. Choose this
                                    for consistent print layout.
                                </p>
                            </div>
                        </label>
                    </div>

                    <div class="flex justify-end gap-3">
                        <button
                            @click="showPrintModal = false"
                            class="px-4 py-2 rounded bg-gray-100"
                        >
                            Cancel
                        </button>
                        <button
                            @click="proceedPrint"
                            class="px-4 py-2 rounded bg-[#005740] text-white flex items-center gap-2"
                        >
                            <i class="pi pi-download"></i>
                            <span>Download</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </Layout>
</template>
