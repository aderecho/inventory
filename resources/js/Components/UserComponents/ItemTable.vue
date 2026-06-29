<script setup>
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
});

const goToPage = (url) => {
    if (!url) return;
    router.get(url, {}, { preserveState: true, replace: true });
};
</script>

<template>
    <div class="overflow-x-auto">
        <table class="w-full">
            <thead>
                <tr class="bg-gray-50 border-b border-gray-200">
                    <th
                        class="px-6 py-4 text-left text-xs font-semibold uppercase tracking-wider text-gray-500 font-['Poppins']"
                    >
                        Item
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
                        Date Assigned
                    </th>

                    <th
                        class="px-6 py-4 text-center text-xs font-semibold uppercase tracking-wider text-gray-500 font-['Poppins']"
                    >
                        Status
                    </th>

                    <th
                        class="px-6 py-4 text-center text-xs font-semibold uppercase tracking-wider text-gray-500 font-['Poppins']"
                    >
                        File
                    </th>
                </tr>
            </thead>

            <tbody>
                <tr
                    v-for="item in items.data"
                    :key="item.id"
                    class="border-b border-gray-100 hover:bg-gray-50 transition-colors"
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
                                item.latest_acknowledgement_item
                                    ?.acknowledgement_receipts?.par_date
                                    ? new Date(
                                          item.latest_acknowledgement_item
                                              .acknowledgement_receipts
                                              .par_date,
                                      ).toLocaleDateString()
                                    : "N/A"
                            }}
                        </span>
                    </td>

                    <!-- Status -->
                    <td class="px-6 py-5 text-center font-['Poppins']">
                        <span
                            class="inline-flex px-3 py-1 rounded-full text-[11px] font-semibold bg-green-100 text-green-700"
                        >
                            Assigned
                        </span>
                    </td>

                    <!-- File -->
                    <td class="px-6 py-5 text-center font-['Poppins']">
                        <a
                            v-if="
                                item.latest_acknowledgement_item?.file
                                    ?.file_path
                            "
                            :href="`/storage/${item.latest_acknowledgement_item.file.file_path}`"
                            target="_blank"
                            class="inline-flex items-center gap-1 px-3 py-1 rounded-full text-[11px] font-semibold bg-blue-100 text-blue-700 hover:bg-blue-200 transition-colors"
                        >
                            <i class="fa-solid fa-file text-xs"></i>
                            View
                        </a>

                        <span v-else class="text-[12px] text-gray-400">
                            No file
                        </span>
                    </td>
                </tr>

                <!-- Empty State -->
                <tr v-if="!items.data?.length">
                    <td colspan="6" class="py-16 text-center font-['Poppins']">
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
    </div>
</template>
