<script setup>
import { computed, ref } from "vue";
import { Trash2 } from "lucide-vue-next";

const props = defineProps({
    // The rows being deleted for good (one row for a single action).
    items: { type: Array, default: () => [] },
    // "item", "supplier", "classification", "user".
    entityLabel: { type: String, default: "record" },
});

const emit = defineEmits(["close", "confirm"]);

const isClosing = ref(false);

const count = computed(() => props.items.length);
const preview = computed(() => props.items.slice(0, 5));
const remaining = computed(() => Math.max(0, count.value - preview.value.length));
const plural = computed(() =>
    count.value === 1 ? props.entityLabel : props.entityLabel + "s",
);

function label(row) {
    return (
        row?.item_name ??
        row?.supplier_name ??
        row?.classification_name ??
        row?.property_number ??
        row?.classification_code ??
        row?.email ??
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
</script>

<template>
    <div class="fixed inset-0 bg-black/40 flex items-center justify-center p-4 z-50">
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
                <Trash2 class="w-7 h-7 text-red-600" />
            </div>

            <h4 class="font-semibold mb-2 text-xl">Confirm Permanent Delete</h4>

            <p class="text-lg font-bold mb-3 text-[#888484]">
                Permanently delete
                <strong class="text-[#D71D1D]">
                    {{ count === 1 ? "this " + entityLabel : count + " " + plural }}
                </strong>
                ?
            </p>

            <ul
                v-if="count"
                class="mb-4 w-full max-h-40 overflow-y-auto rounded-md border border-gray-200 bg-[#F8F8F8] px-4 py-3 text-left text-sm text-gray-700"
            >
                <li
                    v-for="row in preview"
                    :key="row.id"
                    class="flex gap-2 py-0.5"
                >
                    <span class="text-[#D71D1D]">•</span>
                    <span class="truncate">{{ label(row) }}</span>
                </li>
                <li v-if="remaining" class="pt-1 text-xs text-gray-500">
                    and {{ remaining }} more
                </li>
            </ul>

            <p class="text-xs text-gray-500 mb-2">
                This removes the record from the database for good. It cannot be
                undone.
            </p>

            <div class="flex justify-center gap-3 mt-2">
                <button
                    @click="closeWithAnimation"
                    class="px-8 py-3 rounded-md bg-[#D9D9D9] hover:bg-[#cfcece]"
                >
                    Cancel
                </button>

                <button
                    @click="emit('confirm')"
                    class="px-8 py-3 rounded-md bg-[#D71D1D] hover:bg-[#d00d0d] text-white flex items-center gap-2"
                >
                    <Trash2 class="w-4 h-4" />
                    Delete Permanently
                </button>
            </div>
        </div>
    </div>
</template>

