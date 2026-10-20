<script setup>
import { defineProps, ref, computed, onBeforeUnmount, onMounted, watch } from "vue";
import { useToast } from "primevue/usetoast";
import { X, Plus } from "lucide-vue-next";

const props = defineProps({
    show: {
        type: Boolean,
        default: false,
    },
    selectedItems: {
        type: Array,
        default: () => [],
    },
    assetConditions: {
        type: Array,
        default: () => [],
    },
});

const emit = defineEmits(["close", "submit", "add-condition", "delete-condition"]);
const toast = useToast();

// Form state
const inspectionForm = ref({
    asset_condition_id: null,
    inspection_date: new Date().toISOString().split("T")[0],
    remarks: "",
});

// Condition modal state
const showAddConditionModal = ref(false);
const newCondition = ref({
    condition_name: "",
    description: "",
    disposition: "serviceable",
});

// What the condition means to the disposal process, not how it reads.
const CONDITION_MEANINGS = [
    { value: "serviceable", label: "Serviceable" },
    { value: "for_disposal", label: "For disposal" },
    { value: "disposed", label: "Disposed" },
];

// Local conditions list (includes new conditions before submission)
const localConditions = ref([]);

// Conditions removed via hold-to-delete. We can't mutate the assetConditions
// prop directly - this list is filtered out client-side while the parent
// handles the actual backend delete via the emitted event.
const removedConditionIds = ref([]);

const allConditions = computed(() => {
    const combined = [...props.assetConditions, ...localConditions.value];

    const unique = combined.filter(
        (condition, index, self) =>
            index === self.findIndex((c) => c.id === condition.id),
    );

    return unique.filter((c) => !removedConditionIds.value.includes(c.id));
});

const selectedCondition = computed(() =>
    allConditions.value.find(
        (c) => c.id === inspectionForm.value.asset_condition_id,
    ),
);

// Whenever the server sends a fresh assetConditions list - whether from a
// successful add, a successful delete, a failed one, or someone else's
// change - that list is the source of truth. At that point:
//  - clear any client-side hides so a failed delete does not leave a
//    condition permanently hidden in this modal instance.
//  - clear localConditions, since a just-added condition now exists for real
//    in props.assetConditions with its actual database id.
//  - if the temp entry was the current selection, remap the selection to the
//    real id (matched by name) so it does not silently deselect.
watch(
    () => props.assetConditions,
    (freshConditions) => {
        removedConditionIds.value = [];

        const selectedTemp = localConditions.value.find(
            (c) => c.id === inspectionForm.value.asset_condition_id,
        );

        if (selectedTemp) {
            const real = freshConditions.find(
                (c) => c.condition_name === selectedTemp.condition_name,
            );
            if (real) {
                inspectionForm.value.asset_condition_id = real.id;
            }
        }

        localConditions.value = [];
    },
);

// ------------------------------------------------------------------
// CONDITION PILLS
// ------------------------------------------------------------------
// Conditions come straight from the database, so the colour is derived from
// the name instead of being stored: something reading "Good" is green,
// "Disposal" is red, "Not good / poor / repair" is amber, anything else is
// neutral. First match wins, which is why "not good" is tested before "good".
function conditionTone(condition) {
    const name = String(condition?.condition_name ?? "").toLowerCase();

    if (/(dispos|condemn|scrap|lost|stolen|destroy|write-?off)/.test(name)) {
        return "danger";
    }

    if (
        /(not good|poor|damage|defect|repair|unserviceable|fair|needs|broken)/.test(
            name,
        )
    ) {
        return "warning";
    }

    if (
        /(good|excellent|serviceable|functional|working|new|ok|fine)/.test(name)
    ) {
        return "success";
    }

    return "neutral";
}

const TONE_CLASSES = {
    success: {
        on: "border-[#0E6021] bg-[#0E6021] text-white shadow-sm",
        off: "border-emerald-200 bg-emerald-50 text-emerald-700 hover:border-emerald-400 hover:bg-emerald-100",
    },
    danger: {
        on: "border-[#D71D1D] bg-[#D71D1D] text-white shadow-sm",
        off: "border-red-200 bg-red-50 text-red-700 hover:border-red-400 hover:bg-red-100",
    },
    warning: {
        on: "border-amber-500 bg-amber-400 text-[#3B3B3B] shadow-sm",
        off: "border-amber-200 bg-amber-50 text-amber-700 hover:border-amber-400 hover:bg-amber-100",
    },
    neutral: {
        on: "border-[#005740] bg-[#005740] text-white shadow-sm",
        off: "border-gray-300 bg-white text-gray-700 hover:border-[#005740]/40 hover:bg-gray-50",
    },
};

function isChosen(condition) {
    return inspectionForm.value.asset_condition_id === condition.id;
}

function pillClass(condition) {
    const tone = TONE_CLASSES[conditionTone(condition)];

    return [
        "inline-flex items-center gap-2 rounded-full border px-4 py-2 text-sm font-medium transition-colors",
        isChosen(condition) ? tone.on : tone.off,
    ].join(" ");
}

function selectCondition(condition) {
    inspectionForm.value.asset_condition_id = condition.id;
}

// --- Hold-to-delete (2.5s), no confirmation message ---
const HOLD_DURATION_MS = 2500;
const holdingId = ref(null);
let holdTimeouts = {};

// Radial progress ring math: circle radius 8 in a 20x20 viewBox.
// stroke-dashoffset animates from CIRCUMFERENCE (empty) to 0 (full ring)
// over HOLD_DURATION_MS while holding.
const RING_RADIUS = 8;
const CIRCUMFERENCE = 2 * Math.PI * RING_RADIUS;

function startHold(condition) {
    holdingId.value = condition.id;
    holdTimeouts[condition.id] = setTimeout(() => {
        deleteCondition(condition);
        holdingId.value = null;
    }, HOLD_DURATION_MS);
}

function cancelHold(condition) {
    if (holdTimeouts[condition.id]) {
        clearTimeout(holdTimeouts[condition.id]);
        delete holdTimeouts[condition.id];
    }
    if (holdingId.value === condition.id) {
        holdingId.value = null;
    }
}

function deleteCondition(condition) {
    if (condition.is_new) {
        // Only existed locally (added earlier in this session, not saved yet)
        localConditions.value = localConditions.value.filter(
            (c) => c.id !== condition.id,
        );
    } else {
        // Came from the backend - hide it here, let the parent delete it
        removedConditionIds.value.push(condition.id);
        emit("delete-condition", condition);
    }

    if (inspectionForm.value.asset_condition_id === condition.id) {
        inspectionForm.value.asset_condition_id = null;
    }
}

// ------------------------------------------------------------------
// SUBMIT / CLOSE
// ------------------------------------------------------------------
const selectedItemCount = computed(() => props.selectedItems.length);

const canSubmit = computed(
    () =>
        Boolean(inspectionForm.value.asset_condition_id) &&
        Boolean(inspectionForm.value.inspection_date) &&
        selectedItemCount.value > 0,
);

const missingReason = computed(() => {
    if (!selectedItemCount.value) return "Select at least one item first.";
    if (!inspectionForm.value.inspection_date) return "Set the inspection date.";
    if (!inspectionForm.value.asset_condition_id)
        return "Pick an asset condition to continue.";

    return "";
});

const handleClose = () => {
    resetForm();
    showAddConditionModal.value = false;
    emit("close");
};

const handleSubmit = () => {
    if (!canSubmit.value) {
        toast.add({
            severity: "warn",
            summary: "Validation",
            detail:
                missingReason.value || "Please fill in the required fields.",
            life: 3000,
        });

        return;
    }

    emit("submit", {
        ...inspectionForm.value,
        selectedItemIds: props.selectedItems.map((item) => item.id),
    });

    resetForm();
};

const resetForm = () => {
    inspectionForm.value = {
        asset_condition_id: null,
        inspection_date: new Date().toISOString().split("T")[0],
        remarks: "",
    };
    localConditions.value = [];
};

const openAddConditionModal = () => {
    showAddConditionModal.value = true;
    newCondition.value = {
        condition_name: "",
        description: "",
        disposition: "serviceable",
    };
};

const handleAddCondition = () => {
    if (!newCondition.value.condition_name.trim()) {
        toast.add({
            severity: "warn",
            summary: "Validation",
            detail: "Please enter a condition name.",
            life: 3000,
        });

        return;
    }

    const condition = {
        id: Date.now(), // Temporary ID for UI
        condition_name: newCondition.value.condition_name,
        description: newCondition.value.description,
        disposition: newCondition.value.disposition,
        is_new: true, // Mark as new condition
    };

    localConditions.value.push(condition);
    inspectionForm.value.asset_condition_id = condition.id;

    // Emit event to parent to handle backend creation if needed
    emit("add-condition", condition);

    showAddConditionModal.value = false;
    newCondition.value = {
        condition_name: "",
        description: "",
        disposition: "serviceable",
    };
};

// Escape closes the topmost layer.
function onKeydown(event) {
    if (event.key !== "Escape") return;

    if (showAddConditionModal.value) {
        showAddConditionModal.value = false;
        return;
    }

    if (props.show) {
        handleClose();
    }
}

onMounted(() => window.addEventListener("keydown", onKeydown));

onBeforeUnmount(() => {
    Object.values(holdTimeouts).forEach(clearTimeout);
    holdTimeouts = {};
    window.removeEventListener("keydown", onKeydown);
});
</script>


<template>
    <div
        v-if="show"
        class="fixed inset-0 modal-backdrop flex items-center justify-center p-4 z-50"
    >
        <div
            class="bg-white rounded-2xl shadow-2xl w-full max-w-5xl max-h-[90vh] overflow-hidden flex flex-col"
        >
            <!-- HEADER -->
            <div
                class="bg-gradient-to-r from-[#003d2c] via-[#005740] to-[#00795a] px-6 py-4 flex items-center justify-between flex-shrink-0"
            >
                <div>
                    <h2 class="text-lg font-bold text-white">
                        Asset Inspection
                    </h2>

                    <p class="text-xs text-white/70 mt-0.5">
                        Inspect selected inventory items.
                    </p>
                </div>

                <button
                    @click="handleClose"
                    class="text-white/80 hover:text-white hover:bg-white/10 rounded-full w-9 h-9 flex items-center justify-center transition-colors"
                    title="Close"
                >
                    <X class="w-5 h-5" />
                </button>
            </div>

            <!-- INSPECTION DATE BAR -->
            <div
                class="flex flex-col gap-2 border-b border-gray-200 bg-gray-50 px-6 py-3 sm:flex-row sm:items-center sm:justify-between flex-shrink-0"
            >
                <div class="flex flex-wrap items-center gap-3">
                    <label
                        for="inspection-date"
                        class="text-[11px] font-semibold uppercase tracking-wide text-gray-500"
                    >
                        Inspection Date
                        <span class="text-red-500">*</span>
                    </label>

                    <input
                        id="inspection-date"
                        v-model="inspectionForm.inspection_date"
                        type="date"
                        class="rounded-lg border border-gray-300 bg-white px-3 py-1.5 text-sm text-gray-700 transition focus:border-[#005740] focus:outline-none focus:ring-2 focus:ring-[#005740]/20"
                    />
                </div>

                <p class="text-xs text-gray-500">
                    The same condition is applied to every item you ticked.
                </p>
            </div>

            <!-- CONTENT -->
            <div class="flex flex-1 overflow-hidden">
                <!-- LEFT SIDE - CONDITION PICKER -->
                <div
                    class="w-full md:w-2/5 border-r border-gray-200 p-6 overflow-y-auto"
                >
                    <div
                        class="rounded-2xl border border-gray-200 bg-gray-50 p-4"
                    >
                        <div class="flex items-start justify-between gap-3">
                            <div>
                                <p
                                    class="text-xs font-semibold uppercase tracking-wide text-gray-500"
                                >
                                    Asset Condition
                                    <span class="text-red-500">*</span>
                                </p>

                                <p class="mt-1 text-xs text-gray-400">
                                    Pick the condition for all selected items.
                                </p>
                            </div>

                            <button
                                type="button"
                                @click="openAddConditionModal"
                                class="flex h-9 w-9 flex-shrink-0 items-center justify-center rounded-full bg-[#0E6021] text-white transition-colors hover:bg-[#19703a]"
                                title="Add new asset condition"
                            >
                                <Plus class="w-4 h-4" />
                            </button>
                        </div>

                        <!-- CONDITION PILLS -->
                        <div
                            class="mt-4 flex max-h-60 flex-wrap gap-2 overflow-y-auto"
                        >
                            <button
                                v-for="condition in allConditions"
                                :key="condition.id"
                                type="button"
                                :class="pillClass(condition)"
                                :title="condition.description || condition.condition_name"
                                @click="selectCondition(condition)"
                            >
                                <span>{{ condition.condition_name }}</span>

                                <!-- HOLD 2.5s TO DELETE (only on the chosen pill) -->
                                <span
                                    v-if="isChosen(condition)"
                                    class="relative flex h-5 w-5 flex-shrink-0 cursor-pointer items-center justify-center"
                                    title="Hold to delete this condition"
                                    @click.stop
                                    @mousedown.stop="startHold(condition)"
                                    @mouseup="cancelHold(condition)"
                                    @mouseleave="cancelHold(condition)"
                                    @touchstart.prevent.stop="startHold(condition)"
                                    @touchend.prevent="cancelHold(condition)"
                                >
                                    <svg
                                        viewBox="0 0 20 20"
                                        class="w-5 h-5 -rotate-90"
                                    >
                                        <circle
                                            cx="10"
                                            cy="10"
                                            :r="RING_RADIUS"
                                            fill="none"
                                            stroke="currentColor"
                                            stroke-width="3"
                                            class="opacity-30"
                                        />
                                        <circle
                                            cx="10"
                                            cy="10"
                                            :r="RING_RADIUS"
                                            fill="none"
                                            stroke="currentColor"
                                            stroke-width="3"
                                            stroke-linecap="round"
                                            :stroke-dasharray="CIRCUMFERENCE"
                                            :stroke-dashoffset="
                                                holdingId === condition.id
                                                    ? 0
                                                    : CIRCUMFERENCE
                                            "
                                            :style="{
                                                transitionProperty:
                                                    'stroke-dashoffset',
                                                transitionDuration:
                                                    holdingId === condition.id
                                                        ? '2500ms'
                                                        : '150ms',
                                                transitionTimingFunction:
                                                    'linear',
                                            }"
                                        />
                                    </svg>
                                    <X
                                        class="pointer-events-none absolute inset-0 m-auto h-3 w-3"
                                    />
                                </span>
                            </button>

                            <p
                                v-if="!allConditions.length"
                                class="text-sm text-gray-400"
                            >
                                No conditions yet - add one with the + button.
                            </p>
                        </div>
                    </div>

                    <!-- CURRENT CHOICE -->
                    <div
                        class="mt-4 rounded-xl border px-4 py-3 transition-colors"
                        :class="
                            selectedCondition
                                ? 'border-[#005740]/20 bg-[#005740]/[0.06]'
                                : 'border-gray-200 bg-gray-50'
                        "
                    >
                        <p
                            class="text-[11px] uppercase tracking-wide text-gray-400"
                        >
                            Marking as
                        </p>

                        <p
                            class="mt-0.5 text-sm font-semibold"
                            :class="
                                selectedCondition
                                    ? 'text-[#005740]'
                                    : 'text-gray-400'
                            "
                        >
                            {{
                                selectedCondition
                                    ? selectedCondition.condition_name
                                    : "No condition picked yet"
                            }}
                        </p>

                        <p class="mt-1 text-xs text-gray-500">
                            {{ selectedItemCount }}
                            {{ selectedItemCount === 1 ? "item" : "items" }}
                            will be saved with this condition.
                        </p>
                    </div>

                    <p class="mt-3 text-[11px] leading-relaxed text-gray-400">
                        Tip: hold the &times; on the chosen condition for 2.5
                        seconds to delete it.
                    </p>
                </div>

                <!-- RIGHT SIDE - SELECTED ITEMS LIST -->
                <div class="hidden md:flex md:w-3/5 flex-col bg-gray-50">
                    <!-- Header sits on the same margins as the item cards. -->
                    <div
                        class="mx-4 mt-4 flex items-center justify-between gap-3 rounded-xl bg-[#005740] px-4 py-3 shadow-sm"
                    >
                        <h3 class="text-sm font-semibold text-white">
                            {{
                                selectedItemCount === 1
                                    ? "Selected Item"
                                    : "Selected Items"
                            }}
                        </h3>

                        <span
                            class="flex h-7 min-w-[28px] items-center justify-center rounded-full bg-white/20 px-2 text-xs font-bold text-white"
                        >
                            {{ selectedItemCount }}
                        </span>
                    </div>

                    <!-- SCROLLABLE ITEMS LIST -->
                    <div class="flex-1 overflow-y-auto">
                        <div>
                            <div
                                v-for="item in selectedItems"
                                :key="item.id"
                                class="bg-white border border-gray-200 rounded-xl shadow-sm p-4 mx-4 my-3 hover:shadow-md transition"
                            >
                                <div class="space-y-2">
                                    <div>
                                        <p
                                            class="text-[11px] uppercase tracking-wide font-semibold text-gray-400"
                                        >
                                            Property Number
                                        </p>
                                        <p class="font-bold text-gray-800">
                                            {{ item.property_number || "N/A" }}
                                        </p>
                                    </div>
                                    <div class="mt-3">
                                        <p
                                            class="text-[11px] uppercase tracking-wide font-semibold text-gray-400"
                                        >
                                            Item Name
                                        </p>
                                        <p class="text-gray-700">
                                            {{ item.item_name }}
                                        </p>
                                    </div>
                                    <div
                                        v-if="item.serial_number"
                                        class="mt-3 pt-3 border-t border-gray-100"
                                    >
                                        <p
                                            class="text-[11px] uppercase tracking-wide font-semibold text-gray-400"
                                        >
                                            Serial Number
                                        </p>

                                        <p class="text-gray-700">
                                            {{ item.serial_number }}
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- EMPTY STATE -->
                        <div
                            v-if="!selectedItems.length"
                            class="flex items-center justify-center h-full"
                        >
                            <p class="text-gray-400 text-sm">
                                No items selected
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- REMARKS -->
            <div
                class="border-t border-gray-200 bg-white px-6 py-4 flex-shrink-0"
            >
                <label
                    for="inspection-remarks"
                    class="mb-2 block text-xs font-semibold uppercase tracking-wide text-gray-500"
                >
                    Remarks
                </label>

                <textarea
                    id="inspection-remarks"
                    v-model="inspectionForm.remarks"
                    placeholder="Anything worth noting about this inspection..."
                    class="w-full resize-none rounded-xl border border-gray-300 px-4 py-3 text-sm transition focus:border-[#005740] focus:outline-none focus:ring-2 focus:ring-[#005740]/20"
                    rows="3"
                ></textarea>
            </div>

            <!-- FOOTER - ACTION BUTTONS -->
            <div
                class="border-t border-gray-200 bg-gray-50 px-6 py-4 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-end"
            >
                <p
                    v-if="!canSubmit"
                    class="text-xs font-medium text-amber-600 sm:mr-auto"
                >
                    {{ missingReason }}
                </p>

                <div class="flex items-center justify-end gap-3">
                    <button
                        @click="handleClose"
                        class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors"
                    >
                        Cancel
                    </button>
                    <button
                        @click="handleSubmit"
                        :disabled="!canSubmit"
                        class="px-3 py-2 bg-[#005740] hover:bg-[#0E6021] text-white rounded-lg transition-colors flex items-center gap-2 text-sm font-medium disabled:cursor-not-allowed disabled:opacity-40 disabled:hover:bg-[#005740]"
                    >
                        Submit Inspection
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- ADD CONDITION MODAL -->
    <div
        v-if="showAddConditionModal"
        class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 backdrop-blur-sm p-4"
    >
        <div
            class="w-full max-w-lg overflow-hidden rounded-2xl bg-white shadow-2xl"
        >
            <!-- Header -->
            <div
                class="flex items-center justify-between bg-gradient-to-r from-[#003d2c] via-[#005740] to-[#00795a] px-6 py-5"
            >
                <div>
                    <h2 class="text-xl font-bold text-white">
                        Add Asset Condition
                    </h2>

                    <p class="mt-1 text-xs text-white/75">
                        Create a new inspection condition.
                    </p>
                </div>

                <button
                    @click="showAddConditionModal = false"
                    class="flex h-9 w-9 items-center justify-center rounded-full text-white/80 transition hover:bg-white/10 hover:text-white"
                >
                    <X class="w-5 h-5" />
                </button>
            </div>

            <!-- Body -->
            <div class="space-y-5 bg-gray-50 px-6 py-6">
                <div>
                    <label
                        class="mb-2 block text-xs font-semibold uppercase tracking-wide text-gray-500"
                    >
                        Condition Name
                        <span class="text-red-500">*</span>
                    </label>

                    <input
                        v-model="newCondition.condition_name"
                        type="text"
                        placeholder="Excellent, Good, Fair..."
                        class="w-full rounded-xl border border-gray-300 bg-white px-4 py-3 text-sm transition focus:border-[#005740] focus:outline-none focus:ring-2 focus:ring-[#005740]/20"
                    />

                    <div class="mt-4">
                        <label
                            class="mb-2 block text-xs font-semibold uppercase tracking-wide text-gray-500"
                        >
                            What it means <span class="text-red-500">*</span>
                        </label>

                        <div class="flex flex-wrap gap-2">
                            <label
                                v-for="meaning in CONDITION_MEANINGS"
                                :key="meaning.value"
                                class="inline-flex cursor-pointer items-center gap-2 rounded-lg border bg-white px-3 py-2 text-xs font-medium transition"
                                :class="
                                    newCondition.disposition === meaning.value
                                        ? 'border-[#005740] text-[#005740] ring-1 ring-[#005740]/25'
                                        : 'border-gray-200 text-gray-600 hover:border-gray-300'
                                "
                            >
                                <input
                                    v-model="newCondition.disposition"
                                    type="radio"
                                    :value="meaning.value"
                                    class="h-3.5 w-3.5 accent-[#0E6021]"
                                />
                                {{ meaning.label }}
                            </label>
                        </div>
                    </div>
                </div>

                <div>
                    <label
                        class="mb-2 block text-xs font-semibold uppercase tracking-wide text-gray-500"
                    >
                        Description
                    </label>

                    <textarea
                        v-model="newCondition.description"
                        rows="4"
                        placeholder="Describe this asset condition..."
                        class="w-full rounded-xl border border-gray-300 bg-white px-4 py-3 text-sm transition focus:border-[#005740] focus:outline-none focus:ring-2 focus:ring-[#005740]/20 resize-none"
                    ></textarea>
                </div>
            </div>

            <!-- Footer -->
            <div
                class="flex justify-end gap-3 border-t border-gray-100 bg-white px-6 py-5"
            >
                <button
                    @click="showAddConditionModal = false"
                    class="rounded-lg px-5 py-2.5 text-sm font-medium text-gray-600 transition hover:bg-gray-100"
                >
                    Cancel
                </button>

                <button
                    @click="handleAddCondition"
                    class="rounded-lg bg-[#005740] px-6 py-2.5 text-sm font-semibold text-white transition hover:bg-[#00452f] hover:shadow-lg"
                >
                    Add Condition
                </button>
            </div>
        </div>
    </div>
</template>
