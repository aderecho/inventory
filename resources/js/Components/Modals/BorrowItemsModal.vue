<script setup>
import { computed, ref, watch } from "vue";
import {
    Building2,
    CalendarClock,
    GraduationCap,
    HandHelping,
    Lock,
    UserRound,
    UserRoundSearch,
    X,
} from "lucide-vue-next";
import StaffPicker from "@/Components/StaffPicker.vue";

const props = defineProps({
    show: { type: Boolean, default: false },
    // The rows ticked in the Borrow Items list.
    items: { type: Array, default: () => [] },
    // Conditions to choose from on lend and return.
    assetConditions: { type: Array, default: () => [] },
});

const emit = defineEmits(["close", "submit"]);

const TYPES = [
    {
        value: "staff",
        label: "Staff (PUSO)",
        icon: UserRoundSearch,
        hint: "Search the PUSO staff list",
    },
    { value: "student", label: "Student", icon: GraduationCap, hint: "Student account or name" },
    { value: "outsider", label: "Outsider", icon: Building2, hint: "Name and organisation" },
];

const today = () => new Date().toISOString().slice(0, 10);

const form = ref({
    borrower_type: "staff",
    borrower_name: "",
    borrower_user_id: null,
    employee_number: "",
    student_number: "",
    organization: "",
    contact_number: "",
    notes: "",
    borrowed_at: today(),
    due_at: today(),
    no_due_date: true,
    borrowed_from_user_id: null,
    borrowed_from_name: "",
});

const conditionOut = ref({});

// "Borrowed from" is the accountable person of the ticked items, never a
// choice: whoever holds the property is who lends it.
const accountablePeople = computed(() => {
    const people = new Map();

    for (const item of props.items ?? []) {
        const person = item.latest_acknowledgement_item?.accountable_person;

        if (!person) continue;

        const key = person.user_id ?? person.id ?? fullName(person);

        people.set(key, person);
    }

    return Array.from(people.values());
});

function fullName(person) {
    if (!person) return "";

    if (person.full_name) {
        return String(person.full_name).trim();
    }

    return [person.first_name, person.middle_name ? person.middle_name.charAt(0) + "." : null, person.last_name]
        .filter(Boolean)
        .join(" ")
        .trim();
}

const borrowedFromName = computed(() => {
    const names = accountablePeople.value.map(fullName).filter(Boolean);

    return names.length ? names.join(", ") : "Unassigned";
});

const borrowedFromUserId = computed(
    () => accountablePeople.value[0]?.user_id ?? accountablePeople.value[0]?.id ?? null,
);

const hasSeveralLenders = computed(() => accountablePeople.value.length > 1);

const reset = () => {
    form.value = {
        borrower_type: "staff",
        borrower_name: "",
        borrower_user_id: null,
        employee_number: "",
        student_number: "",
        organization: "",
        contact_number: "",
        notes: "",
        borrowed_at: today(),
        due_at: today(),
        no_due_date: true,
        borrowed_from_user_id: borrowedFromUserId.value,
        borrowed_from_name: borrowedFromName.value === "Unassigned" ? "" : borrowedFromName.value,
    };
    // Conditions start at whatever the last inspection recorded, so the
    // common case (nothing changed) needs no input at all.
    conditionOut.value = props.items.reduce((carry, item) => {
        const current =
            item.latest_inspection?.asset_condition_id ??
            item.latest_inspection?.asset_condition?.id ??
            "";

        if (current) carry[item.id] = current;

        return carry;
    }, {});
};

watch(
    () => props.show,
    (open) => {
        if (open) reset();
    },
);

// Keep the lender in step if the ticked rows change while the form is open.
watch(
    () => props.items,
    () => {
        if (!props.show) return;

        form.value.borrowed_from_user_id = borrowedFromUserId.value;
        form.value.borrowed_from_name =
            borrowedFromName.value === "Unassigned" ? "" : borrowedFromName.value;
    },
    { deep: true },
);

function pickBorrowerStaff(row) {
    form.value.borrower_user_id = row.id;
    form.value.borrower_name = row.name;
    form.value.employee_number = row.employee_number ?? "";
    form.value.organization = row.department ?? "";
}

function pickBorrowerStudent(row) {
    form.value.borrower_user_id = row.id;
    form.value.borrower_name = row.name;
    form.value.student_number = row.employee_number ?? form.value.student_number;
}

function unlinkStudent() {
    form.value.borrower_user_id = null;
}

const studentLinked = computed(
    () => form.value.borrower_type === "student" && Boolean(form.value.borrower_user_id),
);

function setType(type) {
    form.value.borrower_type = type;
    form.value.borrower_name = "";
    form.value.borrower_user_id = null;
    form.value.employee_number = "";
}

const staffPicked = computed(
    () => form.value.borrower_type !== "staff" || Boolean(form.value.borrower_user_id),
);

const dueOk = computed(
    () => form.value.no_due_date || Boolean(form.value.due_at),
);

const canSubmit = computed(
    () =>
        props.items.length > 0 &&
        Boolean(form.value.borrower_name?.trim()) &&
        staffPicked.value &&
        dueOk.value,
);

const missingReason = computed(() => {
    if (!props.items.length) return "Tick the items you want to lend first.";
    if (form.value.borrower_type === "staff" && !staffPicked.value)
        return "Pick the staff member from the PUSO list.";
    if (!form.value.borrower_name?.trim()) return "Enter the borrower's name.";
    if (!dueOk.value) return "Set a due date, or tick 'no fixed return date'.";

    return "";
});

function submit() {
    if (!canSubmit.value) return;

    emit("submit", {
        ...form.value,
        // The column is named released_by; here it means "borrowed from".
        released_by: form.value.borrowed_from_user_id,
        borrowed_from_name: form.value.borrowed_from_name,
        due_at: form.value.no_due_date ? null : form.value.due_at,
        items: props.items.map((item) => ({
            id: item.id,
            quantity: 1,
            condition_out_id: conditionOut.value[item.id] || null,
        })),
    });
}
</script>

<template>
    <div
        v-if="show"
        class="fixed inset-0 modal-backdrop z-50 flex items-center justify-center p-4"
    >
        <div
            class="flex max-h-[92vh] w-full max-w-3xl flex-col overflow-hidden rounded-2xl bg-white shadow-2xl"
        >
            <!-- HEADER -->
            <div
                class="flex flex-shrink-0 items-center justify-between bg-gradient-to-r from-[#003d2c] via-[#005740] to-[#00795a] px-6 py-5"
            >
                <div>
                    <h3 class="text-lg font-bold text-white">Lend Items</h3>
                    <p class="mt-0.5 text-xs text-white/75">
                        {{ items.length }} item(s) will be marked as borrowed.
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
            <div class="flex-1 space-y-5 overflow-y-auto bg-gray-50 px-6 py-5">
                <!-- WHO IS BORROWING -->
                <div>
                    <p class="mb-2 text-xs font-semibold uppercase tracking-wide text-gray-500">
                        Borrower
                    </p>

                    <div class="grid grid-cols-1 gap-2 sm:grid-cols-3">
                        <button
                            v-for="type in TYPES"
                            :key="type.value"
                            type="button"
                            @click="setType(type.value)"
                            class="flex items-start gap-2 rounded-xl border bg-white px-3 py-2.5 text-left transition-colors"
                            :class="
                                form.borrower_type === type.value
                                    ? 'border-[#005740] ring-1 ring-[#005740]/30'
                                    : 'border-gray-200 hover:border-[#005740]/40'
                            "
                        >
                            <component
                                :is="type.icon"
                                class="mt-0.5 h-4 w-4 flex-shrink-0"
                                :class="
                                    form.borrower_type === type.value
                                        ? 'text-[#005740]'
                                        : 'text-gray-400'
                                "
                            />
                            <span class="min-w-0">
                                <span class="block text-sm font-semibold text-gray-800">
                                    {{ type.label }}
                                </span>
                                <span class="block text-[11px] text-gray-500">
                                    {{ type.hint }}
                                </span>
                            </span>
                        </button>
                    </div>
                </div>

                <!-- STAFF: PUSO DROPDOWN -->
                <div v-if="form.borrower_type === 'staff'">
                    <label class="mb-2 block text-xs font-semibold uppercase tracking-wide text-gray-500">
                        Staff member <span class="normal-case text-gray-400">(from the PUSO list)</span>
                    </label>

                    <StaffPicker
                        :model-value="form.borrower_name"
                        @picked="pickBorrowerStaff"
                    />

                    <p class="mt-1.5 text-[11px] text-gray-400">
                        Staff are read from the PUSO list already stored in the
                        system, so a name never has to be typed twice.
                    </p>
                </div>

                <!-- STUDENT: OPTIONAL ACCOUNT LINK -->
                <div v-if="form.borrower_type === 'student'">
                    <label class="mb-2 block text-xs font-semibold uppercase tracking-wide text-gray-500">
                        Student account <span class="normal-case text-gray-400">(optional)</span>
                    </label>

                    <StaffPicker
                        :model-value="studentLinked ? form.borrower_name : ''"
                        search-route="borrowings.students"
                        placeholder="Search student accounts by name or student number..."
                        @picked="pickBorrowerStudent"
                    />

                    <p v-if="studentLinked" class="mt-1.5 text-[11px] text-[#005740]">
                        Linked to {{ form.borrower_name }}. This loan will show on their Student Portal.
                        <button
                            type="button"
                            class="ml-1 font-semibold underline hover:text-[#00432f]"
                            @click="unlinkStudent"
                        >
                            Unlink
                        </button>
                    </p>
                    <p v-else class="mt-1.5 text-[11px] text-gray-400">
                        Link the student's account so the loan shows on their Student Portal,
                        or type their name below.
                    </p>
                </div>

                <!-- STUDENT / OUTSIDER -->
                <div v-if="form.borrower_type !== 'staff'" class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                    <div>
                        <label class="mb-1.5 block text-xs font-semibold uppercase tracking-wide text-gray-500">
                            Name <span class="text-red-500">*</span>
                        </label>
                        <input
                            v-model="form.borrower_name"
                            type="text"
                            placeholder="Full name"
                            :readonly="studentLinked"
                            class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2.5 text-sm read-only:bg-gray-50 read-only:text-gray-500 focus:border-[#005740] focus:outline-none focus:ring-1 focus:ring-[#005740]"
                        />
                    </div>

                    <div v-if="form.borrower_type === 'student'">
                        <label class="mb-1.5 block text-xs font-semibold uppercase tracking-wide text-gray-500">
                            Student number <span class="text-gray-400">(optional)</span>
                        </label>
                        <input
                            v-model="form.student_number"
                            type="text"
                            placeholder="2026-00000"
                            class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2.5 text-sm focus:border-[#005740] focus:outline-none focus:ring-1 focus:ring-[#005740]"
                        />
                    </div>

                    <div v-if="form.borrower_type === 'outsider'">
                        <label class="mb-1.5 block text-xs font-semibold uppercase tracking-wide text-gray-500">
                            Organisation
                        </label>
                        <input
                            v-model="form.organization"
                            type="text"
                            placeholder="Where they come from"
                            class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2.5 text-sm focus:border-[#005740] focus:outline-none focus:ring-1 focus:ring-[#005740]"
                        />
                    </div>

                    <div>
                        <label class="mb-1.5 block text-xs font-semibold uppercase tracking-wide text-gray-500">
                            Contact number <span class="text-gray-400">(optional)</span>
                        </label>
                        <input
                            v-model="form.contact_number"
                            type="text"
                            placeholder="0917 000 0000"
                            class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2.5 text-sm focus:border-[#005740] focus:outline-none focus:ring-1 focus:ring-[#005740]"
                        />
                    </div>
                </div>

                <!-- BORROWED FROM: the accountable person, fixed -->
                <div class="rounded-xl border border-gray-200 bg-white p-4">
                    <label class="mb-2 flex items-center gap-2 text-xs font-semibold uppercase tracking-wide text-gray-500">
                        <HandHelping class="h-3.5 w-3.5 text-[#005740]" />
                        Borrowed from
                        <span class="normal-case text-gray-400">(accountable person)</span>
                    </label>

                    <div
                        class="flex min-h-[46px] items-center gap-2 rounded-lg border border-gray-200 bg-gray-50 px-3 py-2.5 text-sm font-semibold text-[#1f2d27]"
                    >
                        <Lock class="h-3.5 w-3.5 shrink-0 text-gray-400" />
                        <span class="truncate">{{ borrowedFromName }}</span>
                    </div>

                    <p class="mt-1.5 text-[11px] text-gray-400">
                        Taken from the accountable person of the selected
                        item(s) and cannot be changed here.
                    </p>

                    <p
                        v-if="hasSeveralLenders"
                        class="mt-1 text-[11px] font-medium text-amber-700"
                    >
                        These items are held by different accountable persons —
                        all of them are shown above.
                    </p>
                </div>

                <!-- WHEN -->
                <div class="grid grid-cols-1 gap-3 rounded-xl border border-gray-200 bg-white p-4 sm:grid-cols-3">
                    <div>
                        <label class="mb-1.5 block text-xs font-semibold uppercase tracking-wide text-gray-500">
                            Borrowed on
                        </label>
                        <input
                            v-model="form.borrowed_at"
                            type="date"
                            class="w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm focus:border-[#005740] focus:outline-none focus:ring-1 focus:ring-[#005740]"
                        />
                    </div>

                    <div>
                        <label class="mb-1.5 block text-xs font-semibold uppercase tracking-wide text-gray-500">
                            Due date
                        </label>
                        <input
                            v-model="form.due_at"
                            type="date"
                            :disabled="form.no_due_date"
                            class="w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm focus:border-[#005740] focus:outline-none focus:ring-1 focus:ring-[#005740] disabled:bg-gray-100 disabled:text-gray-400"
                        />
                    </div>

                    <label class="flex items-end gap-2 pb-2.5 text-sm text-gray-700">
                        <input
                            v-model="form.no_due_date"
                            type="checkbox"
                            class="h-4 w-4 rounded border-gray-300 text-[#005740] focus:ring-[#005740]"
                        />
                        No fixed return date
                    </label>

                    <p class="flex items-center gap-1.5 text-[11px] text-gray-400 sm:col-span-3">
                        <CalendarClock class="h-3.5 w-3.5" />
                        A loan without a due date never shows as overdue.
                    </p>
                </div>

                <!-- ITEMS + CONDITION OUT -->
                <div>
                    <p class="mb-2 text-xs font-semibold uppercase tracking-wide text-gray-500">
                        Items to lend ({{ items.length }})
                    </p>

                    <p class="mb-2 text-[11px] text-gray-400">
                        Conditions are prefilled from each item's last inspection -
                        change one if the item looks different today.
                    </p>

                    <div class="space-y-2">
                        <div
                            v-for="item in items"
                            :key="item.id"
                            class="flex flex-col gap-2 rounded-xl border border-gray-200 bg-white px-3 py-2.5 sm:flex-row sm:items-center sm:justify-between"
                        >
                            <div class="min-w-0">
                                <p class="truncate text-sm font-semibold text-gray-800">
                                    {{ item.property_number }}
                                </p>
                                <p class="truncate text-xs text-gray-500">
                                    {{ item.item_name || "—" }}
                                </p>
                            </div>

                            <select
                                v-model="conditionOut[item.id]"
                                class="w-full rounded-lg border border-gray-300 px-3 py-2 text-xs text-gray-700 focus:border-[#005740] focus:outline-none sm:w-40"
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
                        </div>
                    </div>
                </div>

                <!-- NOTES -->
                <div>
                    <label class="mb-1.5 block text-xs font-semibold uppercase tracking-wide text-gray-500">
                        Additional notes
                    </label>
                    <textarea
                        v-model="form.notes"
                        rows="3"
                        placeholder="Purpose, remarks, who will use it..."
                        class="w-full resize-none rounded-xl border border-gray-300 px-3 py-2.5 text-sm focus:border-[#005740] focus:outline-none focus:ring-1 focus:ring-[#005740]"
                    ></textarea>
                </div>
            </div>

            <!-- FOOTER -->
            <div
                class="flex flex-col gap-3 border-t border-gray-100 px-6 py-4 sm:flex-row sm:items-center sm:justify-end"
            >
                <p v-if="!canSubmit" class="text-xs font-medium text-amber-600 sm:mr-auto">
                    {{ missingReason }}
                </p>

                <div class="flex items-center justify-end gap-3">
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
                        :disabled="!canSubmit"
                        class="flex items-center gap-2 rounded-lg bg-[#005740] px-6 py-2.5 text-sm font-semibold text-white transition-colors hover:bg-[#00452f] disabled:cursor-not-allowed disabled:opacity-50 disabled:hover:bg-[#005740]"
                    >
                        <UserRound class="h-4 w-4" />
                        Lend {{ items.length }} item(s)
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>

