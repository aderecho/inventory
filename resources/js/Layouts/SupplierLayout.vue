<script setup>
import { ref, computed } from "vue";
import { usePage, router } from "@inertiajs/vue3";
import SideBar from "@/Components/SideBar.vue";
import PageHeader from "@/Components/PageHeader.vue";
import InventoryTable from "@/Components/InventoryTable.vue";
import SearchFilterBar from "@/Components/Filters/SearchFilterBar.vue";
import PrimaryButton from "@/Components/Buttons/PrimaryButton.vue";
import SupplierFormModal from "@/Components/Modals/SupplierFormModal.vue";
import ArchiveModal from "@/Components/Modals/ArchiveModal.vue";
import SuccessModal from "@/Components/Modals/SuccessModal.vue";
import SuccessDeleteModal from "@/Components/Modals/SuccessDeleteModal.vue";
import LoadingOverlay from "@/Components/LoadingOverlay.vue";
import NavHeader from "@/Components/NavHeader.vue";
import { useLoading } from "@/Composables/useLoading";
import { usePermissions } from "@/Composables/usePermissions";
import { useSidebar } from "@/Composables/useSidebar";
import SessionTimeoutWarning from "@/Components/SessionTimeoutWarning.vue";
const { isSidebarOpen, toggleSidebar } = useSidebar();

const { supplierActions, canCreateSupplier } = usePermissions();

const { isLoading, loadingTitle, loadingMessage, startLoading, stopLoading } =
    useLoading();

const columns = [
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
                cls =
                    "text-[#D32F2F] font-bold bg-[#F8D4D4] py-2 px-4 rounded-md";
            } else if (status === 1) {
                label = "Active";
                cls =
                    "text-[#2E7D32] font-bold bg-[#D4F8D4] py-2 px-4 rounded-md";
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

//ITEMS FILTER CONTROL
let search = ref("");

let formMode = ref("create"); // CREATE || EDIT || VIEW
let showFormModal = ref(false);
let currentSupplier = ref({});
let showArchiveModal = ref(false);

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
</script>

<template>
    <SessionTimeoutWarning />
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

                <!-- MAIN -->
                <main class="flex-1 sm:p-5 md:p-6 overflow-y-auto m-2">
                    <PageHeader title="Suppliers" />
                    <div class="w-full h-full">
                        <div
                            class="mt-10 flex flex-col md:flex-row gap-4 justify-between"
                        >
                            <PrimaryButton
                                @click="openAdd()"
                                v-if="canCreateSupplier"
                            >
                                <i class="fa-solid fa-user-group"></i>
                                <span>Add Supplier</span>
                            </PrimaryButton>

                            <SearchFilterBar
                                :search="search"
                                @update:search="search = $event"
                                :mode="'suppliers'"
                            />
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
                            message="Supplier archived successfully!"
                            buttonText="Confirm"
                            @close="showDeleteSuccessModal = false"
                        />

                        <InventoryTable
                            :module="'suppliers'"
                            :columns="columns"
                            :rows="suppliers"
                            :actions="supplierActions"
                            @edit="handleEdit"
                            @delete="handleDelete"
                        />
                    </div>
                </main>
            </div>
        </div>
    </div>
</template>
