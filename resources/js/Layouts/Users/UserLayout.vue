<script setup>
import { ref, computed } from "vue";
import { router } from "@inertiajs/vue3";

import NavBar from "@/Components/UserComponents/NavBar.vue";
import ProfileCard from "@/Components/UserComponents/ProfileCard.vue";
import ItemTable from "@/Components/UserComponents/ItemTable.vue";
import ItemCard from "@/Components/UserComponents/ItemCard.vue";
import Banner from '@/Components/UserComponents/Banner.vue';

const props = defineProps({
    user: {
        type: Object,
        required: true,
    },

    items: {
        type: Object,
        required: true,
    },

    stats: {
        type: Object,
        default: () => ({}),
    },
});

const search = ref("");

const searchItems = () => {
    router.get(
        route("user.dashboard"),
        {
            search: search.value,
        },
        {
            preserveState: true,
            replace: true,
        }
    );
};

const profileFields = computed(() => [
    {
        label: "Email Address",
        value: props.user?.email ?? "N/A",
    },
    {
        label: "Contact Number",
        value: props.user?.user_profiles?.contact_number ?? "N/A",
    },
    {
        label: "Member Since",
        value: props.user?.created_at
            ? new Date(props.user.created_at).toLocaleDateString()
            : "N/A",
    },
]);
</script>

<template>
    <div class="min-h-screen bg-gray-50 flex flex-col">
        <!-- Navbar -->
        <NavBar :user="user" />

        <!-- Main -->
        <main class="flex-1">
            <div class="max-w-7xl mx-auto px-6 py-8">

              <Banner :user="user" class="mb-4"/>

                <!-- Stats -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
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

                    <ItemCard
                        title="Total Assets"
                        :value="items?.total ?? 0"
                        description="Inventory assets assigned"
                    />
                </div>

                <!-- Content -->
                <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">

                    <!-- Profile -->
                    <div class="lg:col-span-3">
                        <ProfileCard
                            :user="user"
                            :fields="profileFields"
                        />
                    </div>

                    <!-- Assets -->
                    <div class="lg:col-span-9">
                        <div
                            class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden"
                        >
                            <!-- Header -->
                            <div class="p-6 border-b border-gray-200">
                                <div
                                    class="flex flex-col md:flex-row md:items-center md:justify-between gap-4"
                                >
                                    <div>
                                        <h2
                                            class="text-xl font-semibold text-[#850038]"
                                        >
                                            My Assigned Assets
                                        </h2>

                                        <p
                                            class="text-sm text-gray-500 mt-1"
                                        >
                                            View all inventory items currently assigned to you.
                                        </p>
                                    </div>

                                    <input
                                        v-model="search"
                                        @input="searchItems"
                                        type="text"
                                        placeholder="Search assigned items..."
                                        class="w-full md:w-80 border border-gray-300 rounded-lg px-4 py-2 focus:outline-none focus:ring-2 focus:ring-[#850038]"
                                    />
                                </div>
                            </div>

                            <!-- Table -->
                            <ItemTable
                                :items="items"
                            />
                        </div>
                    </div>

                </div>
            </div>
        </main>

        <!-- Footer -->
        <footer
            class="bg-white border-t border-gray-200 py-4"
        >
            <div
                class="max-w-7xl mx-auto px-6 flex flex-col md:flex-row items-center justify-between gap-2"
            >
                <p class="text-sm text-gray-500">
                    Inventory Management System
                </p>

                <p class="text-sm text-gray-400">
                    © {{ new Date().getFullYear() }}
                    All Rights Reserved
                </p>
            </div>
        </footer>
    </div>
</template>