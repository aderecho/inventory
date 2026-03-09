<script setup>
import { ref, computed } from "vue";
import { usePage } from "@inertiajs/vue3";
import NavHeader from "@/Components/NavHeader.vue";
import SideBar from "@/Components/SideBar.vue";
import PageHeader from "@/Components/PageHeader.vue";
import InventoryTable from "@/Components/InventoryTable.vue";
import ItemFilterControls from "@/Components/Filters/ItemFilterControls.vue";
import DateCategoryFilter from "@/Components/Filters/DateCategoryFilter.vue";
import ExportButton from "@/Components/Buttons/ExportButton.vue";

const columns = [
  { label: "Item Name", key: 'item_name' },
  { label: "Supplier Name", key: 'supplier', format: (val) => val?.supplier_name ?? 'N/A' },
  { label: "Quantity", key: 'quantity' },
  {
    label: "Stock Status",
    key: 'quantity',
    format: (quantity) => {
      let label = 'Unknown', cls = 'text-gray-500', icon = '';
      if (quantity <= 0) {
        label = "Out of Stock";
        cls = "text-[#D32F2F] font-bold bg-[#F8D4D4] py-2 px-4 rounded-full";
        icon = '<i class="fa-solid fa-arrow-trend-down"></i>';
      }
      // ✔ LOW STOCK (1–5)
      else if (quantity > 0 && quantity <= 5) {
        label = "Low Stock";
        cls = "text-[#F59E0B] font-bold bg-[#FAE8CA] py-2 px-4 rounded-full";
        icon = '<i class="fa-solid fa-arrow-trend-down"></i>';
      }
      // ✔ IN STOCK (6 or more)
      else if (quantity > 5) {
        label = "In Stock";
        cls = "text-[#2E7D32] font-bold bg-[#D4F8D4] py-2 px-4 rounded-full";
        icon = '<i class="fa-solid fa-arrow-trend-up"></i>';
      }


      return `<span class="${cls} font-semibold">${icon} ${label}</span>`;
    }
  },
];


const page = usePage();
const items = computed(() => page.props.items);

//INVENTORY FILTER 
let search = ref('');
let status = ref(null);
let cost_range = ref(null);


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
      <!-- MAIN -->
      <main class="flex-1 sm:p-5 md:p-6 overflow-y-auto">
        <div class="m-2">
          <PageHeader title="Reports" />
          <div class="mt-[2rem]">
            <div class="bg-white border-b px-4 py-3 flex items-center rounded-lg shadow-md">
              <div class="flex items-start justify-between w-full">
                <div>
                  <DateCategoryFilter />
                </div>
                <div class="flex items-start gap-4 mt-6">
                  <ExportButton />
                  <ItemFilterControls class="-mt-2" :search="search" :status="status" :mode="'reports'"
                    @update:search="search = $event" @update:status="status = $event"
                    @update:cost_range="cost_range = $event" />
                </div>
              </div>
            </div>

            <InventoryTable :columns="columns" :rows="items" />
          </div>
        </div>
      </main>

    </div>
  </div>
</template>