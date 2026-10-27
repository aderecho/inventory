<script setup>
import { ref, computed } from "vue";
import { usePage, router } from "@inertiajs/vue3";
import { Head } from "@inertiajs/vue3";
import Layout from "@/Layouts/Layout.vue";
import PageHeader from "@/Components/PageHeader.vue";
import InventoryTable from "@/Components/InventoryTable.vue";
import SearchFilterBar from "@/Components/Filters/SearchFilterBar.vue";
import SupplierFormModal from "@/Components/Modals/SupplierFormModal.vue";
import ArchiveModal from "@/Components/Modals/ArchiveModal.vue";
import BatchArchiveModal from "@/Components/Modals/BatchArchiveModal.vue";
import ImportItemsModal from "@/Components/Modals/ImportItemsModal.vue";
import ImportResultModal from "@/Components/Modals/ImportResultModal.vue";
import ExportFormatModal from "@/Components/Modals/ExportFormatModal.vue";
import { useToast } from "primevue/usetoast";
import Toast from "primevue/toast";
import SuccessModal from "@/Components/Modals/SuccessModal.vue";
import SuccessDeleteModal from "@/Components/Modals/SuccessDeleteModal.vue";
import { useLoading } from "@/Composables/useLoading";
import { usePermissions } from "@/Composables/usePermissions";
import axios from "axios";

const { supplierActions, canCreateSupplier, canViewSuppliers } =
    usePermissions();
const { startLoading, stopLoading } = useLoading();
const toast = useToast();

const columns = [
    { label: "", key: "select_all" },
    { label: "Supplier Name", key: "supplier_name" },
    { label: "Contact", key: "contact_no" },
    { label: "Email", key: "email" },
    { label: "Address", key: "address" },
    {
        label: "Status",
        key: "status",
        format: (status) => {
            let label = "Unknown",
                cls = "text-gray-500",
                icon = "";
            if (status === 0) {
                label = "Inactive";
                cls = "text-[#D32F2F] font-bold bg-[#F8D4D4] py-2 px-4 rounded-md";
            } else if (status === 1) {
                label = "Active";
                cls = "text-[#2E7D32] font-bold bg-[#D4F8D4] py-2 px-4 rounded-md";
            }
            return `<span class="${cls}">${icon} ${label}</span>`;
        },
    },
    { label: "Action", key: "action" },
];

const supplierFields = [
    {
        label: "Supplier Name",
        model: "supplier_name",
        placeholder: "Supplier Name",
        type: "text",
        required: true,
    },
    {
        label: "Contact Number",
        model: "contact_no",
        placeholder: "Contact Number",
        type: "text",
        required: true,
    },
    {
        label: "Email",
        model: "email",
        placeholder: "Email",
        type: "text",
        required: false,
    },
    {
        label: "Address",
        model: "address",
        placeholder: "Address",
        type: "text",
        required: true,
    },
];

const statusDropdown = [
    {
        label: "Status",
        model: "status",
        options: [
            { label: "Active", value: "1" },
            { label: "Inactive", value: "0" },
        ],
    },
];

const page = usePage();
const suppliers = computed(() => page.props.suppliers || []);

// ITEMS FILTER CONTROL
const search = ref("");

const formMode = ref("create"); // CREATE || EDIT || VIEW
const showFormModal = ref(false);
const currentSupplier = ref({});
const showArchiveModal = ref(false);

const showSuccessModal = ref(false);
const showDeleteSuccessModal = ref(false);
const successMessage = ref("");

function openAdd() {
    formMode.value = "create";
    currentSupplier.value = {};
    showFormModal.value = true;
}

function handleSubmit() {
    stopLoading();
    showSuccessModal.value = true;
    successMessage.value =
        formMode.value === "edit"
            ? "Supplier updated successfully!"
            : "Supplier added successfully!";

    showFormModal.value = false;
}

function handleEdit(supplier) {
    formMode.value = "edit";
    currentSupplier.value = supplier;
    showFormModal.value = true;
}

function handleDelete(supplier) {
    currentSupplier.value = supplier;
    showArchiveModal.value = true;
}

function confirmArchive() {
    archiveMessage.value = "Supplier archived successfully!";

    router.delete(route("suppliers.destroy", currentSupplier.value.id), {
        preserveScroll: true,
        onSuccess: () => {
            stopLoading();
            showArchiveModal.value = false;
            showDeleteSuccessModal.value = true;
            currentSupplier.value = {};
        },
    });
}

// ------------------------------------------------------------------
// SELECTION (checkbox column)
// ------------------------------------------------------------------
const selectedSuppliersMap = ref(new Map());

const selectedIds = computed(() =>
    Array.from(selectedSuppliersMap.value.keys()),
);
const selectedSuppliers = computed(() =>
    Array.from(selectedSuppliersMap.value.values()),
);

function handleSelectionChanged(ids, currentPageRows = []) {
    const idSet = new Set(ids);
    const newMap = new Map(selectedSuppliersMap.value);

    currentPageRows.forEach((row) => {
        if (idSet.has(row.id)) newMap.set(row.id, row);
    });

    for (const key of newMap.keys()) {
        if (!idSet.has(key)) newMap.delete(key);
    }

    idSet.forEach((id) => {
        if (!newMap.has(id)) {
            const existing = selectedSuppliersMap.value.get(id);
            if (existing) newMap.set(id, existing);
        }
    });

    selectedSuppliersMap.value = newMap;
}

function clearSelection() {
    selectedSuppliersMap.value = new Map();
}

// ------------------------------------------------------------------
// BATCH ADD (same flow as the inventory import)
// ------------------------------------------------------------------
const showImportModal = ref(false);
const importResult = ref(null);

// The file shown in a review, kept so it can be imported after.
const pendingImportFile = ref(null);

const batchAddContent = {
    title: "Batch Add Suppliers",
    subtitle: "Add many suppliers at once from an Excel or CSV file.",
    introTitle: "Follow the sample Excel columns to avoid errors.",
    introBody:
        "Your file must use the same column headers as the sample, in the first row. Each row below the headers is one supplier. Download the sample, replace the example rows with your suppliers, and keep the headers as they are.",
    requiredColumns:
        "Supplier Name. Contact No., Email, Address, and Status are optional.",
    requiredNote:
        "Status accepts Active or Inactive. A blank Status is added as Active.",
    mustExist: [],
    values: [
        "Supplier Name must be unique among active suppliers. If the name is already in the archive, that supplier is restored and updated with this row.",
        "An email that belongs to an active supplier is rejected; one that belongs to an archived supplier is only noted as a warning.",
        "Email must look like an address, for example orders@example.com.",
        "Contact No. is saved as text, so leading zeros are kept.",
        "The names in the sample are examples and will be added if imported as they are.",
    ],
    footer:
        "Rows with problems are skipped. After importing, you will see each skipped row's Excel row number and the reason, so you can fix and import them again.",
    importLabel: "Batch Add",
};

function openBatchAdd() {
    showImportModal.value = true;
}

function downloadSample() {
    window.location.href = route("suppliers.batch-add.sample");
}

// Downloads every supplier in the database, using the sample's columns.
// Export opens the format picker first (xlsx or csv).
const showExportModal = ref(false);

function confirmExport(format) {
    showExportModal.value = false;

    window.location.href = route("suppliers.export", { format });
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
        "Reviewing Suppliers...",
        "Checking every row. Nothing is saved until you import.",
    );

    try {
        const { data } = await axios.post(route("suppliers.batch-add"), formData);
        importResult.value = data;
    } catch (error) {
        // Keep the real cause in the console; the user gets a short message.
        console.error("Review request failed:", error);

        const response = error.response;
        let message = "The file could not be reviewed. Please try again.";

        if (response?.status === 403) {
            message = "You do not have permission to import suppliers.";
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
        "Batch Adding Suppliers...",
        "Reading the file and checking every row. This can take a minute.",
    );

    try {
        const { data } = await axios.post(route("suppliers.batch-add"), formData);
        importResult.value = data;
        pendingImportFile.value = null;

        if (data.imported_rows > 0) {
            router.reload({ only: ["suppliers"] });
        }
    } catch (error) {
        const response = error.response;
        let message = "The batch add could not be completed. Please try again.";

        if (response?.status === 403) {
            message = "You do not have permission to add suppliers.";
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

// ------------------------------------------------------------------
// BATCH ARCHIVE
// ------------------------------------------------------------------
const showBatchArchiveModal = ref(false);
const archiveMessage = ref("Supplier archived successfully!");

function openBatchArchive() {
    if (!selectedIds.value.length) {
        return;
    }

    showBatchArchiveModal.value = true;
}

function confirmBatchArchive() {
    const count = selectedIds.value.length;

    startLoading(
        "Archiving Suppliers...",
        "Please wait while we archive the selected suppliers.",
    );

    router.delete(route("suppliers.batch-archive"), {
        data: { ids: selectedIds.value },
        preserveScroll: true,
        onSuccess: () => {
            stopLoading();
            showBatchArchiveModal.value = false;
            archiveMessage.value =
                count === 1
                    ? "Supplier archived successfully!"
                    : count + " suppliers archived successfully!";
            showDeleteSuccessModal.value = true;
            clearSelection();
            router.reload({ only: ["suppliers"] });
        },
        onError: () => {
            stopLoading();
            toast.add({
                severity: "error",
                summary: "Archive Failed",
                detail: "The selected suppliers could not be archived.",
                life: 5000,
            });
        },
    });
}
</script>

<template>
    <Head title="UP | Suppliers" />

    <Layout>
        <Toast />

        <PageHeader title="Suppliers" />

        <div class="w-full h-full">
            <div
                class="mt-10 flex flex-col gap-4 md:flex-row md:items-end md:justify-between"
            >
                <!-- Buttons sit in two aligned rows: the batch actions on
                     top, with Export and Add Supplier lined up underneath. -->
                <div
                    class="grid w-full grid-cols-1 gap-2 sm:grid-cols-2 md:w-auto"
                >
                    <button
                        v-if="canCreateSupplier"
                        type="button"
                        @click="openBatchAdd"
                        class="flex w-full items-center justify-center gap-2 rounded-md bg-[#0E6021] px-3 py-2 text-xs text-white transition-colors hover:bg-[#19703a] sm:text-sm"
                    >
                        <i class="fa-solid fa-file-import"></i>
                        <span>Batch Add</span>
                    </button>

                    <button
                        v-if="supplierActions.includes('delete')"
                        type="button"
                        @click="openBatchArchive"
                        :disabled="selectedIds.length === 0"
                        :class="[
                            'flex w-full items-center justify-center gap-2 rounded-md px-3 py-2 text-xs text-white transition-colors sm:text-sm',
                            selectedIds.length === 0
                                ? 'cursor-not-allowed bg-[#D71D1D]/60'
                                : 'bg-[#D71D1D] hover:bg-[#b81616]',
                        ]"
                    >
                        <i class="fa-solid fa-box-archive"></i>
                        <span>Batch Archive</span>
                        <span
                            class="inline-flex h-5 w-7 shrink-0 items-center justify-center rounded-full bg-white/25 text-[11px] tabular-nums leading-5"
                        >
                            {{ selectedIds.length }}
                        </span>
                    </button>

                    <button
                        v-if="canCreateSupplier"
                        type="button"
                        @click="openAdd()"
                        class="flex w-full items-center justify-center gap-2 rounded-md bg-[#0E6021] px-3 py-2 text-xs text-white transition-colors hover:bg-[#19703a] sm:text-sm"
                    >
                        <i class="fa-solid fa-user-group"></i>
                        <span>Add Supplier</span>
                    </button>

                    <button
                        v-if="canViewSuppliers"
                        type="button"
                        @click="showExportModal = true"
                        class="flex w-full items-center justify-center gap-2 rounded-md border border-[#0E6021] px-3 py-2 text-xs text-[#0E6021] transition-colors hover:bg-[#0E6021]/5 sm:text-sm"
                    >
                        <i class="fa-solid fa-file-export"></i>
                        <span>Export</span>
                    </button>
                    
                </div>

                <!-- Search is pinned to the right end, level with the buttons. -->
                <div class="flex w-full justify-end md:ml-auto md:w-auto">
                    <SearchFilterBar
                        :search="search"
                        @update:search="search = $event"
                        :mode="'suppliers'"
                    />
                </div>
            </div>

            <SupplierFormModal
                v-if="showFormModal"
                :mode="formMode"
                :supplier="currentSupplier"
                :supplierFields="supplierFields"
                @submit="handleSubmit"
                @close="showFormModal = false"
            />

            <ArchiveModal
                v-if="showArchiveModal"
                :item="currentSupplier"
                @confirm="confirmArchive"
                @close="() => (showArchiveModal = false)"
            />

            <SuccessModal
                v-if="showSuccessModal"
                title="Success"
                :message="successMessage"
                @close="showSuccessModal = false"
            />

            <SuccessDeleteModal
                v-if="showDeleteSuccessModal"
                title="Archive Success"
                :message="archiveMessage"
                buttonText="Confirm"
                @close="showDeleteSuccessModal = false"
            />

            <ImportItemsModal
                v-if="showImportModal"
                :content="batchAddContent"
                :canDownloadSample="canCreateSupplier"
                @close="showImportModal = false"
                @download-sample="downloadSample"
                @import="handleImportFile"
                @review="handleReviewFile"
            />

            <ImportResultModal
                v-if="importResult"
                :result="importResult"
                :canImport="Boolean(pendingImportFile)"
                entityLabel="supplier"
                :showReceipts="false"
                @import="importPendingFile"
                @close="importResult = null"
            />

            <BatchArchiveModal
                v-if="showBatchArchiveModal"
                :items="selectedSuppliers"
                entityLabel="supplier"
                @confirm="confirmBatchArchive"
                @close="showBatchArchiveModal = false"
            />

            <ExportFormatModal
                :show="showExportModal"
                title="Export Suppliers"
                subtitle="Download every supplier in the database. Pick the file format you want."
                @confirm="confirmExport"
                @close="showExportModal = false"
            />

            <InventoryTable
                :module="'suppliers'"
                :columns="columns"
                :rows="suppliers"
                :actions="supplierActions"
                :selected="selectedIds"
                @selection-changed="handleSelectionChanged"
                @edit="handleEdit"
                @delete="handleDelete"
            />
        </div>
    </Layout>
</template>
