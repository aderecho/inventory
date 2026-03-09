<script setup>
import { ref, computed, watch } from "vue";
import { usePage, router } from "@inertiajs/vue3";

import NavHeader from "@/Components/NavHeader.vue";
import SideBar from "@/Components/SideBar.vue";
import PageHeader from "@/Components/PageHeader.vue";
import InventoryTable from "@/Components/InventoryTable.vue";
import ItemFilterControls from "@/Components/Filters/ItemFilterControls.vue";

import PrimaryButton from "@/Components/Buttons/PrimaryButton.vue";
import UserFormModal from "@/Components/Modals/UserFormModal.vue";
import DeleteModal from "@/Components/Modals/DeleteModal.vue";
import SuccessModal from "@/Components/Modals/SuccessModal.vue";
import SuccessDeleteModal from "@/Components/Modals/SuccessDeleteModal.vue";
import RoleFormModal from '@/Components/Modals/RoleFormModal.vue';
import PermissionFormModal from '@/Components/Modals/PermissionFormModal.vue';

const page = usePage();

const users = computed(() => page.props.users || {});
const roles = computed(() => page.props.roles || []);
const permissions = computed(() => page.props.permissions || []);
const showRoleModal = ref(false);
const showPermissionModal = ref(false);

const columns = [
  { label: "Email", key: "email" },
  { label: "First Name", key: "user_profiles.first_name" },
  { label: "Last Name", key: "user_profiles.last_name" },
  { label: "Middle Name", key: "user_profiles.middle_name" },
  { label: "Contact Number", key: "user_profiles.contact_number" },
  {
    label: "Status",
    key: "status",
    format: (status) => {
      let label = "Unknown";
      let cls = "text-gray-500";
      let icon = "";

      if (status === 0) {
        label = "Inactive";
        cls = "text-[#D32F2F] font-bold bg-[#F8D4D4] py-2 px-4 rounded-full";
        icon = '<i class="fa-solid fa-ban"></i>';
      }

      if (status === 1) {
        label = "Active";
        cls = "text-[#2E7D32] font-bold bg-[#D4F8D4] py-2 px-4 rounded-full";
        icon = '<i class="fa-solid fa-circle-check"></i>';
      }

      return `<span class="${cls}">${icon} ${label}</span>`;
    },
  },
  { label: "Action", key: "action" },
];

const search = ref("");
const status = ref(null);

watch([search, status], ([searchVal, statusVal]) => {
  router.get(
    route("user_management.index"),
    {
      search: searchVal || undefined,
      status: statusVal ?? undefined,
    },
    {
      preserveState: true,
      preserveScroll: true,
      replace: true,
    }
  );
});

const formMode = ref("create");
const showFormModal = ref(false);
const currentUser = ref(null);

const showDeleteModal = ref(false);
const showSuccessModal = ref(false);
const showDeleteSuccessModal = ref(false);

const successMessage = ref("");

function openAdd() {
  formMode.value = "create";
  currentUser.value = null;
  showFormModal.value = true;
}

function handleSubmit() {
  showSuccessModal.value = true;
  successMessage.value =
    formMode.value === "edit"
      ? "User updated successfully!"
      : "User added successfully!";

  showFormModal.value = false;
}

function handleEdit(user) {
  formMode.value = "edit";
  currentUser.value = user;
  showFormModal.value = true;
}

function handleDelete(user) {
  currentUser.value = user;
  showDeleteModal.value = true;
}

function confirmDelete() {
  router.delete(route("user_management.destroy", currentUser.value.id), {
    preserveScroll: true,
    onSuccess: () => {
      showDeleteModal.value = false;
      showDeleteSuccessModal.value = true;
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

const isSidebarOpen = ref(true);

const toggleSidebar = () => {
  isSidebarOpen.value = !isSidebarOpen.value;
};

</script>

<template>
  <div class="h-screen flex flex-col bg-gray-100 overflow-hidden">
    <NavHeader class="flex-shrink-0" @toggleSidebar="toggleSidebar" />

    <div class="flex flex-1 overflow-hidden">

      <aside class="transition-all duration-600 ease-in-out transform"
        :class="isSidebarOpen ? 'translate-x-0 opacity-100' : '-translate-x-full opacity-0 w-0'">
        <SideBar />
      </aside>

      <main class="flex-1 sm:p-5 md:p-6 overflow-y-auto m-2">

        <PageHeader title="User Permissions" />

        <div class="w-full h-full">

          <div class="mt-10 flex flex-col md:flex-row gap-4 justify-between">

            <div class="flex gap-3">
              <PrimaryButton @click="openAdd">
                <i class="fa-solid fa-user-group"></i>
                <span>Add User</span>
              </PrimaryButton>

              <button @click="showRoleModal = true"
                class="flex gap-2 bg-[#0E6021] rounded-md text-white px-3 py-2 text-xs sm:text-sm hover:bg-[#19703a] w-full sm:w-auto items-center mt-2 justify-center">
                <i class="fa-solid fa-shield-halved"></i>
                <span>Manage Roles</span>
              </button>


              <button @click="showPermissionModal = true"
                class="flex gap-2 bg-[#0E6021] rounded-md text-white px-3 py-2 text-xs sm:text-sm hover:bg-[#19703a] w-full sm:w-auto items-center mt-2 justify-center">
                <i class="fa-solid fa-key"></i>
                <span>Manage Permissions</span>
              </button>
            </div>

            <ItemFilterControls :search="search" :status="status" :filterStatus="filterStatus"
              @update:search="search = $event" @update:status="status = $event" mode="users" />
          </div>

          <!-- Modals -->
          <RoleFormModal v-if="showRoleModal" :roles="roles" :permissions="permissions"
            @close="showRoleModal = false" />

          <PermissionFormModal v-if="showPermissionModal" :permissions="permissions"
            @close="showPermissionModal = false" />

          <UserFormModal v-if="showFormModal" :mode="formMode" :user="currentUser" :roles="roles"
            :permissions="permissions" @submit="handleSubmit" @close="showFormModal = false" />

          <DeleteModal v-if="showDeleteModal" :item="currentUser" @confirm="confirmDelete"
            @close="showDeleteModal = false" />

          <SuccessModal v-if="showSuccessModal" title="Success" :message="successMessage"
            @close="showSuccessModal = false" />

          <SuccessDeleteModal v-if="showDeleteSuccessModal" title="Delete Success" message="User deleted successfully!"
            buttonText="Confirm" @close="showDeleteSuccessModal = false" />

          <InventoryTable :columns="columns" :rows="users" :actions="['edit', 'delete']" @edit="handleEdit"
            @delete="handleDelete" />

        </div>

      </main>
    </div>
  </div>
</template>