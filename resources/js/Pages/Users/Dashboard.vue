<script setup>
import { computed, onBeforeUnmount, ref, watch } from "vue";
import { Head, router } from "@inertiajs/vue3";
import { motion } from "motion-v";
import UserPortalLayout from "@/Layouts/UserPortalLayout.vue";
import PageHeader from "@/Components/PageHeader.vue";
import ItemTable from "@/Components/UserComponents/ItemTable.vue";
import BorrowedItemsTable from "@/Components/UserComponents/BorrowedItemsTable.vue";
import {
    ArrowRight,
    ArrowUpRight,
    BadgeCheck,
    CircleCheck,
    ClipboardList,
    ClockAlert,
    FileText,
    HandCoins,
    Hourglass,
    Mail,
    Package,
    Phone,
    Search,
    Trash2,
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

const assignedCount = computed(() => props.stats?.assigned_items ?? 0);
const pendingCount = computed(() => props.stats?.pending_items ?? 0);
const approvedCount = computed(() => props.stats?.approved_items ?? 0);

// Approved / pending / rejected as shares of the assigned items, for the bar
// on the left. Empty parts are left out.
const approvalSegments = computed(() => {
    const total = assignedCount.value;

    if (!total) return [];

    return [
        { key: "approved", label: "Approved", count: approvedCount.value, tone: "bg-[#005740] text-white" },
        { key: "pending", label: "Pending", count: pendingCount.value, tone: "bg-amber-300 text-gray-900" },
        { key: "rejected", label: "Rejected", count: props.stats?.rejected_items ?? 0, tone: "bg-[#D71D1D] text-white" },
    ]
        .filter((segment) => segment.count > 0)
        .map((segment) => ({
            ...segment,
            percent: Math.round((segment.count / total) * 100),
        }));
});

// Share of assigned property already confirmed.
const approvedShare = computed(() =>
    assignedCount.value
        ? Math.round((approvedCount.value / assignedCount.value) * 100)
        : 0,
);

// The numbers on the right of the overview. One with a "status" also filters
// the assigned list when clicked; "highlight" adds an amber dot.
const stats_cards = computed(() => [
    {
        key: "assigned",
        label: "Assigned items",
        value: assignedCount.value,
        hint: "Property under your custody",
        icon: Package,
        target: "assigned-items",
        status: "",
        action: "Show all items",
    },
    {
        key: "pending",
        label: "Waiting for you",
        value: pendingCount.value,
        hint: "Not yet approved or rejected",
        icon: Hourglass,
        target: "assigned-items",
        status: "pending",
        action: "Review pending items",
        highlight: pendingCount.value > 0,
    },
    {
        key: "approved",
        label: "Approved",
        value: approvedCount.value,
        hint: approvedShare.value + "% of your items confirmed",
        icon: CircleCheck,
        target: "assigned-items",
        status: "approved",
        action: "Show approved items",
    },
    {
        key: "disposed",
        label: "Disposed",
        value: props.stats?.disposed_items ?? 0,
        hint: "No longer in your custody",
        icon: Trash2,
        target: "assigned-items",
        status: "disposed",
        action: "Show disposed items",
    },
    // {
    //     key: "borrowed",
    //     label: "Borrowed now",
    //     value: props.stats?.borrowed_items ?? 0,
    //     hint: "Items you have on loan",
    //     icon: HandCoins,
    //     tile: "bg-[#005740]/10 text-[#005740]",
    //     accent: "text-[#005740]",
    //     target: "borrowed-items",
    // },
    // {
    //     key: "overdue",
    //     label: "Overdue",
    //     value: props.stats?.overdue_borrowings ?? 0,
    //     hint: "Past the return date",
    //     icon: ClockAlert,
    //     tile: "bg-[#D71D1D]/10 text-[#D71D1D]",
    //     accent: "text-[#D71D1D]",
    //     target: "borrowed-items",
    // },
]);

function scrollToSection(id) {
    document.getElementById(id)?.scrollIntoView({
        behavior: "smooth",
        block: "start",
    });
}

const isActiveStat = (stat) =>
    stat.status !== undefined && approvalStatus.value === stat.status;

function openStat(stat) {
    if (stat.status !== undefined) {
        approvalStatus.value = stat.status;
    }

    scrollToSection(stat.target);
}

// Assigned items: same query parameters the controller already validates.
const search = ref(props.filters?.search ?? "");
// "disposed" is a tab of its own; the server reads it as status=disposed.
const approvalStatus = ref(
    props.filters?.status === "disposed"
        ? "disposed"
        : (props.filters?.approval_status ?? ""),
);
const sortKey = ref(props.filters?.sort ?? null);
const sortDirection = ref(props.filters?.direction ?? "asc");

// Each filter wears its status colour: soft while idle, solid when chosen.
const statusTabs = computed(() => [
    {
        value: "",
        label: "All Asset/s",
        count: assignedCount.value,
        idle: "border-gray-200 bg-white text-gray-700 hover:border-gray-400",
        active: "border-gray-900 bg-gray-900 text-white",
        dot: "bg-gray-400",
    },
    {
        value: "pending",
        label: "Pending Asset/s",
        count: pendingCount.value,
        idle: "border-amber-200 bg-amber-50 text-amber-800 hover:border-amber-400",
        active: "border-amber-500 bg-amber-500 text-white",
        dot: "bg-amber-500",
    },
    {
        value: "approved",
        label: "Approved Asset/s",
        count: approvedCount.value,
        idle: "border-emerald-200 bg-emerald-50 text-emerald-800 hover:border-emerald-400",
        active: "border-[#005740] bg-[#005740] text-white",
        dot: "bg-[#005740]",
    },
    {
        value: "rejected",
        label: "Rejected Asset/s",
        count: props.stats?.rejected_items ?? 0,
        idle: "border-red-200 bg-red-50 text-red-700 hover:border-red-400",
        active: "border-[#D71D1D] bg-[#D71D1D] text-white",
        dot: "bg-[#D71D1D]",
    },
    {
        value: "disposed",
        label: "Disposed Asset/s",
        count: props.stats?.disposed_items ?? 0,
        idle: "border-slate-200 bg-slate-100 text-slate-700 hover:border-slate-400",
        active: "border-slate-600 bg-slate-600 text-white",
        dot: "bg-slate-500",
    },
]);

const isSearching = ref(false);
let searchTimeout = null;

function fetchItems(extra = {}) {
    isSearching.value = true;

    router.get(
        route("user.dashboard"),
        {
            search: search.value || undefined,
            approval_status:
                approvalStatus.value && approvalStatus.value !== "disposed"
                    ? approvalStatus.value
                    : undefined,
            status: approvalStatus.value === "disposed" ? "disposed" : undefined,
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

function reviewPending() {
    approvalStatus.value = "pending";
    scrollToSection("assigned-items");
}

watch(approvalStatus, () => fetchItems());

onBeforeUnmount(() => {
    if (searchTimeout) clearTimeout(searchTimeout);
});

const borrowedItems = computed(() => props.borrowedItems ?? []);
</script>

<template>
    <Head title="UP | My Dashboard" />

    <UserPortalLayout wide tinted>
        <PageHeader title="My Dashboard" />

        <div class="w-full h-full space-y-5">
            <!-- OVERVIEW: who you are on the left, your numbers on the right. -->
            <motion.section
                class="mt-6 flex flex-col gap-8 px-1 xl:flex-row xl:items-end xl:justify-between"
                :initial="{ opacity: 0, y: 12 }"
                :animate="{ opacity: 1, y: 0 }"
                :transition="{ type: 'spring', stiffness: 260, damping: 26 }"
            >
                <div class="min-w-0 flex-1">
                    <p class="text-sm text-gray-500">
                        {{ greeting }}{{ firstName ? "," : "" }}
                    </p>
                    <h1 class="mt-1 truncate text-4xl font-light tracking-tight text-gray-900 sm:text-5xl">
                        {{ fullName }}
                    </h1>
                    <p class="mt-2 text-sm text-gray-600">
                        {{ profile.primary_unit_division_department || "Accountability dashboard" }}
                    </p>

                    <div class="mt-3 flex flex-wrap items-center gap-x-5 gap-y-1.5 text-xs text-gray-500">
                        <span v-if="profile.employee_number" class="inline-flex items-center gap-1.5">
                            <BadgeCheck class="h-3.5 w-3.5" />
                            {{ profile.employee_number }}
                        </span>

                        <span v-if="user?.email" class="inline-flex items-center gap-1.5">
                            <Mail class="h-3.5 w-3.5" />
                            {{ user.email }}
                        </span>

                        <span v-if="profile.contact_number" class="inline-flex items-center gap-1.5">
                            <Phone class="h-3.5 w-3.5" />
                            {{ profile.contact_number }}
                        </span>

                        <span class="inline-flex items-center gap-1.5">
                            <FileText class="h-3.5 w-3.5" />
                            {{ stats?.receipts ?? 0 }} ICS/PAR
                        </span>

                            <!-- <button
                                type="button"
                                class="inline-flex items-center gap-1.5 rounded-full border border-white/25 bg-white/10 px-3 py-1.5 text-[11px] font-semibold text-white transition-colors hover:bg-white/20"
                                @click="scrollToSection('borrowed-items')"
                            >
                                <HandCoins class="h-3.5 w-3.5" />
                                My borrowed items
                            </button> -->
                    </div>

                    <!-- APPROVAL BREAKDOWN: each part as wide as its share. -->
                    <div v-if="approvalSegments.length" class="mt-6 flex max-w-2xl gap-1.5">
                        <div
                            v-for="segment in approvalSegments"
                            :key="segment.key"
                            class="min-w-[5.5rem]"
                            :style="{ flexGrow: segment.count, flexBasis: 0 }"
                        >
                            <p class="mb-1.5 text-xs text-gray-500">{{ segment.label }}</p>
                            <div
                                :class="[
                                    'flex h-10 items-center rounded-full px-4 text-xs font-medium tabular-nums',
                                    segment.tone,
                                ]"
                            >
                                {{ segment.percent }}%
                            </div>
                        </div>
                    </div>
                </div>

                <!-- NUMBERS: each one is a button that filters the list below. -->
                <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-4 xl:w-auto">
                    <motion.button
                        v-for="(stat, index) in stats_cards"
                        :key="stat.key"
                        type="button"
                        :class="[
                            'group relative flex min-w-[11rem] flex-col rounded-2xl border px-5 pb-4 pt-4 text-left transition-all duration-200 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[#005740]/40 focus-visible:ring-offset-2',
                            isActiveStat(stat)
                                ? 'border-gray-900 bg-gray-900 text-white shadow-lg shadow-gray-900/20'
                                : 'border-gray-900/10 bg-white/70 text-gray-900 backdrop-blur hover:border-gray-900/25 hover:bg-white hover:shadow-md',
                        ]"
                        :aria-pressed="isActiveStat(stat)"
                        :title="stat.hint"
                        :initial="{ opacity: 0, y: 8 }"
                        :animate="{ opacity: 1, y: 0 }"
                        :transition="{ delay: 0.08 + index * 0.05, type: 'spring', stiffness: 260, damping: 26 }"
                        :while-hover="isActiveStat(stat) ? undefined : { y: -3 }"
                        :while-press="{ scale: 0.97 }"
                        @click="openStat(stat)"
                    >
                        <div class="flex items-center justify-between gap-3">
                            <span
                                :class="[
                                    'flex items-center gap-1.5 text-xs font-medium',
                                    isActiveStat(stat) ? 'text-white/75' : 'text-gray-500',
                                ]"
                            >
                                <component :is="stat.icon" class="h-3.5 w-3.5" />
                                {{ stat.label }}
                                <span
                                    v-if="stat.highlight"
                                    class="h-1.5 w-1.5 animate-pulse rounded-full bg-amber-500"
                                    aria-hidden="true"
                                ></span>
                            </span>

                            <span
                                :class="[
                                    'flex h-7 w-7 items-center justify-center rounded-full border transition-all duration-200',
                                    isActiveStat(stat)
                                        ? 'rotate-45 border-white/30 bg-white/15 text-white'
                                        : 'border-gray-900/15 text-gray-600 group-hover:rotate-45 group-hover:border-gray-900 group-hover:bg-gray-900 group-hover:text-white',
                                ]"
                                aria-hidden="true"
                            >
                                <ArrowUpRight class="h-3.5 w-3.5" />
                            </span>
                        </div>

                        <p class="mt-3 text-5xl font-light leading-none tabular-nums">
                            {{ stat.value }}
                        </p>

                        <p
                            :class="[
                                'mt-3 text-[11px]',
                                isActiveStat(stat) ? 'text-white/70' : 'text-gray-400 group-hover:text-gray-600',
                            ]"
                        >
                            {{ isActiveStat(stat) ? "Showing in the list" : stat.action ?? "View in the list" }}
                        </p>
                    </motion.button>
                </div>
            </motion.section>

            <!-- NEEDS ATTENTION -->
            <motion.div
                v-if="pendingCount > 0"
                class="flex flex-col gap-3 rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 sm:flex-row sm:items-center sm:justify-between"
                :initial="{ opacity: 0, y: 8 }"
                :animate="{ opacity: 1, y: 0 }"
                :transition="{ delay: 0.05, type: 'spring', stiffness: 260, damping: 26 }"
            >
                <div class="flex items-start gap-3">
                    <span class="flex h-9 w-9 flex-shrink-0 items-center justify-center rounded-lg bg-amber-100 text-amber-700">
                        <Hourglass class="h-4 w-4" />
                    </span>
                    <div>
                        <p class="text-sm font-semibold text-amber-900">
                            {{ pendingCount }} item(s) waiting for your confirmation
                        </p>
                        <p class="text-xs text-amber-800/80">
                            Approve each item you received, or reject it if something is not right.
                        </p>
                    </div>
                </div>

                <button
                    type="button"
                    class="inline-flex items-center justify-center gap-1.5 rounded-lg bg-amber-600 px-3.5 py-2 text-xs font-semibold text-white transition-colors hover:bg-amber-700"
                    @click="reviewPending"
                >
                    Review pending
                    <ArrowRight class="h-3.5 w-3.5" />
                </button>
            </motion.div>

            <!-- ASSIGNED ITEMS -->
            <section
                id="assigned-items"
                class="scroll-mt-24 overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm"
            >
                <header class="border-b border-gray-100 px-4 py-4 sm:px-5">
                    <div class="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
                        <div class="flex items-center gap-3">
                            <span class="flex h-10 w-10 items-center justify-center rounded-xl bg-[#005740]/10 text-[#005740]">
                                <ClipboardList class="h-5 w-5" />
                            </span>
                            <div>
                                <h2 class="text-base font-semibold text-[#1b1b1b]">
                                    Assigned to me
                                </h2>
                                <p class="text-xs text-gray-500">
                                    {{ items.total ?? 0 }} item(s) shown
                                    <span v-if="isSearching" class="text-[#005740]">· updating…</span>
                                </p>
                            </div>
                        </div>

                        <div class="relative w-full lg:w-80">
                            <span class="pointer-events-none absolute inset-y-0 left-3 flex items-center text-gray-400">
                                <Search class="h-4 w-4" />
                            </span>
                            <input
                                v-model="search"
                                type="search"
                                placeholder="Search item, property number, room..."
                                class="h-10 w-full rounded-lg border border-gray-300 bg-white pl-9 pr-3 text-sm text-[#3B3B3B] focus:border-[#005740] focus:outline-none focus:ring-1 focus:ring-[#005740]"
                                @input="searchItems"
                            />
                        </div>
                    </div>

                    <!-- STATUS TABS -->
                    <div
                        class="mt-4 flex max-w-full flex-wrap gap-2"
                        role="tablist"
                        aria-label="Filter by status"
                    >
                        <button
                            v-for="tab in statusTabs"
                            :key="tab.value"
                            type="button"
                            role="tab"
                            :aria-selected="approvalStatus === tab.value"
                            :class="[
                                'inline-flex items-center gap-2 whitespace-nowrap rounded-full border px-3.5 py-1.5 text-xs font-semibold transition-all duration-150 active:scale-95',
                                approvalStatus === tab.value
                                    ? [tab.active, 'shadow-sm']
                                    : tab.idle,
                            ]"
                            @click="approvalStatus = tab.value"
                        >
                            <span
                                :class="[
                                    'h-1.5 w-1.5 rounded-full',
                                    approvalStatus === tab.value ? 'bg-white' : tab.dot,
                                ]"
                            ></span>
                            {{ tab.label }}
                            <span
                                :class="[
                                    'rounded-full px-1.5 text-[10px] tabular-nums',
                                    approvalStatus === tab.value
                                        ? 'bg-white/20 text-white'
                                        : 'bg-white/80 text-current',
                                ]"
                            >
                                {{ tab.count }}
                            </span>
                        </button>
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
            <!-- <section
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
            </section> -->
        </div>
    </UserPortalLayout>
</template>
