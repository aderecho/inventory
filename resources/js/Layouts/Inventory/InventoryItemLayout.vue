<script setup>
import { ref, computed } from "vue";
import { usePage, router } from "@inertiajs/vue3";
import NavHeader from "@/Components/NavHeader.vue";
import SideBar from "@/Components/SideBar.vue";
import InventoryTable from "@/Components/InventoryTable.vue";
import PageHeader from "@/Components/PageHeader.vue";
import PrimaryButton from "@/Components/Buttons/PrimaryButton.vue";
import InventoryFormModal from "@/Components/Modals/InventoryFormModal.vue";
import ItemFilterControls from "@/Components/Filters/ItemFilterControls.vue";
import ImportButton from "@/Components/Buttons/ImportButton.vue";
import ExportButton from "@/Components/Buttons/ExportButton.vue";
import ConvertButton from "@/Components/Buttons/ConvertButton.vue";
import DeleteModal from "@/Components/Modals/DeleteModal.vue";
import SuccessModal from "@/Components/Modals/SuccessModal.vue";
import SuccessDeleteModal from "@/Components/Modals/SuccessDeleteModal.vue";

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
      let label = "Unknown",
        cls = "text-gray-500",
        icon = "";
      if (status === 0) {
        label = "Cancelled";
        cls = "text-[#D32F2F] font-bold bg-[#F8D4D4] py-2 px-4 rounded-full";
        icon = '<i class="fa-solid fa-ban"></i>';
      } else if (status === 1) {
        label = "Received";
        cls = "text-[#2E7D32] font-bold bg-[#D4F8D4] py-2 px-4 rounded-full";
        icon = '<i class="fa-solid fa-circle-check"></i>';
      }
      return `<span class="${cls}">${icon} ${label}</span>`;
    },
  },
  { label: "Action", key: "action" },
];

const viewItem = [
  { label: "Item Name", key: "item_name" },
  { label: "Property Number", key: "property_number" },
  { label: "Unit", key: "unit", format: (val) => val ?? "N/A" },
  { label: "Serial Number", key: "serial_number" },
  {
    label: "Unit Cost",
    key: "unit_cost",
    format: (val) =>
      val !== null && val !== undefined
        ? `₱${Number(val).toLocaleString()}`
        : "N/A",
  },
  { label: "PO Number", key: "po_number" },
  { label: "Supplier", key: "supplier.supplier_name" },
  { label: "PR Number", key: "pr_number" },
  { label: "Description", key: "description" },
  { label: "Invoice", key: "invoice" },
  { label: "Remarks", key: "remarks" },
  { label: "Fund Source", key: "fund_source" },
  { label: "Date Acquired", key: "date_acquired" },
  {
    label: "Status",
    key: "status",
    format: (status) => {
      let label = "Unknown",
        cls = "text-gray-500",
        icon = "";
      if (status === 0) {
        label = "Inactive";
        cls = "text-[#D32F2F] font-bold bg-[#F8D4D4] py-2 px-4 rounded-full";
        icon = '<i class="fa-solid fa-ban"></i>';
      } else if (status === 1) {
        label = "Active";
        cls = "text-[#2E7D32] font-bold bg-[#D4F8D4] py-2 px-4 rounded-full";
        icon = '<i class="fa-solid fa-circle-check"></i>';
      } else if (status === 2) {
        label = "Pending";
        cls = "text-[#8D6E00] font-bold bg-[#FFF3CD] py-2 px-4 rounded-full";
        icon = '<i class="fa-solid fa-clock"></i>';
      }
      return `<span class="${cls}">${icon} ${label}</span>`;
    },
  },
];

const quantityCostFields = [
  { label: "Quantity", model: "quantity", placeholder: "0", type: "number" },
  { label: "Unit Cost", model: "unit_cost", placeholder: "0", type: "number" },
];

const inputFields = [
  {
    label: "Item Name",
    model: "item_name",
    placeholder: "Laptops, Ceiling Fan...",
    type: "text",
  },
];

const inputFieldsEdit = [
  {
    label: "Serial Number",
    model: "serial_number",
    placeholder: "SER-####.",
    type: "text",
    readonly: false,
  },
  {
    label: "Item Name",
    model: "item_name",
    placeholder: "Laptops, Ceiling Fan...",
    type: "text",
    readonly: false,
  },
];

const supplierOptions = [
  {
    label: "Supplier",
    model: "supplier_id",
    name: "suppliers",
    option: "supplier_name",
    value: "id",
  },
];

const requestFields = [
  {
    label: "Purchase Request",
    model: "pr_number",
    placeholder: "PR-###",
    type: "text",
  },
  {
    label: "Purchase Order",
    model: "po_number",
    placeholder: "PO-###",
    type: "text",
  },
  { label: "Remarks", model: "remarks", placeholder: "RM-###", type: "text" },
];

const invoicesFundFields = [
  {
    label: "Invoice Number",
    model: "invoice",
    placeholder: "0000",
    type: "text",
    readonly: false,
  },
  {
    label: "Fund Source",
    model: "fund_source",
    placeholder: "000",
    type: "text",
    readonly: false,
  },
];

const firstDropdown = [
  {
    label: "Classifications",
    model: "item_classification_id",
    name: "itemClass",
    option: "classification_name",
    value: "id",
  },
];

const firstInputField = [
  {
    label: "Property Number",
    model: "property_number",
    placeholder: "PROP-####.",
    type: "text",
  },
];

const secondDropdown = [
  {
    label: "Unit",
    model: "unit",
    options: [
      { label: "unit", value: "unit" },
      { label: "pcs", value: "pcs" },
      { label: "box", value: "box" },
    ],
  },
  {
    label: "Status",
    model: "status",
    options: [
      { label: "Received", value: "1" },
      { label: "Cancelled", value: "0" },
    ],
  },
];

const totalCost = [{ label: "Total Cost" }];

const unitCostOptions = [
  {
    label: "Unit Cost",
    options: [
      { label: "Select All", value: "" },
      { label: "₱50,000 Below", value: "0-50000" },
      { label: "₱50,000 Above", value: "50000-99999999" },
    ],
  },
];

const filterStatus = [
  {
    label: "Status",
    options: [
      { label: "Received", value: 1 },
      { label: "Cancelled", value: 0 },
    ],
  },
];

const page = usePage();
const items = computed(() => page.props.items || []);
const itemClassifications = computed(
  () => page.props.itemClassifications || []
);
const suppliers = computed(() => page.props.suppliers || []);
// const rooms = computed(() => page.props.rooms?.rooms || []);

//INVENTORY FILTER
let search = ref("");
let status = ref(null);
let cost_range = ref(null);

// MODAL FUNCTION
let formMode = ref("create"); // CREATE || EDIT || VIEW || DELETE
let showFormModal = ref(false);
let showDeleteModal = ref(false);
let currentItem = ref({});

const showSuccessModal = ref(false);
const successMessage = ref("");
const showDeleteSuccessModal = ref(false);

function openAdd(item) {
  formMode.value = "create";
  currentItem.value = item;
  showFormModal.value = true;
}

function handleView(item) {
  formMode.value = "view";
  currentItem.value = item;
  showFormModal.value = true;
}

function handleEdit(item) {
  formMode.value = "edit";
  currentItem.value = item;
  showFormModal.value = true;
}

function handleDelete(item) {
  currentItem.value = item;
  showDeleteModal.value = true;
}

function handleSubmit() {
  showSuccessModal.value = true;
  successMessage.value =
    formMode.value === "edit"
      ? "Item updated successfully!"
      : "Item added successfully!";

  showFormModal.value = false;
}

function confirmDelete(item) {
  router.delete(route("items.destroy", item.id), {
    preserveScroll: true,
    onSuccess: () => {
      showDeleteModal.value = false;
      showDeleteSuccessModal.value = true;
    },
  });
}

function handleAction() {
  router.visit("/inventory/acknowledgements");
}

//-----------DYNAMIC ICON-------------------
const iconAdded = `
  <svg class="w-16 h-16" viewBox="0 0 50 50" fill="none">
    <rect width="50" height="50" rx="25" fill="#C8EFD4"/>
    <path d="M36.25 19.75H13.75V14.75H36.25V19.75ZM26.25 34.75C26.25 35.625 26.4125 36.4625 26.6875 37.25H15V21H35V27.3625C34.5875 27.3 34.175 27.25 33.75 27.25C29.6125 27.25 26.25 30.6125 26.25 34.75ZM28.75 27.25V25.375C28.75 25.025 28.475 24.75 28.125 24.75H21.875C21.525 24.75 21.25 25.025 21.25 25.375V27.25H28.75ZM35 33.5V29.75H32.5V33.5H28.75V36H32.5V39.75H35V36H38.75V33.5H35Z" fill="#41BD66"/>
  </svg>`;

const iconEdit = `
  <svg class="w-16 h-16" viewBox="0 0 50 50" fill="none">
    <rect width="50" height="50" rx="25" fill="#C8EFD4"/>
    <path d="M38.2962 23.1706C38.2896 22.7431 38.2812 22.3321 38.2712 21.9375C37.8312 21.9375 37.2756 21.9363 36.6712 21.93C35.2519 21.9156 33.5194 21.875 32.3794 21.7656C30.2962 21.5656 28.6762 19.9056 28.4756 17.8125C28.3663 16.6719 28.3287 14.9531 28.3169 13.5481C28.3119 12.9469 28.3119 12.395 28.3131 11.9594C27.6298 11.9452 26.9004 11.9379 26.125 11.9375C22.1781 11.9375 19.4537 12.1394 17.7387 12.3356C15.9294 12.5419 14.555 13.9175 14.3556 15.73C14.1519 17.5763 13.9375 20.6438 13.9375 25.375C13.9375 30.1063 14.1519 33.1737 14.3556 35.02C14.5556 36.8325 15.9294 38.2075 17.7387 38.4144C19.3337 38.5969 21.8019 38.7844 25.3144 38.8094V38.7375C25.3162 37.3456 25.3981 36.3119 25.4875 35.5969C25.6312 34.45 26.2062 33.5094 26.8937 32.8219L35.3825 24.3325C36.0588 23.6562 37.1306 23.0925 38.2962 23.1706ZM25.14 17.2606C25.3045 17.0244 25.3692 16.7329 25.3202 16.4493C25.2712 16.1657 25.1123 15.9128 24.8781 15.7455C24.6439 15.5783 24.3532 15.5101 24.069 15.5557C23.7848 15.6013 23.5301 15.7572 23.36 15.9894L20.985 19.3138L19.3981 17.7263C19.2972 17.6218 19.1765 17.5385 19.043 17.4812C18.9096 17.4239 18.7661 17.3938 18.6208 17.3925C18.4756 17.3913 18.3316 17.419 18.1972 17.474C18.0628 17.5291 17.9407 17.6103 17.838 17.713C17.7353 17.8157 17.6541 17.9379 17.5992 18.0723C17.5442 18.2067 17.5166 18.3507 17.5179 18.496C17.5191 18.6412 17.5493 18.7847 17.6067 18.9181C17.664 19.0516 17.7474 19.1723 17.8519 19.2731L20.3519 21.7731C20.4642 21.8854 20.5996 21.9719 20.7487 22.0265C20.8978 22.0812 21.057 22.1027 21.2153 22.0897C21.3736 22.0766 21.5271 22.0292 21.6652 21.9508C21.8033 21.8724 21.9227 21.7648 22.015 21.6356L25.14 17.2606ZM24.8856 22.61C25.1217 22.7786 25.281 23.034 25.3287 23.3201C25.3765 23.6062 25.3086 23.8996 25.14 24.1356L22.015 28.5106C21.9227 28.6398 21.8033 28.7474 21.6652 28.8258C21.5271 28.9042 21.3736 28.9516 21.2153 28.9647C21.057 28.9777 20.8978 28.9562 20.7487 28.9015C20.5996 28.8469 20.4642 28.7604 20.3519 28.6481L17.8519 26.1481C17.7459 26.0476 17.6611 25.9269 17.6025 25.7931C17.5439 25.6592 17.5127 25.515 17.5108 25.369C17.5089 25.2229 17.5362 25.0779 17.5912 24.9426C17.6462 24.8073 17.7278 24.6843 17.8311 24.5811C17.9343 24.4778 18.0573 24.3962 18.1926 24.3412C18.3279 24.2862 18.4729 24.2589 18.619 24.2608C18.765 24.2627 18.9092 24.2939 19.0431 24.3525C19.1769 24.4111 19.2976 24.4959 19.3981 24.6019L20.9856 26.1888L23.36 22.8638C23.5287 22.6278 23.7842 22.4686 24.0703 22.421C24.3564 22.3734 24.6497 22.4414 24.8856 22.61ZM30.1919 13.5325C30.2038 14.9419 30.2419 16.5844 30.3419 17.6337C30.4594 18.8575 31.3944 19.7875 32.5581 19.8994C33.6081 20 35.2662 20.0406 36.69 20.0556C37.1919 20.0606 37.6587 20.0619 38.0531 20.0619C37.6825 19.1912 36.7825 17.5975 34.6581 15.5144C32.6562 13.5519 31.0994 12.6575 30.1875 12.2581C30.1875 12.6319 30.1881 13.0669 30.1919 13.5325ZM41.4669 30.4163C42.0369 29.8463 42.2856 29.0256 41.8838 28.3263C41.6081 27.8463 41.1819 27.25 40.5281 26.5969C39.875 25.9431 39.2781 25.5169 38.7987 25.2406C38.0994 24.8387 37.2787 25.0881 36.7087 25.6588L35.5325 26.8344C36.1937 27.2031 37.0569 27.8031 38.0212 28.7669C39.0413 29.7869 39.6531 30.6931 40.015 31.3669C40.0783 31.4856 40.1344 31.5967 40.1831 31.7L41.4669 30.4163ZM34.535 28.4256C34.4046 28.3551 34.2711 28.2905 34.135 28.2319L28.22 34.1475C27.7587 34.6081 27.4288 35.1825 27.3481 35.8288C27.2688 36.4638 27.1906 37.4212 27.1887 38.7406C27.1885 38.8977 27.2193 39.0533 27.2793 39.1985C27.3393 39.3436 27.4273 39.4755 27.5384 39.5866C27.6495 39.6977 27.7814 39.7857 27.9265 39.8457C28.0717 39.9057 28.2273 39.9365 28.3844 39.9363C29.7037 39.9338 30.6613 39.8562 31.2956 39.7769C31.9425 39.6956 32.5169 39.3662 32.9775 38.905L38.7337 33.1488C38.7054 33.0873 38.6838 33.023 38.6694 32.9569C38.6567 32.9139 38.6426 32.8714 38.6269 32.8294C38.553 32.6314 38.4648 32.439 38.3631 32.2537C38.0881 31.7413 37.5856 30.9831 36.6956 30.0931C35.805 29.2025 35.0469 28.7006 34.535 28.4256Z" fill="#41BD66"/>
  </svg>`;

const iconDelete = `
  <svg class="w-16 h-16" viewBox="0 0 50 50" fill="none" xmlns="http://www.w3.org/2000/svg">
    <rect width="50" height="50" rx="25" fill="#C8EFD4"/>
    <path d="M39.4346 21.7708C38.8917 21.2034 38.3301 20.6188 38.1184 20.1047C37.9226 19.6338 37.9111 18.8533 37.8995 18.0973C37.8779 16.6919 37.8549 15.0992 36.7475 13.9918C35.6402 12.8844 34.0475 12.8614 32.642 12.8398C31.886 12.8283 31.1055 12.8168 30.6346 12.6209C30.122 12.4092 29.5359 11.8476 28.9685 11.3047C27.9749 10.35 26.846 9.26855 25.3426 9.26855C23.8392 9.26855 22.7117 10.35 21.7166 11.3047C21.1492 11.8476 20.5646 12.4092 20.0505 12.6209C19.5825 12.8168 18.7991 12.8283 18.0431 12.8398C16.6377 12.8614 15.045 12.8844 13.9376 13.9918C12.8302 15.0992 12.8144 16.6919 12.7856 18.0973C12.7741 18.8533 12.7626 19.6338 12.5667 20.1047C12.355 20.6173 11.7934 21.2034 11.2505 21.7708C10.2958 22.7644 9.21436 23.8934 9.21436 25.3968C9.21436 26.9002 10.2958 28.0277 11.2505 29.0227C11.7934 29.5901 12.355 30.1748 12.5667 30.6888C12.7626 31.1597 12.7741 31.9402 12.7856 32.6962C12.8072 34.1017 12.8302 35.6944 13.9376 36.8017C15.045 37.9091 16.6377 37.9321 18.0431 37.9537C18.7991 37.9653 19.5796 37.9768 20.0505 38.1726C20.5632 38.3843 21.1492 38.9459 21.7166 39.4888C22.7102 40.4435 23.8392 41.525 25.3426 41.525C26.846 41.525 27.9735 40.4435 28.9685 39.4888C29.5359 38.9459 30.1206 38.3843 30.6346 38.1726C31.1055 37.9768 31.886 37.9653 32.642 37.9537C34.0475 37.9321 35.6402 37.9091 36.7475 36.8017C37.8549 35.6944 37.8779 34.1017 37.8995 32.6962C37.9111 31.9402 37.9226 31.1597 38.1184 30.6888C38.3301 30.1762 38.8917 29.5901 39.4346 29.0227C40.3893 28.0291 41.4708 26.9002 41.4708 25.3968C41.4708 23.8934 40.3893 22.7659 39.4346 21.7708ZM31.9177 22.7558L23.8536 30.8199C23.7466 30.927 23.6195 31.012 23.4797 31.0699C23.3398 31.1279 23.1899 31.1578 23.0385 31.1578C22.8872 31.1578 22.7372 31.1279 22.5974 31.0699C22.4575 31.012 22.3305 30.927 22.2235 30.8199L18.7674 27.3638C18.6604 27.2568 18.5755 27.1297 18.5176 26.9899C18.4597 26.85 18.4298 26.7002 18.4298 26.5488C18.4298 26.3974 18.4597 26.2475 18.5176 26.1077C18.5755 25.9678 18.6604 25.8408 18.7674 25.7337C18.9836 25.5176 19.2768 25.3961 19.5825 25.3961C19.7339 25.3961 19.8838 25.4259 20.0236 25.4839C20.1634 25.5418 20.2905 25.6267 20.3975 25.7337L23.0385 28.3762L30.2876 21.1257C30.3946 21.0186 30.5217 20.9337 30.6616 20.8758C30.8014 20.8179 30.9513 20.7881 31.1027 20.7881C31.254 20.7881 31.4039 20.8179 31.5438 20.8758C31.6836 20.9337 31.8107 21.0186 31.9177 21.1257C32.0247 21.2327 32.1096 21.3598 32.1676 21.4996C32.2255 21.6395 32.2553 21.7894 32.2553 21.9407C32.2553 22.0921 32.2255 22.242 32.1676 22.3818C32.1096 22.5217 32.0247 22.6487 31.9177 22.7558Z" fill="#41BD66"/>
  </svg>`;

const successIcon = computed(() => {
  return formMode.value === "edit" ? iconEdit : iconAdded;
});

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
      <aside class="transition-all duration-600 ease-in-out transform" :class="isSidebarOpen
          ? 'translate-x-0 opacity-100'
          : '-translate-x-full opacity-0 w-0'
        ">
        <SideBar />
      </aside>

      <!-- Main -->
      <main class="flex-1 sm:p-5 md:p-6 md:mx-0 overflow-y-auto">
        <div class="m-2">
          <PageHeader title="Items" />
          <div class="w-full h-full">
            <div class="flex flex-col md:flex-row gap-2 justify-end mt-6">
              <ConvertButton />
              <ImportButton />
              <ExportButton />
            </div>

            <div class="flex flex-col md:flex-row justify-between mt-10">
              <PrimaryButton @click="openAdd">
                <i class="fa-solid fa-plus"></i>
                <span> Add Item</span>
              </PrimaryButton>

              <ItemFilterControls :search="search" :cost_range="cost_range" :status="status"
                :unitCostOptions="unitCostOptions" :filterStatus="filterStatus" @update:search="search = $event"
                @update:status="status = $event" @update:cost_range="cost_range = $event" :mode="'inventory'" />
            </div>

            <InventoryFormModal v-if="showFormModal" :mode="formMode" :firstDropdown="firstDropdown"
              :firstInputField="firstInputField" :secondDropdown="secondDropdown"
              :quantityCostFields="quantityCostFields" :input-fields="inputFields"
              :invoicesFundFields="invoicesFundFields" :supplierOptions="supplierOptions" :requestFields="requestFields"
              :inputFieldsEdit="inputFieldsEdit" :totalCost="totalCost" :itemClass="itemClassifications"
              :initialValues="currentItem" :suppliers="suppliers" :item="currentItem" :viewItem="viewItem"
              @submit="handleSubmit" @close="() => (showFormModal = false)" />

            <SuccessModal v-if="showSuccessModal" :icon="successIcon"
              :title="formMode === 'edit' ? 'Edit Success' : 'Added Success'" :message="successMessage"
              :actionButtonLabel="formMode === 'edit' ? 'View Item' : 'Assign'" @action="handleAction"
              @close="showSuccessModal = false" />

            <SuccessDeleteModal v-if="showDeleteSuccessModal" :icon="iconDelete" title="Delete Success"
              message="Item deleted successfully!" buttonText="Confirm" @close="showDeleteSuccessModal = false" />

            <DeleteModal v-if="showDeleteModal" :item="currentItem" @confirm="confirmDelete"
              @close="() => (showDeleteModal = false)" />

            <InventoryTable :columns="columns" :rows="items" @view="handleView" @edit="handleEdit"
              @delete="handleDelete" :actions="['view', 'delete', 'edit']" />
          </div>
        </div>
      </main>
    </div>
  </div>
</template>
