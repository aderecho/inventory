<script setup>
import { ref, watch, onUnmounted } from "vue";
import { router } from "@inertiajs/vue3";
import { motion } from "motion-v";

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

const openModal = (item) => {
    selectedItem.value = item;
    isModalOpen.value = true;
};

const closeModal = () => {
    isModalOpen.value = false;
    selectedItem.value = null;
    copiedField.value = null;
};

const formatDate = (dateStr) => {
    return dateStr ? new Date(dateStr).toLocaleDateString() : "N/A";
};

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
    <div class="overflow-x-auto">
        <table class="w-full">
            <thead>
                <tr class="border-b border-[#00432f] bg-[#005740]">
                    <th
                        @click="toggleSort('item_name')"
                        class="px-6 py-4 text-left text-xs font-semibold uppercase tracking-wider text-white/90 font-['Poppins'] cursor-pointer select-none hover:text-white transition-colors"
                    >
                        <span class="inline-flex items-center gap-1">
                            Item
                            <i
                                class="fa-solid text-[9px]"
                                :class="
                                    sortKey === 'item_name'
                                        ? sortDirection === 'asc'
                                            ? 'fa-arrow-up text-white'
                                            : 'fa-arrow-down text-white'
                                        : 'fa-sort text-white/40'
                                "
                            ></i>
                        </span>
                    </th>

                    <th
                        class="px-6 py-4 text-left text-xs font-semibold uppercase tracking-wider text-white/90 font-['Poppins']"
                    >
                        Property Number
                    </th>

                    <th
                        class="px-6 py-4 text-left text-xs font-semibold uppercase tracking-wider text-white/90 font-['Poppins']"
                    >
                        Unit Cost
                    </th>

                    <th
                        class="px-6 py-4 text-left text-xs font-semibold uppercase tracking-wider text-white/90 font-['Poppins']"
                    >
                        Facility
                    </th>

                    <th
                        @click="toggleSort('date_assigned')"
                        class="px-6 py-4 text-left text-xs font-semibold uppercase tracking-wider text-white/90 font-['Poppins'] cursor-pointer select-none hover:text-white transition-colors"
                    >
                        <span class="inline-flex items-center gap-1">
                            Date Assigned
                            <i
                                class="fa-solid text-[9px]"
                                :class="
                                    sortKey === 'date_assigned'
                                        ? sortDirection === 'asc'
                                            ? 'fa-arrow-up text-white'
                                            : 'fa-arrow-down text-white'
                                        : 'fa-sort text-white/40'
                                "
                            ></i>
                        </span>
                    </th>

                    <th
                        @click="toggleSort('date_acquired')"
                        class="px-6 py-4 text-left text-xs font-semibold uppercase tracking-wider text-white/90 font-['Poppins'] cursor-pointer select-none hover:text-white transition-colors"
                    >
                        <span class="inline-flex items-center gap-1">
                            Date Acquired
                            <i
                                class="fa-solid text-[9px]"
                                :class="
                                    sortKey === 'date_acquired'
                                        ? sortDirection === 'asc'
                                            ? 'fa-arrow-up text-white'
                                            : 'fa-arrow-down text-white'
                                        : 'fa-sort text-white/40'
                                "
                            ></i>
                        </span>
                    </th>

                    <th
                        class="px-6 py-4 text-left text-xs font-semibold uppercase tracking-wider text-white/90 font-['Poppins']"
                    >
                        Receipt
                    </th>

                    <th
                        class="px-6 py-4 text-left text-xs font-semibold uppercase tracking-wider text-white/90 font-['Poppins']"
                    >
                        Approval
                    </th>

                    <th
                        class="px-6 py-4 text-center text-xs font-semibold uppercase tracking-wider text-white/90 font-['Poppins']"
                    >
                        Actions
                    </th>
                </tr>
            </thead>

            <tbody>
                <tr
                    v-for="item in items.data"
                    :key="item.id"
                    class="cursor-pointer border-b border-gray-100 transition-colors odd:bg-white even:bg-[#005740]/[0.02] hover:bg-[#005740]/[0.05]"
                    @click="openModal(item)"
                >
                    <td class="px-6 py-4 font-['Poppins']">
                        <div class="flex items-center gap-3">
                            <span
                                class="hidden h-9 w-9 flex-shrink-0 items-center justify-center rounded-lg bg-[#005740]/10 text-[#005740] sm:flex"
                            >
                                <i class="fa-solid fa-box text-[11px]"></i>
                            </span>

                            <div class="min-w-0">
                                <p class="truncate text-[13px] font-semibold text-gray-900">
                                    {{ item.item_name }}
                                </p>
                                <p class="mt-0.5 truncate text-[12px] text-gray-400">
                                    {{ item.serial_number ?? "No Serial Number" }}
                                </p>
                            </div>
                        </div>
                    </td>

                    <td class="px-6 py-4 font-['Poppins']">
                        <span class="text-[13px] font-medium text-gray-700">
                            {{ item.property_number }}
                        </span>
                    </td>

                    <td class="px-6 py-4 font-['Poppins']">
                        <span class="text-[13px] font-semibold text-[#005740]">
                            {{ formatCurrency(item.unit_cost) }}
                        </span>
                    </td>

                    <td class="px-6 py-4 font-['Poppins']">
                        <div>
                            <p class="font-semibold text-gray-900 text-[13px]">
                                {{ item.room_name ?? "N/A" }}
                            </p>
                            <p
                                class="text-[12px] text-gray-400 mt-0.5 line-clamp-2"
                            >
                                {{
                                    item.room_description ??
                                    "No description available."
                                }}
                            </p>
                        </div>
                    </td>

                    <td class="px-6 py-4 font-['Poppins']">
                        <span class="text-[13px] text-gray-600">
                            {{
                                formatDate(
                                    item.latest_acknowledgement_item
                                        ?.acknowledgement_receipts?.par_date,
                                )
                            }}
                        </span>
                    </td>

                    <td class="px-6 py-4 font-['Poppins']">
                        <span class="text-[13px] text-gray-600">
                            {{ formatDate(item.date_acquired) }}
                        </span>
                    </td>

                    <td class="px-6 py-4 font-['Poppins']">
                        <span class="text-[13px] text-gray-600">
                            {{
                                item.latest_acknowledgement_item
                                    ?.acknowledgement_receipts?.category ??
                                "N/A"
                            }}
                        </span>
                    </td>

                    <td class="px-6 py-4 font-['Poppins']">
                        <span
                            :class="[
                                'inline-flex items-center gap-1.5 rounded-full border px-2.5 py-1 text-[11px] font-semibold',
                                approvalBadge(item.approval_status).cls,
                            ]"
                        >
                            <span class="h-1.5 w-1.5 rounded-full bg-current"></span>
                            {{ approvalBadge(item.approval_status).label }}
                        </span>
                    </td>

                    <td class="px-6 py-4 font-['Poppins']" @click.stop>
                        <div class="flex items-center justify-center gap-1.5">
                            <button
                                type="button"
                                @click="openModal(item)"
                                class="flex h-8 w-8 items-center justify-center rounded-lg bg-[#005740]/10 text-[#005740] transition-colors hover:bg-[#005740] hover:text-white"
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
                                    class="flex h-8 w-8 items-center justify-center rounded-lg bg-green-50 text-green-700 transition-colors hover:bg-green-600 hover:text-white disabled:cursor-not-allowed disabled:opacity-50"
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
                                    class="flex h-8 w-8 items-center justify-center rounded-lg bg-red-50 text-red-700 transition-colors hover:bg-red-600 hover:text-white disabled:cursor-not-allowed disabled:opacity-50"
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
                    <td colspan="9" class="py-16 text-center font-['Poppins']">
                        <div class="flex flex-col items-center gap-2">
                            <div
                                class="w-14 h-14 rounded-full bg-gray-100 flex items-center justify-center"
                            >
                                <i
                                    class="fa-solid fa-box text-gray-400 text-xl"
                                ></i>
                            </div>
                            <h3
                                class="text-[14px] font-semibold text-gray-800 mt-2"
                            >
                                No Assigned Assets
                            </h3>
                            <p class="text-[13px] text-gray-400">
                                You currently have no inventory items assigned.
                            </p>
                        </div>
                    </td>
                </tr>
            </tbody>
        </table>

        <div
            v-if="items.last_page > 1"
            class="flex items-center justify-between px-6 py-4 border-t border-gray-100 font-['Poppins']"
        >
            <p class="text-[12px] text-gray-400">
                Page
                <span class="font-semibold text-gray-700">{{
                    items.current_page
                }}</span>
                of
                <span class="font-semibold text-gray-700">{{
                    items.last_page
                }}</span>
            </p>

            <div class="flex items-center gap-2">
                <button
                    @click="goToPage(items.prev_page_url)"
                    :disabled="!items.prev_page_url"
                    class="flex items-center gap-1.5 px-4 py-2 text-[12px] font-medium rounded-lg border border-gray-200 transition-colors duration-150"
                    :class="
                        items.prev_page_url
                            ? 'text-gray-700 hover:bg-[#005740] hover:text-white hover:border-[#005740]'
                            : 'text-gray-300 cursor-not-allowed bg-gray-50'
                    "
                >
                    <svg
                        class="w-3.5 h-3.5"
                        fill="none"
                        stroke="currentColor"
                        stroke-width="2"
                        viewBox="0 0 24 24"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            d="M15 19l-7-7 7-7"
                        />
                    </svg>
                    Previous
                </button>

                <button
                    @click="goToPage(items.next_page_url)"
                    :disabled="!items.next_page_url"
                    class="flex items-center gap-1.5 px-4 py-2 text-[12px] font-medium rounded-lg border border-gray-200 transition-colors duration-150"
                    :class="
                        items.next_page_url
                            ? 'text-gray-700 hover:bg-[#005740] hover:text-white hover:border-[#005740]'
                            : 'text-gray-300 cursor-not-allowed bg-gray-50'
                    "
                >
                    Next
                    <svg
                        class="w-3.5 h-3.5"
                        fill="none"
                        stroke="currentColor"
                        stroke-width="2"
                        viewBox="0 0 24 24"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            d="M9 5l7 7-7 7"
                        />
                    </svg>
                </button>
            </div>
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