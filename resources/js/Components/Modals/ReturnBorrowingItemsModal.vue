<script setup>
import { computed, ref, watch } from "vue";
import { PackageCheck, X } from "lucide-vue-next";

const props = defineProps({
    show: { type: Boolean, default: false },
    // Open borrow lines being taken back: { id, property_number, item_name, reference_code }
    lines: { type: Array, default: () => [] },
    assetConditions: { type: Array, default: () => [] },
});

const emit = defineEmits(["close", "submit"]);

const conditionIn = ref("");
const notes = ref("");

const reference = computed(() => props.lines[0]?.reference_code ?? "");

// Which lines are being taken back. Everything open starts ticked, and the
// rest of the reference stays on loan either way.
const picked = ref([]);

const allPicked = computed(
    () => props.lines.length > 0 && picked.value.length === props.lines.length,
);

const references = computed(() => [
    ...new Set(props.lines.map((line) => line.reference_code)),
]);

function toggleLine(line) {
    picked.value = picked.value.includes(line.id)
        ? picked.value.filter((id) => id !== line.id)
        : [...picked.value, line.id];
}

function selectAll() {
    picked.value = props.lines.map((line) => line.id);
}

function selectNone() {
    picked.value = [];
}

watch(
    () => props.show,
    (open) => {
        if (open) {
            // Start from the condition the item went out with, so a clean
            // return needs no clicking. Mixed loans keep it blank.
            const outgoing = props.lines.map((line) => line.condition_out_id || null);
            const sameOutgoing =
                outgoing.length > 0 && outgoing.every((id) => id === outgoing[0]);

            conditionIn.value = sameOutgoing ? outgoing[0] ?? "" : "";
            notes.value = "";
            picked.value = props.lines.map((line) => line.id);
        }
    },
);

function submit() {
    if (!picked.value.length) return;

    emit("submit", {
        lines: [...picked.value],
        condition_in_id: conditionIn.value || null,
        notes: notes.value || null,
    });
}
</script>

<template>
    <div
        v-if="show"
        class="fixed inset-0 modal-backdrop z-50 flex items-center justify-center p-4"
    >
        <div class="w-full max-w-xl overflow-hidden rounded-2xl bg-white shadow-2xl">
            <!-- HEADER -->
            <div
                class="flex items-center justify-between bg-gradient-to-r from-[#003d2c] via-[#005740] to-[#00795a] px-6 py-5"
            >
                <div>
                    <h3 class="text-lg font-bold text-white">Take Items Back</h3>
                    <p class="mt-0.5 text-xs text-white/75">
                        {{ lines.length }} item(s)
                        <template v-if="reference">· {{ reference }}</template>
                    </p>
                </div>

                <button
                    type="button"
                    @click="emit('close')"
                    class="flex h-9 w-9 items-center justify-center rounded-full text-white/80 transition hover:bg-white/10 hover:text-white"
                >
                    <X class="h-5 w-5" />
                </button>
            </div>

            <!-- BODY -->
            <div class="space-y-5 bg-gray-50 px-6 py-5">
                <div>
                    <div class="mb-2 flex items-center justify-between gap-3">
                        <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">
                            Items coming back
                            <span class="ml-1 normal-case text-gray-400">
                                ({{ picked.length }} of {{ lines.length }} ticked)
                            </span>
                        </p>

                        <div class="flex items-center gap-2">
                            <button
                                type="button"
                                @click="selectAll"
                                :disabled="allPicked"
                                class="text-[11px] font-semibold text-[#005740] hover:underline disabled:cursor-not-allowed disabled:text-gray-400 disabled:no-underline"
                            >
                                Tick all
                            </button>
                            <span class="text-gray-300">|</span>
                            <button
                                type="button"
                                @click="selectNone"
                                :disabled="!picked.length"
                                class="text-[11px] font-semibold text-[#005740] hover:underline disabled:cursor-not-allowed disabled:text-gray-400 disabled:no-underline"
                            >
                                Tick none
                            </button>
                        </div>
                    </div>

                    <p class="mb-2 text-[11px] text-gray-400">
                        Leave an item unticked to keep it on the same
                        reference{{ references.length > 1 ? "s" : "" }} and take
                        it back later.
                    </p>

                    <ul class="max-h-56 space-y-2 overflow-y-auto pr-1">
                        <li
                            v-for="line in lines"
                            :key="line.id"
                            class="cursor-pointer rounded-xl border bg-white px-3 py-2.5 transition-colors"
                            :class="
                                picked.includes(line.id)
                                    ? 'border-[#005740]/40 bg-[#005740]/[0.04]'
                                    : 'border-gray-200 hover:border-gray-300'
                            "
                            @click="toggleLine(line)"
                        >
                            <div class="flex items-start gap-3">
                                <input
                                    type="checkbox"
                                    class="mt-0.5 h-4 w-4 flex-shrink-0 rounded border-gray-300 text-[#005740] focus:ring-[#005740]"
                                    :checked="picked.includes(line.id)"
                                    @click.stop="toggleLine(line)"
                                />

                                <div class="min-w-0">
                                    <p class="truncate text-sm font-semibold text-gray-800">
                                        {{ line.property_number }}
                                    </p>
                                    <p class="truncate text-xs text-gray-500">
                                        {{ line.item_name || "—" }}
                                    </p>
                                    <p
                                        v-if="references.length > 1"
                                        class="mt-0.5 text-[11px] font-medium text-gray-400"
                                    >
                                        {{ line.reference_code }}
                                    </p>
                                </div>
                            </div>
                        </li>
                    </ul>
                </div>

                <div>
                    <label class="mb-1.5 block text-xs font-semibold uppercase tracking-wide text-gray-500">
                        Condition now
                    </label>
                    <select
                        v-model="conditionIn"
                        class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2.5 text-sm focus:border-[#005740] focus:outline-none focus:ring-1 focus:ring-[#005740]"
                    >
                        <option value="">Not recorded</option>
                        <option
                            v-for="condition in assetConditions"
                            :key="condition.id"
                            :value="condition.id"
                        >
                            {{ condition.condition_name }}
                        </option>
                    </select>
                    <p class="mt-1.5 text-[11px] text-gray-400">
                        Say how the item came back, so a damaged return is on
                        record.
                    </p>
                </div>

                <div>
                    <label class="mb-1.5 block text-xs font-semibold uppercase tracking-wide text-gray-500">
                        Notes
                    </label>
                    <textarea
                        v-model="notes"
                        rows="3"
                        placeholder="Anything to note about the return..."
                        class="w-full resize-none rounded-xl border border-gray-300 bg-white px-3 py-2.5 text-sm focus:border-[#005740] focus:outline-none focus:ring-1 focus:ring-[#005740]"
                    ></textarea>
                </div>
            </div>

            <!-- FOOTER -->
            <div
                class="flex items-center justify-end gap-3 border-t border-gray-100 px-6 py-4"
            >
                <button
                    type="button"
                    @click="emit('close')"
                    class="rounded-lg px-5 py-2.5 text-sm font-medium text-gray-600 transition-colors hover:bg-gray-100"
                >
                    Cancel
                </button>

                <button
                    type="button"
                    @click="submit"
                    :disabled="!picked.length"
                    class="flex items-center gap-2 rounded-lg bg-[#005740] px-6 py-2.5 text-sm font-semibold text-white transition-colors hover:bg-[#00452f] disabled:cursor-not-allowed disabled:opacity-50 disabled:hover:bg-[#005740]"
                >
                    <PackageCheck class="h-4 w-4" />
                    Take back {{ picked.length }} item(s)
                </button>
            </div>
        </div>
    </div>
</template>
