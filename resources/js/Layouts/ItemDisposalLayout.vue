<script setup>
import { ref, computed, watch } from "vue";
import { usePage, router } from "@inertiajs/vue3";
import SideBar from "@/Components/SideBar.vue";
import PageHeader from "@/Components/PageHeader.vue";
import InventoryArchiveTable from "@/Components/InventoryArchiveTable.vue";
import RestoreModal from "@/Components/Modals/RestoreModal.vue";
import ForceDeleteModal from "@/Components/Modals/ForceDeleteModal.vue";
import NavHeader from "@/Components/NavHeader.vue";
import Toast from "primevue/toast";
import { useToast } from "primevue/usetoast";
import LoadingOverlay from "@/Components/LoadingOverlay.vue";
import { useLoading } from "@/Composables/useLoading";
import { usePermissions } from "@/Composables/usePermissions";
import { useSidebar } from "@/Composables/useSidebar";
const { isSidebarOpen, toggleSidebar } = useSidebar();

const { isLoading, loadingTitle, loadingMessage, startLoading, stopLoading } =
    useLoading();

const { archiveItemActions } = usePermissions();

const columns = [
    { label: "Item Name", key: "item_name" },
    { label: "Unit", key: "unit", format: (val) => val ?? "N/A" },
    {
        label: "Unit Cost",
        key: "unit_cost",
        format: (val) => (val ? `₱${val}` : "N/A"),
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

const page = usePage();
const items = computed(() => page.props.items || { data: [] });
const toast = useToast();

const search = ref(page.props.filters?.search ?? "");
const status = ref(page.props.filters?.status ?? null);

let debounceTimer = null;

watch(search, (value) => {
    clearTimeout(debounceTimer);
    debounceTimer = setTimeout(() => {
        router.get(
            route("items.archive.index"),
            { search: value, status: status.value ?? "" },
            {
                preserveState: true,
                preserveScroll: true,
                replace: true,
                only: ["items"],
            },
        );
    }, 300);
});

watch(status, (value) => {
    router.get(
        route("items.archive.index"),
        { search: search.value, status: value ?? "" },
        {
            preserveState: true,
            preserveScroll: true,
            replace: true,
            only: ["items"],
        },
    );
});

// MODAL STATE
const showRestoreModal = ref(false);
const showForceDeleteModal = ref(false);
const currentItem = ref(null);

function handleRestore(item) {
    currentItem.value = item;
    showRestoreModal.value = true;
}

function handleForceDelete(item) {
    currentItem.value = item;
    showForceDeleteModal.value = true;
}

function confirmRestore() {
    router.patch(
        route("items.restore", currentItem.value.id),
        {},
        {
            preserveScroll: true,

            onSuccess: () => {
                stopLoading();
                showRestoreModal.value = false;

                toast.add({
                    severity: "success",
                    summary: "Restored",
                    detail: `${currentItem.value.item_name} has been restored.`,
                    life: 3000,
                });
            },

            onError: (errors) => {
                showRestoreModal.value = false;

                toast.add({
                    severity: "error",
                    summary: "Restore Failed",
                    detail: errors.restore,
                    life: 5000,
                });
            },
        },
    );
}

function confirmForceDelete() {
    router.delete(route("items.forceDelete", currentItem.value.id), {
        preserveScroll: true,
        onSuccess: () => {
            stopLoading();
            showForceDeleteModal.value = false;
            toast.add({
                severity: "success",
                summary: "Permanently Deleted",
                detail: `${currentItem.value.item_name} has been permanently deleted.`,
                life: 3000,
            });
        },
    });
}

// console.log(page.props.auth.permissions);
</script>

<template>
    <Toast />
    <LoadingOverlay
        :show="isLoading"
        :title="loadingTitle"
        :message="loadingMessage"
    />

    <RestoreModal
        v-if="showRestoreModal"
        :item="currentItem"
        @confirm="confirmRestore"
        @close="showRestoreModal = false"
    />

    <ForceDeleteModal
        v-if="showForceDeleteModal"
        :item="currentItem"
        @confirm="confirmForceDelete"
        @close="showForceDeleteModal = false"
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

                <main class="flex-1 sm:p-5 md:p-6 m-2">
                    <PageHeader title="Item Disposal" />
                    <div class="bg-white h-screen drop-shadow-md mt-[1rem]">
                        <!-- Search + Filter -->
                        <div class="p-4 flex items-center justify-end gap-3">
                            <input
                                v-model="search"
                                type="text"
                                placeholder="Search items..."
                                class="w-80 border border-gray-300 rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-[#850038]"
                            />
                            <select
                                v-model="status"
                                class="border border-gray-300 rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-[#850038]"
                            >
                                <option :value="null">All Status</option>
                                <option :value="1">Serviceable</option>
                                <option :value="0">Unserviceable</option>
                            </select>
                        </div>

                        <div
                            v-if="!items.data?.length"
                            class="text-center py-10 text-gray-500"
                        >
                            No archived items yet.
                        </div>

                        <InventoryArchiveTable
                            :rows="items"
                            :columns="columns"
                            :module="'archive_item'"
                            :actions="archiveItemActions"
                            @restore="handleRestore"
                            @permanent-delete="handleForceDelete"
                        />
                    </div>
                </main>
            </div>
        </div>
    </div>
</template>
