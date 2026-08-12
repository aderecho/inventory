<script setup>
import { ref, watch } from "vue";
import { router } from "@inertiajs/vue3";
import NavHeader from "@/Components/NavHeader.vue";
import SideBar from "@/Components/SideBar.vue";
import PageHeader from "@/Components/PageHeader.vue";
import LoadingOverlay from "@/Components/LoadingOverlay.vue";
import ItemHistoryTable from "@/Components/ItemHistoryTable.vue";
import SearchFilterBar from "@/Components/Filters/SearchFilterBar.vue";
import { useLoading } from "@/Composables/useLoading";

defineProps({
    items: Object,
    rooms: Array,
});

const { isLoading, loadingTitle, loadingMessage, startLoading, stopLoading } =
    useLoading();

const isSidebarOpen = ref(true);
const toggleSidebar = () => {
    isSidebarOpen.value = !isSidebarOpen.value;
};

function viewItem(id) {
    startLoading("Loading item history", "Fetching location records...");
    router.visit(route("item-histories.show", id), {
        onFinish: () => stopLoading(),
    });
}
</script>

<template>
    <LoadingOverlay
        :show="isLoading"
        :title="loadingTitle"
        :message="loadingMessage"
    />

    <div class="h-screen flex flex-col bg-gray-100">
        <div class="flex flex-1 overflow-hidden">
            <aside
                class="h-full transition-all duration-300 ease-in-out flex-shrink-0"
            >
                <SideBar
                    :isOpen="isSidebarOpen"
                    @toggleSidebar="toggleSidebar"
                />
            </aside>

            <div class="flex flex-col flex-1 overflow-hidden">
                <NavHeader
                    :isSidebarOpen="isSidebarOpen"
                    @toggleSidebar="toggleSidebar"
                />

                <main class="flex-1 overflow-y-auto sm:p-5 md:p-6 m-2">
                    <PageHeader title="Item Location History" />

                    <div
                                class="flex flex-col md:flex-row md:items-center justify-between gap-3 mt-5"
                            >
                    <SearchFilterBar
                        :search="search"
                        :room_id="room_id"
                        :rooms="rooms"
                        :acknowledgement_status="acknowledgement_status"
                        @update:acknowledgement_status="
                            acknowledgement_status = $event
                        "
                        @update:search="search = $event"
                        :mode="'item-history'"
                    />
                    </div>

                    <!-- Table -->
                    <ItemHistoryTable :items="items" @view="viewItem" />
                </main>
            </div>
        </div>
    </div>
</template>
