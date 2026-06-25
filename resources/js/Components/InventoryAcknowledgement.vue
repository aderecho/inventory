<script setup>
import { Link } from "@inertiajs/vue3";
import { router } from "@inertiajs/vue3";

defineProps({ receipts: Object });
defineEmits(["view"]);

const goToPage = (url) => {
    router.visit(url, { preserveState: true });
};
</script>

<template>
    <div>
        <div class="overflow-x-auto mt-3">
            <table
                class="w-full table-auto border-collapse text-left bg-white text-xs sm:text-sm"
            >
                <thead class="bg-[#850038]">
                    <tr class="text-white border">
                        <th
                            class="p-2 sm:p-3 md:p-4 text-left first:rounded-tl last:rounded-tr"
                        >
                            Receipts
                        </th>
                        <th class="p-2 sm:p-3 md:p-4 text-left">Date Release</th>
                        <th class="p-2 sm:p-3 md:p-4 text-left">
                            Accountable Person
                        </th>
                        <!-- <th class="p-2 sm:p-3 md:p-4 text-left">Issued By</th> -->
                        <th class="p-2 sm:p-3 md:p-4 text-left">Items</th>
                        <th class="p-2 sm:p-3 md:p-4 text-left last:rounded-tr">
                            Action
                        </th>
                    </tr>
                </thead>

                <tbody class="text-gray-700">
                    <template v-if="receipts.data.length > 0">
                        <tr
                            v-for="receipt in receipts.data"
                            :key="receipt.id"
                            class="border border-rose-300 even:bg-gray-200"
                        >
                            <td class="p-2 sm:p-3 md:p-4">
                                <span
                                    class="px-2.5 py-1 rounded-full font-medium"
                                >
                                    {{ receipt.category ?? "N/A" }}
                                </span>
                            </td>
                            <td class="p-2 sm:p-3 md:p-4">
                                {{ receipt.par_date ?? "N/A" }}
                            </td>
                            <td class="p-2 sm:p-3 md:p-4">
                                {{
                                    [
                                        ...new Set(
                                            receipt.acknowledgement_items.map(
                                                (item) =>
                                                    item.accountable_person
                                                        ?.full_name ?? "N/A",
                                            ),
                                        ),
                                    ].join(", ")
                                }}
                            </td>
                            <!-- <td class="p-2 sm:p-3 md:p-4">
                                {{
                                    receipt.issued_by?.user_profiles
                                        .full_name ?? "N/A"
                                }}
                            </td> -->
                            <td class="p-2 sm:p-3 md:p-4">
                                {{ receipt.acknowledgement_items.length }} items
                            </td>
                            <td class="p-2 sm:p-3 md:p-4">
                                <button
                                    @click="$emit('view', receipt)"
                                    class="text-[#3F3F3F] hover:text-[#191818]"
                                    title="View"
                                >
                                    <i
                                        class="fa-solid fa-upload text-[#0E6021] hover:text-[#0a7523]"
                                    ></i>
                                </button>
                            </td>
                        </tr>
                    </template>

                    <tr v-else>
                        <td
                            colspan="5"
                            class="p-6 text-center text-gray-400 text-sm"
                        >
                            <i
                                class="fa-solid fa-folder-open text-3xl text-gray-300 block mb-2"
                            ></i>
                            No acknowledgement receipts found.
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <div class="mt-2 flex justify-between items-center mx-2">
            <div>
                <p class="text-base font-bold text-[#3B3B3B]">
                    Results:
                    <span class="text-[#850038]"
                        >{{ receipts.from }}-{{ receipts.to }} of
                        {{ receipts.total }}</span
                    >
                </p>
            </div>
            <div class="flex justify-center">
                <div class="flex items-center gap-1 sm:gap-2 py-2">
                    <span v-for="link in receipts.links" :key="link.label">
                        <span
                            v-if="link.url"
                            @click="goToPage(link.url)"
                            class="flex items-center justify-center min-w-[32px] h-8 px-2 text-base rounded-full transition-all duration-200 cursor-pointer"
                            :class="{
                                'text-[#000000] hover:bg-[#e7e7e7]':
                                    !link.active,
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
    </div>
</template>
