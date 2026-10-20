<script setup>
import { ref, computed } from "vue";
import { Link, usePage, router } from "@inertiajs/vue3";
import { Head } from "@inertiajs/vue3";
import Layout from "@/Layouts/Layout.vue";
import PageHeader from "@/Components/PageHeader.vue";
import InventoryTable from "@/Components/InventoryTable.vue";
import SearchFilterBar from "@/Components/Filters/SearchFilterBar.vue";
import PrimaryButton from "@/Components/Buttons/PrimaryButton.vue";
import UserFormModal from "@/Components/Modals/UserFormModal.vue";
import ArchiveModal from "@/Components/Modals/ArchiveModal.vue";
import { useLoading } from "@/Composables/useLoading";
import { usePermissions } from "@/Composables/usePermissions";
import { useToast } from "primevue/usetoast";

const { stopLoading } = useLoading();
const toast = useToast();

const page = usePage();

const users = computed(() => page.props.users || {});
const roles = computed(() => page.props.roles || []);
const permissions = computed(() => page.props.permissions || []);
const { roleActions, permissionActions, userActions } = usePermissions();

const canViewUsers = computed(() => userActions.value.includes("view"));
const canViewRoles = computed(() => roleActions.value.includes("view"));
const canViewPermissions = computed(() =>
    permissionActions.value.includes("view"),
);

const status = ref(null);
const search = ref("");

const columns = [
    { label: "Employee Number", key: "user_profiles.employee_number" },
    { label: "Email", key: "email" },
    { label: "Full Name", key: "user_profiles.full_name" },
    { label: "Extension", key: "user_profiles.ext_name" },
    {
        label: "Department",
        key: "user_profiles.primary_unit_division_department",
    },
    { label: "Contact Number", key: "user_profiles.contact_number" },
    {
        label: "Status",
        key: "status",
        format: (status) => {
            let label = "Unknown";
            let cls = "text-gray-500";

            if (status === 0) {
                label = "Inactive";
                cls =
                    "text-[#D32F2F] font-bold bg-[#F8D4D4] py-2 px-4 rounded-md";
            }

            if (status === 1) {
                label = "Active";
                cls =
                    "text-[#2E7D32] font-bold bg-[#D4F8D4] py-2 px-4 rounded-md";
            }

            return `<span class="${cls}">${label}</span>`;
        },
    },
    { label: "Action", key: "action" },
];

const formMode = ref("create");
const showFormModal = ref(false);
const currentUser = ref(null);

const showArchiveModal = ref(false);

function openAdd() {
    formMode.value = "create";
    currentUser.value = null;
    showFormModal.value = true;
}

function handleSubmit() {
    stopLoading();

    toast.add({
        severity: "success",
        summary: formMode.value === "edit" ? "User updated" : "User added",
        detail:
            formMode.value === "edit"
                ? "The user details were saved."
                : "The new user can now sign in.",
        life: 3000,
    });

    showFormModal.value = false;
}

function handleEdit(user) {
    formMode.value = "edit";
    currentUser.value = user;
    showFormModal.value = true;
}

function handleDelete(user) {
    currentUser.value = user;
    showArchiveModal.value = true;
}

function confirmArchive() {
    router.delete(route("user_management.destroy", currentUser.value.id), {
        preserveScroll: true,
        onSuccess: () => {
            stopLoading();
            showArchiveModal.value = false;

            toast.add({
                severity: "success",
                summary: "User archived",
                detail: "The user was moved to the archive.",
                life: 3000,
            });

            currentUser.value = null;
        },
    });
}

const filterStatus = [
    {
        label: "Status",
        options: [
            { label: "Active", value: 1 },
            { label: "Inactive", value: 0 },
        ],
    },
];
</script>

<template>
    <Head title="UP | User Permissions" />

    <Layout>
        <PageHeader title="User Permissions" />

        <div class="w-full h-full">
            <div
                class="mt-10 flex flex-col md:flex-row md:items-end md:justify-between gap-3"
            >
                <div class="flex flex-col sm:flex-row items-stretch gap-3">
                    <PrimaryButton v-if="canViewUsers" @click="openAdd">
                        <i class="fa-solid fa-user-group"></i>
                        <span>Add User</span>
                    </PrimaryButton>

                    <Link
                        v-if="canViewRoles"
                        :href="route('roles.index')"
                        class="flex gap-2 bg-[#0E6021] rounded-md text-white px-3 py-2 text-xs sm:text-sm hover:bg-[#19703a] w-full sm:w-auto items-center mt-2 justify-center"
                    >
                        <i class="fa-solid fa-shield-halved"></i>
                        <span>Manage Roles</span>
                    </Link>

                    <Link
                        v-if="canViewPermissions"
                        :href="route('permissions.index')"
                        class="flex gap-2 bg-[#0E6021] rounded-md text-white px-3 py-2 text-xs sm:text-sm hover:bg-[#19703a] w-full sm:w-auto items-center mt-2 justify-center"
                    >
                        <i class="fa-solid fa-key"></i>
                        <span>Manage Permissions</span>
                    </Link>
                </div>

                <div
                    class="flex flex-col sm:flex-row sm:flex-wrap sm:items-end gap-3"
                >
                    <SearchFilterBar
                        :search="search"
                        :status="status"
                        :filterStatus="filterStatus"
                        @update:search="search = $event"
                        @update:status="status = $event"
                        mode="users"
                    />
                </div>
            </div>

            <UserFormModal
                v-if="showFormModal"
                :mode="formMode"
                :user="currentUser"
                :roles="roles"
                :permissions="permissions"
                @submit="handleSubmit"
                @close="showFormModal = false"
            />

            <ArchiveModal
                v-if="showArchiveModal"
                :item="currentUser"
                @confirm="confirmArchive"
                @close="showArchiveModal = false"
            />

            <InventoryTable
                :columns="columns"
                :module="'users'"
                :rows="users"
                :actions="['edit', 'delete']"
                @edit="handleEdit"
                @delete="handleDelete"
            />
        </div>
    </Layout>
</template>
