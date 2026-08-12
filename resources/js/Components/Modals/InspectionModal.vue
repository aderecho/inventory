<script setup>
import { defineProps, ref, computed, onBeforeUnmount, watch } from "vue";
import { useToast } from "primevue/usetoast";
import { X, Plus, ChevronDown } from "lucide-vue-next";

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
});

// Local conditions list (includes new conditions before submission)
const localConditions = ref([]);

// Conditions removed via hold-to-delete. We can't mutate `assetConditions`
// directly since it's a prop - this list is filtered out client-side while
// the parent handles the actual backend delete via the emitted event.
const removedConditionIds = ref([]);

const allConditions = computed(() => {
    const combined = [...props.assetConditions, ...localConditions.value];
    // Remove duplicates
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
//  - clear any client-side hides (removedConditionIds) so a failed delete
//    doesn't leave a condition permanently hidden in this modal instance.
//  - clear localConditions, since a just-added condition now exists for
//    real in props.assetConditions with its actual database id - keeping
//    the old temp-id entry around is what caused the duplicate-looking row.
//  - if the temp entry was the current selection, remap the selection to
//    the real id (matched by name) so it doesn't silently deselect.
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

// --- Custom condition dropdown ---
const isConditionOpen = ref(false);

function toggleConditionDropdown() {
    isConditionOpen.value = !isConditionOpen.value;
}

function selectCondition(condition) {
    inspectionForm.value.asset_condition_id = condition.id;
    isConditionOpen.value = false;
}

// --- Hold-to-delete (2.5s), no confirmation message ---
const HOLD_DURATION_MS = 2500;
const holdingId = ref(null);
let holdTimeouts = {};

// Radial progress ring math: circle radius 8 in a 20x20 viewBox.
// stroke-dashoffset animates from CIRCUMFERENCE (empty) to 0 (full red ring)
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
        // Came from the backend - hide it here, let the parent delete it for real
        removedConditionIds.value.push(condition.id);
        emit("delete-condition", condition);
    }

    if (inspectionForm.value.asset_condition_id === condition.id) {
        inspectionForm.value.asset_condition_id = null;
    }
}

onBeforeUnmount(() => {
    Object.values(holdTimeouts).forEach(clearTimeout);
    holdTimeouts = {};
});

const handleClose = () => {
    resetForm();
    showAddConditionModal.value = false;
    isConditionOpen.value = false;
    emit("close");
};

const handleSubmit = () => {
    if (
        !inspectionForm.value.asset_condition_id ||
        !inspectionForm.value.inspection_date
    ) {
        toast.add({
            severity: "warn",
            summary: "Validation",
            detail: "Please fill in the required fields.",
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
    };
};

const selectedItemCount = computed(() => props.selectedItems.length);
</script>

<template>
    <div
        v-if="show"
        class="fixed inset-0 bg-black/50 flex items-center justify-center p-4 z-50"
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
                >
                    <X class="w-5 h-5" />
                </button>
            </div>

            <!-- CONTENT -->
            <div class="flex flex-1 overflow-hidden">
                <!-- LEFT SIDE - FORM -->
                <div
                    class="w-full md:w-2/5 border-r border-gray-200 p-6 overflow-y-auto"
                >
                    <div class="space-y-5">
                        <!-- SELECTED ITEMS COUNT -->
                        <div
                            class="bg-[#E8F5EC] border border-[#B6DCC3] rounded-xl p-4"
                        >
                            <p class="text-sm font-semibold text-[#005740]">
                                {{ selectedItemCount }}
                                {{ selectedItemCount === 1 ? "item" : "items" }}
                                selected
                            </p>
                        </div>

                        <!-- ASSET CONDITION DROPDOWN (custom, supports hold-to-delete) -->
                        <div>
                            <label
                                class="block text-xs font-semibold text-gray-500 uppercase tracking-wide mb-2"
                            >
                                Asset Condition
                                <span class="text-red-500">*</span>
                            </label>
                            <div class="flex gap-2">
                                <div class="relative flex-1">
                                    <button
                                        type="button"
                                        @click="toggleConditionDropdown"
                                        class="w-full flex items-center justify-between px-3 py-2 border border-gray-300 rounded-lg text-sm bg-white focus:outline-none focus:ring-2 focus:ring-[#005740] focus:border-[#005740]"
                                    >
                                        <span
                                            :class="selectedCondition ? 'text-gray-800' : 'text-gray-400'"
                                        >
                                            {{ selectedCondition ? selectedCondition.condition_name : "Select a condition..." }}
                                        </span>
                                        <ChevronDown class="w-4 h-4 text-gray-400 flex-shrink-0" />
                                    </button>

                                    <ul
                                        v-if="isConditionOpen"
                                        class="absolute z-20 mt-1 w-full max-h-56 overflow-y-auto bg-white border border-gray-200 rounded-lg shadow-lg"
                                    >
                                        <li
                                            v-if="!allConditions.length"
                                            class="px-3 py-2 text-sm text-gray-400"
                                        >
                                            No conditions yet
                                        </li>

                                        <li
                                            v-for="condition in allConditions"
                                            :key="condition.id"
                                            class="group flex items-center justify-between gap-2 px-3 py-2 text-sm hover:bg-gray-50 cursor-pointer"
                                            @click="selectCondition(condition)"
                                        >
                                            <span
                                                :class="inspectionForm.asset_condition_id === condition.id ? 'font-semibold text-[#005740]' : 'text-gray-700'"
                                            >
                                                {{ condition.condition_name }}
                                            </span>

                                            <!-- HOLD 2.5s TO DELETE -->
                                            <div
                                                class="relative w-5 h-5 flex-shrink-0 cursor-pointer"
                                                title="Hold to delete"
                                                @click.stop
                                                @mousedown="startHold(condition)"
                                                @mouseup="cancelHold(condition)"
                                                @mouseleave="cancelHold(condition)"
                                                @touchstart.prevent="startHold(condition)"
                                                @touchend.prevent="cancelHold(condition)"
                                            >
                                                <svg viewBox="0 0 20 20" class="w-5 h-5 -rotate-90">
                                                    <circle
                                                        cx="10"
                                                        cy="10"
                                                        :r="RING_RADIUS"
                                                        fill="none"
                                                        stroke="currentColor"
                                                        stroke-width="3"
                                                        class="text-gray-200"
                                                    />
                                                    <circle
                                                        cx="10"
                                                        cy="10"
                                                        :r="RING_RADIUS"
                                                        fill="none"
                                                        stroke="currentColor"
                                                        stroke-width="3"
                                                        stroke-linecap="round"
                                                        class="text-red-500"
                                                        :stroke-dasharray="CIRCUMFERENCE"
                                                        :stroke-dashoffset="holdingId === condition.id ? 0 : CIRCUMFERENCE"
                                                        :style="{
                                                            transitionProperty: 'stroke-dashoffset',
                                                            transitionDuration: holdingId === condition.id ? '2500ms' : '150ms',
                                                            transitionTimingFunction: 'linear',
                                                        }"
                                                    />
                                                </svg>
                                                <X class="w-3 h-3 text-gray-500 absolute inset-0 m-auto pointer-events-none" />
                                            </div>
                                        </li>
                                    </ul>
                                </div>

                                <button
                                    @click="openAddConditionModal"
                                    class="px-3 py-2 bg-[#005740] hover:bg-[#0E6021] text-white rounded-lg transition-colors flex items-center gap-2 text-sm font-medium"
                                    title="Add new asset condition"
                                >
                                    <Plus class="w-4 h-4" />
                                    Add
                                </button>
                            </div>
                        </div>

                        <!-- INSPECTION DATE -->
                        <div>
                            <label
                                class="block text-xs font-semibold text-gray-500 uppercase tracking-wide mb-2"
                            >
                                Inspection Date
                                <span class="text-red-500">*</span>
                            </label>
                            <input
                                v-model="inspectionForm.inspection_date"
                                type="date"
                                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-[#005740] focus:border-[#005740] text-sm"
                            />
                        </div>

                        <!-- REMARKS -->
                        <div>
                            <label
                                class="block text-xs font-semibold text-gray-500 uppercase tracking-wide mb-2"
                            >
                                Remarks
                            </label>
                            <textarea
                                v-model="inspectionForm.remarks"
                                placeholder="Enter any additional remarks..."
                                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-[#005740] focus:border-[#005740] text-sm resize-none"
                                rows="6"
                            ></textarea>
                        </div>
                    </div>
                </div>

                <!-- RIGHT SIDE - SELECTED ITEMS LIST -->
                <div class="hidden md:flex md:w-3/5 flex-col bg-gray-50">
                    <div class="px-6 py-4 border-b border-gray-200 bg-white">
                        <h3 class="text-sm font-semibold text-gray-800">
                            Selected Items
                        </h3>
                    </div>

                    <!-- SCROLLABLE ITEMS LIST -->
                    <div class="flex-1 overflow-y-auto">
                        <ul class="divide-y divide-gray-200">
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
                        </ul>

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

            <!-- FOOTER - ACTION BUTTONS -->
            <div
                class="border-t border-gray-200 bg-gray-50 px-6 py-4 flex items-center justify-end gap-3"
            >
                <button
                    @click="handleClose"
                    class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors"
                >
                    Cancel
                </button>
                <button
                    @click="handleSubmit"
                    class="px-3 py-2 bg-[#005740] hover:bg-[#0E6021] text-white rounded-lg transition-colors flex items-center gap-2 text-sm font-medium"
                >
                    Submit Inspection
                </button>
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