<script setup>
import { router } from "@inertiajs/vue3";

defineProps({ items: Object });
defineEmits(["view"]);

const goToPage = (url) => {
    if (!url) return;
    router.visit(url, { preserveState: true });
};
</script>

<template>
    <div class="overflow-x-auto mt-3">
        <table
            class="w-full table-auto border-collapse text-left bg-white text-xs sm:text-sm"
        >
            <thead class="bg-[#005740]">
                <tr class="text-white border">
                    <th class="p-2 sm:p-3 md:p-4 text-left first:rounded-tl">
                        Item
                    </th>
                    <th class="p-2 sm:p-3 md:p-4 text-left">Property No.</th>
                    <th class="p-2 sm:p-3 md:p-4 text-left">
                        Current Location
                    </th>
                    <th class="p-2 sm:p-3 md:p-4 text-left">
                        Accountable Person
                    </th>
                    <th class="p-2 sm:p-3 md:p-4 text-left">History Count</th>
                    <th class="p-2 sm:p-3 md:p-4 text-center last:rounded-tr">
                        Actions
                    </th>
                </tr>
            </thead>

            <tbody class="text-gray-700">
                <template v-if="items.data && items.data.length > 0">
                    <tr
                        v-for="item in items.data"
                        :key="item.id"
                        class="border border-rose-300 even:bg-gray-200"
                    >
                        <!-- Item name + classification -->
                        <td class="p-2 sm:p-3 md:p-4">
                            <div class="font-medium text-gray-900">
                                {{ item.item_name }}
                            </div>
                            <div class="text-xs text-gray-400 mt-0.5">
                                {{
                                    item.item_classification
                                        ?.classification_name ?? "—"
                                }}
                            </div>
                        </td>

                        <!-- Property number -->
                        <td class="p-2 sm:p-3 md:p-4 font-mono text-xs">
                            {{ item.property_number ?? "—" }}
                        </td>

                        <!-- Current location -->
                        <td class="p-2 sm:p-3 md:p-4">
                            <div
                                v-if="
                                    item.room_name && item.room_name !== 'N/A'
                                "
                                class="flex items-center gap-1.5"
                            >
                                <span
                                    class="inline-block w-1.5 h-1.5 rounded-full bg-[#0E6021] flex-shrink-0"
                                ></span>
                                <span>{{ item.room_name }}</span>
                            </div>
                            <span v-else class="text-gray-400 text-xs italic">
                                No location set
                            </span>
                        </td>

                        <!-- Accountable person -->
                        <td class="p-2 sm:p-3 md:p-4">
                            <span
                                :class="{
                                    'text-red-600 font-medium':
                                        !item.latest_acknowledgement_item
                                            ?.accountable_person?.full_name,
                                }"
                            >
                                {{
                                    item.latest_acknowledgement_item
                                        ?.accountable_person?.full_name ??
                                    "Unassigned"
                                }}
                            </span>
                        </td>

                        <!-- History count -->
                        <td class="p-2 sm:p-3 md:p-4">
                            <span
                                class="inline-flex items-center gap-1 text-gray-600"
                            >
                                <i
                                    class="fa-solid fa-clock-rotate-left text-gray-400"
                                ></i>
                                {{ item.history_locations?.length ?? 0 }}
                                move{{
                                    (item.history_locations?.length ?? 0) !== 1
                                        ? "s"
                                        : ""
                                }}
                            </span>
                        </td>

                        <!-- Action -->
                        <td class="p-2 sm:p-3 md:p-4 text-center">
                            <button
                                @click="$emit('view', item.id)"
                                class="text-[#3F3F3F] hover:text-[#191818]"
                                title="View history"
                            >
                                <i class="fa-solid fa-eye"></i>
                            </button>
                        </td>
                    </tr>
                </template>

                <tr v-else>
                    <td colspan="6" class="text-center py-16 text-gray-400">
                        <div class="flex flex-col items-center gap-2">
                            <i
                                class="fa-solid fa-box-archive text-2xl text-gray-300"
                            ></i>
                            <span class="text-sm"
                                >No items with location history found</span
                            >
                        </div>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>

    <!-- Pagination -->
    <div
        v-if="items.links && items.data.length > 0"
        class="mt-2 flex justify-between items-center mx-2"
    >
        <div>
            <p class="text-base font-bold text-[#3B3B3B]">
                Results:
                <span class="text-[#850038]"
                    >{{ items.from }}-{{ items.to }} of {{ items.total }}</span
                >
            </p>
        </div>
        <div class="flex justify-center">
            <div class="flex items-center gap-1 sm:gap-2 py-2">
                <span v-for="link in items.links" :key="link.label">
                    <span
                        v-if="link.url"
                        @click="goToPage(link.url)"
                        class="flex items-center justify-center min-w-[32px] h-8 px-2 text-base rounded-full transition-all duration-200 cursor-pointer"
                        :class="{
                            'text-[#000000] hover:bg-[#e7e7e7]': !link.active,
                            'bg-[#850038] text-white font-semibold shadow-sm':
                                link.active,
                        }"
                    >
                        <i
                            v-if="link.label.includes('Previous')"
                            class="fa-solid fa-chevron-left"
                        ></i>
                        <i
                            v-else-if="link.label.includes('Next')"
                            class="fa-solid fa-chevron-right"
                        ></i>
                        <span v-else>{{ link.label }}</span>
                    </span>
                </span>
            </div>
        </div>
    </div>
</template>
