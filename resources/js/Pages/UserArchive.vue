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
const { archiveUserActions } = usePermissions();

const columns = [
    { label: "Email", key: "email" },
    { label: "First Name", key: "userProfiles.first_name" },
    { label: "Last Name", key: "userProfiles.last_name" },
    { label: "Status", key: "status" },
    { label: "Action", key: "action" },
];

const page = usePage();
const users = computed(() => page.props.users || { data: [] });
const toast = useToast();

const search = ref(page.props.search ?? "");

let debounceTimer = null;

watch(search, (value) => {
    clearTimeout(debounceTimer);

    debounceTimer = setTimeout(() => {
        router.get(
            route("users.archive.index"),
            { search: value },
            {
                preserveState: true,
                preserveScroll: true,
                replace: true,
                only: ["users"],
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
        route("user_management.restore", currentItem.value.id),
        {},
        {
            preserveScroll: true,
            onSuccess: () => {
                stopLoading();
                showRestoreModal.value = false;

                toast.add({
                    severity: "success",
                    summary: "Restored",
                    detail: `${currentItem.value.email} has been restored.`,
                    life: 3000,
                });
            },
        },
    );
}

function confirmForceDelete() {
    router.delete(route("user_management.forceDelete", currentItem.value.id), {
        preserveScroll: true,
        onSuccess: () => {
            stopLoading();
            showForceDeleteModal.value = false;

            toast.add({
                severity: "success",
                summary: "Permanently Deleted",
                detail: `${currentItem.value.email} has been permanently deleted.`,
                life: 3000,
            });
        },
    });
}
</script>

<template>
    <Head title="UP | User Disposal" />

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

        <PageHeader title="User Archive" />
        <div class="bg-white h-screen drop-shadow-md mt-[1rem]">
            <!-- Search + Filter -->
            <div class="p-4 flex items-center justify-end gap-3">
                <input
                    v-model="search"
                    type="text"
                    placeholder="Search user..."
                    class="w-80 border border-gray-300 rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-[#850038]"
                />
            </div>

            <div v-if="!users.data?.length" class="text-center py-10 text-gray-500">
                No archived users yet.
            </div>

            <InventoryArchiveTable
                :rows="users"
                :columns="columns"
                :module="'archive_users'"
                :actions="archiveUserActions"
                @restore="handleRestore"
                @permanent-delete="handleForceDelete"
            />
        </div>
    </Layout>
</template>