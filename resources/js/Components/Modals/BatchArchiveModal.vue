<script setup>
import { computed, ref } from "vue";
import { Archive } from "lucide-vue-next";

const props = defineProps({
    // The selected rows, used for the count and a short preview.
    items: { type: Array, default: () => [] },
    // "item" for the Items list, "supplier" for the Suppliers list.
    entityLabel: { type: String, default: "item" },
});

const emit = defineEmits(["close", "confirm"]);

const isClosing = ref(false);

const count = computed(() => props.items.length);
const preview = computed(() => props.items.slice(0, 5));
const remaining = computed(() => Math.max(0, count.value - preview.value.length));

function label(row) {
    // The first field that exists is used, so each module shows its own name:
    // item name, supplier name, classification name, property number, then code.
    return (
        row?.item_name ??
        row?.supplier_name ??
        row?.classification_name ??
        row?.property_number ??
        row?.classification_code ??
        "Record #" + (row?.id ?? "")
    );
}

function closeWithAnimation() {
    isClosing.value = true;
    setTimeout(() => {
        emit("close");
        isClosing.value = false;
    }, 200);
}

function confirmArchive() {
    emit("confirm");
}
</script>

<template>
    <div class="fixed inset-0 modal-backdrop flex items-center justify-center p-4 z-50">
        <div
            :class="[
                'bg-white rounded-lg p-6 max-w-[600px] w-full flex flex-col items-center text-center',
                isClosing ? 'animate-pop-out' : 'animate-pop-in',
            ]"
        >
            <div
                class="w-14 h-14 rounded-full flex items-center justify-center mb-4"
                style="background-color: #f5cccc"
            >
                <Archive class="w-7 h-7 text-red-600" />
            </div>

            <h4 class="font-semibold mb-2 text-xl">Confirm Batch Archive</h4>

            <p class="text-lg font-bold mb-3 text-[#888484]">
                Are you sure you want to archive
                <strong class="text-[#D71D1D]">
                    {{ count }}
                    {{
                        count === 1 ? entityLabel : entityLabel + "s"
                    }}
                </strong>
                ?
            </p>

            <ul
                v-if="count"
                class="mb-4 w-full max-h-40 overflow-y-auto rounded-md border border-gray-200 bg-[#F8F8F8] px-4 py-3 text-left text-sm text-gray-700"
            >
                <li
                    v-for="item in preview"
                    :key="item.id"
                    class="flex gap-2 py-0.5"
                >
                    <span class="text-[#D71D1D]">•</span>
                    <span class="truncate">{{ label(item) }}</span>
                </li>
                <li v-if="remaining" class="pt-1 text-xs text-gray-500">
                    and {{ remaining }} more
                </li>
            </ul>

            <p class="text-xs text-gray-500 mb-2">
                Archived items move to the Archive list and can be restored from
                there.
            </p>

            <div class="flex justify-center gap-3 mt-2">
                <button
                    @click="closeWithAnimation"
                    class="px-8 py-3 rounded-md bg-[#D9D9D9] hover:bg-[#cfcece]"
                >
                    Cancel
                </button>

                <button
                    @click="confirmArchive"
                    class="px-8 py-3 rounded-md bg-[#D71D1D] hover:bg-[#d00d0d] text-white flex items-center gap-2"
                >
                    <Archive class="w-4 h-4" />
                    Archive
                </button>
            </div>
        </div>
    </div>
</template>

