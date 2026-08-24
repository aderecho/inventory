<script setup>
import { useForm, router } from "@inertiajs/vue3";
import { computed, ref, watch } from "vue";
import Toast from "primevue/toast";
import { useToast } from "primevue/usetoast";
import "/node_modules/@vueform/multiselect/themes/default.css";
import Multiselect from "@vueform/multiselect";
import DatePicker from "primevue/datepicker";
import SessionTimeoutWarning from "@/Components/SessionTimeoutWarning.vue";

const props = defineProps({
    mode: { type: String, default: "create" },
    accountableField: { type: Array, default: () => [] },
    adminProfiles: { type: Array, default: () => [] },
    itemSelectedField: { type: Array, default: () => [] },
    selectedIDs: { type: Array, default: () => [] },
    items: { type: Object, default: () => ({ data: [] }) },
    accPerson: { type: Object, default: () => ({ data: [] }) },
    users: { type: Array, default: () => [] },
    userProfiles: { type: Array, default: () => [] },
    rooms: { type: Array, default: () => [] }, // <-- Add this
    viewItem: { type: Array, default: () => [] },
    item: { type: Object, default: () => ({}) },
});

const selectedCategory = ref("");
const firstNumberError = ref("");
const emit = defineEmits(["submit", "close", "created"]);
const toast = useToast();

const form = useForm({
    inventory_item_id: [],
    accountable_persons_id: "",
    issued_by_id: "",
    room_id: "",
    category: "",
    created_by: "",
    par_date: "",
    remarks: "",
});

const itemMap = computed(() => {
    const map = {};
    props.items.data?.forEach((item) => {
        map[item.id] = item;
    });
    return map;
});

const availableAccountableUsers = computed(() => {
    const assignedPersonIds = new Set();

    props.selectedIDs.forEach((id) => {
        const item = itemMap.value[id];

        const personId =
            item?.latest_acknowledgement_item?.accountable_person?.id ??
            item?.latest_acknowledgement_item?.accountable_persons_id ??
            item?.accountable_person?.id ??
            item?.accountable_persons_id;

        if (personId !== null && personId !== undefined) {
            assignedPersonIds.add(Number(personId));
        }
    });

    return (props.userProfiles ?? []).filter(
        (user) => !assignedPersonIds.has(Number(user.id)),
    );
});

const availableIssuedByUsers = computed(() => {
    return props.adminProfiles ?? [];
});

function submit() {
    if (props.selectedIDs.length > 0) {
        const firstNumbers = props.selectedIDs.map((id) => {
            const item = itemMap.value[id];
            if (!item?.property_number) return null;
            return item.property_number.split("-")[0];
        });

        const uniqueFirstNumbers = [...new Set(firstNumbers)];

        if (uniqueFirstNumbers.length > 1) {
            firstNumberError.value =
                "All selected items must have the same Category in Property Number.";
            return;
        }
    }

    form.inventory_item_id = props.selectedIDs;
    form.created_by = props.userProfiles[0]?.id ?? null;

    let updateCategoryPromise;

    if (selectedCategory.value && props.selectedIDs.length) {
        updateCategoryPromise = new Promise((resolve) => {
            router.put(
                route("inventory.items.update-category"),
                {
                    inventory_item_ids: props.selectedIDs,
                    category: selectedCategory.value,
                },
                {
                    onSuccess: () => resolve(),
                    onError: () => resolve(),
                },
            );
        });
    } else {
        updateCategoryPromise = Promise.resolve();
    }

    updateCategoryPromise.then(() => {
        if (props.mode === "edit") {
            if (!form.id) {
                console.error("Edit mode but form.id is missing", form);
                return;
            }

            form.put(route("items.update", form.id), {
                onSuccess: () => {
                    toast.add({
                        severity: "success",
                        summary: "Updated",
                        detail: "Item updated successfully.",
                        life: 5000,
                    });

                    emit("close");
                    emit("submit", form);
                },
                onError: (errors) => {
                    toast.add({
                        severity: "warn",
                        summary: "Validation Failed",
                        detail: "Please check the highlighted fields.",
                        life: 5000,
                    });

                    console.error("Update failed", errors);
                },
            });
        } else {
            form.post(route("inventory.acknowledgements.store"), {
                onSuccess: () => {
                    toast.add({
                        severity: "success",
                        summary: "Created",
                        detail: "Item assigned successfully.",
                        life: 5000,
                    });

                        const printedIds = [...props.selectedIDs];

                    emit("close");
                        emit("created");
                        emit("submit", form);
                        emit("assigned", printedIds);
                    selectedCategory.value = "";
                    form.reset();
                },
                onError: (errors) => {
                    toast.add({
                        severity: "error",
                        summary: "Validation Failed",
                        detail: "Please fill up required fields.",
                        life: 5000,
                    });

                    console.error("Create failed", errors);
                },
            });
        }
    });
}

// Generates a category like "233-2025-12" from the first selected item's property_number
function generateCategoryFromFirstSelected() {
    if (!props.selectedIDs.length) return "";

    const firstID = props.selectedIDs[0];
    const item = itemMap.value[firstID];
    if (!item) return "";

    // Extract the first number before the dash
    const propertyNumber = item.property_number || "";
    const firstNumber = propertyNumber.split("-")[0] || "";

    const now = new Date();
    const year = now.getFullYear();
    const month = String(now.getMonth() + 1).padStart(2, "0");

    return `${firstNumber}-${year}-${month}-`;
}

// Watch selectedIDs prop and initialize selectedCategory from first selected item
watch(
    () => props.selectedIDs,
    (newVal) => {
        if (newVal && newVal.length > 0) {
            const firstItem = itemMap.value[newVal[0]];

            if (firstItem) {
                selectedCategory.value = firstItem.category || "";
                form.category = generateCategoryFromFirstSelected();

                // Initialize room from the selected item
                form.room_id = Number(firstItem.room_id);
            }
        } else {
            selectedCategory.value = "";
            form.category = "";
            form.room_id = null;
        }
    },
    { immediate: true },
);

watch(
    () => props.mode,
    (mode) => {
        if (mode === "create" && !form.par_date) {
            form.par_date = getTodayLocalDate();
        }
    },
    { immediate: true },
);

const isClosing = ref(false);

function closeWithAnimation() {
    isClosing.value = true;

    // Match your animation duration: popOut = 0.5s
    setTimeout(() => {
        emit("close");
        isClosing.value = false; // reset for next open
    }, 200);
}

function getNestedValue(obj, path) {
    if (!obj || !path) return null;
    return path.split(".").reduce((o, k) => (o ? o[k] : null), obj);
}

function getViewValue(view) {
    const rawValue = getNestedValue(props.item, view.key);

    if (view.format) {
        return view.format(rawValue);
    }
    return rawValue ?? "N/A";
}

function formatLocalDate(dateValue) {
    if (!dateValue) return null;

    const date = new Date(dateValue);
    const year = date.getFullYear();
    const month = String(date.getMonth() + 1).padStart(2, "0");
    const day = String(date.getDate()).padStart(2, "0");

    return `${year}-${month}-${day}`;
}

function getTodayLocalDate() {
    const today = new Date();
    return formatLocalDate(today);
}

function roomSearchFilter(option, query) {
    if (!query) return true;

    const search = query.toLowerCase().trim();

    const searchFields = [
        "room_name",
        "room_code",
        "description",
        "building",
        "building_name",
        "capacity",
    ];

    return searchFields.some((field) => {
        const value = option?.[field];

        if (value === null || value === undefined) {
            return false;
        }

        return String(value).toLowerCase().includes(search);
    });
}
</script>

<template>
    <SessionTimeoutWarning />
    <div
        class="fixed inset-0 bg-black/50 flex items-center justify-center p-4 z-50"
        @click="closeWithAnimation"
    >
        <div
            :class="[
                'bg-white rounded-2xl w-full max-w-6xl shadow-2xl overflow-hidden flex flex-col max-h-[92vh]',
                isClosing ? 'animate-pop-out' : 'animate-pop-in',
            ]"
            @click.stop
        >
            <Toast />

            <!-- Header -->
            <div
                class="bg-gradient-to-r from-[#003d2c] via-[#005740] to-[#00795a] px-6 py-5 flex items-center justify-between flex-shrink-0"
            >
                <div>
                    <h3 class="text-lg font-bold text-white">
                        {{
                            mode === "edit"
                                ? "Edit Item"
                                : mode === "view"
                                  ? "Item Details"
                                  : "Assign Item"
                        }}
                    </h3>
                    <p class="text-xs text-white/70 mt-0.5">
                        {{
                            mode === "edit"
                                ? "Update this assignment's details."
                                : mode === "view"
                                  ? "View assignment information."
                                  : "Assign selected items to an accountable person."
                        }}
                    </p>
                </div>
                <button
                    @click="closeWithAnimation"
                    class="text-white/80 hover:text-white hover:bg-white/10 rounded-full h-9 w-9 flex items-center justify-center transition-colors"
                    title="Close"
                >
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>

            <form
                @submit.prevent="submit"
                v-if="mode !== 'view'"
                class="flex flex-col flex-1 overflow-hidden"
            >
                <div class="p-6 overflow-y-auto flex-1">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div class="space-y-6">
                            <!-- PAR/ICS NUMBER -->
                            <div class="flex justify-between gap-4">
                                <div class="flex flex-col flex-1">
                                    <label
                                        class="block text-xs font-semibold text-gray-500 uppercase tracking-wide mb-1.5"
                                        >PAR/ICS Number</label
                                    >
                                    <input
                                        type="text"
                                        v-model="form.category"
                                        placeholder="Enter new category"
                                        :class="[
                                            'w-full rounded-md px-3 py-3 bg-[#F8F8F8] text-[#3B3B3B] text-sm focus:ring-1 focus:outline-none border',
                                            form.errors.category
                                                ? 'border-red-500 focus:ring-red-500 focus:border-red-500'
                                                : 'border-gray-300 focus:ring-[#005740] focus:border-[#005740]',
                                        ]"
                                    />
                                    <div
                                        v-if="form.errors.category"
                                        class="text-red-500 text-xs mt-1"
                                    >
                                        {{ form.errors.category }}
                                    </div>
                                </div>

                                <div class="flex flex-col">
                                    <label
                                        class="block text-xs font-semibold text-gray-500 uppercase tracking-wide mb-1.5"
                                        >PAR Date</label
                                    >
                                    <DatePicker
                                        v-model="form.par_date"
                                        placeholder="Select date"
                                        date-format="yy-mm-dd"
                                        :show-time="false"
                                        @update:modelValue="
                                            (val) =>
                                                (form.par_date = val
                                                    ? formatLocalDate(val)
                                                    : null)
                                        "
                                    />
                                    <div
                                        v-if="form.errors.par_date"
                                        class="text-red-500 text-xs mt-1"
                                    >
                                        {{ form.errors.par_date }}
                                    </div>
                                </div>
                            </div>

                            <!-- ACCOUNTABLE FIELD -->
                            <div class="flex justify-between gap-4">
                                <div
                                    v-for="accf in accountableField"
                                    :key="accf.model"
                                    class="flex flex-col flex-1"
                                >
                                    <label
                                        class="block text-xs font-semibold text-gray-500 uppercase tracking-wide mb-1.5"
                                    >
                                        {{ accf.label }}
                                    </label>
                                    <Multiselect
                                        v-model="form[accf.model]"
                                        :options="
                                            accf.model ===
                                            'accountable_persons_id'
                                                ? availableAccountableUsers
                                                : accf.model === 'issued_by_id'
                                                  ? availableIssuedByUsers
                                                  : props[accf.name]
                                        "
                                        :searchable="true"
                                        :value-prop="accf.value"
                                        :label="accf.option"
                                        :track-by="accf.option"
                                        placeholder="Select"
                                        :class="accf.class"
                                    />
                                    <div
                                        v-if="form.errors[accf.model]"
                                        class="text-red-500 text-xs mt-1"
                                    >
                                        {{ form.errors[accf.model] }}
                                    </div>
                                </div>
                            </div>
                            <!-- ROOM -->
                            <div>
                                <label
                                    class="block text-xs font-semibold text-gray-500 uppercase tracking-wide mb-1.5"
                                >
                                    Room <span class="text-red-500">*</span>
                                </label>

                                <Multiselect
                                    v-model="form.room_id"
                                    :options="props.rooms"
                                    mode="single"
                                    valueProp="id"
                                    trackBy="id"
                                    label="room_name"
                                    :object="false"
                                    :searchable="true"
                                    :canClear="false"
                                    :search-filter="roomSearchFilter"
                                    placeholder="Select a room"
                                >
                                    <template #option="{ option }">
                                        <div class="py-1">
                                            <div
                                                class="font-medium text-gray-900"
                                            >
                                                {{ option.room_name }}
                                            </div>
                                            <div class="text-xs text-gray-500">
                                                {{ option.description }}
                                            </div>
                                        </div>
                                    </template>

                                    <template #singlelabel="{ value }">
                                        <div
                                            v-if="
                                                props.rooms.find(
                                                    (r) => r.id === value,
                                                )
                                            "
                                            class="py-1"
                                        >
                                            <div
                                                class="font-medium text-gray-900"
                                            >
                                                {{
                                                    props.rooms.find(
                                                        (r) => r.id === value,
                                                    ).room_name
                                                }}
                                            </div>

                                            <div class="text-xs text-gray-500">
                                                {{
                                                    props.rooms.find(
                                                        (r) => r.id === value,
                                                    ).description
                                                }}
                                            </div>
                                        </div>
                                    </template>
                                </Multiselect>

                                <div
                                    v-if="form.errors.room_id"
                                    class="text-red-500 text-xs mt-1"
                                >
                                    {{ form.errors.room_id }}
                                </div>
                            </div>

                            <!-- REMARKS -->
                            <div>
                                <label
                                    class="block text-xs font-semibold text-gray-500 uppercase tracking-wide mb-1.5"
                                    >Remarks</label
                                >
                                <textarea
                                    v-model="form.remarks"
                                    placeholder="Input a remarks"
                                    class="w-full h-56 rounded-md border border-gray-300 px-3 py-2 bg-[#F8F8F8] text-[#3B3B3B] text-sm focus:ring-1 focus:ring-[#005740] focus:outline-none focus:border-[#005740]"
                                ></textarea>
                                <div
                                    v-if="form.errors.remarks"
                                    class="text-red-500 text-xs mt-1"
                                >
                                    {{ form.errors.remarks }}
                                </div>
                            </div>
                        </div>

                        <div class="space-y-4">
                            <!-- ITEM SELECTED -->
                            <div
                                v-if="firstNumberError"
                                class="text-red-500 text-xs mb-2"
                            >
                                {{ firstNumberError }}
                            </div>
                            <div
                                v-for="select in itemSelectedField"
                                :key="select.model"
                            >
                                <div>
                                    <label
                                        class="block text-xs font-semibold text-gray-500 uppercase tracking-wide mb-1.5"
                                        >{{ select.label }}</label
                                    >
                                    <div
                                        class="bg-[#F8F8F8] border border-gray-300 rounded-md h-[calc(100vh-360px)] md:h-[calc(100vh-420px)] p-3 overflow-y-auto"
                                    >
                                        <div v-if="selectedIDs.length === 0">
                                            <p class="text-gray-500 text-sm">
                                                No items selected
                                            </p>
                                        </div>
                                        <div>
                                            <ul class="space-y-2">
                                                <li
                                                    v-for="id in selectedIDs"
                                                    :key="id"
                                                    class="bg-white border border-gray-200 p-2.5 rounded-md shadow-sm text-sm"
                                                >
                                                    <p class="gap-1 flex">
                                                        <strong
                                                            class="text-[#005740]"
                                                            >Item Name:</strong
                                                        >
                                                        <span
                                                            class="text-[#3B3B3B]"
                                                            >{{
                                                                itemMap[id]
                                                                    ?.item_name
                                                            }}</span
                                                        >
                                                    </p>

                                                    <p class="gap-1 flex">
                                                        <strong
                                                            class="text-[#005740]"
                                                            >Property
                                                            Number:</strong
                                                        >
                                                        <span
                                                            class="text-[#3B3B3B]"
                                                            >{{
                                                                itemMap[id]
                                                                    ?.property_number
                                                            }}</span
                                                        >
                                                    </p>

                                                    <p class="gap-1 flex">
                                                        <strong
                                                            class="text-[#005740]"
                                                            >PO Number:</strong
                                                        >
                                                        <span
                                                            class="text-[#3B3B3B]"
                                                            >{{
                                                                itemMap[id]
                                                                    ?.po_number
                                                            }}</span
                                                        >
                                                    </p>

                                                    <p class="gap-1 flex">
                                                        <strong
                                                            class="text-[#005740]"
                                                            >Quantity:</strong
                                                        >
                                                        <span
                                                            class="text-[#3B3B3B]"
                                                            >{{
                                                                itemMap[id]
                                                                    ?.quantity
                                                            }}</span
                                                        >
                                                    </p>
                                                </li>
                                            </ul>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Footer -->
                <div
                    class="flex justify-end gap-3 px-6 py-4 border-t border-gray-100 flex-shrink-0"
                >
                    <button
                        type="button"
                        @click="closeWithAnimation"
                        class="px-6 py-2.5 rounded-lg text-sm font-medium text-gray-600 hover:bg-gray-100 transition-colors"
                    >
                        Cancel
                    </button>
                    <button
                        type="submit"
                        :disabled="form.processing"
                        class="bg-gradient-to-r from-[#005740] to-[#00795a] text-white px-8 py-2.5 rounded-lg text-sm font-semibold hover:shadow-md hover:from-[#00432f] hover:to-[#006548] transition-all disabled:opacity-60"
                    >
                        {{
                            mode === "edit"
                                ? "Update"
                                : mode === "view"
                                  ? "Assigned Details"
                                  : "Assign"
                        }}
                    </button>
                </div>
            </form>

            <!-- VIEW MODAL -->
            <div v-else class="flex flex-col flex-1 overflow-hidden">
                <div class="p-6 overflow-y-auto flex-1">
                    <div
                        class="grid grid-cols-1 sm:grid-cols-2 gap-x-6 gap-y-3"
                    >
                        <div
                            v-for="view in viewItem"
                            :key="view.key"
                            class="flex items-start justify-between gap-4 border-b pb-2"
                        >
                            <!-- LABEL -->
                            <div
                                class="text-sm font-semibold text-[#1f2d27] w-1/2"
                            >
                                {{ view.label }}:
                            </div>

                            <!-- VALUE -->
                            <div
                                class="text-sm font-medium text-gray-600 w-1/2 text-right"
                                v-html="getViewValue(view)"
                            />
                        </div>
                    </div>
                </div>

                <!-- BUTTON -->
                <div
                    class="flex justify-end px-6 py-4 border-t border-gray-100 flex-shrink-0"
                >
                    <button
                        @click="closeWithAnimation"
                        class="px-6 py-2.5 rounded-lg text-sm font-medium text-gray-600 hover:bg-gray-100 transition-colors"
                    >
                        Back
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>
<style>
:deep(.multiselect) {
    color: #374151 !important;
    background: #f8f8f8 !important;
    border: 1px solid #d1d5db !important;
}

:deep(.multiselect-single-label) {
    color: #111827 !important;
}

:deep(.multiselect-single-label-text) {
    color: #111827 !important;
}

:deep(.multiselect-placeholder) {
    color: #9ca3af !important;
}

:deep(.multiselect-option) {
    color: #111827 !important;
}

:deep(.multiselect-option.is-selected) {
    color: #111827 !important;
}
</style>
