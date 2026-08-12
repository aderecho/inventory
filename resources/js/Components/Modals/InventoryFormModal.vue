<script setup>
import { useForm, usePage } from "@inertiajs/vue3";
import { computed, watch, ref, nextTick } from "vue";
import Toast from "primevue/toast";
import { useToast } from "primevue/usetoast";
import Multiselect from "@vueform/multiselect";
import "/node_modules/@vueform/multiselect/themes/default.css";
import draggable from "vuedraggable";
import SessionTimeoutWarning from "@/Components/SessionTimeoutWarning.vue";

const props = defineProps({
    mode: { type: String, default: "create" },
    initialValues: { type: Object, default: () => ({}) },
    inputFields: { type: Array, default: () => [] },
    firstDropdown: { type: Array, default: () => [] },
    firstInputField: { type: Array, default: () => [] },
    secondDropdown: { type: Array, default: () => [] },
    invoicesFundFields: { type: Array, default: () => [] },
    quantityCostFields: { type: Array, default: () => [] },
    supplierOptions: { type: Array, default: () => [] },
    requestFields: { type: Array, default: () => [] },
    totalCost: { type: Array, default: () => [] },
    itemClass: { type: Array, default: () => [] },
    suppliers: { type: Array, default: () => [] },
    inputFieldsEdit: { type: Array, default: () => [] },
    viewItem: { type: Array, default: () => [] },
    item: { type: Object, default: () => ({}) },
    roomDropdown: { type: Array, default: () => [] },
    rooms: { type: Array, default: () => [] },
});

const toast = useToast();

const emit = defineEmits(["submit", "close", "created"]);

const form = useForm({
    id: null,
    item_classification_id: "",
    room_id: "",
    supplier_id: "",
    invoice: "",
    fund_source: "",
    item_name: "",
    brand: "",
    model: "",
    description: "",
    quantity: "1",
    unit: "",
    unit_cost: "",
    total_amount: 0,
    property_number: "",
    serial_numbers: [], // FOR ADD ITEM
    serial_number: "", // FOR EDIT ITEM
    descriptions: [], // per-item descriptions (descriptions[0] mirrors `description`)
    pr_number: "",
    po_number: "",
    remarks: "",
    date_acquired: "",
    status: "",
    is_private: 0,
});

const isEditing = ref(false);
const separateDescriptions = ref(false);

const calculateTotalAmount = () => {
    const qty = Number(form.quantity) || 0;
    const cost = Number(form.unit_cost) || 0;
    const total = qty * cost;

    form.total_amount = total.toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ",");
};

watch(() => [form.quantity, form.unit_cost], calculateTotalAmount);

watch(
    () => form.item_classification_id,
    (newVal) => {
        if (isEditing.value || !newVal) return;
        const selectedClass = props.itemClass.find((c) => c.id === newVal);

        if (selectedClass) {
            const code = selectedClass.classification_code;
            const now = new Date();
            const year = now.getFullYear();
            const month = String(now.getMonth() + 1).padStart(2, "0");

            if (code) {
                const formatted = `${code}-${year}-${month}-`;
                form.category = formatted;
                form.property_number = `${code}-`;
            } else {
                form.category = "";
                form.property_number = "";
            }
        }
    },
);

// --- EDIT MODE ---
watch(
    () => props.initialValues,
    (item) => {
        if (props.mode !== "edit" || !item) return;

        isEditing.value = true;
        form.id = item.id ?? null;
        form.item_classification_id = item.item_classification_id ?? "";
        form.room_id = item.room_id ?? "";
        form.supplier_id = item.supplier_id ?? "";
        form.invoice = item.invoice ?? "";
        form.fund_source = item.fund_source ?? "";
        form.item_name = item.item_name ?? "";
        form.brand = item.brand ?? "";
        form.model = item.model ?? "";
        form.description = item.description ?? "";
        form.quantity = item.quantity ?? 1;
        form.unit = item.unit ?? "";
        form.unit_cost = item.unit_cost ?? 0;
        form.total_amount = item.total_amount ?? 0;
        // If the API provided a full property number (with -NNN), store only the prefix
        if (item.property_number) {
            const m = String(item.property_number).match(/^(.*)-(\d{3})$/);
            form.property_number = m ? m[1] + "-" : item.property_number;
        } else {
            form.property_number = "";
        }
        form.serial_number = item.serial_number ?? [];
        form.pr_number = item.pr_number ?? "";
        form.po_number = item.po_number ?? "";
        form.remarks = item.remarks ?? "";
        form.date_acquired = item.date_acquired
            ? item.date_acquired.split("T")[0]
            : "";
        form.status = item.status ?? "";
        form.is_private = item.is_private ?? 0;
    },
    { immediate: true },
);

watch(
    () => form.quantity,
    (newVal) => {
        if (props.mode !== "create") return;

        const qty = parseInt(newVal) || 0;

        if (qty <= 0) {
            form.serial_numbers = [];
            form.descriptions = [];
            return;
        }

        // Ensure arrays match the quantity. Keep existing values when present.
        form.serial_numbers = Array.from(
            { length: qty },
            (_, i) => form.serial_numbers[i] ?? "",
        );

        if (separateDescriptions.value) {
            form.descriptions = Array.from({ length: qty }, (_, i) => {
                if (form.descriptions && form.descriptions[i] !== undefined)
                    return form.descriptions[i];
                return i === 0 ? form.description || "" : "";
            });
        } else {
            // Shared mode: replicate main description across all items but keep fields visible
            form.descriptions = Array.from(
                { length: qty },
                () => form.description || "",
            );
        }
    },
    { immediate: true },
);

// Keep the main description and descriptions[0] in sync
watch(
    () => form.description,
    (val) => {
        form.descriptions = form.descriptions || [];
        form.descriptions[0] = val;
        if (!separateDescriptions.value) {
            const qty = parseInt(form.quantity) || 1;
            form.descriptions = Array.from({ length: qty }, () => val || "");
        }
    },
);

watch(separateDescriptions, (val) => {
    const qty = parseInt(form.quantity) || 1;
    if (!val) {
        // turning OFF separate descriptions -> replicate main description
        form.descriptions = Array.from(
            { length: qty },
            () => form.description || "",
        );
    } else {
        // turning ON separate descriptions -> ensure array length and preserve existing values
        form.descriptions = Array.from(
            { length: qty },
            (_, i) =>
                form.descriptions[i] ?? (i === 0 ? form.description || "" : ""),
        );
    }
});

watch(
    () => form.descriptions && form.descriptions[0],
    (val) => {
        if (val !== form.description) form.description = val || "";
    },
);

function getPropertyNumberFor(index) {
    const prefix = form.property_number || "";
    return `${prefix}${String(index + 1).padStart(3, "0")}`;
}

const propertyEditing = ref(false);
const propertyInput = ref("");

const displayPropertyValue = computed(() => {
    if (propertyEditing.value) return propertyInput.value;
    return getPropertyNumberFor(0);
});

function startEditingProperty() {
    propertyEditing.value = true;
    propertyInput.value = (form.property_number || "").replace(/-$/, "");
}

function onPropertyInput(e) {
    propertyInput.value = e.target.value || "";
}

function finishEditingProperty() {
    propertyEditing.value = false;
    const v = propertyInput.value || "";
    form.property_number = v === "" ? "" : v.endsWith("-") ? v : v + "-";
}

function autoResize(e) {
    const el = e.target;
    if (!el) return;
    el.style.height = "auto";
    el.style.height = `${el.scrollHeight}px`;
}

function resizeAllDescriptions() {
    nextTick(() => {
        document.querySelectorAll(".auto-resize-textarea").forEach((el) => {
            el.style.height = "auto";
            el.style.height = `${el.scrollHeight}px`;
        });
    });
}

watch(
    () => form.descriptions,
    () => {
        resizeAllDescriptions();
    },
    { deep: true, immediate: true },
);

const propertyNumberSuffix = computed(() => {
    const qty = parseInt(form.quantity) || 1;
    return qty <= 1 ? "001" : `001–${String(qty).padStart(3, "0")}`;
});

function submit() {
    form.total_amount = parseFloat(form.total_amount);

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
                const firstError = Object.values(errors)[0];

                toast.add({
                    severity: "warn",
                    summary: "Validation Failed",
                    detail: firstError,
                    life: 5000,
                });

                console.error("Update failed", errors);
            },
        });
    } else {
        form.post(route("items.store"), {
            onSuccess: () => {
                toast.add({
                    severity: "success",
                    summary: "Created",
                    detail: "Item added successfully.",
                    life: 5000,
                });

                emit("close");
                emit("submit", form);
                form.reset();
            },

            onError: (errors) => {
                const firstError = Object.values(errors)[0];

                toast.add({
                    severity: "error",
                    summary: "Validation Failed",
                    detail: firstError,
                    life: 5000,
                });

                console.error("Create failed", errors);
            },
        });
    }
}

const isClosing = ref(false);

// ANIMATION FOR CLOSE
function closeWithAnimation() {
    isClosing.value = true;
    setTimeout(() => {
        emit("close");
        isClosing.value = false;
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

const searchQueries = ref({});

function getFilteredOptions(fdp) {
    const query = (searchQueries.value[fdp.model] || "").toLowerCase().trim();
    const options = props[fdp.name] || [];

    if (!query) return options;

    const keys = fdp.searchKeys ?? [fdp.option];
    return options.filter((opt) =>
        keys.some((key) =>
            String(opt[key] ?? "")
                .toLowerCase()
                .includes(query),
        ),
    );
}

const initialSerialRaw = ref(""); // controlled display value for the initial serial input

function countSerialTokens(raw) {
    return raw.split(/[\s,]+/).filter(Boolean).length;
}

// Truncates raw text so it never contains more than `qty` comma/space-separated tokens
function enforceSerialLimit(raw, qty) {
    const tokens = countSerialTokens(raw);
    if (tokens <= qty) return raw; // still under the cap, allow typing (incl. trailing separator)

    // Walk through raw and cut right after the qty-th token
    const re = /[^\s,]+/g;
    let match;
    let count = 0;
    let cutIndex = raw.length;

    while ((match = re.exec(raw)) !== null) {
        count++;
        if (count === qty) {
            cutIndex = match.index + match[0].length;
            break;
        }
    }
    return raw.slice(0, cutIndex);
}

function onInitialSerialInput(e) {
    const qty = parseInt(form.quantity) || 1;
    const limited = enforceSerialLimit(e.target.value, qty);
    initialSerialRaw.value = limited;

    // If we truncated, force the DOM input back in sync (blocks the extra keystroke visually too)
    if (limited !== e.target.value) {
        nextTick(() => {
            e.target.value = limited;
        });
    }
}

function incrementSerial(base, offset) {
    const match = String(base).match(/^(.*?)(\d+)$/);
    if (!match) return `${base}-${offset + 1}`;
    const [, prefix, numStr] = match;
    const next = parseInt(numStr, 10) + offset;
    return `${prefix}${String(next).padStart(numStr.length, "0")}`;
}

function parseSerialParts(raw) {
    return raw
        .split(/[\s,]+/)
        .map((s) => s.trim())
        .filter(Boolean);
}

// Rebuild form.serial_numbers whenever the raw initial input or quantity changes
watch(
    () => [initialSerialRaw.value, form.quantity],
    () => {
        const qty = parseInt(form.quantity) || 1;
        const raw = initialSerialRaw.value || "";

        if (/[\s,]/.test(raw.trim())) {
            const parts = parseSerialParts(raw);
            form.serial_numbers = Array.from(
                { length: qty },
                (_, i) => parts[i] ?? "",
            );
        } else {
            form.serial_numbers = Array.from({ length: qty }, (_, i) =>
                i === 0 ? raw : raw ? incrementSerial(raw, i) : "",
            );
        }
    },
);

watch(
    () => [initialSerialRaw.value, form.quantity],
    () => {
        const qty = parseInt(form.quantity) || 1;
        const raw = initialSerialRaw.value || "";

        const parts = parseSerialParts(raw);
        // index 0 = first typed value, rest come only from comma/space separated entries
        // if the user typed a single value with no separators, everything past index 0 stays blank
        form.serial_numbers = Array.from(
            { length: qty },
            (_, i) => parts[i] ?? "",
        );
    },
);

const serialInputText = ref(""); // what's currently being typed, not yet committed as a badge

function commitSerialToken() {
    const qty = parseInt(form.quantity) || 1;
    const val = serialInputText.value.trim();
    if (!val) return;

    const current = form.serial_numbers.filter(Boolean);
    if (current.length >= qty) {
        serialInputText.value = ""; // at cap, discard
        return;
    }

    form.serial_numbers = Array.from({ length: qty }, (_, i) =>
        i < current.length ? current[i] : i === current.length ? val : "",
    );
    serialInputText.value = "";
}

function onSerialKeydown(e) {
    if (e.key === "Enter" || e.key === "," || e.key === " ") {
        e.preventDefault();
        commitSerialToken();
    } else if (e.key === "Backspace" && !serialInputText.value) {
        // backspace on empty input removes the last badge
        removeSerialAt(form.serial_numbers.filter(Boolean).length - 1);
    }
}

function removeSerialAt(index) {
    const qty = parseInt(form.quantity) || 1;
    const current = form.serial_numbers.filter(Boolean);
    if (index < 0 || index >= current.length) return;
    current.splice(index, 1);
    form.serial_numbers = Array.from(
        { length: qty },
        (_, i) => current[i] ?? "",
    );
}

// Wrapper so vuedraggable can bind v-model to only the filled badges, then we pad back to qty on change
const serialBadges = computed({
    get: () =>
        form.serial_numbers
            .filter(Boolean)
            .map((val, i) => ({ id: i + "-" + val, val })),
    set: (newList) => {
        const qty = parseInt(form.quantity) || 1;
        const vals = newList.map((b) => b.val);
        form.serial_numbers = Array.from(
            { length: qty },
            (_, i) => vals[i] ?? "",
        );
    },
});

function onSerialPaste(e) {
    e.preventDefault();
    const qty = parseInt(form.quantity) || 1;
    const pasted = (e.clipboardData || window.clipboardData).getData("text");

    const newTokens = pasted
        .split(/[\s,]+/)
        .map((s) => s.trim())
        .filter(Boolean);
    if (newTokens.length === 0) return;

    const current = form.serial_numbers.filter(Boolean);
    const combined = [...current, ...newTokens].slice(0, qty);
    form.serial_numbers = Array.from(
        { length: qty },
        (_, i) => combined[i] ?? "",
    );
    serialInputText.value = "";

    if (current.length + newTokens.length > qty) {
        toast.add({
            severity: "warn",
            summary: "Serial limit reached",
            detail: `Only ${qty} serial(s) allowed for the current quantity. Extra entries were discarded.`,
            life: 4000,
        });
    }
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
                                  : "Add Item"
                        }}
                    </h3>
                    <p class="text-xs text-white/70 mt-0.5">
                        {{
                            mode === "edit"
                                ? "Update this item's details."
                                : mode === "view"
                                  ? "View item information and accountability history."
                                  : "Add a new item to your inventory."
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
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                        <!-- LEFT -->
                        <div class="space-y-4 col-span-1 md:col-span-1">
                            <div
                                class="grid grid-cols-1 md:grid-cols-2 gap-x-5"
                            >
                                <div class="space-y-4">
                                    <!-- FIRST DROP DOWN -->
                                    <div
                                        class="flex flex-col md:flex-row gap-2 mb-4"
                                    >
                                        <div
                                            v-for="fdp in firstDropdown"
                                            :key="fdp.model"
                                        >
                                            <label
                                                class="block text-xs font-semibold text-gray-500 uppercase tracking-wide mb-1.5"
                                            >
                                                {{ fdp.label }}
                                                <span class="text-red-500"
                                                    >*</span
                                                >
                                            </label>
                                            <Multiselect
                                                v-model="form[fdp.model]"
                                                :options="
                                                    getFilteredOptions(fdp)
                                                "
                                                :searchable="true"
                                                :filter-results="false"
                                                @search-change="
                                                    (q) =>
                                                        (searchQueries[
                                                            fdp.model
                                                        ] = q)
                                                "
                                                :value-prop="fdp.value"
                                                :label="fdp.option"
                                                :track-by="fdp.option"
                                                placeholder="Select"
                                                class="first-dropdown-select"
                                            >
                                                <template
                                                    v-if="fdp.labelFormat"
                                                    #singlelabel="{ value }"
                                                >
                                                    <div
                                                        class="multiselect-single-label"
                                                    >
                                                        {{
                                                            fdp.labelFormat(
                                                                value,
                                                            )
                                                        }}
                                                    </div>
                                                </template>

                                                <template
                                                    v-if="fdp.labelFormat"
                                                    #option="{ option }"
                                                >
                                                    <span>{{
                                                        fdp.labelFormat(option)
                                                    }}</span>
                                                </template>
                                            </Multiselect>
                                            <div
                                                v-if="form.errors[fdp.model]"
                                                class="text-red-500 text-xs mt-1"
                                            >
                                                {{ form.errors[fdp.model] }}
                                            </div>
                                        </div>

                                        <!-- FIRST INPUT FIELD SECTION -->
                                        <div
                                            v-for="fif in firstInputField"
                                            :key="fif.model"
                                            class="flex flex-col"
                                        >
                                            <label
                                                class="block text-xs font-semibold text-gray-500 uppercase tracking-wide mb-1.5"
                                            >
                                                {{ fif.label }}
                                                <span class="text-red-500"
                                                    >*</span
                                                >
                                            </label>

                                            <!-- Property number: editable prefix + locked suffix -->
                                            <div
                                                v-if="
                                                    fif.model ===
                                                    'property_number'
                                                "
                                                :class="[
                                                    'w-full sm:w-[15.7rem] rounded-md',
                                                    form.errors[fif.model]
                                                        ? 'border-red-500 border focus-within:ring-red-500'
                                                        : 'border-gray-300',
                                                ]"
                                            >
                                                <input
                                                    type="text"
                                                    :value="
                                                        displayPropertyValue
                                                    "
                                                    @focus="
                                                        startEditingProperty
                                                    "
                                                    @input="onPropertyInput"
                                                    @blur="
                                                        finishEditingProperty
                                                    "
                                                    :placeholder="
                                                        fif.placeholder
                                                    "
                                                    :required="fif.required"
                                                    class="w-full rounded-md px-3 py-3 bg-[#F8F8F8] text-[#3B3B3B] text-sm focus:outline-none"
                                                />
                                            </div>

                                            <!-- Every other first-input-field, unchanged -->
                                            <input
                                                v-else
                                                :type="fif.type || 'text'"
                                                v-model="form[fif.model]"
                                                :placeholder="fif.placeholder"
                                                :required="fif.required"
                                                :class="[
                                                    'w-full sm:w-[15.7rem] rounded-md px-3 py-3 text-[#3B3B3B] bg-[#F8F8F8] text-sm focus:ring-1 focus:outline-none border',
                                                    form.errors[fif.model]
                                                        ? 'border-red-500 focus:ring-red-500 focus:border-red-500'
                                                        : 'border-gray-300 focus:ring-[#005740] focus:border-[#005740]',
                                                ]"
                                            />

                                            <div
                                                v-if="form.errors[fif.model]"
                                                class="text-red-500 text-xs mt-1"
                                            >
                                                {{ form.errors[fif.model] }}
                                            </div>
                                        </div>
                                    </div>

                                    <!-- SECOND DROP DOWN -->
                                    <div
                                        class="w-full flex md:flex-row gap-4 mb-8"
                                    >
                                        <div
                                            v-for="sdf in secondDropdown"
                                            :key="sdf.label"
                                            class="flex gap-3"
                                        >
                                            <div>
                                                <label
                                                    class="block text-xs font-semibold text-gray-500 uppercase tracking-wide mb-1.5"
                                                    >{{ sdf.label }}
                                                    <span class="text-red-500"
                                                        >*</span
                                                    ></label
                                                >
                                                <select
                                                    v-model="form[sdf.model]"
                                                    :class="[
                                                        'w-full sm:w-[10rem] rounded-md px-3 py-3 bg-[#F8F8F8] text-gray-500 text-sm focus:ring-1 focus:outline-none border',
                                                        form.errors[sdf.model]
                                                            ? 'border-red-500 focus:ring-red-500 focus:border-red-500'
                                                            : 'border-gray-300 focus:ring-[#005740] focus:border-[#005740]',
                                                    ]"
                                                >
                                                    <option value="">
                                                        Select
                                                    </option>
                                                    <option
                                                        v-for="op in sdf.options"
                                                        :key="op.value"
                                                        :value="op.value"
                                                    >
                                                        {{ op.label }}
                                                    </option>
                                                </select>
                                                <div
                                                    v-if="
                                                        form.errors[sdf.model]
                                                    "
                                                    class="text-red-500 text-xs mt-1"
                                                >
                                                    {{ form.errors[sdf.model] }}
                                                </div>
                                            </div>
                                        </div>
                                        <div>
                                            <label
                                                class="block text-xs font-semibold text-gray-500 uppercase tracking-wide mb-1.5"
                                                >Date Acquired
                                                <span class="text-red-500"
                                                    >*</span
                                                ></label
                                            >
                                            <input
                                                v-model="form.date_acquired"
                                                :class="[
                                                    'w-full sm:w-[10rem] rounded-md border px-3 py-3 bg-[#F8F8F8] text-gray-500 text-sm focus:ring-1 focus:outline-none',
                                                    form.errors.date_acquired
                                                        ? 'border-red-500 focus:ring-red-500 focus:border-red-500'
                                                        : 'border-gray-300 focus:ring-[#005740] focus:border-[#005740]',
                                                ]"
                                                type="date"
                                            />
                                            <div
                                                v-if="form.errors.date_acquired"
                                                class="text-red-500 text-xs mt-1"
                                            >
                                                {{ form.errors.date_acquired }}
                                            </div>
                                        </div>
                                    </div>

                                    <!-- QUANTITY/UNIT COST -->
                                    <div class="flex gap-8">
                                        <div
                                            v-for="qcf in quantityCostFields"
                                            :key="qcf.quantityCostFields"
                                            class="flex flex-col flex-1 min-w-[8rem] sm:min-w-[10rem] md:min-w-[15rem] lg:min-w-[14.3rem]"
                                        >
                                            <label
                                                class="block text-xs font-semibold text-gray-500 uppercase tracking-wide mb-1.5"
                                                >{{ qcf.label }}
                                                <span class="text-red-500"
                                                    >*</span
                                                ></label
                                            >
                                            <input
                                                v-if="!qcf.format"
                                                v-model="form[qcf.model]"
                                                :key="qcf.model"
                                                :type="qcf.type"
                                                :placeholder="qcf.placeholder"
                                                :readonly="
                                                    props.mode === 'edit' &&
                                                    qcf.model === 'quantity'
                                                "
                                                :class="[
                                                    'w-full sm:w-[15.7rem] rounded-md border px-3 py-3 bg-[#F8F8F8] text-[#3B3B3B] text-sm focus:ring-1 focus:outline-none',
                                                    props.mode === 'edit' &&
                                                    qcf.model === 'quantity'
                                                        ? 'cursor-not-allowed bg-gray-100'
                                                        : '',
                                                    form.errors[qcf.model]
                                                        ? 'border-red-500 focus:ring-red-500 focus:border-red-500'
                                                        : 'border-gray-300 focus:ring-[#005740] focus:border-[#005740]',
                                                ]"
                                            />
                                            <!-- Formatted input for comma display -->
                                            <input
                                                v-else
                                                :value="
                                                    qcf.format(form[qcf.model])
                                                "
                                                @input="
                                                    form[qcf.model] =
                                                        $event.target.value.replace(
                                                            /,/g,
                                                            '',
                                                        )
                                                "
                                                :key="qcf.model + '_formatted'"
                                                type="text"
                                                :placeholder="qcf.placeholder"
                                                :class="[
                                                    'w-full sm:w-[15.7rem] rounded-md border px-3 py-3 bg-[#F8F8F8] text-[#3B3B3B] text-sm focus:ring-1 focus:outline-none',
                                                    form.errors[qcf.model]
                                                        ? 'border-red-500 focus:ring-red-500 focus:border-red-500'
                                                        : 'border-gray-300 focus:ring-[#005740] focus:border-[#005740]',
                                                ]"
                                            />
                                            <div
                                                v-if="form.errors[qcf.model]"
                                                class="text-red-500 text-xs mt-1"
                                            >
                                                {{ form.errors[qcf.model] }}
                                            </div>
                                        </div>
                                    </div>
                                    <!-- INITIAL SERIAL NUMBER (first item) -->
                                    <div class="flex flex-col gap-2">
                                        <label
                                            class="block text-xs font-semibold text-gray-500 uppercase tracking-wide mb-1.5"
                                        >
                                            Serial Number
                                        </label>

                                        <div
                                            class="w-full sm:w-[32rem] min-h-[3rem] rounded-md border border-gray-300 bg-[#F8F8F8] px-2 py-2 flex flex-wrap gap-1.5 items-center focus-within:ring-1 focus-within:ring-[#005740] focus-within:border-[#005740]"
                                        >
                                            <draggable
                                                v-model="serialBadges"
                                                item-key="id"
                                                class="flex flex-wrap gap-1.5"
                                                :animation="150"
                                                ghost-class="serial-badge-ghost"
                                            >
                                                <template
                                                    #item="{ element, index }"
                                                >
                                                    <span
                                                        class="inline-flex items-center gap-1 bg-[#005740] text-white text-xs font-medium px-2 py-1 rounded-full cursor-grab active:cursor-grabbing select-none"
                                                    >
                                                        {{ element.val }}
                                                        <button
                                                            type="button"
                                                            @click="
                                                                removeSerialAt(
                                                                    index,
                                                                )
                                                            "
                                                            class="hover:bg-white/20 rounded-full h-4 w-4 flex items-center justify-center"
                                                        >
                                                            <i
                                                                class="fa-solid fa-xmark text-[10px]"
                                                            ></i>
                                                        </button>
                                                    </span>
                                                </template>
                                            </draggable>

                                            <input
                                                v-model="serialInputText"
                                                @keydown="onSerialKeydown"
                                                @paste="onSerialPaste"
                                                @blur="commitSerialToken"
                                                type="text"
                                                :placeholder="
                                                    form.serial_numbers.filter(
                                                        Boolean,
                                                    ).length === 0
                                                        ? 'Type SER-001 then press space/comma/enter'
                                                        : ''
                                                "
                                                class="flex-1 min-w-[8rem] bg-transparent text-sm text-[#3B3B3B] focus:outline-none py-1"
                                            />
                                        </div>

                                        <p class="text-xs text-gray-400">
                                            {{
                                                form.serial_numbers.filter(
                                                    Boolean,
                                                ).length
                                            }}/{{
                                                parseInt(form.quantity) || 1
                                            }}
                                            serials entered — drag to reorder
                                        </p>
                                    </div>

                                    <!-- ITEM NAME / BRAND / MODEL -->
                                    <!-- CREATE MODE -->
                                    <div
                                        v-if="mode !== 'edit'"
                                        class="w-full flex md:flex-row gap-4 mb-4"
                                    >
                                        <div
                                            v-for="ip in inputFields"
                                            :key="ip.model"
                                        >
                                            <label
                                                class="block text-xs font-semibold text-gray-500 uppercase tracking-wide mb-1.5"
                                            >
                                                {{ ip.label }}
                                                <span
                                                    v-if="ip.required"
                                                    class="text-red-500"
                                                >
                                                    *
                                                </span>
                                            </label>

                                            <input
                                                v-model="form[ip.model]"
                                                :readonly="ip.readonly"
                                                :required="ip.required"
                                                :type="ip.type || 'text'"
                                                :placeholder="ip.placeholder"
                                                :class="[
                                                    'w-full sm:w-[10rem] rounded-md border px-3 py-3 bg-[#F8F8F8] text-[#3B3B3B] text-sm focus:ring-1 focus:outline-none',
                                                    form.errors[ip.model]
                                                        ? 'border-red-500 focus:ring-red-500 focus:border-red-500'
                                                        : 'border-gray-300 focus:ring-[#005740] focus:border-[#005740]',
                                                ]"
                                            />

                                            <div
                                                v-if="form.errors[ip.model]"
                                                class="text-red-500 text-xs mt-1"
                                            >
                                                {{ form.errors[ip.model] }}
                                            </div>
                                        </div>
                                    </div>

                                    <!-- EDIT MODE -->
                                    <div
                                        v-else
                                        class="grid grid-cols-2 gap-4 mb-4 w-[32rem] max-w-2xl"
                                    >
                                        <div
                                            v-for="ip in inputFieldsEdit"
                                            :key="ip.model"
                                        >
                                            <label
                                                class="block text-xs font-semibold text-gray-500 uppercase tracking-wide mb-1.5"
                                            >
                                                {{ ip.label }}
                                                <span
                                                    v-if="ip.required"
                                                    class="text-red-500"
                                                >
                                                    *
                                                </span>
                                            </label>

                                            <input
                                                v-model="form[ip.model]"
                                                :disabled="ip.readonly"
                                                :required="ip.required"
                                                :type="ip.type || 'text'"
                                                :placeholder="ip.placeholder"
                                                :class="[
                                                    'w-full rounded-md border px-3 py-3 text-sm focus:ring-1 focus:outline-none',
                                                    ip.readonly
                                                        ? 'bg-gray-100 text-gray-500 cursor-not-allowed'
                                                        : 'bg-[#F8F8F8] text-[#3B3B3B]',
                                                    form.errors[ip.model]
                                                        ? 'border-red-500 focus:ring-red-500 focus:border-red-500'
                                                        : 'border-gray-300 focus:ring-[#005740] focus:border-[#005740]',
                                                ]"
                                            />

                                            <div
                                                v-if="form.errors[ip.model]"
                                                class="text-red-500 text-xs mt-1"
                                            >
                                                {{ form.errors[ip.model] }}
                                            </div>
                                        </div>
                                    </div>

                                    <!-- DESCRIPTION -->
                                    <div>
                                        <label
                                            class="block text-xs font-semibold text-gray-500 uppercase tracking-wide mb-1.5"
                                            >Description</label
                                        >
                                        <textarea
                                            v-model="form.description"
                                            placeholder="Input a description"
                                            @input="autoResize"
                                            class="auto-resize-textarea w-full sm:w-[32rem] rounded-md border border-gray-300 px-3 py-2 bg-[#F8F8F8] text-[#3B3B3B] text-sm focus:ring-1 focus:ring-[#005740] focus:outline-none focus:border-[#005740] resize-none overflow-hidden"
                                            rows="2"
                                        ></textarea>

                                        <div
                                            class="flex items-center gap-3 mt-2"
                                        >
                                            <label
                                                class="text-xs font-semibold text-gray-500 uppercase tracking-wide"
                                                >Separate descriptions</label
                                            >
                                            <button
                                                type="button"
                                                @click="
                                                    separateDescriptions =
                                                        !separateDescriptions
                                                "
                                                class="relative inline-flex h-6 w-11 items-center rounded-full transition-colors duration-300 focus:outline-none"
                                                :class="
                                                    separateDescriptions
                                                        ? 'bg-[#005740]'
                                                        : 'bg-gray-300'
                                                "
                                            >
                                                <span
                                                    class="inline-block h-4 w-4 transform rounded-full bg-white shadow transition-transform duration-300"
                                                    :class="
                                                        separateDescriptions
                                                            ? 'translate-x-6'
                                                            : 'translate-x-1'
                                                    "
                                                />
                                            </button>
                                            <span
                                                class="text-sm text-gray-500"
                                                >{{
                                                    separateDescriptions
                                                        ? "Per item editable"
                                                        : "Shared (mirrored)"
                                                }}</span
                                            >
                                        </div>

                                        <div
                                            v-if="form.errors.description"
                                            class="text-red-500 text-xs mt-1"
                                        >
                                            {{ form.errors.description }}
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <!-- RIGHT -->
                        <div class="space-y-4">
                            <!-- ROOM DROPDOWN -->
                            <div
                                v-for="rdp in roomDropdown"
                                :key="rdp.model"
                                class="flex flex-col"
                            >
                                <label
                                    class="block text-xs font-semibold text-gray-500 uppercase tracking-wide mb-1.5"
                                >
                                    {{ rdp.label }}
                                    <span class="text-red-500">*</span>
                                </label>

                                <Multiselect
                                    v-model="form[rdp.model]"
                                    :options="getFilteredOptions(rdp)"
                                    :searchable="true"
                                    :filter-results="false"
                                    @search-change="
                                        (q) => (searchQueries[rdp.model] = q)
                                    "
                                    :value-prop="rdp.value"
                                    :label="rdp.option"
                                    :track-by="rdp.option"
                                    placeholder="Select a room"
                                    class="room-dropdown-select"
                                >
                                    <template #option="{ option }">
                                        <div class="w-full">
                                            <div
                                                class="grid grid-cols-2 gap-2 text-sm font-medium"
                                            >
                                                <span>{{
                                                    option[rdp.option] || "N/A"
                                                }}</span>
                                                <span
                                                    class="text-right text-xs text-gray-400 truncate"
                                                >
                                                    {{
                                                        option.description ||
                                                        "N/A"
                                                    }}
                                                </span>
                                            </div>
                                            <div
                                                class="grid grid-cols-2 gap-2 text-xs text-gray-400 mt-0.5"
                                            >
                                                <span>{{
                                                    option.building_name ||
                                                    "N/A"
                                                }}</span>
                                                <span class="text-right"
                                                    >Cap:
                                                    {{
                                                        option.capacity || "N/A"
                                                    }}</span
                                                >
                                            </div>
                                        </div>
                                    </template>
                                </Multiselect>

                                <div
                                    v-if="form.errors[rdp.model]"
                                    class="text-red-500 text-xs mt-1"
                                >
                                    {{ form.errors[rdp.model] }}
                                </div>
                            </div>
                            <!-- SUPPLIER OPTIONS -->
                            <div class="space-y-4">
                                <div
                                    v-for="sup in supplierOptions"
                                    :key="sup.label"
                                    class="flex flex-col"
                                >
                                    <label
                                        class="block text-xs font-semibold text-gray-500 uppercase tracking-wide mb-1.5"
                                    >
                                        {{ sup.label }}
                                        <span class="text-red-500">*</span>
                                    </label>

                                    <Multiselect
                                        v-model="form[sup.model]"
                                        :options="props[sup.name]"
                                        :searchable="true"
                                        :value-prop="sup.value"
                                        :label="sup.option"
                                        :track-by="sup.option"
                                        placeholder="Select a supplier"
                                        class="supplier-select"
                                    />

                                    <div
                                        v-if="form.errors[sup.model]"
                                        class="text-red-500 text-xs mt-1"
                                    >
                                        {{ form.errors[sup.model] }}
                                    </div>
                                </div>
                            </div>

                            <!-- REQUEST FIELDS -->
                            <div class="space-y-4">
                                <div
                                    v-for="rf in requestFields"
                                    :key="rf.model"
                                    class="flex flex-col"
                                >
                                    <label
                                        class="block text-xs font-semibold text-gray-500 uppercase tracking-wide mb-1.5"
                                    >
                                        {{ rf.label }}
                                        <span
                                            v-if="rf.required"
                                            class="text-red-500"
                                            >*</span
                                        >
                                    </label>
                                    <input
                                        v-model="form[rf.model]"
                                        :key="rf.model"
                                        type="text"
                                        :placeholder="rf.placeholder"
                                        :class="[
                                            'w-full sm:w-[34rem] rounded-md px-3 py-3 bg-[#F8F8F8] text-[#3B3B3B] text-sm focus:ring-1 focus:outline-none border',
                                            form.errors[rf.model]
                                                ? 'border-red-500 focus:ring-red-500 focus:border-red-500'
                                                : 'border-gray-300 focus:ring-[#005740] focus:border-[#005740]',
                                        ]"
                                    />
                                    <div
                                        v-if="form.errors[rf.model]"
                                        class="text-red-500 text-xs mt-1"
                                    >
                                        {{ form.errors[rf.model] }}
                                    </div>
                                </div>
                            </div>

                            <!-- Invoices + Fund Sources -->
                            <div class="flex flex-col md:flex-row gap-4 mb-4">
                                <div
                                    v-for="inv in invoicesFundFields"
                                    :key="inv.model"
                                >
                                    <label
                                        class="block text-xs font-semibold text-gray-500 uppercase tracking-wide mb-1.5"
                                        >{{ inv.label }}
                                        <span class="text-red-500"
                                            >*</span
                                        ></label
                                    >
                                    <input
                                        v-model="form[inv.model]"
                                        :key="inv.model"
                                        type="text"
                                        :placeholder="inv.placeholder"
                                        :class="[
                                            'w-full sm:w-[16.5rem] rounded-md px-3 py-3 bg-[#F8F8F8] text-[#3B3B3B] text-sm focus:ring-1 focus:outline-none border',
                                            form.errors[inv.model]
                                                ? 'border-red-500 focus:ring-red-500 focus:border-red-500'
                                                : 'border-gray-300 focus:ring-[#005740] focus:border-[#005740]',
                                        ]"
                                    />
                                    <div
                                        v-if="form.errors[inv.model]"
                                        class="text-red-500 text-xs mt-1"
                                    >
                                        {{ form.errors[inv.model] }}
                                    </div>
                                </div>
                            </div>

                            <!-- IS PRIVATE TOGGLE -->
                            <div class="flex items-center gap-3 mt-2">
                                <label
                                    class="text-xs font-semibold text-gray-500 uppercase tracking-wide"
                                    >Private Item</label
                                >
                                <button
                                    type="button"
                                    @click="
                                        form.is_private =
                                            form.is_private === 1 ? 0 : 1
                                    "
                                    class="relative inline-flex h-6 w-11 items-center rounded-full transition-colors duration-300 focus:outline-none"
                                    :class="
                                        form.is_private === 1
                                            ? 'bg-[#005740]'
                                            : 'bg-gray-300'
                                    "
                                >
                                    <span
                                        class="inline-block h-4 w-4 transform rounded-full bg-white shadow transition-transform duration-300"
                                        :class="
                                            form.is_private === 1
                                                ? 'translate-x-6'
                                                : 'translate-x-1'
                                        "
                                    />
                                </button>
                                <span class="text-sm text-gray-500">
                                    {{
                                        form.is_private === 1
                                            ? "Private"
                                            : "Public"
                                    }}
                                </span>
                            </div>
                        </div>
                    </div>

                    <!-- List of items -->
                    <div
                        v-if="parseInt(form.quantity) > 1"
                        class="px-6 pb-4 border-t"
                    >
                        <p
                            class="text-sm font-semibold text-gray-600 mb-2 mt-5"
                        >
                            List of Items
                        </p>

                        <div
                            v-for="(_, idx) in form.serial_numbers"
                            :key="idx"
                            class="grid grid-cols-12 gap-2 items-start mb-2"
                        >
                            <div class="col-span-3">
                                <label class="text-xs text-gray-500 uppercase">
                                    Property No.
                                </label>
                                <input
                                    type="text"
                                    :value="getPropertyNumberFor(idx)"
                                    readonly
                                    class="w-full rounded-md px-3 py-2 bg-gray-100 text-sm border"
                                />
                            </div>

                            <div class="col-span-6">
                                <label class="text-xs text-gray-500 uppercase">
                                    Description
                                </label>
                                <textarea
                                    v-model="form.descriptions[idx]"
                                    :readonly="!separateDescriptions"
                                    placeholder="Input description"
                                    @input="autoResize"
                                    :class="[
                                        'auto-resize-textarea w-full rounded-md px-3 py-2 text-sm border resize-none overflow-hidden',
                                        !separateDescriptions
                                            ? 'bg-gray-100 text-gray-600'
                                            : 'bg-[#F8F8F8] text-[#3B3B3B] border-gray-300 focus:ring-1 focus:ring-[#005740]',
                                    ]"
                                    rows="2"
                                ></textarea>
                            </div>

                            <div class="col-span-3">
                                <label class="text-xs text-gray-500 uppercase">
                                    Serial No.
                                </label>
                                <input
                                    :value="form.serial_numbers[idx]"
                                    type="text"
                                    disabled
                                    placeholder="—"
                                    class="w-full rounded-md px-3 py-2 bg-gray-100 text-gray-400 text-sm border cursor-not-allowed"
                                />
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Footer -->
                <div
                    class="flex justify-between items-center gap-4 px-6 py-4 border-t border-gray-100 flex-shrink-0"
                >
                    <!-- TOTAL COST -->
                    <div class="flex items-center gap-4">
                        <div
                            v-for="total in totalCost"
                            :key="total.label"
                            class="flex items-center gap-3"
                        >
                            <label
                                class="text-sm text-gray-500 font-semibold uppercase tracking-wide"
                            >
                                {{ total.label }}:
                            </label>

                            <input
                                v-model="form.total_amount"
                                readonly
                                placeholder="0.00"
                                class="block text-lg font-semibold text-[#005740] border-none pointer-events-none bg-transparent"
                            />
                        </div>
                    </div>

                    <!-- BUTTONS -->
                    <div class="flex items-center gap-3">
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
                            {{ mode === "edit" ? "Update" : "Add" }}
                        </button>
                    </div>
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

                    <!-- Accountable Person -->
                    <div
                        v-if="item?.acknowledgement_history?.length"
                        class="pb-4"
                    >
                        <hr class="pt-4" />

                        <!-- Current Accountable Person -->
                        <div
                            class="flex flex-col items-center text-center mb-4"
                        >
                            <p class="text-lg font-bold text-[#005740]">
                                {{
                                    item.acknowledgement_history[0]
                                        ?.accountable_person?.full_name ??
                                    "Unassigned"
                                }}
                            </p>

                            <p class="text-xs text-[#005740] font-medium">
                                Current Accountable Person
                            </p>

                            <p class="text-xs text-gray-500 mt-1">
                                PAR Date:
                                {{
                                    item.acknowledgement_history[0]
                                        ?.acknowledgement_receipts?.par_date
                                        ? new Date(
                                              item.acknowledgement_history[0]
                                                  .acknowledgement_receipts
                                                  .par_date,
                                          ).toLocaleDateString()
                                        : "N/A"
                                }}
                            </p>
                        </div>

                        <hr
                            v-if="item.acknowledgement_history.length > 1"
                            class="border-gray-300 mb-4"
                        />

                        <!-- Previous History -->
                        <div
                            v-if="item.acknowledgement_history.length > 1"
                            class="space-y-3"
                        >
                            <p
                                class="text-xs font-semibold uppercase tracking-wide text-gray-500"
                            >
                                Previous Assignments
                            </p>

                            <div
                                v-for="history in item.acknowledgement_history.slice(
                                    1,
                                )"
                                :key="history.id"
                            >
                                <p class="text-sm font-medium text-gray-700">
                                    {{
                                        history.accountable_person?.full_name ??
                                        "Unassigned"
                                    }}
                                </p>

                                <p class="text-xs text-gray-500">
                                    PAR Date:
                                    {{
                                        history.acknowledgement_receipts
                                            ?.par_date
                                            ? new Date(
                                                  history
                                                      .acknowledgement_receipts
                                                      .par_date,
                                              ).toLocaleDateString()
                                            : "N/A"
                                    }}
                                </p>

                                <hr class="border-gray-200 mt-2" />
                            </div>
                        </div>
                    </div>
                    <div v-else class="border-b pb-4">
                        <hr class="pt-4" />
                        <div
                            class="flex flex-col items-center justify-center text-center py-4"
                        >
                            <p class="text-lg font-bold text-red-600">
                                Unassigned
                            </p>

                            <p class="text-xs text-red-500 font-medium">
                                No accountable person assigned
                            </p>
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
.first-dropdown-select.multiselect {
    width: 15.7rem !important;
    min-height: 46px;
    margin: 0 !important;
}

.room-dropdown-select.multiselect {
    width: 98% !important;
    min-height: 46px;
    margin: 0 !important;
}

.supplier-select.multiselect {
    width: 98% !important;
    min-height: 46px;
    margin: 0 !important;
}

.multiselect-wrapper {
    min-height: 46px;
}
</style>
