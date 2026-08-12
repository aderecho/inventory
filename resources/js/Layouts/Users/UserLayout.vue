<script setup>
import { ref, computed, onBeforeUnmount } from "vue";
import { router } from "@inertiajs/vue3";

import NavBar from "@/Components/UserComponents/NavBar.vue";
import ItemTable from "@/Components/UserComponents/ItemTable.vue";
import ItemCard from "@/Components/UserComponents/ItemCard.vue";
import Banner from "@/Components/UserComponents/Banner.vue";
import StatusStrip from "@/Components/UserComponents/StatusStrip.vue";
import SessionTimeoutWarning from "@/Components/SessionTimeoutWarning.vue";

const props = defineProps({
    user: { type: Object, required: true },
    items: { type: Object, required: true },
    stats: { type: Object, default: () => ({}) },
    filters: {
        type: Object,
        default: () => ({ search: null, sort: null, direction: "asc" }),
    },
});

const search = ref(props.filters?.search ?? "");
const sortKey = ref(props.filters?.sort ?? null);
const sortDirection = ref(props.filters?.direction ?? "asc");

const isSearching = ref(false);
let searchTimeout = null;

const fetchItems = (extra = {}) => {
    isSearching.value = true;

    router.get(
        route("user.dashboard"),
        {
            search: search.value,
            sort: sortKey.value,
            direction: sortDirection.value,
            ...extra,
        },
        {
            preserveState: true,
            preserveScroll: true,
            replace: true,
            onFinish: () => {
                isSearching.value = false;
            },
        }
    );
};

const searchItems = () => {
    if (searchTimeout) clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => fetchItems(), 350);
};

const handleSort = (key) => {
    if (sortKey.value === key) {
        sortDirection.value = sortDirection.value === "asc" ? "desc" : "asc";
    } else {
        sortKey.value = key;
        sortDirection.value = "asc";
    }
    fetchItems();
};

onBeforeUnmount(() => {
    if (searchTimeout) clearTimeout(searchTimeout);
});

</script>

<template>
    <div class="min-h-screen bg-gray-50 flex flex-col">
        <!-- Navbar -->
        <NavBar :user="user" />

        <!-- Main -->
        <main class="flex-1">
            <div class="max-w-7xl mx-auto px-6 py-8">
                <Banner :user="user" class="mb-4" />
                <StatusStrip class="mb-8" />
                 <SessionTimeoutWarning />
                <!-- Stats -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
                    <ItemCard
                        title="Assigned Items"
                        :value="stats?.assigned_items ?? 0"
                        description="Items currently assigned to you"
                    />

                    <ItemCard
                        title="Receipts"
                        :value="stats?.receipts ?? 0"
                        description="Acknowledgement receipts"
                    />
                </div>

                <!-- Content -->
                <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">

                    <!-- Assets -->
                    <div class="lg:col-span-12">
                        <div
                            class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden"
                        >
                            <!-- Header -->
                            <div
                                class="p-6 border-b border-gray-200 bg-gradient-to-r from-[#005740]/5 via-transparent to-transparent"
                            >
                                <div
                                    class="flex flex-col md:flex-row md:items-center md:justify-between gap-4"
                                >
                                    <div>
                                        <h2
                                            class="text-xl font-semibold text-[#005740]"
                                        >
                                            My Assigned Assets
                                        </h2>

                                        <p class="text-sm text-gray-500 mt-1">
                                            View all inventory items currently
                                            assigned to you.
                                        </p>
                                    </div>

                                    <div class="relative w-full md:w-80">
                                        <input
                                            v-model="search"
                                            @input="searchItems"
                                            type="text"
                                            placeholder="Search assigned items..."
                                            class="w-full border border-gray-300 rounded-lg pl-4 pr-10 py-2 focus:outline-none focus:ring-2 focus:ring-[#005740] transition-shadow"
                                        />
                                        <div
                                            class="absolute right-3 top-1/2 -translate-y-1/2"
                                        >
                                            <svg
                                                v-if="isSearching"
                                                class="w-4 h-4 text-[#005740] animate-spin"
                                                fill="none"
                                                viewBox="0 0 24 24"
                                            >
                                                <circle
                                                    class="opacity-25"
                                                    cx="12"
                                                    cy="12"
                                                    r="10"
                                                    stroke="currentColor"
                                                    stroke-width="4"
                                                />
                                                <path
                                                    class="opacity-75"
                                                    fill="currentColor"
                                                    d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"
                                                />
                                            </svg>
                                            <i
                                                v-else
                                                class="fa-solid fa-magnifying-glass text-gray-300 text-[13px]"
                                            ></i>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Table -->
                            <ItemTable
                                :items="items"
                                :sort-key="sortKey"
                                :sort-direction="sortDirection"
                                @sort="handleSort"
                            />
                        </div>
                    </div>
                </div>
            </div>
        </main>

        <!-- Footer -->
        <footer class="bg-white border-t border-gray-200 py-4">
            <div
                class="max-w-7xl mx-auto px-6 flex flex-col md:flex-row items-center justify-between gap-2"
            >
                <p class="text-sm text-gray-500">Inventory Management System</p>

                <p class="text-sm text-gray-400">
                    © {{ new Date().getFullYear() }}
                    All Rights Reserved UP-CEBU
                </p>
            </div>
        </footer>
    </div>
</template>
