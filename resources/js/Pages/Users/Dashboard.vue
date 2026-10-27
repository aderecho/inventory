<script setup>
import { computed, onBeforeUnmount, ref, watch } from "vue";
import { Head, router } from "@inertiajs/vue3";
import { motion } from "motion-v";
import UserPortalLayout from "@/Layouts/UserPortalLayout.vue";
import PageHeader from "@/Components/PageHeader.vue";
import ItemTable from "@/Components/UserComponents/ItemTable.vue";
import BorrowedItemsTable from "@/Components/UserComponents/BorrowedItemsTable.vue";
import {
    BadgeCheck,
    ClipboardList,
    ClockAlert,
    FileText,
    HandCoins,
    Package,
    Search,
} from "lucide-vue-next";

const props = defineProps({
    user: { type: Object, default: () => ({}) },
    items: { type: Object, default: () => ({ data: [] }) },
    stats: { type: Object, default: () => ({}) },
    borrowedItems: { type: Array, default: () => [] },
    filters: { type: Object, default: () => ({}) },
});

const profile = computed(() => props.user?.user_profiles ?? {});
const fullName = computed(() => profile.value.full_name || props.user?.email || "there");
const firstName = computed(() => profile.value.first_name || "");

const greeting = computed(() => {
    const hour = new Date().getHours();

    if (hour < 12) return "Good morning";
    if (hour < 18) return "Good afternoon";

    return "Good evening";
});

const stats_cards = computed(() => [
    {
        key: "assigned",
        label: "Assigned items",
        value: props.stats?.assigned_items ?? 0,
        hint: "Property under your custody",
        icon: Package,
        tile: "bg-[#005740]/10 text-[#005740]",
        accent: "text-[#005740]",
        target: "assigned-items",
    },
    {
        key: "borrowed",
        label: "Borrowed now",
        value: props.stats?.borrowed_items ?? 0,
        hint: "Items you have on loan",
        icon: HandCoins,
        tile: "bg-[#005740]/10 text-[#005740]",
        accent: "text-[#005740]",
        target: "borrowed-items",
    },
    {
        key: "overdue",
        label: "Overdue",
        value: props.stats?.overdue_borrowings ?? 0,
        hint: "Past the return date",
        icon: ClockAlert,
        tile: "bg-[#D71D1D]/10 text-[#D71D1D]",
        accent: "text-[#D71D1D]",
        target: "borrowed-items",
    },
    {
        key: "receipts",
        label: "Receipts",
        value: props.stats?.receipts ?? 0,
        hint: "Acknowledgement documents",
        icon: FileText,
        tile: "bg-[#005740]/10 text-[#005740]",
        accent: "text-[#005740]",
        target: "assigned-items",
    },
]);

function scrollToSection(id) {
    document.getElementById(id)?.scrollIntoView({
        behavior: "smooth",
        block: "start",
    });
}

// Assigned items: same query parameters the controller already validates.
const search = ref(props.filters?.search ?? "");
const approvalStatus = ref(props.filters?.approval_status ?? "");
const sortKey = ref(props.filters?.sort ?? null);
const sortDirection = ref(props.filters?.direction ?? "asc");

const isSearching = ref(false);
let searchTimeout = null;

function fetchItems(extra = {}) {
    isSearching.value = true;

    router.get(
        route("user.dashboard"),
        {
            search: search.value || undefined,
            approval_status: approvalStatus.value || undefined,
            sort: sortKey.value || undefined,
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
        },
    );
}

function searchItems() {
    if (searchTimeout) clearTimeout(searchTimeout);

    searchTimeout = setTimeout(() => fetchItems(), 350);
}

function handleSort(key) {
    if (sortKey.value === key) {
        sortDirection.value = sortDirection.value === "asc" ? "desc" : "asc";
    } else {
        sortKey.value = key;
        sortDirection.value = "asc";
    }

    fetchItems();
}

watch(approvalStatus, () => fetchItems());

onBeforeUnmount(() => {
    if (searchTimeout) clearTimeout(searchTimeout);
});

const borrowedItems = computed(() => props.borrowedItems ?? []);
</script>

<template>
    <Head title="UP | My Dashboard" />

    <UserPortalLayout>
        <PageHeader title="My Dashboard" />

        <div class="w-full h-full space-y-5">
            <!-- GREETING -->
            <motion.section
                class="relative mt-6 overflow-hidden rounded-2xl bg-gradient-to-r from-[#003d2c] via-[#005740] to-[#0E6021] px-6 py-6 text-white shadow-sm sm:px-8"
                :initial="{ opacity: 0, y: 12 }"
                :animate="{ opacity: 1, y: 0 }"
                :transition="{ type: 'spring', stiffness: 260, damping: 26 }"
            >
                <div
                    class="pointer-events-none absolute inset-0"
                    style="
                        background:
                            radial-gradient(circle at 82% 18%, rgba(255, 255, 255, 0.18), transparent 45%),
                            radial-gradient(circle at 12% 120%, rgba(255, 255, 255, 0.10), transparent 50%);
                    "
                ></div>

                <div class="flex flex-col gap-5 lg:flex-row lg:items-end lg:justify-between">
                    <div class="min-w-0">
                        <p class="text-[11px] font-semibold uppercase tracking-[0.16em] text-white/70">
                            {{ greeting }}{{ firstName ? "," : "" }}
                        </p>
                        <h1 class="mt-1 truncate text-2xl font-bold sm:text-3xl">
                            {{ fullName }}
                        </h1>
                        <p class="mt-1 text-sm text-white/80">
                            {{ profile.primary_unit_division_department || "Accountability dashboard" }}
                        </p>
                    </div>

                    <div class="flex flex-wrap gap-2 text-[11px]">
                        <span
                            v-if="profile.employee_number"
                            class="inline-flex items-center gap-1.5 rounded-full border border-white/25 bg-white/10 px-3 py-1.5 font-semibold"
                        >
                            <BadgeCheck class="h-3.5 w-3.5" />
                            {{ profile.employee_number }}
                        </span>

                        <span
                            v-if="user?.email"
                            class="inline-flex items-center gap-1.5 rounded-full border border-white/25 bg-white/10 px-3 py-1.5 font-semibold"
                        >
                            {{ user.email }}
                        </span>

                        <span
                            v-if="profile.contact_number"
                            class="inline-flex items-center gap-1.5 rounded-full border border-white/25 bg-white/10 px-3 py-1.5 font-semibold"
                        >
                            {{ profile.contact_number }}
                        </span>
                    </div>
                </div>

                <div class="relative mt-5 flex flex-wrap gap-2">
                    <button
                        type="button"
                        class="inline-flex items-center gap-1.5 rounded-full border border-white/25 bg-white/10 px-3 py-1.5 text-[11px] font-semibold text-white transition-colors hover:bg-white/20"
                        @click="scrollToSection('assigned-items')"
                    >
                        <Package class="h-3.5 w-3.5" />
                        My assigned items
                    </button>

                    <button
                        type="button"
                        class="inline-flex items-center gap-1.5 rounded-full border border-white/25 bg-white/10 px-3 py-1.5 text-[11px] font-semibold text-white transition-colors hover:bg-white/20"
                        @click="scrollToSection('borrowed-items')"
                    >
                        <HandCoins class="h-3.5 w-3.5" />
                        My borrowed items
                    </button>
                </div>
            </motion.section>

            <!-- STATS -->
            <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 xl:grid-cols-4">
                <motion.button
                    v-for="(card, index) in stats_cards"
                    :key="card.key"
                    type="button"
                    class="rounded-xl border border-gray-200 bg-white p-4 text-left shadow-sm transition-colors hover:border-[#005740]/30 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[#005740]/30"
                    :initial="{ opacity: 0, y: 14 }"
                    :animate="{ opacity: 1, y: 0 }"
                    :transition="{
                        delay: 0.06 + index * 0.05,
                        type: 'spring',
                        stiffness: 260,
                        damping: 26,
                    }"
                    :while-hover="{ y: -3 }"
                    :while-press="{ scale: 0.99 }"
                    @click="scrollToSection(card.target)"
                >
                    <div class="flex items-start justify-between gap-3">
                        <div>
                            <p class="text-[11px] font-semibold uppercase tracking-wide text-gray-500">
                                {{ card.label }}
                            </p>
                            <p class="mt-1 text-2xl font-bold" :class="card.accent">
                                {{ card.value }}
                            </p>
                            <p class="mt-0.5 text-[11px] text-gray-400">
                                {{ card.hint }}
                            </p>
                        </div>

                        <span
                            class="flex h-10 w-10 items-center justify-center rounded-lg"
                            :class="card.tile"
                        >
                            <component :is="card.icon" class="h-5 w-5" />
                        </span>
                    </div>
                </motion.button>
            </div>

            <!-- ASSIGNED ITEMS -->
            <section
                id="assigned-items"
                class="scroll-mt-24 overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm"
            >
                <header
                    class="flex flex-col gap-3 border-b border-gray-100 px-4 py-3 lg:flex-row lg:items-center lg:justify-between"
                >
                    <div class="flex items-center gap-2">
                        <span class="flex h-9 w-9 items-center justify-center rounded-lg bg-[#005740]/10 text-[#005740]">
                            <ClipboardList class="h-4 w-4" />
                        </span>
                        <div>
                            <h2 class="text-sm font-semibold text-[#1b1b1b]">
                                Assigned to me
                            </h2>
                            <p class="text-[11px] text-gray-500">
                                {{ items.total ?? 0 }} item(s) under your accountability
                            </p>
                        </div>
                    </div>

                    <div class="flex flex-col gap-2 sm:flex-row sm:items-center">
                        <select
                            v-model="approvalStatus"
                            class="h-10 w-full rounded-lg border border-gray-300 bg-white px-3 text-xs text-gray-700 focus:border-[#005740] focus:outline-none focus:ring-1 focus:ring-[#005740] sm:w-40"
                        >
                            <option value="">All statuses</option>
                            <option value="pending">Pending</option>
                            <option value="approved">Approved</option>
                            <option value="rejected">Rejected</option>
                        </select>

                        <div class="relative w-full sm:w-72">
                            <span class="pointer-events-none absolute inset-y-0 left-3 flex items-center text-gray-400">
                                <Search class="h-4 w-4" />
                            </span>
                            <input
                                v-model="search"
                                type="search"
                                placeholder="Search assigned items..."
                                class="h-10 w-full rounded-lg border border-gray-300 bg-white pl-9 pr-3 text-sm text-[#3B3B3B] focus:border-[#005740] focus:outline-none focus:ring-1 focus:ring-[#005740]"
                                @input="searchItems"
                            />
                        </div>
                    </div>
                </header>

                <ItemTable
                    :items="items"
                    :sort-key="sortKey"
                    :sort-direction="sortDirection"
                    @sort="handleSort"
                />
            </section>

            <!-- BORROWED ITEMS -->
            <section
                id="borrowed-items"
                class="scroll-mt-24 overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm"
            >
                <header class="flex items-center gap-2 border-b border-gray-100 px-4 py-3">
                    <span class="flex h-9 w-9 items-center justify-center rounded-lg bg-[#1d4ed8]/10 text-[#1d4ed8]">
                        <HandCoins class="h-4 w-4" />
                    </span>
                    <div>
                        <h2 class="text-sm font-semibold text-[#1b1b1b]">
                            Borrowed items
                        </h2>
                        <p class="text-[11px] text-gray-500">
                            {{ borrowedItems.length }} item(s) still out on loan to you
                        </p>
                    </div>
                </header>

                <BorrowedItemsTable
                    :items="borrowedItems"
                    empty-text="Nothing is borrowed on your account right now."
                />
            </section>
        </div>
    </UserPortalLayout>
</template>

