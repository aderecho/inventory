<script setup>
import { ref } from "vue";
import { router } from "@inertiajs/vue3";
import Layout from "@/Layouts/ItemHistory/ItemHistoryLayout.vue";
import ItemHistoryTable from "@/Components/ItemHistoryTable.vue";
import { useLoading } from "@/Composables/useLoading";

defineProps({
    items: Object,
    rooms: Array,
});

const { isLoading, loadingTitle, loadingMessage, startLoading, stopLoading } =
    useLoading();

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
</script>

<template>
    <Layout
        title="Item Location History"
        :is-loading="isLoading"
        :loading-title="loadingTitle"
        :loading-message="loadingMessage"
    >
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
        <div class="mx-3">
            <ItemHistoryTable :items="items" @view="viewItem" />
        </div>
    </Layout>
</template>