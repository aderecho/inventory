<script setup>
import { computed, ref } from "vue";
import { router } from "@inertiajs/vue3";
import { useToast } from "primevue/usetoast";
import { CircleAlert, PackageX } from "lucide-vue-next";

/**
 * Disposes of the ticked items. Disposing means recording the inspection that
 * says so, which needs a condition flagged as Disposed — so when there is none,
 * this guides the user to add one instead of failing silently.
 */
const props = defineProps({
    items: { type: Array, default: () => [] },
    disposedCondition: { type: Object, default: null },
});

const emit = defineEmits(["close", "add-condition", "disposed"]);

const toast = useToast();
const processing = ref(false);

const count = computed(() => props.items.length);
const today = computed(() =>
    new Date().toLocaleDateString("en-PH", {
        month: "long",
        day: "2-digit",
        year: "numeric",
    }),
);

function dispose() {
    if (processing.value || ! count.value) {
        return;
    }

    processing.value = true;

    router.patch(
        route("items.dispose"),
        { ids: props.items.map((item) => item.id) },
        {
            preserveScroll: true,
            onSuccess: () => {
                toast.add({
                    severity: "success",
                    summary: "Items disposed",
                    detail:
                        count.value === 1
                            ? "1 item is now disposed."
                            : `${count.value} items are now disposed.`,
                    life: 4000,
                });

                // The page owns the progress card, so it prints on our behalf.
                emit("disposed", props.items);
                emit("close");
            },
            onError: (errors) => {
                toast.add({
                    severity: "error",
                    summary: "Nothing was disposed",
                    detail:
                        Object.values(errors)[0] ??
                        "The selected items could not be disposed of.",
                    life: 8000,
                });
            },
            onFinish: () => {
                processing.value = false;
            },
        },
    );
}
</script>

<template>
    <div class="fixed inset-0 z-50 flex items-center justify-center p-4">
        <div class="modal-backdrop absolute inset-0" @click="emit('close')"></div>

        <div
            class="relative flex max-h-[85vh] w-full max-w-lg flex-col overflow-hidden rounded-2xl bg-white shadow-2xl"
        >
            <div class="border-b border-gray-100 px-6 py-5">
                <div class="flex items-start gap-3">
                    <span
                        class="flex h-10 w-10 flex-shrink-0 items-center justify-center rounded-xl"
                        :class="
                            disposedCondition
                                ? 'bg-[#D71D1D]/10 text-[#D71D1D]'
                                : 'bg-amber-100 text-amber-600'
                        "
                    >
                        <PackageX class="h-5 w-5" />
                    </span>

                    <div class="min-w-0">
                        <h2 class="text-lg font-semibold text-gray-900">
                            {{ disposedCondition ? "Dispose selected items" : "No disposal condition yet" }}
                        </h2>
                        <p class="mt-1 text-xs text-gray-500">
                            <template v-if="disposedCondition">
                                {{ count }} item(s) will be marked disposed and leave
                                the inventory list.
                            </template>
                            <template v-else>
                                Disposing needs a condition flagged as Disposed, and
                                there is none yet.
                            </template>
                        </p>
                    </div>
                </div>
            </div>

            <!-- Guide: nothing is flagged as disposed yet -->
            <div v-if="!disposedCondition" class="bg-gray-50 px-6 py-6">
                <div
                    class="flex items-start gap-3 rounded-xl border border-amber-200 bg-amber-50 px-4 py-3"
                >
                    <CircleAlert
                        class="mt-0.5 h-4 w-4 flex-shrink-0 text-amber-600"
                    />
                    <p class="text-xs leading-relaxed text-amber-800">
                        Add a condition (for example "Disposed" or "Unserviceable")
                        and choose <strong>Disposed</strong> as what it means. Items
                        inspected with that condition are the ones that leave the
                        inventory list.
                    </p>
                </div>
            </div>

            <!-- Confirm: show exactly what is about to be disposed of -->
            <div v-else class="min-h-0 flex-1 overflow-y-auto bg-gray-50 px-6 py-5">
                <p
                    class="text-[11px] font-semibold uppercase tracking-wide text-gray-500"
                >
                    Selected items
                </p>

                <ul class="mt-2 divide-y divide-gray-100 overflow-hidden rounded-xl border border-gray-200 bg-white">
                    <li
                        v-for="item in items"
                        :key="item.id"
                        class="flex items-center justify-between gap-3 px-4 py-2.5"
                    >
                        <span class="min-w-0 truncate text-xs text-gray-700">
                            {{ item.item_name || "Item" }}
                        </span>
                        <span class="flex-shrink-0 text-[11px] tabular-nums text-gray-500">
                            {{ item.property_number || "—" }}
                        </span>
                    </li>
                </ul>

                <p class="mt-4 text-xs leading-relaxed text-gray-500">
                    Disposing records an inspection dated {{ today }} under
                    <strong>{{ disposedCondition.condition_name }}</strong> for each
                    item. A later inspection with a serviceable condition puts an
                    item back in stock.
                </p>
            </div>

            <div
                class="flex flex-col gap-2 border-t border-gray-100 bg-white px-6 py-5 sm:flex-row sm:justify-end"
            >
                <button
                    type="button"
                    class="rounded-lg px-5 py-2.5 text-sm font-medium text-gray-600 transition hover:bg-gray-100"
                    @click="emit('close')"
                >
                    Cancel
                </button>

                <button
                    v-if="!disposedCondition"
                    type="button"
                    class="rounded-lg bg-[#0E6021] px-6 py-2.5 text-sm font-semibold text-white transition hover:bg-[#19703a]"
                    @click="emit('add-condition')"
                >
                    Add a Disposed condition
                </button>

                <button
                    v-else
                    type="button"
                    :disabled="processing || !count"
                    class="rounded-lg bg-[#D71D1D] px-6 py-2.5 text-sm font-semibold text-white transition hover:bg-[#c50e0e] disabled:cursor-not-allowed disabled:opacity-50"
                    @click="dispose"
                >
                    {{ processing ? "Disposing..." : `Dispose ${count} item(s)` }}
                </button>
            </div>
        </div>
    </div>
</template>
