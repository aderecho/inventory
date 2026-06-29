<script setup>
import { ref, computed, onMounted } from "vue";
import { usePage, router } from "@inertiajs/vue3";
import NavHeader from "@/Components/NavHeader.vue";
import SideBar from "@/Components/SideBar.vue";
import ItemOverview from "@/Components/ItemOverview.vue";
import PageHeader from "@/Components/PageHeader.vue";
import BarChartCard from "@/Components/BarChartCard.vue";
import { useLoading } from "@/Composables/useLoading";
import LoadingOverlay from "@/Components/LoadingOverlay.vue";

const { isLoading, loadingTitle, loadingMessage, startLoading, stopLoading } =
    useLoading();

const page = usePage();
const stats = computed(() => page.props.stats);

const itemOverview = computed(() => [
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

const isSidebarOpen = ref(true);
const toggleSidebar = () => {
    isSidebarOpen.value = !isSidebarOpen.value;
};
console.log(page.props.auth.permissions);
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
                        </div>

                       
                    </div>
                </div>
            </main>
        </div>
    </div>
</template>
