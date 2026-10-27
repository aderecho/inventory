<script setup>
import { computed, ref } from "vue";
import { router, useForm } from "@inertiajs/vue3";
import { useToast } from "primevue/usetoast";
import { Check, Pencil, Plus, Trash2, X } from "lucide-vue-next";

/**
 * The condition list: add a condition, rename one, change what it means, or
 * delete it. The meaning stays part of the form on purpose - the app routes
 * disposal by what a condition means, never by what it is called.
 */
const props = defineProps({
    conditions: { type: Array, default: () => [] },
});

const emit = defineEmits(["close"]);

const toast = useToast();

const MEANINGS = [
    {
        value: "serviceable",
        label: "Serviceable",
        hint: "Normal stock, still in service.",
    },
    {
        value: "for_disposal",
        label: "For disposal",
        hint: "A candidate: stays in inventory while it waits to be disposed of.",
    },
    {
        value: "disposed",
        label: "Disposed",
        hint: "Terminal: the item leaves the inventory list.",
    },
];

const form = useForm({
    condition_name: "",
    description: "",
    disposition: "serviceable",
});

const editing = ref(null);
const deletingId = ref(null);
const isClosing = ref(false);

const isEditing = computed(() => Boolean(editing.value));

const meaningLabel = (value) =>
    MEANINGS.find((meaning) => meaning.value === value)?.label ??
    (value || "Serviceable");

function close() {
    isClosing.value = true;

    setTimeout(() => {
        emit("close");
        isClosing.value = false;
    }, 200);
}

function startEdit(condition) {
    editing.value = condition;
    form.condition_name = condition.condition_name;
    form.description = condition.description ?? "";
    form.disposition = condition.disposition ?? "serviceable";
    form.clearErrors();
    deletingId.value = null;
}

function cancelEdit() {
    editing.value = null;
    form.reset();
    form.clearErrors();
}

function refreshLists() {
    router.reload({ only: ["assetConditions", "items"] });
}

function submit() {
    const action = isEditing.value ? "updated" : "added";
    const options = {
        preserveScroll: true,
        onSuccess: () => {
            toast.add({
                severity: "success",
                summary: `Condition ${action}`,
                detail: form.condition_name,
                life: 3000,
            });

            cancelEdit();
            refreshLists();
        },
        onError: (errors) => {
            toast.add({
                severity: "error",
                summary: "Condition not saved",
                detail: Object.values(errors)[0] ?? "Please check the form.",
                life: 5000,
            });
        },
    };

    if (isEditing.value) {
        form.put(route("asset-conditions.update", editing.value.id), options);
    } else {
        form.post(route("asset-conditions.store"), options);
    }
}

function destroy(condition) {
    router.delete(route("asset-conditions.destroy", condition.id), {
        preserveScroll: true,
        onSuccess: () => {
            toast.add({
                severity: "success",
                summary: "Condition deleted",
                detail: condition.condition_name,
                life: 3000,
            });

            if (editing.value?.id === condition.id) {
                cancelEdit();
            }

            deletingId.value = null;
            refreshLists();
        },
        onError: () => {
            toast.add({
                severity: "error",
                summary: "Condition not deleted",
                detail: "It may still be in use by existing inspections.",
                life: 5000,
            });
            deletingId.value = null;
        },
    });
}
</script>

<template>
    <div
        class="fixed inset-0 z-50 flex items-center justify-center p-4"
        :class="isClosing ? 'opacity-0 transition-opacity duration-200' : ''"
    >
        <div class="modal-backdrop absolute inset-0" @click="close"></div>

        <div
            class="relative flex max-h-[92vh] w-full max-w-4xl flex-col overflow-hidden rounded-2xl bg-white shadow-2xl"
        >
            <!-- HEADER -->
            <div class="flex items-start justify-between gap-3 border-b border-gray-100 px-6 py-5">
                <div>
                    <h2 class="text-lg font-semibold text-gray-900">
                        Manage Conditions
                    </h2>
                    <p class="mt-1 text-xs text-gray-500">
                        Add a condition, rename one, change what it means, or
                        delete it. What a condition means is what the system
                        follows.
                    </p>
                </div>

                <button
                    type="button"
                    class="flex h-9 w-9 items-center justify-center rounded-lg text-gray-400 transition hover:bg-gray-100 hover:text-gray-600"
                    @click="close"
                >
                    <X class="h-4 w-4" />
                </button>
            </div>

            <!-- BODY -->
            <div class="flex-1 space-y-5 overflow-y-auto bg-gray-50 px-6 py-6">
                <!-- ADD / EDIT FORM -->
                <section class="rounded-xl border border-gray-200 bg-white p-4">
                    <div class="mb-3 flex items-center justify-between gap-2">
                        <h3 class="text-xs font-semibold uppercase tracking-wide text-gray-500">
                            {{ isEditing ? "Edit condition" : "Add condition" }}
                        </h3>

                        <span
                            v-if="isEditing"
                            class="rounded-full bg-[#005740]/10 px-2.5 py-1 text-[11px] font-semibold text-[#005740]"
                        >
                            Editing {{ editing.condition_name }}
                        </span>
                    </div>

                    <div class="grid gap-4 md:grid-cols-2">
                        <div class="space-y-4">
                            <div>
                                <label class="mb-1.5 block text-[11px] font-semibold uppercase tracking-wide text-gray-500">
                                    Condition name <span class="text-red-500">*</span>
                                </label>

                                <input
                                    v-model="form.condition_name"
                                    type="text"
                                    placeholder="Disposed, For disposal, Unserviceable..."
                                    class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2.5 text-sm transition focus:border-[#005740] focus:outline-none focus:ring-2 focus:ring-[#005740]/20"
                                    @keyup.enter="submit"
                                />

                                <p
                                    v-if="form.errors.condition_name"
                                    class="mt-1.5 text-xs font-medium text-[#D71D1D]"
                                >
                                    {{ form.errors.condition_name }}
                                </p>
                            </div>

                            <div>
                                <label class="mb-1.5 block text-[11px] font-semibold uppercase tracking-wide text-gray-500">
                                    Description
                                </label>

                                <textarea
                                    v-model="form.description"
                                    rows="3"
                                    placeholder="Optional note shown next to this condition."
                                    class="w-full resize-none rounded-lg border border-gray-300 bg-white px-3 py-2.5 text-sm transition focus:border-[#005740] focus:outline-none focus:ring-2 focus:ring-[#005740]/20"
                                ></textarea>

                                <p
                                    v-if="form.errors.description"
                                    class="mt-1.5 text-xs font-medium text-[#D71D1D]"
                                >
                                    {{ form.errors.description }}
                                </p>
                            </div>
                        </div>

                        <div>
                            <label class="mb-1.5 block text-[11px] font-semibold uppercase tracking-wide text-gray-500">
                                What it means <span class="text-red-500">*</span>
                            </label>

                            <div class="space-y-2">
                                <label
                                    v-for="meaning in MEANINGS"
                                    :key="meaning.value"
                                    class="flex cursor-pointer items-start gap-3 rounded-lg border bg-white px-3 py-2.5 transition"
                                    :class="
                                        form.disposition === meaning.value
                                            ? 'border-[#005740] ring-1 ring-[#005740]/25'
                                            : 'border-gray-200 hover:border-gray-300'
                                    "
                                >
                                    <input
                                        v-model="form.disposition"
                                        type="radio"
                                        :value="meaning.value"
                                        class="mt-0.5 h-4 w-4 accent-[#0E6021]"
                                    />

                                    <span>
                                        <span class="block text-sm font-semibold text-gray-800">
                                            {{ meaning.label }}
                                        </span>
                                        <span class="block text-xs text-gray-500">
                                            {{ meaning.hint }}
                                        </span>
                                    </span>
                                </label>
                            </div>

                            <p
                                v-if="form.errors.disposition"
                                class="mt-1.5 text-xs font-medium text-[#D71D1D]"
                            >
                                {{ form.errors.disposition }}
                            </p>
                        </div>
                    </div>

                    <div class="mt-4 flex justify-end gap-2">
                        <button
                            v-if="isEditing"
                            type="button"
                            class="rounded-lg px-4 py-2 text-sm font-medium text-gray-600 transition hover:bg-gray-100"
                            @click="cancelEdit"
                        >
                            Cancel edit
                        </button>

                        <button
                            type="button"
                            :disabled="form.processing"
                            class="flex items-center gap-2 rounded-lg bg-[#0E6021] px-5 py-2 text-sm font-semibold text-white transition hover:bg-[#19703a] disabled:cursor-not-allowed disabled:opacity-50"
                            @click="submit"
                        >
                            <component :is="isEditing ? Check : Plus" class="h-4 w-4" />
                            {{
                                form.processing
                                    ? "Saving..."
                                    : isEditing
                                      ? "Save changes"
                                      : "Add condition"
                            }}
                        </button>
                    </div>
                </section>

                <!-- CONDITION TABLE -->
                <section class="overflow-hidden rounded-xl border border-gray-200 bg-white">
                    <div class="flex items-center justify-between border-b border-gray-100 px-4 py-3">
                        <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">
                            Conditions
                        </p>
                        <span class="text-[11px] font-medium text-gray-400">
                            {{ conditions.length }} total
                        </span>
                    </div>

                    <table class="w-full table-fixed text-left text-sm">
                        <colgroup>
                            <col style="width: 30%" />
                            <col style="width: 20%" />
                            <col />
                            <col style="width: 8.5rem" />
                        </colgroup>
                        <thead class="bg-gray-50 text-[11px] uppercase tracking-wide text-gray-500">
                            <tr>
                                <th class="px-4 py-2 font-semibold">Condition</th>
                                <th class="px-4 py-2 font-semibold">Meaning</th>
                                <th class="px-4 py-2 font-semibold">Description</th>
                                <th class="px-4 py-2 text-right font-semibold">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            <tr
                                v-for="condition in conditions"
                                :key="condition.id"
                                class="transition-colors"
                                :class="
                                    editing?.id === condition.id ? 'bg-[#005740]/5' : 'hover:bg-gray-50'
                                "
                            >
                                <td class="px-4 py-2.5 font-medium text-[#1f2d27]">
                                    {{ condition.condition_name }}
                                </td>
                                <td class="px-4 py-2.5">
                                    <span
                                        class="text-[11px] font-semibold"
                                        :class="
                                            condition.disposition === 'disposed'
                                                ? 'text-[#D32F2F]'
                                                : condition.disposition === 'for_disposal'
                                                  ? 'text-amber-700'
                                                  : 'text-[#2E7D32]'
                                        "
                                    >
                                        {{ meaningLabel(condition.disposition) }}
                                    </span>
                                </td>
                                <td class="truncate px-4 py-2.5 text-xs text-gray-500">
                                    {{ condition.description || "—" }}
                                </td>
                                <td class="px-4 py-2.5">
                                    <div
                                        v-if="deletingId === condition.id"
                                        class="flex items-center justify-end gap-1"
                                    >
                                        <button
                                            type="button"
                                            class="rounded-md bg-[#D71D1D] px-2 py-1 text-[11px] font-semibold text-white transition hover:bg-[#b81616]"
                                            @click="destroy(condition)"
                                        >
                                            Delete
                                        </button>
                                        <button
                                            type="button"
                                            class="rounded-md px-2 py-1 text-[11px] font-medium text-gray-500 transition hover:bg-gray-100"
                                            @click="deletingId = null"
                                        >
                                            Cancel
                                        </button>
                                    </div>

                                    <div v-else class="flex items-center justify-end gap-1">
                                        <button
                                            type="button"
                                            class="flex h-8 w-8 items-center justify-center rounded-md text-gray-400 transition hover:bg-[#005740]/10 hover:text-[#005740]"
                                            title="Edit condition"
                                            @click="startEdit(condition)"
                                        >
                                            <Pencil class="h-3.5 w-3.5" />
                                        </button>
                                        <button
                                            type="button"
                                            class="flex h-8 w-8 items-center justify-center rounded-md text-gray-400 transition hover:bg-red-50 hover:text-red-600"
                                            title="Delete condition"
                                            @click="deletingId = condition.id"
                                        >
                                            <Trash2 class="h-3.5 w-3.5" />
                                        </button>
                                    </div>
                                </td>
                            </tr>

                            <tr v-if="!conditions.length">
                                <td colspan="4" class="px-4 py-8 text-center text-sm text-gray-400">
                                    No conditions yet - add the first one above.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </section>
            </div>

            <!-- FOOTER -->
            <div class="flex justify-end border-t border-gray-100 bg-white px-6 py-4">
                <button
                    type="button"
                    class="rounded-lg px-5 py-2 text-sm font-medium text-gray-600 transition hover:bg-gray-100"
                    @click="close"
                >
                    Close
                </button>
            </div>
        </div>
    </div>
</template>

