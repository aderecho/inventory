<script setup>
import { ref, computed } from 'vue';
import { usePage, router } from '@inertiajs/vue3';

import NavHeader from '@/Components/NavHeader.vue';
import SideBar from '@/Components/SideBar.vue';
import PageHeader from '@/Components/PageHeader.vue';
import InventoryTable from '@/Components/InventoryTable.vue';
import AcknowledgementFormModal from '@/Components/Modals/AcknowledgementFormModal.vue';
import PrimaryButton from '@/Components/Buttons/PrimaryButton.vue';
import ItemFilterControls from '@/Components/Filters/ItemFilterControls.vue';
import SuccessModal from '@/Components/Modals/SuccessModal.vue';

const page = usePage();

const items = computed(() => page.props.items ?? []);
const accPerson = computed(() => page.props.accPerson ?? []);
const users = computed(() => page.props.users ?? []);

const userProfiles = computed(() => {
  return (page.props.userProfiles ?? []).map(u => ({
    ...u,
    full_name: `${u.last_name}, ${u.first_name}`.trim(),
  }));
});

const columns = [
  { label: "", key: "select_all" },
  { label: "Item Name", key: "item_name" },
  { label: "Unit", key: "unit" },
  { label: "Unit Cost", key: "unit_cost", format: (val) => val ? `₱${val}` : 'N/A' },
  { label: "Property Number", key: "property_number" },
  { label: "Serial Number", key: 'serial_number' },
  { label: "Invoice", key: 'invoice' },
  { label: "Supplier Name", key: "supplier", format: (val) => val?.supplier_name ?? 'N/A' },
  {
    label: "Status",
    key: 'status',
    format: (status) => {
      let label = 'Unknown', cls = 'text-gray-500', icon = '';
      if (status === 1) {
        label = 'Unassigned';
        cls = 'text-[#D32F2F] font-bold bg-[#F8D4D4] py-2 px-4 rounded-full';
        icon = '<i class="fa-solid fa-ban"></i>';
      }
      return `<span class="${cls}">${icon} ${label}</span>`;
    }
  },
  { label: "Action", key: "action" }
];

const viewItem = [
  { label: "Item Name", key: "item_name" },
  { label: "Property Number", key: "property_number" },
  { label: "Unit", key: "unit", format: (val) => val ?? "N/A" },
  { label: "Serial Number", key: "serial_number" },
  { label: "Unit Cost", key: "unit_cost", format: (val) => val != null ? `₱${Number(val).toLocaleString()}` : "N/A" },
  { label: "PO Number", key: "po_number" },
  { label: "Supplier", key: "supplier.supplier_name" },
  { label: "PR Number", key: "pr_number" },
  { label: "Description", key: "description" },
  { label: "Invoice", key: "invoice" },
  { label: "Remarks", key: "remarks" },
  { label: "Fund Source", key: "fund_source" },
  { label: "Date Acquired", key: "date_acquired" },
];

const accountableField = [
  {
    label: "Accountable Person",
    model: "accountable_persons_id",
    name: "accPerson",
    option: "full_name",
    value: "id"
  },
  {
    label: "Issued By",
    model: "issued_by_id",
    name: "userProfiles",
    option: "full_name",
    value: "id"
  },
];

const inputFields = [
  { label: "Rooms", model: "item_name", placeholder: "Room 000", type: "text" },
];

const itemSelectedField = [
  { label: "Item Selected", model: "item_name" },
];

const search = ref('');
const status = ref(null);
const cost_range = ref(null);

const formMode = ref('create');
const showFormModal = ref(false);
const showSuccessModal = ref(false);

const selectedIds = ref([]);
const currentItem = ref(null);

function openAdd() {
  formMode.value = 'create';
  showFormModal.value = true;
}

function handleView(item) {
  currentItem.value = item;
  formMode.value = 'view';
  showFormModal.value = true;
}

function handleSubmit() {
  showSuccessModal.value = true;
  showFormModal.value = false;
}

function handleAction() {
  console.log('Action clicked');
}

const isSidebarOpen = ref(true);
const toggleSidebar = () => {
  isSidebarOpen.value = !isSidebarOpen.value;
};
</script>


<template>
  <div class="h-screen flex flex-col bg-gray-100 overflow-hidden">
    <!-- Pass toggle event -->
    <NavHeader class="flex-shrink-0" @toggleSidebar="toggleSidebar" />

    <div class="flex flex-1 overflow-hidden">
      <!-- Sidebar -->
      <aside class="transition-all duration-600 ease-in-out transform"
        :class="isSidebarOpen ? 'translate-x-0 opacity-100' : '-translate-x-full opacity-0 w-0'">
        <SideBar />
      </aside>

      <!-- Main -->
      <main class="flex-1 sm:p-5 md:p-6 overflow-y-auto">
        <!-- HEAD TITLE -->
        <div class="m-2">
          <PageHeader title="Acknowledgements" />
          <div class="w-full h-full">
            <div class="flex flex-col md:flex-row justify-between mt-12">
              <PrimaryButton @click="openAdd">
                <i class="fa-solid fa-user-plus"></i>
                <span> Assign </span>
              </PrimaryButton>

              <ItemFilterControls :search="search" :status="status" @update:search="search = $event"
                @update:status="status = $event" :mode="'acknowledgements'" />

            </div>
            <AcknowledgementFormModal v-if="showFormModal" :mode="formMode" :accountableField="accountableField"
              :inputFields="inputFields" :accPerson="accPerson" :users="users" :userProfiles="userProfiles"
              :itemSelectedField="itemSelectedField" :selectedIDs="selectedIds.value" :items="items" :item="currentItem"
              :viewItem="viewItem" @submit="handleSubmit" @close="() => showFormModal = false" />

            <SuccessModal v-if="showSuccessModal" :icon="successIcon"
              :title="formMode === 'edit' ? 'Edit Success' : 'Added Success'"
              :message="formMode === 'edit' ? 'Edit successfully!' : 'Assign successfully!'" @action="handleAction"
              @close="showSuccessModal = false" />

            <InventoryTable :rows="items" :columns="columns" :actions="['view']" @view="handleView"
              @update:selected="ids => selectedIds.value = ids"
              @selection-changed="ids => console.log('Item Selected', ids)" />
          </div>
        </div>
      </main>
    </div>
  </div>
</template>