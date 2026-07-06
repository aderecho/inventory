<script setup>
import { ref } from "vue";
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

const openModal = (item) => {
    selectedItem.value = item;
    isModalOpen.value = true;
};

const closeModal = () => {
    isModalOpen.value = false;
    selectedItem.value = null;
};

const formatDate = (dateStr) => {
    return dateStr ? new Date(dateStr).toLocaleDateString() : "N/A";
};

const toggleSort = (key) => {
    emit("sort", key);
};
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
                                :class="sortKey === 'item_name' ? (sortDirection === 'asc' ? 'fa-arrow-up text-[#005740]' : 'fa-arrow-down text-[#005740]') : 'fa-sort text-gray-300'"
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
                        @click="toggleSort('date_assigned')"
                        class="px-6 py-4 text-left text-xs font-semibold uppercase tracking-wider text-gray-500 font-['Poppins'] cursor-pointer select-none hover:text-[#005740] transition-colors"
                    >
                        <span class="inline-flex items-center gap-1">
                            Date Assigned
                            <i
                                class="fa-solid text-[9px]"
                                :class="sortKey === 'date_assigned' ? (sortDirection === 'asc' ? 'fa-arrow-up text-[#005740]' : 'fa-arrow-down text-[#005740]') : 'fa-sort text-gray-300'"
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
                                :class="sortKey === 'date_acquired' ? (sortDirection === 'asc' ? 'fa-arrow-up text-[#005740]' : 'fa-arrow-down text-[#005740]') : 'fa-sort text-gray-300'"
                            ></i>
                        </span>
                    </th>

                    <th
                        class="px-6 py-4 text-left text-xs font-semibold uppercase tracking-wider text-gray-500 font-['Poppins']"
                    >
                        Receipt
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
                    <!-- Item -->
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

                    <!-- Property Number -->
                    <td class="px-6 py-5 font-['Poppins']">
                        <span class="text-[13px] font-medium text-gray-700">
                            {{ item.property_number }}
                        </span>
                    </td>

                    <!-- Unit Cost -->
                    <td class="px-6 py-5 font-['Poppins']">
                        <span class="text-[13px] font-semibold text-[#850038]">
                            ₱{{ Number(item.unit_cost || 0).toLocaleString() }}
                        </span>
                    </td>

                    <!-- Date Assigned -->
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

                     <!-- Date Acquired -->
                    <td class="px-6 py-5 font-['Poppins']">
                        <span class="text-[13px] text-gray-600">
                            {{ item.date_acquired
                                ? new Date(item.date_acquired).toLocaleDateString()
                                : "N/A" }}
                        </span>
                    </td>

                    <!-- Category -->
                    <td class="px-6 py-5 font-['Poppins']">
                        <span class="text-[13px] text-gray-600">
                            {{
                                item.latest_acknowledgement_item
                                    ?.acknowledgement_receipts?.category ?? "N/A"
                            }}
                        </span>
                    </td>

                    <!-- Actions -->
                    <td class="px-6 py-5 text-center font-['Poppins']">
                        <button
                            @click="openModal(item)"
                            class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-[11px] font-semibold bg-[#850038]/10 text-[#850038] hover:bg-[#850038] hover:text-white transition-colors"
                        >
                            <i class="fa-solid fa-eye text-xs"></i>
                            View
                        </button>
                    </td>
                </tr>

                <!-- Empty State -->
                <tr v-if="!items.data?.length">
                    <td colspan="7" class="py-16 text-center font-['Poppins']">
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

        <!-- Pagination -->
        <div
            v-if="items.last_page > 1"
            class="flex items-center justify-between px-6 py-4 border-t border-gray-100 font-['Poppins']"
        >
            <!-- Page Info -->
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

            <!-- Buttons -->
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

        <!-- View Details Modal -->
        <Teleport to="body">
            <div
                v-if="isModalOpen"
                class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 px-4 font-['Poppins']"
                @click.self="closeModal"
            >
                <div
                    class="bg-white rounded-2xl shadow-xl w-full max-w-3xl max-h-[85vh] overflow-y-auto"
                >
                    <!-- Modal Header -->
                    <div
                        class="flex items-center justify-between px-6 py-4 sticky top-0 rounded-t-2xl bg-gradient-to-r from-[#005740] via-[#006B4F] to-[#0E6021]"
                    >
                        <h3 class="text-[15px] font-semibold text-white">
                            Item Details
                        </h3>
                        <button
                            @click="closeModal"
                            class="w-8 h-8 flex items-center justify-center rounded-full text-white/80 hover:bg-white/15 hover:text-white transition-colors"
                        >
                            <i class="fa-solid fa-xmark"></i>
                        </button>
                    </div>

                    <!-- Modal Body -->
                    <div v-if="selectedItem" class="px-6 py-5">
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                            <!-- Left: Item Name, Property Number, Serial Number, Date Acquired -->
                            <div class="grid grid-cols-1 gap-4">
                                <div>
                                    <p class="text-[11px] uppercase tracking-wider text-gray-400 font-semibold">
                                        Item Name
                                    </p>
                                    <p class="text-[13px] font-semibold text-gray-900 mt-1">
                                        {{ selectedItem.item_name ?? "N/A" }}
                                    </p>
                                </div>

                                <div>
                                    <p class="text-[11px] uppercase tracking-wider text-gray-400 font-semibold">
                                        Property Number
                                    </p>
                                    <p class="text-[13px] font-medium text-gray-700 mt-1">
                                        {{ selectedItem.property_number ?? "N/A" }}
                                    </p>
                                </div>

                                <div>
                                    <p class="text-[11px] uppercase tracking-wider text-gray-400 font-semibold">
                                        Serial Number
                                    </p>
                                    <p class="text-[13px] font-medium text-gray-700 mt-1">
                                        {{ selectedItem.serial_number ?? "No Serial Number" }}
                                    </p>
                                </div>

                                <div>
                                    <p class="text-[11px] uppercase tracking-wider text-gray-400 font-semibold">
                                        Date Acquired
                                    </p>
                                    <p class="text-[13px] text-gray-600 mt-1">
                                        {{ formatDate(selectedItem.date_acquired) }}
                                    </p>
                                </div>
                            </div>

                            <!-- Middle: PO Number, PR Number, Invoice -->
                            <div class="grid grid-cols-1 gap-4">
                                <div>
                                    <p class="text-[11px] uppercase tracking-wider text-gray-400 font-semibold">
                                        PO Number
                                    </p>
                                    <p class="text-[13px] font-medium text-gray-700 mt-1">
                                        {{ selectedItem.po_number ?? "N/A" }}
                                    </p>
                                </div>

                                <div>
                                    <p class="text-[11px] uppercase tracking-wider text-gray-400 font-semibold">
                                        PR Number
                                    </p>
                                    <p class="text-[13px] font-medium text-gray-700 mt-1">
                                        {{ selectedItem.pr_number ?? "N/A" }}
                                    </p>
                                </div>

                                <div>
                                    <p class="text-[11px] uppercase tracking-wider text-gray-400 font-semibold">
                                        Invoice
                                    </p>
                                    <p class="text-[13px] font-medium text-gray-700 mt-1">
                                        {{ selectedItem.invoice ?? "N/A" }}
                                    </p>
                                </div>
                            </div>

                            <!-- Right: Files -->
                            <div>
                                <p class="text-[11px] uppercase tracking-wider text-gray-400 font-semibold mb-2">
                                    Files
                                </p>

                                <div
                                    v-if="selectedItem.latest_acknowledgement_item?.files?.length"
                                    class="flex flex-col gap-2"
                                >
                                    <a
                                        v-for="(file, index) in selectedItem.latest_acknowledgement_item.files"
                                        :key="file.id"
                                        :href="`/storage/${file.file_path}`"
                                        target="_blank"
                                        class="flex items-center justify-between px-4 py-2.5 rounded-lg border border-gray-200 hover:border-[#006B4F] hover:bg-[#006B4F]/5 transition-colors group"
                                    >
                                        <span class="flex items-center gap-2 text-[13px] font-medium text-gray-700 group-hover:text-[#006B4F]">
                                            <i class="fa-solid fa-file text-xs"></i>
                                            File {{ index + 1 }}
                                        </span>
                                        <i class="fa-solid fa-arrow-up-right-from-square text-[11px] text-gray-400 group-hover:text-[#006B4F]"></i>
                                    </a>
                                </div>

                                <p v-else class="text-[13px] text-gray-400">
                                    No files attached.
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- Modal Footer -->
                    <div class="px-6 py-4 border-t border-gray-100 flex justify-end">
                        <button
                            @click="closeModal"
                            class="px-4 py-2 text-[12px] font-medium rounded-lg border border-gray-200 text-gray-700 hover:bg-gray-50 transition-colors"
                        >
                            Close
                        </button>
                    </div>
                </div>
            </div>
        </Teleport>
    </div>
</template>