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
    { label: "Classification Code", key: "classification_code" },
    { label: "Classification Name", key: "classification_name" },
    { label: "Action", key: "action" },
];

const page = usePage();
const categories = computed(() => page.props.categories || { data: [] });
const toast = useToast();

const search = ref(page.props.search ?? "");

let debounceTimer = null;

watch(search, (value) => {
    clearTimeout(debounceTimer);

    debounceTimer = setTimeout(() => {
        router.get(
            route("categories.archive.index"),
            { search: value },
            {
                preserveState: true,
                preserveScroll: true,
                replace: true,
                only: ["categories"],
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
        route("categories.restore", currentItem.value.id),
        {},
        {
            preserveScroll: true,
            onSuccess: () => {
                stopLoading();
                showRestoreModal.value = false;

                toast.add({
                    severity: "success",
                    summary: "Restored",
                    detail: `${currentItem.value.classification_name} has been restored.`,
                    life: 3000,
                });
            },
        },
    );
}

function confirmForceDelete() {
    router.delete(route("categories.forceDelete", currentItem.value.id), {
        preserveScroll: true,
        onSuccess: () => {
            stopLoading();
            showForceDeleteModal.value = false;

            toast.add({
                severity: "success",
                summary: "Permanently Deleted",
                detail: `${currentItem.value.classification_name} has been permanently deleted.`,
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

        <PageHeader title="Classification Archive" />
        <div class="bg-white h-screen drop-shadow-md mt-[1rem]">
            <!-- Search + Filter -->
            <div class="p-4 flex items-center justify-end gap-3">
                <input
                    v-model="search"
                    type="text"
                    placeholder="Search classification name..."
                    class="w-80 border border-gray-300 rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-[#850038]"
                />
            </div>

            <div v-if="!categories.data?.length" class="text-center py-10 text-gray-500">
                No archived categories yet.
            </div>

            <InventoryArchiveTable
                :rows="categories"
                :columns="columns"
                :module="'archive_categories'"
                :actions="archiveSupplierActions"
                @restore="handleRestore"
                @permanent-delete="handleForceDelete"
            />
        </div>
    </Layout>
</template>