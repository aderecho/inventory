<script setup>
import { ref, computed } from "vue";
import { usePage, router } from "@inertiajs/vue3";
import { Head } from "@inertiajs/vue3";
import Layout from "@/Layouts/Layout.vue";
import PageHeader from "@/Components/PageHeader.vue";
import InventoryTable from "@/Components/InventoryTable.vue";
import SearchFilterBar from "@/Components/Filters/SearchFilterBar.vue";
import CategoriesFormModal from "@/Components/Modals/CategoriesFormModal.vue";
import ArchiveModal from "@/Components/Modals/ArchiveModal.vue";
import BatchArchiveModal from "@/Components/Modals/BatchArchiveModal.vue";
import ImportItemsModal from "@/Components/Modals/ImportItemsModal.vue";
import ImportResultModal from "@/Components/Modals/ImportResultModal.vue";
import ExportFormatModal from "@/Components/Modals/ExportFormatModal.vue";
import { useToast } from "primevue/usetoast";
import Toast from "primevue/toast";
import axios from "axios";
import SuccessModal from "@/Components/Modals/SuccessModal.vue";
import SuccessDeleteModal from "@/Components/Modals/SuccessDeleteModal.vue";
import { useLoading } from "@/Composables/useLoading";
import { usePermissions } from "@/Composables/usePermissions";

const { startLoading, stopLoading } = useLoading();
const toast = useToast();

const { categoryActions, canCreateCategory, canViewCategories } =
    usePermissions();

const columns = [
    { label: "", key: "select_all" },
    { label: "Classification Name", key: "classification_name" },
    { label: "Classification Code", key: "classification_code" },
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

const categoriesFields = [
    {
        label: "Classification Name",
        model: "classification_name",
        placeholder: "Classification Name",
        type: "text",
    },
    {
        label: "Classification Code",
        model: "classification_code",
        placeholder: "Classification Code",
        type: "text",
    },
];

const page = usePage();
const categories = computed(() => page.props.categories || []);

const search = ref("");

const formMode = ref("create"); // create | edit
const showFormModal = ref(false);
const showArchiveModal = ref(false);

const currentCategories = ref({});

const showSuccessModal = ref(false);
const showDeleteSuccessModal = ref(false);
const successMessage = ref("");

function openAdd() {
    formMode.value = "create";
    currentCategories.value = {};
    showFormModal.value = true;
}

function handleEdit(category) {
    formMode.value = "edit";
    currentCategories.value = category;
    showFormModal.value = true;
}

function handleDelete(category) {
    currentCategories.value = category;
    showArchiveModal.value = true;
}

function handleSubmit(form) {
    if (formMode.value === "create") {
        form.post(route("categories.store"), {
            preserveScroll: true,
            onSuccess: () => {
                stopLoading();
                showFormModal.value = false;
                form.reset();

                successMessage.value = "Category added successfully!";
                showSuccessModal.value = true;
            },
        });
    } else {
        form.put(route("categories.update", form.id), {
            preserveScroll: true,
            onSuccess: () => {
                stopLoading();
                showFormModal.value = false;
                form.reset();

                successMessage.value = "Category updated successfully!";
                showSuccessModal.value = true;
            },
        });
    }
}

function confirmArchive() {
    archiveMessage.value = "Category archived successfully!";

    router.delete(route("categories.destroy", currentCategories.value.id), {
        preserveScroll: true,
        onSuccess: () => {
            stopLoading();
            showArchiveModal.value = false;
            showDeleteSuccessModal.value = true;
            currentCategories.value = {};
        },
    });
}

// ------------------------------------------------------------------
// SELECTION (checkbox column)
// ------------------------------------------------------------------
const selectedCategoriesMap = ref(new Map());

const selectedIds = computed(() =>
    Array.from(selectedCategoriesMap.value.keys()),
);
const selectedCategories = computed(() =>
    Array.from(selectedCategoriesMap.value.values()),
);

function handleSelectionChanged(ids, currentPageRows = []) {
    const idSet = new Set(ids);
    const newMap = new Map(selectedCategoriesMap.value);

    currentPageRows.forEach((row) => {
        if (idSet.has(row.id)) newMap.set(row.id, row);
    });

    for (const key of newMap.keys()) {
        if (!idSet.has(key)) newMap.delete(key);
    }

    idSet.forEach((id) => {
        if (!newMap.has(id)) {
            const existing = selectedCategoriesMap.value.get(id);
            if (existing) newMap.set(id, existing);
        }
    });

    selectedCategoriesMap.value = newMap;
}

function clearSelection() {
    selectedCategoriesMap.value = new Map();
}

// ------------------------------------------------------------------
// BATCH ADD (same flow as the inventory and suppliers modules)
// ------------------------------------------------------------------
const showImportModal = ref(false);
const importResult = ref(null);

// The file shown in a review, kept so it can be imported after.
const pendingImportFile = ref(null);

const batchAddContent = {
    title: "Batch Add Categories",
    subtitle: "Add many categories at once from an Excel or CSV file.",
    introTitle: "Follow the sample Excel columns to avoid errors.",
    introBody:
        "Your file must use the same column headers as the sample, in the first row. Each row below the headers is one category. Download the sample, replace the example rows with your categories, and keep the headers as they are.",
    requiredColumns:
        "Classification Code and Classification Name. Status is optional.",
    requiredNote:
        "Status accepts Active or Inactive. A blank Status is added as Active.",
    mustExist: [],
    values: [
        "Classification Code must be unique among active categories. If the code is already in the archive, that category is restored and updated with this row.",
        "The code is the first number of a property number, for example 221 in 221-905-001, so keep it short. Up to 50 characters.",
        "The codes in the sample are examples and will be added if imported as they are.",
    ],
    footer:
        "Rows with problems are skipped. After importing, you will see each skipped row's Excel row number and the reason, so you can fix and import them again.",
    importLabel: "Batch Add",
};

function openBatchAdd() {
    showImportModal.value = true;
}

function downloadSample() {
    window.location.href = route("categories.batch-add.sample");
}

// Downloads every category in the database, using the sample's columns.
// Export opens the format picker first (xlsx or csv).
const showExportModal = ref(false);

function confirmExport(format) {
    showExportModal.value = false;

    window.location.href = route("categories.export", { format });
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
        "Reviewing Classifications...",
        "Checking every row. Nothing is saved until you import.",
    );

    try {
        const { data } = await axios.post(route("categories.batch-add"), formData);
        importResult.value = data;
    } catch (error) {
        // Keep the real cause in the console; the user gets a short message.
        console.error("Review request failed:", error);

        const response = error.response;
        let message = "The file could not be reviewed. Please try again.";

        if (response?.status === 403) {
            message = "You do not have permission to import categories.";
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
        "Batch Adding Categories...",
        "Reading the file and checking every row. This can take a minute.",
    );

    try {
        const { data } = await axios.post(
            route("categories.batch-add"),
            formData,
        );
        importResult.value = data;
        pendingImportFile.value = null;

        if (data.imported_rows > 0) {
            router.reload({ only: ["categories"] });
        }
    } catch (error) {
        const response = error.response;
        let message = "The batch add could not be completed. Please try again.";

        if (response?.status === 403) {
            message = "You do not have permission to add categories.";
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
// BATCH DELETE
// ------------------------------------------------------------------
const showBatchDeleteModal = ref(false);
const archiveMessage = ref("Category archived successfully!");

function openBatchDelete() {
    if (!selectedIds.value.length) {
        return;
    }

    showBatchDeleteModal.value = true;
}

function confirmBatchDelete() {
    const count = selectedIds.value.length;

    startLoading(
        "Deleting Categories...",
        "Please wait while we archive the selected categories.",
    );

    router.delete(route("categories.batch-delete"), {
        data: { ids: selectedIds.value },
        preserveScroll: true,
        onSuccess: () => {
            stopLoading();
            showBatchDeleteModal.value = false;
            archiveMessage.value =
                count === 1
                    ? "Category archived successfully!"
                    : count + " categories archived successfully!";
            showDeleteSuccessModal.value = true;
            clearSelection();
            router.reload({ only: ["categories"] });
        },
        onError: () => {
            stopLoading();
            toast.add({
                severity: "error",
                summary: "Delete Failed",
                detail: "The selected categories could not be archived.",
                life: 5000,
            });
        },
    });
}
</script>

<template>
    <Head title="UP | Categories" />

    <Layout>
        <Toast />

        <PageHeader title="Categories" />

        <div
            class="mt-10 flex flex-col gap-4 md:flex-row md:items-end md:justify-between"
        >
            <!-- Buttons sit in two aligned rows: the batch actions on
                 top, with Export and Add Category lined up underneath. -->
            <div
                class="grid w-full grid-cols-1 gap-2 sm:grid-cols-2 md:w-auto"
            >

             <button
                    v-if="canCreateCategory"
                    type="button"
                    @click="openAdd()"
                    class="flex w-full items-center justify-center gap-2 rounded-md bg-[#0E6021] px-3 py-2 text-xs text-white transition-colors hover:bg-[#19703a] sm:text-sm"
                >
                    <i class="fa-solid fa-user-group"></i>
                    <span>Add Category</span>
                </button>

                <button
                    v-if="canCreateCategory"
                    type="button"
                    @click="openBatchAdd"
                    class="flex w-full items-center justify-center gap-2 rounded-md bg-[#0E6021] px-3 py-2 text-xs text-white transition-colors hover:bg-[#19703a] sm:text-sm"
                >
                    <i class="fa-solid fa-file-import"></i>
                    <span>Batch Add</span>
                </button>

                 <button
                    v-if="canViewCategories"
                    type="button"
                    @click="showExportModal = true"
                    class="flex w-full items-center justify-center gap-2 rounded-md border border-[#0E6021] px-3 py-2 text-xs text-[#0E6021] transition-colors hover:bg-[#0E6021]/5 sm:text-sm"
                >
                    <i class="fa-solid fa-file-export"></i>
                    <span>Export</span>
                </button>

                <button
                    v-if="categoryActions.includes('delete')"
                    type="button"
                    @click="openBatchDelete"
                    :disabled="selectedIds.length === 0"
                    :class="[
                        'flex w-full items-center justify-center gap-2 rounded-md px-3 py-2 text-xs text-white transition-colors sm:text-sm',
                        selectedIds.length === 0
                            ? 'cursor-not-allowed bg-[#D71D1D]/60'
                            : 'bg-[#D71D1D] hover:bg-[#b81616]',
                    ]"
                >
                    <i class="fa-solid fa-box-archive"></i>
                    <span>Batch Delete</span>
                    <span
                        class="inline-flex h-5 w-7 shrink-0 items-center justify-center rounded-full bg-white/25 text-[11px] tabular-nums leading-5"
                    >
                        {{ selectedIds.length }}
                    </span>
                </button>
            </div>

            <!-- Search is pinned to the right end, level with the buttons. -->
            <div class="flex w-full justify-end md:ml-auto md:w-auto">
                <SearchFilterBar
                    :search="search"
                    @update:search="search = $event"
                    :mode="'categories'"
                />
            </div>
        </div>

        <CategoriesFormModal
            v-if="showFormModal"
            :mode="formMode"
            :categories="currentCategories"
            :categoriesFields="categoriesFields"
            @submit="handleSubmit"
            @close="showFormModal = false"
        />

        <ArchiveModal
            v-if="showArchiveModal"
            :item="currentCategories"
            @confirm="confirmArchive"
            @close="showArchiveModal = false"
        />

        <SuccessModal
            v-if="showSuccessModal"
            title="Success"
            :message="successMessage"
            actionButtonLabel="OK"
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
            :canDownloadSample="canCreateCategory"
            @close="showImportModal = false"
            @download-sample="downloadSample"
                @import="handleImportFile"
            @review="handleReviewFile"
        />

        <ImportResultModal
            v-if="importResult"
            :result="importResult"
            :canImport="Boolean(pendingImportFile)"
            entityLabel="category"
            :showReceipts="false"
            @import="importPendingFile"
            @close="importResult = null"
        />

        <BatchArchiveModal
            v-if="showBatchDeleteModal"
            :items="selectedCategories"
            entityLabel="category"
            @confirm="confirmBatchDelete"
            @close="showBatchDeleteModal = false"
        />

        <ExportFormatModal
            :show="showExportModal"
            title="Export Categories"
            subtitle="Download every classification in the database. Pick the file format you want."
            @confirm="confirmExport"
            @close="showExportModal = false"
        />

        <InventoryTable
            :columns="columns"
            :module="'categories'"
            :rows="categories"
            :actions="categoryActions"
            :selected="selectedIds"
            @selection-changed="handleSelectionChanged"
            @edit="handleEdit"
            @delete="handleDelete"
        />
    </Layout>
</template>
