<script setup>
import { ref, computed } from "vue";
import { router } from "@inertiajs/vue3";
import NavHeader from "@/Components/NavHeader.vue";
import SideBar from "@/Components/SideBar.vue";
import PageHeader from "@/Components/PageHeader.vue";
import LoadingOverlay from "@/Components/LoadingOverlay.vue";
import { useLoading } from "@/Composables/useLoading";

const props = defineProps({
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

function handleSearch() {
    router.get(
        route("item-histories.index"),
        { search: search.value },
        { preserveState: true, replace: true }
    );
}

function viewItem(id) {
    startLoading("Loading item history", "Fetching location records...");
    router.visit(route("item-histories.show", id), {
        onFinish: () => stopLoading(),
    });
}

const statusColor = (status) => {
    const map = {
        active: "status-active",
        inactive: "status-inactive",
        disposed: "status-disposed",
    };
    return map[status?.toLowerCase()] ?? "status-default";
};
</script>

<template>
    <LoadingOverlay
        :show="isLoading"
        :title="loadingTitle"
        :message="loadingMessage"
    />

    <div class="h-screen flex flex-col bg-gray-100">
        <NavHeader class="flex-shrink-0" @toggleSidebar="toggleSidebar" />

        <div class="flex flex-1 overflow-hidden">
            <aside
                class="transition-all duration-300 ease-in-out"
                :class="
                    isSidebarOpen
                        ? 'translate-x-0 opacity-100'
                        : '-translate-x-full opacity-0 w-0'
                "
            >
                <SideBar />
            </aside>

            <main class="flex-1 p-4 sm:p-5 md:p-6 overflow-y-auto">
                <PageHeader title="Item Location History" class="ml-4" />

                <!-- Search bar -->
                <div class="mx-3 mt-4 mb-5 flex items-center gap-3">
                    <div class="relative flex-1 max-w-sm">
                        <span class="absolute inset-y-0 left-3 flex items-center text-gray-400">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-4.35-4.35M17 11A6 6 0 1 1 5 11a6 6 0 0 1 12 0z" />
                            </svg>
                        </span>
                        <input
                            v-model="search"
                            type="text"
                            placeholder="Search by item name or property number..."
                            class="w-full pl-9 pr-4 py-2 text-sm border border-gray-200 rounded-lg bg-white focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                            @keydown.enter="handleSearch"
                        />
                    </div>
                    <button
                        class="px-4 py-2 text-sm bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors"
                        @click="handleSearch"
                    >
                        Search
                    </button>
                </div>

                <!-- Table -->
                <div class="mx-3 bg-white rounded-xl border border-gray-200 overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="min-w-full text-sm">
                            <thead>
                                <tr class="border-b border-gray-100 bg-gray-50">
                                    <th class="text-left px-5 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wide">Item</th>
                                    <th class="text-left px-5 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wide">Property No.</th>
                                    <th class="text-left px-5 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wide">Current Location</th>
                                    <th class="text-left px-5 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wide">Accountable Person</th>
                                    <th class="text-left px-5 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wide">Status</th>
                                    <th class="text-left px-5 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wide">History Count</th>
                                    <th class="px-5 py-3"></th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                <template v-if="items.data && items.data.length > 0">
                                    <tr
                                        v-for="item in items.data"
                                        :key="item.id"
                                        class="hover:bg-gray-50 transition-colors"
                                    >
                                        <!-- Item name + classification -->
                                        <td class="px-5 py-4">
                                            <div class="font-medium text-gray-900">{{ item.item_name }}</div>
                                            <div class="text-xs text-gray-400 mt-0.5">
                                                {{ item.item_classification?.classification_name ?? "—" }}
                                            </div>
                                        </td>

                                        <!-- Property number -->
                                        <td class="px-5 py-4 text-gray-600 font-mono text-xs">
                                            {{ item.property_number ?? "—" }}
                                        </td>

                                        <!-- Current location -->
                                        <td class="px-5 py-4">
                                            <div v-if="item.room_name && item.room_name !== 'N/A'" class="flex items-center gap-1.5">
                                                <span class="inline-block w-1.5 h-1.5 rounded-full bg-blue-500 flex-shrink-0"></span>
                                                <span class="text-gray-800">{{ item.room_name }}</span>
                                            </div>
                                            <span v-else class="text-gray-400 text-xs italic">No location set</span>
                                        </td>

                                        <!-- Accountable person -->
                                        <td class="px-5 py-4 text-gray-600">
                                            {{ item.latest_acknowledgement_item?.accountable_person?.full_name ?? "—" }}
                                        </td>

                                        <!-- Status badge -->
                                        <td class="px-5 py-4">
                                            <span
                                                class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium"
                                                :class="{
                                                    'bg-green-100 text-green-700': item.status == 1,
                                                    'bg-red-100 text-red-700': item.status == 0,
                                                    'bg-gray-100 text-gray-500': item.status == null,
                                                }"
                                            >
                                                {{ item.status == 1 ? "Active" : item.status == 0 ? "Inactive" : "Unknown" }}
                                            </span>
                                        </td>

                                        <!-- History count -->
                                        <td class="px-5 py-4">
                                            <span class="inline-flex items-center gap-1 text-gray-600">
                                                <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6l4 2m6-2a10 10 0 1 1-20 0 10 10 0 0 1 20 0z" />
                                                </svg>
                                                {{ item.history_locations?.length ?? 0 }} move{{ (item.history_locations?.length ?? 0) !== 1 ? "s" : "" }}
                                            </span>
                                        </td>

                                        <!-- Action -->
                                        <td class="px-5 py-4 text-right">
                                            <button
                                                class="text-blue-600 hover:text-blue-800 text-xs font-medium transition-colors"
                                                @click="viewItem(item.id)"
                                            >
                                                View history →
                                            </button>
                                        </td>
                                    </tr>
                                </template>

                                <tr v-else>
                                    <td colspan="7" class="px-5 py-16 text-center text-gray-400">
                                        <div class="flex flex-col items-center gap-2">
                                            <svg class="w-8 h-8 text-gray-300" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 6.75V15m6-6v8.25m.503 3.498 4.875-2.437c.381-.19.622-.58.622-1.006V4.82c0-.836-.88-1.38-1.628-1.006l-3.869 1.934c-.317.159-.69.159-1.006 0L9.503 3.252a1.125 1.125 0 0 0-1.006 0L3.622 5.689C3.24 5.88 3 6.27 3 6.695V19.18c0 .836.88 1.38 1.628 1.006l3.869-1.934c.317-.159.69-.159 1.006 0l4.994 2.497c.317.158.69.158 1.006 0Z" />
                                            </svg>
                                            <span class="text-sm">No items with location history found</span>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- Pagination -->
                    <div
                        v-if="items.links && items.data.length > 0"
                        class="px-5 py-3 border-t border-gray-100 flex items-center justify-between"
                    >
                        <p class="text-xs text-gray-400">
                            Showing {{ items.from }}–{{ items.to }} of {{ items.total }} items
                        </p>
                        <div class="flex items-center gap-1">
                            <template v-for="link in items.links" :key="link.label">
                                <button
                                    v-if="link.url"
                                    class="px-3 py-1 rounded text-xs border transition-colors"
                                    :class="
                                        link.active
                                            ? 'bg-blue-600 text-white border-blue-600'
                                            : 'bg-white text-gray-600 border-gray-200 hover:border-gray-300'
                                    "
                                    @click="router.visit(link.url, { preserveState: true })"
                                    v-html="link.label"
                                />
                                <span
                                    v-else
                                    class="px-3 py-1 rounded text-xs border border-gray-100 text-gray-300 cursor-default"
                                    v-html="link.label"
                                />
                            </template>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>
</template>