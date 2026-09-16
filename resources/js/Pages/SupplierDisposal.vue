<script setup>
import { ref, computed, watch } from "vue";
import { usePage, router } from "@inertiajs/vue3";
import { Head } from "@inertiajs/vue3";
import Layout from "@/Layouts/Layout.vue";
import PageHeader from "@/Components/PageHeader.vue";
import InventoryArchiveTable from "@/Components/InventoryArchiveTable.vue";
import RestoreModal from "@/Components/Modals/RestoreModal.vue";
import ForceDeleteModal from "@/Components/Modals/ForceDeleteModal.vue";
import Toast from "primevue/toast";
import { useToast } from "primevue/usetoast";
import { useLoading } from "@/Composables/useLoading";
import { usePermissions } from "@/Composables/usePermissions";

const { stopLoading } = useLoading();
const { archiveSupplierActions } = usePermissions();

const columns = [
    { label: "Supplier Name", key: "supplier_name" },
    { label: "Contact No.", key: "contact_no" },
    { label: "Email", key: "email" },
    { label: "Address", key: "address" },
    { label: "Action", key: "action" },
];

const page = usePage();
const suppliers = computed(() => page.props.suppliers || { data: [] });
const toast = useToast();

const search = ref(page.props.search ?? "");

let debounceTimer = null;

watch(search, (value) => {
    clearTimeout(debounceTimer);

    debounceTimer = setTimeout(() => {
        router.get(
            route("suppliers.archive.index"),
            { search: value },
            {
                preserveState: true,
                preserveScroll: true,
                replace: true,
                only: ["suppliers"],
            },
        );
    }, 300);
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
        route("suppliers.restore", currentItem.value.id),
        {},
        {
            preserveScroll: true,
            onSuccess: () => {
                stopLoading();
                showRestoreModal.value = false;

                toast.add({
                    severity: "success",
                    summary: "Restored",
                    detail: `${currentItem.value.supplier_name} has been restored.`,
                    life: 3000,
                });
            },
        },
    );
}

function confirmForceDelete() {
    router.delete(route("suppliers.forceDelete", currentItem.value.id), {
        preserveScroll: true,
        onSuccess: () => {
            stopLoading();
            showForceDeleteModal.value = false;

            toast.add({
                severity: "success",
                summary: "Permanently Deleted",
                detail: `${currentItem.value.supplier_name} has been permanently deleted.`,
                life: 3000,
            });
        },
    });
}
</script>

<template>
    <Head title="UP | Item Disposal" />

    <Layout>
        <Toast />

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

        <PageHeader title="Supplier Archive" />
        <div class="bg-white h-screen drop-shadow-md mt-[1rem]">
            <!-- Search + Filter -->
            <div class="p-4 flex items-center justify-end gap-3">
                <input
                    v-model="search"
                    type="text"
                    placeholder="Search suppliers..."
                    class="w-80 border border-gray-300 rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-[#850038]"
                />
            </div>

            <div v-if="!suppliers.data?.length" class="text-center py-10 text-gray-500">
                No archived suppliers yet.
            </div>

            <InventoryArchiveTable
                :rows="suppliers"
                :columns="columns"
                :module="'archive_supplier'"
                :actions="archiveSupplierActions"
                @restore="handleRestore"
                @permanent-delete="handleForceDelete"
            />
        </div>
    </Layout>
</template>