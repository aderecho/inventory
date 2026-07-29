<script setup>
import { ref, watch } from "vue";
import { router } from "@inertiajs/vue3";
import NavHeader from "@/Components/NavHeader.vue";
import SideBar from "@/Components/SideBar.vue";
import PageHeader from "@/Components/PageHeader.vue";
import LoadingOverlay from "@/Components/LoadingOverlay.vue";
import ItemHistoryTable from "@/Components/ItemHistoryTable.vue";
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

const search = ref("");
const room = ref("");

let debounceTimer = null;
watch([search, room], ([searchValue, roomValue]) => {
    clearTimeout(debounceTimer);
    debounceTimer = setTimeout(() => {
        router.get(
            route("item-histories.index"),
            { search: searchValue, room: roomValue },
            { preserveState: true, replace: true }
        );
    }, 400);
});

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

                    <!-- Search bar -->
                    <div class="flex items-center justify-end gap-3 mt-6">
                        <select
                            v-model="room"
                            class="text-sm border border-gray-200 rounded-lg bg-white px-3 py-2 focus:outline-none focus:ring-2 focus:ring-[#850038] focus:border-transparent"
                        >
                            <option value="">All rooms</option>
                            <option
                                v-for="r in rooms"
                                :key="r.id"
                                :value="r.id"
                            >
                                {{ r.room_name }}
                            </option>
                        </select>

                        <div class="relative w-full max-w-sm">
                            <span
                                class="absolute inset-y-0 left-3 flex items-center text-gray-400"
                            >
                                <svg
                                    class="w-4 h-4"
                                    fill="none"
                                    stroke="currentColor"
                                    stroke-width="1.5"
                                    viewBox="0 0 24 24"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        d="M21 21l-4.35-4.35M17 11A6 6 0 1 1 5 11a6 6 0 0 1 12 0z"
                                    />
                                </svg>
                            </span>
                            <input
                                v-model="search"
                                type="text"
                                placeholder="Search by item name, property no,, assignee..."
                                class="w-full pl-9 pr-4 py-2 text-sm border border-gray-200 rounded-lg bg-white focus:outline-none focus:ring-2 focus:ring-[#850038] focus:border-transparent"
                            />
                        </div>
                    </div>

                    <!-- Table -->
                    <ItemHistoryTable :items="items" @view="viewItem" />
                </main>
            </div>
        </div>
    </div>
</template>