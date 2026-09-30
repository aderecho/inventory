<script setup>
import { ref, watch, onUnmounted } from "vue";
import { router } from "@inertiajs/vue3";

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

const fileLabel = (file, index) => {
    const path = file?.file_path ?? file?.name ?? "";
    const name = path.split("/").pop();
    return name || `File ${index + 1}`;
};

const fileIcon = (file) => {
    const name = (file?.file_path ?? "").toLowerCase();
    if (name.endsWith(".pdf")) return "fa-file-pdf";
    if (/\.(png|jpe?g|gif|webp|svg)$/.test(name)) return "fa-file-image";
    if (/\.(xlsx?|csv)$/.test(name)) return "fa-file-excel";
    if (/\.docx?$/.test(name)) return "fa-file-word";
    return "fa-file-lines";
};

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
                <tr class="bg-gray-50 border-b border-gray-200">
                    <th
                        @click="toggleSort('item_name')"
                        class="px-6 py-4 text-left text-xs font-semibold uppercase tracking-wider text-gray-500 font-['Poppins'] cursor-pointer select-none hover:text-[#005740] transition-colors"
                    >
                        <span class="inline-flex items-center gap-1">
                            Item
                            <i
                                class="fa-solid text-[9px]"
                                :class="
                                    sortKey === 'item_name'
                                        ? sortDirection === 'asc'
                                            ? 'fa-arrow-up text-[#005740]'
                                            : 'fa-arrow-down text-[#005740]'
                                        : 'fa-sort text-gray-300'
                                "
                            ></i>
                        </span>
                    </th>

                    <th
                        class="px-6 py-4 text-left text-xs font-semibold uppercase tracking-wider text-gray-500 font-['Poppins']"
                    >
                        Property Number
                    </th>

                    <th
                        class="px-6 py-4 text-left text-xs font-semibold uppercase tracking-wider text-gray-500 font-['Poppins']"
                    >
                        Unit Cost
                    </th>

                    <th
                        class="px-6 py-4 text-left text-xs font-semibold uppercase tracking-wider text-gray-500 font-['Poppins']"
                    >
                        Facility
                    </th>

                    <th
                        @click="toggleSort('date_assigned')"
                        class="px-6 py-4 text-left text-xs font-semibold uppercase tracking-wider text-gray-500 font-['Poppins'] cursor-pointer select-none hover:text-[#005740] transition-colors"
                    >
                        <span class="inline-flex items-center gap-1">
                            Date Assigned
                            <i
                                class="fa-solid text-[9px]"
                                :class="
                                    sortKey === 'date_assigned'
                                        ? sortDirection === 'asc'
                                            ? 'fa-arrow-up text-[#005740]'
                                            : 'fa-arrow-down text-[#005740]'
                                        : 'fa-sort text-gray-300'
                                "
                            ></i>
                        </span>
                    </th>

                    <th
                        @click="toggleSort('date_acquired')"
                        class="px-6 py-4 text-left text-xs font-semibold uppercase tracking-wider text-gray-500 font-['Poppins'] cursor-pointer select-none hover:text-[#005740] transition-colors"
                    >
                        <span class="inline-flex items-center gap-1">
                            Date Acquired
                            <i
                                class="fa-solid text-[9px]"
                                :class="
                                    sortKey === 'date_acquired'
                                        ? sortDirection === 'asc'
                                            ? 'fa-arrow-up text-[#005740]'
                                            : 'fa-arrow-down text-[#005740]'
                                        : 'fa-sort text-gray-300'
                                "
                            ></i>
                        </span>
                    </th>

                    <th
                        class="px-6 py-4 text-left text-xs font-semibold uppercase tracking-wider text-gray-500 font-['Poppins']"
                    >
                        Receipt
                    </th>

                    <th
                        class="px-6 py-4 text-left text-xs font-semibold uppercase tracking-wider text-gray-500 font-['Poppins']"
                    >
                        Approval
                    </th>

                    <th
                        class="px-6 py-4 text-center text-xs font-semibold uppercase tracking-wider text-gray-500 font-['Poppins']"
                    >
                        Actions
                    </th>
                </tr>
            </thead>

            <tbody>
                <tr
                    v-for="item in items.data"
                    :key="item.id"
                    class="border-b border-gray-100 hover:bg-gray-50 hover:shadow-sm transition-all duration-150"
                >
                    <td class="px-6 py-5 font-['Poppins']">
                        <div>
                            <p class="font-semibold text-gray-900 text-[13px]">
                                {{ item.item_name }}
                            </p>
                            <p class="text-[12px] text-gray-400 mt-0.5">
                                {{ item.serial_number ?? "No Serial Number" }}
                            </p>
                        </div>
                    </td>

                    <td class="px-6 py-5 font-['Poppins']">
                        <span class="text-[13px] font-medium text-gray-700">
                            {{ item.property_number }}
                        </span>
                    </td>

                    <td class="px-6 py-5 font-['Poppins']">
                        <span class="text-[13px] font-semibold text-[#850038]">
                            {{ formatCurrency(item.unit_cost) }}
                        </span>
                    </td>

                    <td class="px-6 py-5 font-['Poppins']">
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

                    <td class="px-6 py-5 font-['Poppins']">
                        <span class="text-[13px] text-gray-600">
                            {{
                                formatDate(
                                    item.latest_acknowledgement_item
                                        ?.acknowledgement_receipts?.par_date,
                                )
                            }}
                        </span>
                    </td>

                    <td class="px-6 py-5 font-['Poppins']">
                        <span class="text-[13px] text-gray-600">
                            {{ formatDate(item.date_acquired) }}
                        </span>
                    </td>

                    <td class="px-6 py-5 font-['Poppins']">
                        <span class="text-[13px] text-gray-600">
                            {{
                                item.latest_acknowledgement_item
                                    ?.acknowledgement_receipts?.category ??
                                "N/A"
                            }}
                        </span>
                    </td>

                    <td class="px-6 py-5 font-['Poppins']">
                        <span
                            :class="[
                                'inline-flex items-center px-2.5 py-1 rounded-full text-[11px] font-semibold border',
                                approvalBadge(item.approval_status).cls,
                            ]"
                        >
                            {{ approvalBadge(item.approval_status).label }}
                        </span>
                    </td>

                    <td class="px-6 py-5 font-['Poppins']">
                        <div class="flex items-center justify-center gap-2">
                            <button
                                @click="openModal(item)"
                                class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-[11px] font-semibold bg-[#850038]/10 text-[#850038] hover:bg-[#850038] hover:text-white transition-colors"
                            >
                                <i class="fa-solid fa-eye text-xs"></i>
                                View
                            </button>

                            <template v-if="isPending(item)">
                                <button
                                    @click="approveItem(item)"
                                    :disabled="processingId === item.id"
                                    class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-[11px] font-semibold bg-green-50 text-green-700 hover:bg-green-600 hover:text-white transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
                                >
                                    <i class="fa-solid fa-check text-xs"></i>
                                    Approve
                                </button>

                                <button
                                    @click="rejectItem(item)"
                                    :disabled="processingId === item.id"
                                    class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-[11px] font-semibold bg-red-50 text-red-700 hover:bg-red-600 hover:text-white transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
                                >
                                    <i class="fa-solid fa-xmark text-xs"></i>
                                    Reject
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
                            ? 'text-gray-700 hover:bg-[#850038] hover:text-white hover:border-[#850038]'
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
                            ? 'text-gray-700 hover:bg-[#850038] hover:text-white hover:border-[#850038]'
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
                v-if="isModalOpen"
                class="fixed inset-0 z-50 flex items-end sm:items-center justify-center bg-black/45 backdrop-blur-[2px] px-0 sm:px-4 font-['Poppins']"
                @click.self="closeModal"
            >
                <div
                    class="bg-white w-full sm:max-w-3xl sm:rounded-2xl rounded-t-2xl shadow-2xl max-h-[92vh] flex flex-col overflow-hidden"
                >
                    <div
                        class="relative shrink-0 px-5 sm:px-6 pt-5 pb-5 bg-gradient-to-r from-[#005740] via-[#006B4F] to-[#0E6021]"
                    >
                        <div
                            class="pointer-events-none absolute inset-0 opacity-20"
                            style="
                                background-image: radial-gradient(
                                    circle at 90% 10%,
                                    #fff 0,
                                    transparent 42%
                                );
                            "
                        ></div>

                        <div class="relative flex items-start gap-3">
                            <div
                                class="h-12 w-12 rounded-xl bg-white/15 border border-white/20 flex items-center justify-center shrink-0"
                            >
                                <i
                                    class="fa-solid fa-box-open text-white text-lg"
                                ></i>
                            </div>

                            <div class="min-w-0 flex-1">
                                <p
                                    class="text-[10px] uppercase tracking-[0.16em] text-white/70 font-semibold"
                                >
                                    Item details
                                </p>
                                <h3
                                    class="text-[18px] font-semibold text-white leading-snug truncate mt-0.5"
                                >
                                    {{
                                        selectedItem?.item_name ??
                                        "Untitled item"
                                    }}
                                </h3>
                                <p
                                    class="text-[12px] text-white/75 mt-1 truncate"
                                >
                                    {{
                                        selectedItem?.property_number ??
                                        "No property number"
                                    }}
                                    <span class="text-white/40 mx-1.5">·</span>
                                    {{
                                        selectedItem?.serial_number ??
                                        "No serial number"
                                    }}
                                </p>
                            </div>

                            <div class="flex items-center gap-2 shrink-0">
                                <span
                                    v-if="selectedItem"
                                    :class="[
                                        'hidden sm:inline-flex items-center px-2.5 py-1 rounded-full text-[11px] font-semibold border bg-white/95',
                                        approvalBadge(
                                            selectedItem.approval_status,
                                        ).cls,
                                    ]"
                                >
                                    {{
                                        approvalBadge(
                                            selectedItem.approval_status,
                                        ).label
                                    }}
                                </span>
                                <button
                                    @click="closeModal"
                                    class="w-8 h-8 flex items-center justify-center rounded-full text-white/80 hover:bg-white/15 hover:text-white transition-colors"
                                >
                                    <i class="fa-solid fa-xmark"></i>
                                </button>
                            </div>
                        </div>

                        <div
                            v-if="selectedItem"
                            class="relative mt-4 grid grid-cols-2 sm:grid-cols-4 gap-2"
                        >
                            <div
                                class="rounded-xl bg-white/10 border border-white/10 px-3 py-2.5"
                            >
                                <p
                                    class="text-[10px] uppercase tracking-wider text-white/60"
                                >
                                    Unit cost
                                </p>
                                <p
                                    class="text-[13px] font-semibold text-white mt-0.5"
                                >
                                    {{ formatCurrency(selectedItem.unit_cost) }}
                                </p>
                            </div>
                            <div
                                class="rounded-xl bg-white/10 border border-white/10 px-3 py-2.5"
                            >
                                <p
                                    class="text-[10px] uppercase tracking-wider text-white/60"
                                >
                                    Facility
                                </p>
                                <p
                                    class="text-[13px] font-semibold text-white mt-0.5 truncate"
                                >
                                    {{ selectedItem.room_name ?? "N/A" }}
                                </p>
                            </div>
                            <div
                                class="rounded-xl bg-white/10 border border-white/10 px-3 py-2.5"
                            >
                                <p
                                    class="text-[10px] uppercase tracking-wider text-white/60"
                                >
                                    Date assigned
                                </p>
                                <p
                                    class="text-[13px] font-semibold text-white mt-0.5"
                                >
                                    {{
                                        formatDate(
                                            receiptOf(selectedItem)?.par_date,
                                        )
                                    }}
                                </p>
                            </div>
                            <div
                                class="rounded-xl bg-white/10 border border-white/10 px-3 py-2.5"
                            >
                                <p
                                    class="text-[10px] uppercase tracking-wider text-white/60"
                                >
                                    Receipt
                                </p>
                                <p
                                    class="text-[13px] font-semibold text-white mt-0.5 truncate"
                                >
                                    {{
                                        receiptOf(selectedItem)?.category ??
                                        "N/A"
                                    }}
                                </p>
                            </div>
                        </div>
                    </div>

                    <div
                        v-if="selectedItem"
                        class="flex-1 overflow-y-auto px-5 sm:px-6 py-5 space-y-5"
                    >
                        <span
                            class="sm:hidden inline-flex items-center px-2.5 py-1 rounded-full text-[11px] font-semibold border"
                            :class="
                                approvalBadge(selectedItem.approval_status).cls
                            "
                        >
                            {{
                                approvalBadge(selectedItem.approval_status)
                                    .label
                            }}
                        </span>

                        <section>
                            <div class="flex items-center gap-2 mb-3">
                                <span
                                    class="w-1 h-4 rounded-full bg-[#005740]"
                                ></span>
                                <h4
                                    class="text-[11px] font-semibold uppercase tracking-wider text-[#005740]"
                                >
                                    Identification
                                </h4>
                            </div>
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                <div
                                    class="rounded-xl border border-gray-100 bg-gray-50/70 px-4 py-3"
                                >
                                    <p
                                        class="text-[10px] uppercase tracking-wider text-gray-400 font-semibold"
                                    >
                                        Item name
                                    </p>
                                    <p
                                        class="text-[13px] font-semibold text-gray-900 mt-1"
                                    >
                                        {{ selectedItem.item_name ?? "N/A" }}
                                    </p>
                                </div>

                                <div
                                    class="rounded-xl border border-gray-100 bg-gray-50/70 px-4 py-3"
                                >
                                    <p
                                        class="text-[10px] uppercase tracking-wider text-gray-400 font-semibold"
                                    >
                                        Property number
                                    </p>
                                    <div
                                        class="flex items-center justify-between gap-2 mt-1"
                                    >
                                        <p
                                            class="text-[13px] font-medium text-gray-800 truncate"
                                        >
                                            {{
                                                selectedItem.property_number ??
                                                "N/A"
                                            }}
                                        </p>
                                        <button
                                            type="button"
                                            class="shrink-0 text-[11px] text-[#005740] hover:text-[#0E6021]"
                                            @click="
                                                copyText(
                                                    'property',
                                                    selectedItem.property_number,
                                                )
                                            "
                                        >
                                            {{
                                                copiedField === "property"
                                                    ? "Copied"
                                                    : "Copy"
                                            }}
                                        </button>
                                    </div>
                                </div>

                                <div
                                    class="rounded-xl border border-gray-100 bg-gray-50/70 px-4 py-3"
                                >
                                    <p
                                        class="text-[10px] uppercase tracking-wider text-gray-400 font-semibold"
                                    >
                                        Serial number
                                    </p>
                                    <p
                                        class="text-[13px] font-medium text-gray-800 mt-1"
                                    >
                                        {{
                                            selectedItem.serial_number ??
                                            "No Serial Number"
                                        }}
                                    </p>
                                </div>

                                <div
                                    class="rounded-xl border border-gray-100 bg-gray-50/70 px-4 py-3"
                                >
                                    <p
                                        class="text-[10px] uppercase tracking-wider text-gray-400 font-semibold"
                                    >
                                        Date acquired
                                    </p>
                                    <p class="text-[13px] text-gray-700 mt-1">
                                        {{
                                            formatDate(
                                                selectedItem.date_acquired,
                                            )
                                        }}
                                    </p>
                                </div>
                            </div>
                        </section>

                        <section>
                            <div class="flex items-center gap-2 mb-3">
                                <span
                                    class="w-1 h-4 rounded-full bg-[#005740]"
                                ></span>
                                <h4
                                    class="text-[11px] font-semibold uppercase tracking-wider text-[#005740]"
                                >
                                    Procurement
                                </h4>
                            </div>
                            <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                                <div
                                    class="rounded-xl border border-gray-100 px-4 py-3"
                                >
                                    <p
                                        class="text-[10px] uppercase tracking-wider text-gray-400 font-semibold"
                                    >
                                        PO number
                                    </p>
                                    <p
                                        class="text-[13px] font-medium text-gray-800 mt-1"
                                    >
                                        {{ selectedItem.po_number ?? "N/A" }}
                                    </p>
                                </div>
                                <div
                                    class="rounded-xl border border-gray-100 px-4 py-3"
                                >
                                    <p
                                        class="text-[10px] uppercase tracking-wider text-gray-400 font-semibold"
                                    >
                                        PR number
                                    </p>
                                    <p
                                        class="text-[13px] font-medium text-gray-800 mt-1"
                                    >
                                        {{ selectedItem.pr_number ?? "N/A" }}
                                    </p>
                                </div>
                                <div
                                    class="rounded-xl border border-gray-100 px-4 py-3"
                                >
                                    <p
                                        class="text-[10px] uppercase tracking-wider text-gray-400 font-semibold"
                                    >
                                        Invoice
                                    </p>
                                    <p
                                        class="text-[13px] font-medium text-gray-800 mt-1"
                                    >
                                        {{ selectedItem.invoice ?? "N/A" }}
                                    </p>
                                </div>
                            </div>
                        </section>

                        <section>
                            <div class="flex items-center gap-2 mb-3">
                                <span
                                    class="w-1 h-4 rounded-full bg-[#005740]"
                                ></span>
                                <h4
                                    class="text-[11px] font-semibold uppercase tracking-wider text-[#005740]"
                                >
                                    Location
                                </h4>
                            </div>
                            <div
                                class="rounded-xl border border-gray-100 px-4 py-3.5"
                            >
                                <p
                                    class="text-[14px] font-semibold text-gray-900"
                                >
                                    {{ selectedItem.room_name ?? "N/A" }}
                                </p>
                                <p
                                    v-if="selectedItem.building_name"
                                    class="text-[12px] text-[#005740] font-medium mt-0.5"
                                >
                                    {{ selectedItem.building_name }}
                                </p>
                                <p
                                    class="text-[12px] text-gray-500 mt-1 leading-relaxed"
                                >
                                    {{
                                        selectedItem.room_description ??
                                        "No description available."
                                    }}
                                </p>
                            </div>
                        </section>

                        <section>
                            <div class="flex items-center gap-2 mb-3">
                                <span
                                    class="w-1 h-4 rounded-full bg-[#005740]"
                                ></span>
                                <h4
                                    class="text-[11px] font-semibold uppercase tracking-wider text-[#005740]"
                                >
                                    Attachments
                                </h4>
                            </div>

                            <div
                                v-if="
                                    selectedItem.latest_acknowledgement_item
                                        ?.files?.length
                                "
                                class="flex flex-col gap-2"
                            >
                                <a
                                    v-for="(file, index) in selectedItem
                                        .latest_acknowledgement_item.files"
                                    :key="file.id ?? index"
                                    :href="`/storage/${file.file_path}`"
                                    target="_blank"
                                    rel="noopener noreferrer"
                                    class="flex items-center justify-between gap-3 px-4 py-3 rounded-xl border border-gray-200 hover:border-[#005740] hover:bg-[#005740]/5 transition-colors group"
                                >
                                    <span
                                        class="flex items-center gap-3 min-w-0"
                                    >
                                        <span
                                            class="w-9 h-9 rounded-lg bg-[#005740]/10 text-[#005740] flex items-center justify-center shrink-0"
                                        >
                                            <i
                                                class="fa-solid text-sm"
                                                :class="fileIcon(file)"
                                            ></i>
                                        </span>
                                        <span class="min-w-0">
                                            <span
                                                class="block text-[13px] font-medium text-gray-800 group-hover:text-[#005740] truncate"
                                            >
                                                {{ fileLabel(file, index) }}
                                            </span>
                                            <span
                                                class="block text-[11px] text-gray-400"
                                            >
                                                Open in new tab
                                            </span>
                                        </span>
                                    </span>
                                    <i
                                        class="fa-solid fa-arrow-up-right-from-square text-[11px] text-gray-400 group-hover:text-[#005740]"
                                    ></i>
                                </a>
                            </div>

                            <div
                                v-else
                                class="rounded-xl border border-dashed border-gray-200 px-4 py-8 text-center"
                            >
                                <i
                                    class="fa-regular fa-folder-open text-gray-300 text-lg"
                                ></i>
                                <p class="text-[12px] text-gray-400 mt-2">
                                    No files attached to this item.
                                </p>
                            </div>
                        </section>
                    </div>

                    <div
                        class="shrink-0 px-5 sm:px-6 py-4 border-t border-gray-100 bg-white flex flex-col-reverse sm:flex-row items-stretch sm:items-center justify-between gap-2"
                    >
                        <button
                            @click="closeModal"
                            class="px-4 py-2 text-[12px] font-medium rounded-lg border border-gray-200 text-gray-700 hover:bg-gray-50 transition-colors"
                        >
                            Close
                        </button>

                        <div
                            v-if="isPending(selectedItem)"
                            class="flex items-center gap-2"
                        >
                            <button
                                @click="rejectItem(selectedItem)"
                                :disabled="processingId === selectedItem.id"
                                class="flex-1 sm:flex-none inline-flex items-center justify-center gap-1.5 px-4 py-2 rounded-lg text-[12px] font-semibold bg-red-50 text-red-700 hover:bg-red-600 hover:text-white transition-colors disabled:opacity-50"
                            >
                                <i class="fa-solid fa-xmark text-xs"></i>
                                Reject
                            </button>
                            <button
                                @click="approveItem(selectedItem)"
                                :disabled="processingId === selectedItem.id"
                                class="flex-1 sm:flex-none inline-flex items-center justify-center gap-1.5 px-4 py-2 rounded-lg text-[12px] font-semibold bg-[#005740] text-white hover:bg-[#0E6021] transition-colors disabled:opacity-50"
                            >
                                <i class="fa-solid fa-check text-xs"></i>
                                Approve
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </Teleport>
    </div>
</template>