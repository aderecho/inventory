<script setup>
import { computed } from "vue";
import { Head, router } from "@inertiajs/vue3";
import { motion } from "motion-v";
import UserPortalLayout from "@/Layouts/UserPortalLayout.vue";
import PageHeader from "@/Components/PageHeader.vue";
import BorrowedItemsTable from "@/Components/UserComponents/BorrowedItemsTable.vue";
import {
    BadgeCheck,
    ChevronLeft,
    ChevronRight,
    ClockAlert,
    GraduationCap,
    HandCoins,
    History,
    PackageCheck,
} from "lucide-vue-next";

const props = defineProps({
    user: { type: Object, default: () => ({}) },
    stats: { type: Object, default: () => ({}) },
    borrowedItems: { type: Array, default: () => [] },
    returnedItems: { type: Object, default: () => ({ data: [] }) },
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

const statCards = computed(() => [
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
        key: "returned",
        label: "Returned",
        value: props.stats?.returned_items ?? 0,
        hint: "Items you have given back",
        icon: PackageCheck,
        tile: "bg-[#0E6021]/10 text-[#0E6021]",
        accent: "text-[#0E6021]",
        target: "borrowing-history",
    },
]);

function scrollToSection(id) {
    document.getElementById(id)?.scrollIntoView({ behavior: "smooth", block: "start" });
}

function goToPage(url) {
    if (!url) return;

    router.get(url, {}, { preserveState: true, preserveScroll: true, only: ["returnedItems"] });
}
</script>

<template>
    <Head title="UP | Student Dashboard" />

    <UserPortalLayout home-route="student.dashboard" portal-label="Student Portal">
        <PageHeader title="Student Dashboard" />

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

                <div class="relative flex flex-col gap-5 lg:flex-row lg:items-end lg:justify-between">
                    <div class="min-w-0">
                        <p class="text-[11px] font-semibold uppercase tracking-[0.16em] text-white/70">
                            {{ greeting }}{{ firstName ? "," : "" }}
                        </p>
                        <h1 class="mt-1 truncate text-2xl font-bold sm:text-3xl">
                            {{ fullName }}
                        </h1>
                        <p class="mt-1 text-sm text-white/80">
                            {{ profile.primary_unit_division_department || "Your borrowed items and return history" }}
                        </p>
                    </div>

                    <div class="flex flex-wrap gap-2 text-[11px]">
                        <span
                            class="inline-flex items-center gap-1.5 rounded-full border border-white/25 bg-white/10 px-3 py-1.5 font-semibold"
                        >
                            <GraduationCap class="h-3.5 w-3.5" />
                            Student
                        </span>

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
                    </div>
                </div>
            </motion.section>

            <!-- STATS -->
            <div class="grid grid-cols-1 gap-3 sm:grid-cols-3">
                <motion.button
                    v-for="(card, index) in statCards"
                    :key="card.key"
                    type="button"
                    class="rounded-xl border border-gray-200 bg-white p-4 text-left shadow-sm transition-colors hover:border-[#005740]/30 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[#005740]/30"
                    :initial="{ opacity: 0, y: 14 }"
                    :animate="{ opacity: 1, y: 0 }"
                    :transition="{ delay: 0.06 + index * 0.05, type: 'spring', stiffness: 260, damping: 26 }"
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

                        <span class="flex h-10 w-10 items-center justify-center rounded-lg" :class="card.tile">
                            <component :is="card.icon" class="h-5 w-5" />
                        </span>
                    </div>
                </motion.button>
            </div>

            <!-- BORROWED NOW -->
            <section
                id="borrowed-items"
                class="scroll-mt-24 overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm"
            >
                <header class="flex items-center gap-2 border-b border-gray-100 px-4 py-3">
                    <span class="flex h-9 w-9 items-center justify-center rounded-lg bg-[#1d4ed8]/10 text-[#1d4ed8]">
                        <HandCoins class="h-4 w-4" />
                    </span>
                    <div>
                        <h2 class="text-sm font-semibold text-[#1b1b1b]">Borrowed items</h2>
                        <p class="text-[11px] text-gray-500">
                            {{ borrowedItems.length }} item(s) still out on loan to you
                        </p>
                    </div>
                </header>

                <BorrowedItemsTable
                    :items="borrowedItems"
                    empty-text="You have no borrowed items right now."
                />
            </section>

            <!-- HISTORY -->
            <section
                id="borrowing-history"
                class="scroll-mt-24 overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm"
            >
                <header class="flex items-center gap-2 border-b border-gray-100 px-4 py-3">
                    <span class="flex h-9 w-9 items-center justify-center rounded-lg bg-[#0E6021]/10 text-[#0E6021]">
                        <History class="h-4 w-4" />
                    </span>
                    <div>
                        <h2 class="text-sm font-semibold text-[#1b1b1b]">Borrowing history</h2>
                        <p class="text-[11px] text-gray-500">
                            {{ returnedItems.total ?? 0 }} item(s) returned
                        </p>
                    </div>
                </header>

                <BorrowedItemsTable
                    :items="returnedItems.data ?? []"
                    returned
                    empty-text="Items you return will be listed here."
                />

                <div
                    v-if="returnedItems.last_page > 1"
                    class="flex items-center justify-between border-t border-gray-100 px-4 py-3"
                >
                    <p class="text-[12px] text-gray-400">
                        Page
                        <span class="font-semibold text-gray-700">{{ returnedItems.current_page }}</span>
                        of
                        <span class="font-semibold text-gray-700">{{ returnedItems.last_page }}</span>
                    </p>

                    <div class="flex items-center gap-2">
                        <button
                            v-for="nav in [
                                { label: 'Previous', url: returnedItems.prev_page_url, icon: ChevronLeft },
                                { label: 'Next', url: returnedItems.next_page_url, icon: ChevronRight },
                            ]"
                            :key="nav.label"
                            type="button"
                            :disabled="!nav.url"
                            class="flex items-center gap-1.5 rounded-lg border border-gray-200 px-4 py-2 text-[12px] font-medium transition-colors"
                            :class="
                                nav.url
                                    ? 'text-gray-700 hover:border-[#005740] hover:bg-[#005740] hover:text-white'
                                    : 'cursor-not-allowed bg-gray-50 text-gray-300'
                            "
                            @click="goToPage(nav.url)"
                        >
                            <component :is="nav.icon" v-if="nav.label === 'Previous'" class="h-3.5 w-3.5" />
                            {{ nav.label }}
                            <component :is="nav.icon" v-if="nav.label === 'Next'" class="h-3.5 w-3.5" />
                        </button>
                    </div>
                </div>
            </section>
        </div>
    </UserPortalLayout>
</template>
