<script setup>
import { onMounted, ref, computed } from "vue";
import { usePage, router } from "@inertiajs/vue3";
import NavHeader from "@/Components/NavHeader.vue";
import SideBar from "@/Components/SideBar.vue";
import ItemOverview from "@/Components/ItemOverview.vue";
import PageHeader from "@/Components/PageHeader.vue";
import BarChartCard from "@/Components/BarChartCard.vue";
import ItemFilterControls from "@/Components/Filters/ItemFilterControls.vue";
import InventoryTable from "@/Components/InventoryTable.vue";
import SupplierChartCard from "@/Components/SupplierChartCard.vue";
import UserActivity from "@/Components/UserActivity.vue";
import { useLoading } from "@/Composables/useLoading";
import LoadingOverlay from "@/Components/LoadingOverlay.vue";

const { isLoading, loadingTitle, loadingMessage, startLoading, stopLoading } =
    useLoading();

const columns = [
    { label: "Item Name", key: "item_name" },
    { label: "Unit", key: "unit", format: (val) => val ?? "N/A" },
    {
        label: "Unit Cost",
        key: "unit_cost",
        format: (val) => (val ? `₱${val}` : "N/A"),
    },
    { label: "Property Number", key: "property_number" },
    { label: "Invoice", key: "invoice" },
    { label: "Supplier", key: "supplier.supplier_name" },
    {
        label: "Status",
        key: "status",
        format: (status) => {
            let label = "Unknown",
                cls = "text-gray-500",
                icon = "";
            if (status === 0) {
                label = "Cancelled";
                cls =
                    "text-[#D32F2F] font-bold bg-[#F8D4D4] py-2 px-4 rounded-full";
            } else if (status === 1) {
                label = "Recieved";
                cls =
                    "text-[#2E7D32] font-bold bg-[#D4F8D4] py-2 px-4 rounded-full";
            }
            return `<span class="${cls}">${icon} ${label}</span>`;
        },
    },
    // { label: "Action", key: "action" }
];

const stats = computed(() => page.props.stats);

const itemOverview = computed(() => [
    // {
    //     title: "Total Items",
    //     icon: "fa-solid fa-boxes-stacked text-[#850038]",
    //     bgColor: "bg-[#850038]",
    //     value: stats.value.items,
    // },
    {
        title: "Total Item Classification",
        icon: "fa-solid fa-layer-group text-[#850038]",
        bgColor: "bg-[#850038]",
        value: stats.value.item_classifications,
    },
    {
        title: "Total Suppliers",
        icon: "fa-solid fa-truck text-[#850038]",
        bgColor: "bg-[#850038]",
        value: stats.value.suppliers,
    },
    {
        title: "Total Users",
        icon: "fa-solid fa-users text-[#850038]",
        bgColor: "bg-[#850038]",
        value: stats.value.users,
    },
]);

const supplierChart = [
    { name: "Supplied", icon: "fa-solid fa-circle text-[#7E19FA]" },
    { name: "Delivered", icon: "fa-solid fa-circle text-[#19FA21]" },
    { name: "Cancelled", icon: "fa-solid fa-circle text-[#FA2C19]" },
];

const dropdownSupplierChart = [
    {
        model: "supplier",
        options: ["Budget Office", "ITC Office", "ILC", "CMO Office"],
    },
];

const page = usePage();
const items = computed(() => page.props.items);

//INVENTORY FILTER
let search = ref("");
let status = ref(null);
let cost_range = ref(null);

const isSidebarOpen = ref(true);
const toggleSidebar = () => {
    isSidebarOpen.value = !isSidebarOpen.value;
};
</script>

<template>
    <LoadingOverlay
        :show="isLoading"
        :title="loadingTitle"
        :message="loadingMessage"
    />

    <div class="h-screen flex flex-col bg-gray-100">
        <!-- NAVHEADER -->
        <NavHeader class="flex-shrink-0" @toggleSidebar="toggleSidebar" />

        <!-- SIDEBAR + MAIN -->
        <div class="flex flex-1 overflow-hidden">
            <!-- SIDEBAR -->
            <aside
                class="transition-all duration-600 ease-in-out transform"
                :class="
                    isSidebarOpen
                        ? 'translate-x-0 opacity-100'
                        : '-translate-x-full opacity-0 w-0'
                "
            >
                <SideBar />
            </aside>

            <!-- MAIN CONTENT -->
            <main class="flex-1 p-4 sm:p-5 md:p-6 overflow-y-auto">
                <div class="mx-4">
                    <PageHeader title="Dashboard" />
                    <div class="flex flex-col lg:flex-row gap-4 my-5 flex-wrap">
                        <div class="flex-1 w-full lg:w-[22rem] xl:w-[25rem]">
                            <ItemOverview :item-overview="itemOverview" />
                            <div class="mt-3">
                                <BarChartCard :stats="stats" />
                            </div>
                            <div class="my-5">
                                <ItemFilterControls
                                    :search="search"
                                    :cost_range="cost_range"
                                    :status="status"
                                    @update:search="search = $event"
                                    @update:status="status = $event"
                                    @update:cost_range="cost_range = $event"
                                    :mode="'dashboard'"
                                />
                            </div>
                            <div class="my-5">
                                <InventoryTable
                                    :columns="columns"
                                    :rows="items"
                                    :actions="['view']"
                                />
                            </div>
                        </div>

                        <!-- RIGHT SECTION -->
                        <!-- <div class="w-full lg:w-[22rem] xl:w-[25rem] space-y-4 flex-shrink-0">
                <SupplierChartCard
                  title="Supplier Statistics"
                  :supplier-chart="supplierChart"
                  :dropdown-supplier-list="dropdownSupplierChart"
                />
                <UserActivity title="Recent Activity" />
              </div> -->
                    </div>
                </div>
            </main>
        </div>
    </div>
</template>
