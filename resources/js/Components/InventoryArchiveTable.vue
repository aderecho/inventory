<script setup>
import { router } from "@inertiajs/vue3";
import { defineProps } from "vue";
import TableCell from "./TableCell.vue";
import { useAuth } from "@/Composables/useAuth";

const { can } = useAuth();

const props = defineProps({
    columns: Array,
    rows: Object,
    module: {
        type: String,
        default: "",
    },
    actions: {
        type: Array,
        default: () => [],
    },
});

const emit = defineEmits(["restore", "permanent-delete"]);

const goToPage = (url) => {
    if (!url) return;
    router.visit(url, {
        preserveState: true,
        replace: true,
        preserveScroll: true,
    });
};

function getValue(obj, path) {
    return path.split(".").reduce((acc, key) => acc?.[key], obj) ?? "N/A";
}
</script>

<template>
    <div class="overflow-x-auto mt-3">
        <table class="w-full table-auto border-collapse text-left bg-white text-xs sm:text-sm">
            <thead class="bg-[#850038]">
                <tr class="text-white border">
                    <th
                        v-for="col in props.columns"
                        :key="col.key"
                        class="p-2 sm:p-3 md:p-4 text-left first:rounded-tl last:rounded-tr"
                    >
                        {{ col.label }}
                    </th>
                </tr>
            </thead>

            <tbody class="text-gray-700">
                <tr
                    v-for="item in props.rows.data"
                    :key="item.id"
                    class="border border-rose-300 even:bg-gray-200"
                >
                    <TableCell v-for="col in props.columns" :key="col.key">
                        <template v-if="col.key !== 'action'">
                            <span
                                v-if="col.format"
                                v-html="col.format(getValue(item, col.key))"
                            ></span>
                            <span v-else>{{ getValue(item, col.key) ?? "N/A" }}</span>
                        </template>

                        <template v-else>
                            <div class="flex items-center gap-2">
                                <button
                                    v-if="actions.includes('restore')"
                                    @click="$emit('restore', item)"
                                    class="text-[#2E7D32] hover:text-[#1b5e20]"
                                    title="Restore"
                                >
                                    <i class="fa-solid fa-rotate-left"></i>
                                </button>

                                <button
                                    v-if="actions.includes('force-delete')"
                                    @click="$emit('permanent-delete', item)"
                                    class="text-[#D32F2F] hover:text-[#b71c1c]"
                                    title="Permanent Delete"
                                >
                                    <i class="fa-solid fa-trash-can"></i>
                                </button>
                            </div>
                        </template>
                    </TableCell>
                </tr>
            </tbody>
        </table>
    </div>

    <!-- Pagination -->
    <div class="mt-2 flex justify-between items-center mx-2">
        <div>
            <p class="text-base font-bold text-[#3B3B3B]">
                Results:
                <span class="text-[#850038]">{{ rows.from }}-{{ rows.to }} of {{ rows.total }}</span>
            </p>
        </div>
        <div class="flex justify-center">
            <div class="flex items-center gap-1 sm:gap-2 py-2">
                <span v-for="link in rows.links" :key="link.label">
                    <span
                        v-if="link.url"
                        @click="goToPage(link.url)"
                        class="flex items-center justify-center min-w-[32px] h-8 px-2 text-base rounded-full transition-all duration-200 cursor-pointer"
                        :class="{
                            'text-[#000000] hover:bg-[#e7e7e7]': !link.active,
                            'bg-[#850038] text-white font-semibold shadow-sm': link.active,
                        }"
                    >
                        <i v-if="link.label.includes('Previous')" class="fa-solid fa-chevron-left"></i>
                        <i v-else-if="link.label.includes('Next')" class="fa-solid fa-chevron-right"></i>
                        <span v-else>{{ link.label }}</span>
                    </span>
                </span>
            </div>
        </div>
    </div>
</template>