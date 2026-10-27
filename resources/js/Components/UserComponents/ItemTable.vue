<script setup>
import { computed, ref, watch, onUnmounted } from "vue";
import { router } from "@inertiajs/vue3";
import { motion } from "motion-v";
import TableCheckbox from "@/Components/TableCheckbox.vue";

const props = defineProps({
    items: {
        type: Object,
        default: () => ({
            data: [],
            current_page: 1,
            last_page: 1,
            prev_page_url: null,
            next_page_url: null,
        }),
    },
    sortKey: { type: String, default: null },
    sortDirection: { type: String, default: "asc" },
});

const emit = defineEmits(["sort"]);

// ------------------------------------------------------------------
// COLUMN VISIBILITY (remembered in this browser)
// ------------------------------------------------------------------
// Item and Actions always show, so a row can still be found and acted on.
// The dates and the ICS/PAR number start hidden; the picker brings them back.
const HIDEABLE_COLUMNS = [
    { key: "description", label: "Description" },
    { key: "property_number", label: "Property Number" },
    { key: "unit_cost", label: "Unit Cost" },
    { key: "facility", label: "Facility" },
    { key: "date_assigned", label: "Date Assigned" },
    { key: "date_acquired", label: "Date Acquired" },
    { key: "receipt", label: "ICS/PAR" },
    { key: "approval", label: "Approval" },
];

const DEFAULT_HIDDEN_COLUMNS = ["date_assigned", "date_acquired", "receipt"];

const COLUMN_STORAGE_KEY = "table-columns:user-items";

function readHiddenColumns() {
    try {
        const saved = JSON.parse(window.localStorage.getItem(COLUMN_STORAGE_KEY));

        if (Array.isArray(saved)) {
            return saved.filter((key) => HIDEABLE_COLUMNS.some((col) => col.key === key));
        }
    } catch (error) {
        // Unreadable storage: fall back to the defaults below.
    }

    return [...DEFAULT_HIDDEN_COLUMNS];
}

const hiddenColumns = ref(readHiddenColumns());
const showColumnPicker = ref(false);

const isVisible = (key) => !hiddenColumns.value.includes(key);

// Item and Actions plus whatever is still shown.
const visibleColumnCount = computed(
    () => 2 + HIDEABLE_COLUMNS.length - hiddenColumns.value.length,
);

function persistColumns() {
    try {
        window.localStorage.setItem(COLUMN_STORAGE_KEY, JSON.stringify(hiddenColumns.value));
    } catch (error) {
        // Storage is optional; the picker still works for this page view.
    }
}

function toggleColumn(key) {
    hiddenColumns.value = isVisible(key)
        ? [...hiddenColumns.value, key]
        : hiddenColumns.value.filter((hidden) => hidden !== key);

    persistColumns();
}

function showAllColumns() {
    hiddenColumns.value = [];
    persistColumns();
}

const goToPage = (url) => {
    if (!url) return;
    router.get(url, {}, { preserveState: true, replace: true });
};

const isModalOpen = ref(false);
const selectedItem = ref(null);
const copiedField = ref(null);
const processingId = ref(null);

// Instant action tooltip: native title attributes wait about a second before
// showing, which feels broken on this table.
const actionTooltip = ref({ visible: false, text: "", x: 0, y: 0 });

function showActionTooltip(event, text) {
    actionTooltip.value = {
        visible: true,
        text,
        x: event.clientX,
        y: event.clientY,
    };
}

function moveActionTooltip(event) {
    if (!actionTooltip.value.visible) return;

    actionTooltip.value = {
        ...actionTooltip.value,
        x: event.clientX,
        y: event.clientY,
    };
}

function hideActionTooltip() {
    actionTooltip.value = { ...actionTooltip.value, visible: false };
}

// ------------------------------------------------------------------
// TABLE LOOK
// ------------------------------------------------------------------
const headCell =
    "border-b border-gray-200 bg-gray-50 px-4 py-3 font-semibold first:pl-5 last:pr-5";
// Cells read the row's hover so the pinned Actions cell follows it too.
const bodyCell =
    "border-b border-gray-100 bg-white px-4 py-3.5 align-middle transition-colors first:pl-5 last:pr-5 group-hover:bg-gray-50 group-last:border-b-0";

function sortIcon(key) {
    if (props.sortKey !== key) return "fa-sort text-gray-400";

    return props.sortDirection === "asc" ? "fa-arrow-up" : "fa-arrow-down";
}

// Two letters for the row's tile: "Desktop Computer" -> "DC".
function initials(name) {
    const words = String(name ?? "").trim().split(/\s+/).filter(Boolean);

    return (words[0]?.[0] ?? "?").toUpperCase() + (words[1]?.[0] ?? "").toUpperCase();
}

// The paginator's numbered links without its own Previous/Next entries.
const pageLinks = computed(() =>
    (props.items?.links ?? []).slice(1, -1).map((link, index) => ({
        key: index,
        url: link.url,
        label: link.label,
        active: link.active,
    })),
);

const openModal = (item) => {
    selectedItem.value = item;
    isModalOpen.value = true;
};

const closeModal = () => {
    isModalOpen.value = false;
    selectedItem.value = null;
    copiedField.value = null;
};

// "Oct 9, 2026"
const formatDate = (dateStr) =>
    dateStr
        ? new Date(dateStr).toLocaleDateString("en-US", {
              month: "short",
              day: "numeric",
              year: "numeric",
          })
        : "—";

const formatCurrency = (value) =>
    `₱${Number(value || 0).toLocaleString()}`;

const isPending = (item) =>
    !!item &&
    item.approval_status !== "approved" &&
    item.approval_status !== "rejected";

const receiptOf = (item) =>
    item?.latest_acknowledgement_item?.acknowledgement_receipts ?? null;

async function copyText(label, text) {
    if (!text || text === "N/A") return;
    try {
        await navigator.clipboard.writeText(String(text));
        copiedField.value = label;
        setTimeout(() => {
            if (copiedField.value === label) copiedField.value = null;
        }, 1400);
    } catch (e) {
        // ignore
    }
}

const toggleSort = (key) => {
    emit("sort", key);
};

const approvalBadge = (status) => {
    switch (status) {
        case "approved":
            return {
                label: "Approved",
                cls: "bg-green-50 text-green-700 border-green-200",
            };
        case "rejected":
            return {
                label: "Rejected",
                cls: "bg-red-50 text-red-700 border-red-200",
            };
        default:
            return {
                label: "Pending",
                cls: "bg-yellow-50 text-yellow-700 border-yellow-200",
            };
    }
};

function updateApprovalStatus(item, status) {
    if (processingId.value) return;

    processingId.value = item.id;

    router.patch(
        route("inventory_items.approval", item.id),
        { approval_status: status },
        {
            preserveScroll: true,
            preserveState: true,
            onSuccess: () => {
                if (selectedItem.value?.id === item.id) {
                    selectedItem.value = {
                        ...selectedItem.value,
                        approval_status: status,
                    };
                }
            },
            onFinish: () => {
                processingId.value = null;
            },
        },
    );
}

function approveItem(item) {
    updateApprovalStatus(item, "approved");
}

function rejectItem(item) {
    updateApprovalStatus(item, "rejected");
}

watch(isModalOpen, (open) => {
    const onKey = (e) => {
        if (e.key === "Escape") closeModal();
    };

    if (open) {
        document.addEventListener("keydown", onKey);
        document.body.style.overflow = "hidden";
    } else {
        document.removeEventListener("keydown", onKey);
        document.body.style.overflow = "";
    }
});

onUnmounted(() => {
    document.body.style.overflow = "";
});
</script>

<template>
    <div class="font-['Poppins']">
        <!-- TOP BAR: what is shown, and which columns -->
        <div
            class="flex items-center justify-between gap-3 border-b border-gray-100 px-4 py-3 sm:px-5"
        >
            <p class="text-xs text-gray-500">
                <template v-if="items.total">
                    Showing
                    <span class="font-semibold text-gray-900 tabular-nums">{{ items.from }}–{{ items.to }}</span>
                    of
                    <span class="font-semibold text-gray-900 tabular-nums">{{ items.total }}</span>
                    · click a row for details
                </template>
                <template v-else>No items to show</template>
            </p>

            <div class="relative">
                <button
                    type="button"
                    @click="showColumnPicker = !showColumnPicker"
                    :class="[
                        'flex items-center gap-2 rounded-full border px-3.5 py-1.5 text-xs font-medium transition-colors',
                        showColumnPicker
                            ? 'border-gray-900 bg-gray-900 text-white'
                            : 'border-gray-200 bg-white text-gray-600 hover:border-gray-300 hover:text-gray-900',
                    ]"
                >
                    <i class="fa-solid fa-sliders text-[11px]"></i>
                    Columns
                    <span
                        v-if="hiddenColumns.length"
                        :class="[
                            'rounded-full px-1.5 text-[10px] font-semibold',
                            showColumnPicker ? 'bg-white/20 text-white' : 'bg-gray-100 text-gray-600',
                        ]"
                    >
                        {{ hiddenColumns.length }} hidden
                    </span>
                </button>

                <!-- click-away layer -->
                <div
                    v-if="showColumnPicker"
                    class="fixed inset-0 z-20"
                    @click="showColumnPicker = false"
                ></div>

                <div
                    v-if="showColumnPicker"
                    class="absolute right-0 z-30 mt-2 w-64 overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-xl"
                >
                    <div
                        class="flex items-center justify-between border-b border-gray-100 px-4 py-2.5"
                    >
                        <p
                            class="text-[11px] font-semibold uppercase tracking-wider text-gray-500"
                        >
                            Show columns
                        </p>
                        <button
                            type="button"
                            @click="showAllColumns"
                            class="text-[11px] font-semibold text-[#005740] hover:underline"
                        >
                            Show all
                        </button>
                    </div>

                    <ul class="max-h-72 overflow-y-auto py-1">
                        <li
                            v-for="col in HIDEABLE_COLUMNS"
                            :key="col.key"
                            class="flex items-center gap-3 px-4 py-2 transition-colors hover:bg-gray-50"
                        >
                            <TableCheckbox
                                :checked="isVisible(col.key)"
                                tone="brand"
                                size="sm"
                                :title="'Show ' + col.label"
                                @toggle="toggleColumn(col.key)"
                            />
                            <button
                                type="button"
                                class="flex-1 text-left text-sm text-gray-700"
                                @click="toggleColumn(col.key)"
                            >
                                {{ col.label }}
                            </button>
                        </li>
                    </ul>
                </div>
            </div>
        </div>

        <!-- TABLE -->
        <div class="overflow-x-auto">
            <table class="w-full border-separate border-spacing-0 text-left">
                <thead>
                    <tr class="text-[11px] font-semibold uppercase tracking-wider text-gray-900">
                        <th :class="headCell">
                            <button
                                type="button"
                                class="inline-flex items-center gap-1.5 uppercase tracking-wider transition-colors hover:text-[#005740]"
                                :class="{ 'text-[#005740]': sortKey === 'item_name' }"
                                @click="toggleSort('item_name')"
                            >
                                Item
                                <i class="fa-solid text-[9px]" :class="sortIcon('item_name')"></i>
                            </button>
                        </th>
                        <th v-if="isVisible('description')" :class="headCell">Description</th>
                        <th v-if="isVisible('property_number')" :class="headCell">Property No.</th>
                        <th v-if="isVisible('unit_cost')" :class="[headCell, 'text-right']">Unit Cost</th>
                        <th v-if="isVisible('facility')" :class="headCell">Facility</th>
                        <th v-if="isVisible('date_assigned')" :class="headCell">
                            <button
                                type="button"
                                class="inline-flex items-center gap-1.5 uppercase tracking-wider transition-colors hover:text-[#005740]"
                                :class="{ 'text-[#005740]': sortKey === 'date_assigned' }"
                                @click="toggleSort('date_assigned')"
                            >
                                Date Assigned
                                <i class="fa-solid text-[9px]" :class="sortIcon('date_assigned')"></i>
                            </button>
                        </th>
                        <th v-if="isVisible('date_acquired')" :class="headCell">
                            <button
                                type="button"
                                class="inline-flex items-center gap-1.5 uppercase tracking-wider transition-colors hover:text-[#005740]"
                                :class="{ 'text-[#005740]': sortKey === 'date_acquired' }"
                                @click="toggleSort('date_acquired')"
                            >
                                Date Acquired
                                <i class="fa-solid text-[9px]" :class="sortIcon('date_acquired')"></i>
                            </button>
                        </th>
                        <th v-if="isVisible('receipt')" :class="headCell">ICS/PAR</th>
                        <th v-if="isVisible('approval')" :class="headCell">Approval</th>
                        <!-- Actions stay pinned to the right edge while the table scrolls. -->
                        <th :class="[headCell, 'sticky right-0 z-10 text-right shadow-[-12px_0_12px_-12px_rgba(15,23,42,0.18)]']">
                            Actions
                        </th>
                    </tr>
                </thead>

                <tbody>
                    <tr
                        v-for="item in items.data"
                        :key="item.id"
                        class="group cursor-pointer"
                        @click="openModal(item)"
                    >
                        <td :class="bodyCell">
                            <div class="flex items-center gap-3">
                                <span
                                    class="flex h-10 w-10 flex-shrink-0 items-center justify-center rounded-xl bg-[#005740]/[0.08] text-sm font-semibold text-[#005740] transition-colors group-hover:bg-[#005740] group-hover:text-white"
                                    aria-hidden="true"
                                >
                                    {{ initials(item.item_name) }}
                                </span>

                                <div class="min-w-0">
                                    <p class="max-w-[16rem] truncate text-[13px] font-semibold text-gray-900">
                                        {{ item.item_name }}
                                    </p>
                                    <p class="mt-0.5 max-w-[16rem] truncate text-[11px] text-gray-400">
                                        SN: {{ item.serial_number ?? "none" }}
                                    </p>
                                </div>
                            </div>
                        </td>

                        <td v-if="isVisible('description')" :class="[bodyCell, 'min-w-[14rem] max-w-sm']">
                            <p
                                class="line-clamp-2 text-[12px] leading-relaxed text-gray-600"
                                :title="item.description || undefined"
                            >
                                {{ item.description || "—" }}
                            </p>
                        </td>

                        <td v-if="isVisible('property_number')" :class="bodyCell">
                            <span class="whitespace-nowrap rounded-md bg-gray-100 px-2 py-1 font-mono text-[11px] font-medium text-gray-700">
                                {{ item.property_number || "—" }}
                            </span>
                        </td>

                        <td v-if="isVisible('unit_cost')" :class="[bodyCell, 'text-right']">
                            <span class="whitespace-nowrap text-[13px] font-semibold tabular-nums text-gray-900">
                                {{ formatCurrency(item.unit_cost) }}
                            </span>
                        </td>

                        <td v-if="isVisible('facility')" :class="[bodyCell, 'min-w-[11rem]']">
                            <p class="flex items-center gap-1.5 text-[13px] font-medium text-gray-900">
                                <i class="fa-solid fa-location-dot text-[10px] text-gray-400"></i>
                                {{ item.room_name ?? "N/A" }}
                            </p>
                            <p
                                v-if="item.building_name || item.room_description"
                                class="mt-0.5 line-clamp-1 pl-4 text-[11px] text-gray-400"
                            >
                                {{ item.building_name || item.room_description }}
                            </p>
                        </td>

                        <td v-if="isVisible('date_assigned')" :class="bodyCell">
                            <span class="whitespace-nowrap text-[13px] text-gray-600">
                                {{ formatDate(item.latest_acknowledgement_item?.acknowledgement_receipts?.par_date) }}
                            </span>
                        </td>

                        <td v-if="isVisible('date_acquired')" :class="bodyCell">
                            <span class="whitespace-nowrap text-[13px] text-gray-600">
                                {{ formatDate(item.date_acquired) }}
                            </span>
                        </td>

                        <td v-if="isVisible('receipt')" :class="bodyCell">
                            <span class="whitespace-nowrap font-mono text-[12px] text-gray-600">
                                {{ item.latest_acknowledgement_item?.acknowledgement_receipts?.category ?? "—" }}
                            </span>
                        </td>

                        <td v-if="isVisible('approval')" :class="bodyCell">
                            <span
                                :class="[
                                    'inline-flex items-center gap-1.5 whitespace-nowrap rounded-full border px-2.5 py-1 text-[11px] font-semibold',
                                    approvalBadge(item.approval_status).cls,
                                ]"
                            >
                                <span class="h-1.5 w-1.5 rounded-full bg-current"></span>
                                {{ approvalBadge(item.approval_status).label }}
                            </span>
                        </td>

                        <td
                            :class="[bodyCell, 'sticky right-0 z-10 shadow-[-12px_0_12px_-12px_rgba(15,23,42,0.18)]']"
                            @click.stop
                        >
                            <div class="flex items-center justify-end gap-1">
                                <button
                                    type="button"
                                    @click="openModal(item)"
                                    class="flex h-8 w-8 items-center justify-center rounded-full text-gray-500 transition-colors hover:bg-gray-900 hover:text-white"
                                    aria-label="View details"
                                    @mouseenter="showActionTooltip($event, 'View details')"
                                    @mousemove="moveActionTooltip"
                                    @mouseleave="hideActionTooltip"
                                >
                                    <i class="fa-solid fa-eye text-[11px]"></i>
                                </button>

                                <template v-if="isPending(item)">
                                    <button
                                        type="button"
                                        @click="approveItem(item)"
                                        :disabled="processingId === item.id"
                                        class="flex h-8 w-8 items-center justify-center rounded-full text-green-700 transition-colors hover:bg-green-600 hover:text-white disabled:cursor-not-allowed disabled:opacity-50"
                                        aria-label="Approve item"
                                        @mouseenter="showActionTooltip($event, 'Approve item')"
                                        @mousemove="moveActionTooltip"
                                        @mouseleave="hideActionTooltip"
                                    >
                                        <i class="fa-solid fa-check text-[11px]"></i>
                                    </button>

                                    <button
                                        type="button"
                                        @click="rejectItem(item)"
                                        :disabled="processingId === item.id"
                                        class="flex h-8 w-8 items-center justify-center rounded-full text-red-600 transition-colors hover:bg-red-600 hover:text-white disabled:cursor-not-allowed disabled:opacity-50"
                                        aria-label="Reject item"
                                        @mouseenter="showActionTooltip($event, 'Reject item')"
                                        @mousemove="moveActionTooltip"
                                        @mouseleave="hideActionTooltip"
                                    >
                                        <i class="fa-solid fa-xmark text-[11px]"></i>
                                    </button>
                                </template>
                            </div>
                        </td>
                    </tr>

                    <tr v-if="!items.data?.length">
                        <td :colspan="visibleColumnCount" class="px-6 py-20 text-center">
                            <div class="flex flex-col items-center">
                                <span class="flex h-14 w-14 items-center justify-center rounded-2xl bg-gray-100">
                                    <i class="fa-solid fa-box-open text-xl text-gray-400"></i>
                                </span>
                                <h3 class="mt-4 text-sm font-semibold text-gray-900">
                                    No assigned items here
                                </h3>
                                <p class="mt-1 text-[13px] text-gray-500">
                                    Try another status or search, or check back after property is issued to you.
                                </p>
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- PAGING -->
        <div
            v-if="items.last_page > 1"
            class="flex flex-col items-center justify-between gap-3 border-t border-gray-100 px-4 py-3 sm:flex-row sm:px-5"
        >
            <p class="text-xs text-gray-500">
                Page
                <span class="font-semibold text-gray-900 tabular-nums">{{ items.current_page }}</span>
                of
                <span class="font-semibold text-gray-900 tabular-nums">{{ items.last_page }}</span>
            </p>

            <nav class="flex items-center gap-1" aria-label="Pages">
                <button
                    type="button"
                    :disabled="!items.prev_page_url"
                    class="flex h-8 w-8 items-center justify-center rounded-full text-gray-600 transition-colors hover:bg-gray-100 disabled:cursor-not-allowed disabled:opacity-30 disabled:hover:bg-transparent"
                    aria-label="Previous page"
                    @click="goToPage(items.prev_page_url)"
                >
                    <i class="fa-solid fa-chevron-left text-[10px]"></i>
                </button>

                <template v-for="link in pageLinks" :key="link.key">
                    <span v-if="!link.url" class="px-1 text-xs text-gray-400">…</span>
                    <button
                        v-else
                        type="button"
                        :class="[
                            'flex h-8 min-w-[2rem] items-center justify-center rounded-full px-2 text-xs font-medium tabular-nums transition-colors',
                            link.active
                                ? 'bg-gray-900 text-white'
                                : 'text-gray-600 hover:bg-gray-100',
                        ]"
                        :aria-current="link.active ? 'page' : undefined"
                        @click="goToPage(link.url)"
                    >
                        {{ link.label }}
                    </button>
                </template>

                <button
                    type="button"
                    :disabled="!items.next_page_url"
                    class="flex h-8 w-8 items-center justify-center rounded-full text-gray-600 transition-colors hover:bg-gray-100 disabled:cursor-not-allowed disabled:opacity-30 disabled:hover:bg-transparent"
                    aria-label="Next page"
                    @click="goToPage(items.next_page_url)"
                >
                    <i class="fa-solid fa-chevron-right text-[10px]"></i>
                </button>
            </nav>
        </div>

        <Teleport to="body">
            <div
                v-if="actionTooltip.visible"
                class="pointer-events-none fixed z-[80] -translate-x-1/2 -translate-y-full rounded-md bg-gray-900 px-2 py-1 text-[11px] font-medium text-white shadow-lg"
                :style="{
                    left: actionTooltip.x + 'px',
                    top: actionTooltip.y - 12 + 'px',
                }"
            >
                {{ actionTooltip.text }}
                <span
                    class="absolute left-1/2 top-full h-2 w-2 -translate-x-1/2 -translate-y-1/2 rotate-45 bg-gray-900"
                ></span>
            </div>
        </Teleport>

        <Teleport to="body">
            <motion.div
                v-if="isModalOpen"
                class="fixed inset-0 z-50 flex items-end justify-center bg-black/45 p-0 backdrop-blur-[2px] sm:items-center sm:p-4"
                :initial="{ opacity: 0 }"
                :animate="{ opacity: 1 }"
                @click.self="closeModal"
            >
                <motion.div
                    class="flex max-h-[92vh] w-full flex-col overflow-hidden rounded-t-2xl bg-white shadow-2xl sm:max-w-4xl sm:rounded-2xl"
                    :initial="{ opacity: 0, y: 24, scale: 0.98 }"
                    :animate="{ opacity: 1, y: 0, scale: 1 }"
                    :transition="{ type: 'spring', stiffness: 300, damping: 30 }"
                >
                    <!-- HEADER -->
                    <div class="relative flex items-start gap-3 border-b border-gray-100 px-5 pb-4 pt-5 sm:px-6">
                        <span
                            class="absolute inset-x-0 top-0 h-1 bg-gradient-to-r from-[#003d2c] via-[#005740] to-[#0E6021]"
                        ></span>

                        <span
                            class="mt-0.5 flex h-11 w-11 flex-shrink-0 items-center justify-center rounded-xl bg-[#005740]/10 text-[#005740]"
                        >
                            <i class="fa-solid fa-box-open text-lg"></i>
                        </span>

                        <div class="min-w-0 flex-1">
                            <p class="text-[10px] font-bold uppercase tracking-[0.16em] text-[#005740]">
                                Item details
                            </p>
                            <h3 class="mt-0.5 truncate text-lg font-bold text-[#1b1b1b]">
                                {{ selectedItem?.item_name ?? "Untitled item" }}
                            </h3>

                            <div class="mt-1.5 flex flex-wrap items-center gap-1.5">
                                <span
                                    class="inline-flex items-center gap-1.5 rounded-md border border-gray-200 bg-gray-50 px-2 py-0.5 text-[11px] font-medium text-gray-600"
                                >
                                    {{ selectedItem?.property_number ?? "No property number" }}

                                    <button
                                        v-if="selectedItem"
                                        type="button"
                                        class="text-[#005740] transition-colors hover:text-[#0E6021]"
                                        :title="copiedField === 'property' ? 'Copied' : 'Copy property number'"
                                        @click="
                                            copyText(
                                                'property',
                                                selectedItem.property_number,
                                            )
                                        "
                                    >
                                        <i
                                            class="fa-solid text-[10px]"
                                            :class="
                                                copiedField === 'property'
                                                    ? 'fa-check'
                                                    : 'fa-copy'
                                            "
                                        ></i>
                                    </button>
                                </span>

                                <span
                                    class="inline-flex items-center rounded-md border border-gray-200 bg-gray-50 px-2 py-0.5 text-[11px] text-gray-500"
                                >
                                    SN: {{ selectedItem?.serial_number ?? "—" }}
                                </span>
                            </div>
                        </div>

                        <div class="flex flex-shrink-0 items-center gap-1.5">
                            <span
                                v-if="selectedItem"
                                class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-[10px] font-semibold sm:text-[11px]"
                                :class="approvalBadge(selectedItem.approval_status).cls"
                            >
                                <span class="h-1.5 w-1.5 rounded-full bg-current"></span>
                                {{ approvalBadge(selectedItem.approval_status).label }}
                            </span>

                            <button
                                type="button"
                                class="flex h-9 w-9 items-center justify-center rounded-lg text-gray-400 transition-colors hover:bg-gray-100 hover:text-gray-600"
                                @click="closeModal"
                            >
                                <i class="fa-solid fa-xmark"></i>
                            </button>
                        </div>
                    </div>

                    <!-- BODY -->
                    <div
                        v-if="selectedItem"
                        class="flex-1 overflow-y-auto bg-gray-50/70 px-5 py-5 sm:px-6"
                    >
                        <div class="grid gap-4 lg:grid-cols-[minmax(0,1fr)_17rem]">
                            <div class="space-y-4">
                                <!-- IDENTIFICATION -->
                                <section class="rounded-xl border border-gray-200 bg-white p-4">
                                    <header class="mb-3 flex items-center gap-2">
                                        <span
                                            class="flex h-7 w-7 items-center justify-center rounded-lg bg-[#005740]/10 text-[#005740]"
                                        >
                                            <i class="fa-solid fa-fingerprint text-[11px]"></i>
                                        </span>
                                        <h4 class="text-[11px] font-bold uppercase tracking-wider text-[#005740]">
                                            Identification
                                        </h4>
                                    </header>

                                    <dl class="grid grid-cols-1 gap-x-6 gap-y-3 text-sm sm:grid-cols-2">
                                        <div class="min-w-0">
                                            <dt class="text-[10px] font-semibold uppercase tracking-wider text-gray-400">
                                                Item name
                                            </dt>
                                            <dd class="mt-0.5 truncate font-semibold text-gray-900">
                                                {{ selectedItem.item_name ?? "N/A" }}
                                            </dd>
                                        </div>

                                        <div class="min-w-0">
                                            <dt class="text-[10px] font-semibold uppercase tracking-wider text-gray-400">
                                                Property number
                                            </dt>
                                            <dd class="mt-0.5 truncate font-medium text-gray-800">
                                                {{ selectedItem.property_number ?? "N/A" }}
                                            </dd>
                                        </div>

                                        <div class="min-w-0">
                                            <dt class="text-[10px] font-semibold uppercase tracking-wider text-gray-400">
                                                Serial number
                                            </dt>
                                            <dd class="mt-0.5 truncate font-medium text-gray-800">
                                                {{ selectedItem.serial_number ?? "No Serial Number" }}
                                            </dd>
                                        </div>

                                        <div class="min-w-0">
                                            <dt class="text-[10px] font-semibold uppercase tracking-wider text-gray-400">
                                                Date acquired
                                            </dt>
                                            <dd class="mt-0.5 font-medium text-gray-700">
                                                {{ formatDate(selectedItem.date_acquired) }}
                                            </dd>
                                        </div>
                                    </dl>
                                </section>

                                <!-- PROCUREMENT -->
                                <section class="rounded-xl border border-gray-200 bg-white p-4">
                                    <header class="mb-3 flex items-center gap-2">
                                        <span
                                            class="flex h-7 w-7 items-center justify-center rounded-lg bg-[#005740]/10 text-[#005740]"
                                        >
                                            <i class="fa-solid fa-file-invoice-dollar text-[11px]"></i>
                                        </span>
                                        <h4 class="text-[11px] font-bold uppercase tracking-wider text-[#005740]">
                                            Purchase
                                        </h4>
                                    </header>

                                    <dl class="grid grid-cols-1 gap-3 text-sm sm:grid-cols-3">
                                        <div>
                                            <dt class="text-[10px] font-semibold uppercase tracking-wider text-gray-400">
                                                PO number
                                            </dt>
                                            <dd class="mt-0.5 truncate font-medium text-gray-800">
                                                {{ selectedItem.po_number ?? "N/A" }}
                                            </dd>
                                        </div>
                                        <div>
                                            <dt class="text-[10px] font-semibold uppercase tracking-wider text-gray-400">
                                                PR number
                                            </dt>
                                            <dd class="mt-0.5 truncate font-medium text-gray-800">
                                                {{ selectedItem.pr_number ?? "N/A" }}
                                            </dd>
                                        </div>
                                        <div>
                                            <dt class="text-[10px] font-semibold uppercase tracking-wider text-gray-400">
                                                Invoice
                                            </dt>
                                            <dd class="mt-0.5 truncate font-medium text-gray-800">
                                                {{ selectedItem.invoice ?? "N/A" }}
                                            </dd>
                                        </div>
                                    </dl>
                                </section>

                                <!-- LOCATION -->
                                <section class="rounded-xl border border-gray-200 bg-white p-4">
                                    <header class="mb-3 flex items-center gap-2">
                                        <span
                                            class="flex h-7 w-7 items-center justify-center rounded-lg bg-[#005740]/10 text-[#005740]"
                                        >
                                            <i class="fa-solid fa-location-dot text-[11px]"></i>
                                        </span>
                                        <h4 class="text-[11px] font-bold uppercase tracking-wider text-[#005740]">
                                            Location
                                        </h4>
                                    </header>

                                    <div class="flex items-start gap-3">
                                        <span
                                            class="flex h-10 w-10 flex-shrink-0 items-center justify-center rounded-xl bg-[#005740]/10 text-[#005740]"
                                        >
                                            <i class="fa-solid fa-building text-sm"></i>
                                        </span>

                                        <div class="min-w-0">
                                            <p class="font-semibold text-gray-900">
                                                {{ selectedItem.room_name ?? "N/A" }}
                                            </p>
                                            <p
                                                v-if="selectedItem.building_name"
                                                class="mt-0.5 text-[12px] font-medium text-[#005740]"
                                            >
                                                {{ selectedItem.building_name }}
                                            </p>
                                            <p class="mt-1 text-[12px] leading-relaxed text-gray-500">
                                                {{
                                                    selectedItem.room_description ??
                                                    "No description available."
                                                }}
                                            </p>
                                        </div>
                                    </div>
                                </section>
                            </div>

                            <!-- SUMMARY -->
                            <aside class="space-y-4">
                                <div
                                    class="relative overflow-hidden rounded-xl bg-gradient-to-br from-[#005740] to-[#003d2c] p-4 text-white shadow-sm"
                                >
                                    <div
                                        class="pointer-events-none absolute -right-8 -top-8 h-24 w-24 rounded-full bg-white/10"
                                    ></div>

                                    <p class="text-[10px] uppercase tracking-wider text-white/60">
                                        Unit cost
                                    </p>
                                    <p class="mt-1 text-2xl font-bold">
                                        {{ formatCurrency(selectedItem.unit_cost) }}
                                    </p>

                                    <dl class="mt-4 space-y-2.5 text-[12px]">
                                        <div class="flex items-center justify-between gap-3 border-t border-white/15 pt-2.5">
                                            <dt class="text-white/60">Facility</dt>
                                            <dd class="truncate text-right font-semibold">
                                                {{ selectedItem.room_name ?? "N/A" }}
                                            </dd>
                                        </div>
                                        <div class="flex items-center justify-between gap-3">
                                            <dt class="text-white/60">Date assigned</dt>
                                            <dd class="font-semibold">
                                                {{
                                                    formatDate(
                                                        receiptOf(selectedItem)?.par_date,
                                                    )
                                                }}
                                            </dd>
                                        </div>
                                        <div class="flex items-center justify-between gap-3">
                                            <dt class="text-white/60">Receipt</dt>
                                            <dd class="truncate text-right font-semibold">
                                                {{
                                                    receiptOf(selectedItem)?.category ??
                                                    "N/A"
                                                }}
                                            </dd>
                                        </div>
                                    </dl>
                                </div>

                                <div
                                    class="rounded-xl border border-[#005740]/15 bg-[#005740]/[0.04] p-4"
                                >
                                    <p class="text-[10px] font-semibold uppercase tracking-wider text-[#005740]">
                                        Accountability
                                    </p>
                                    <p class="mt-1 text-[12px] leading-relaxed text-gray-600">
                                        This property sits under your accountability.
                                        Approving confirms you hold it in the
                                        condition recorded by the inspection.
                                    </p>
                                </div>
                            </aside>
                        </div>
                    </div>

                    <!-- FOOTER -->
                    <div
                        class="flex flex-col-reverse items-stretch justify-between gap-2 border-t border-gray-100 bg-white px-5 py-3.5 sm:flex-row sm:items-center sm:px-6"
                    >
                        <button
                            type="button"
                            class="rounded-lg px-4 py-2 text-xs font-medium text-gray-600 transition-colors hover:bg-gray-100"
                            @click="closeModal"
                        >
                            Close
                        </button>

                        <div v-if="isPending(selectedItem)" class="flex items-center gap-2">
                            <button
                                type="button"
                                class="flex flex-1 items-center justify-center gap-1.5 rounded-lg border border-red-200 px-4 py-2 text-xs font-semibold text-red-600 transition-colors hover:bg-red-50 disabled:cursor-not-allowed disabled:opacity-50 sm:flex-none"
                                :disabled="processingId === selectedItem.id"
                                @click="rejectItem(selectedItem)"
                            >
                                <i class="fa-solid fa-xmark text-[11px]"></i>
                                Reject
                            </button>

                            <button
                                type="button"
                                class="flex flex-1 items-center justify-center gap-1.5 rounded-lg bg-[#005740] px-4 py-2 text-xs font-semibold text-white transition-colors hover:bg-[#00432f] disabled:cursor-not-allowed disabled:opacity-50 sm:flex-none"
                                :disabled="processingId === selectedItem.id"
                                @click="approveItem(selectedItem)"
                            >
                                <i class="fa-solid fa-check text-[11px]"></i>
                                Approve
                            </button>
                        </div>
                    </div>
                </motion.div>
            </motion.div>
        </Teleport>
    </div>
</template>