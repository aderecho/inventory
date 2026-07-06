<script setup>
import { ref, watch } from "vue";
import { useForm } from "@inertiajs/vue3";
import Toast from "primevue/toast";
import { useToast } from "primevue/usetoast";

const toast = useToast();

const props = defineProps({
    mode: { type: String, default: "create" },
    supplierFields: { type: Array, default: () => [] },
    supplier: { type: Object, default: () => ({}) },
});

const emit = defineEmits(["submit", "close"]);

const form = useForm({
    id: null,
    supplier_name: "",
    contact_no: "",
    email: "",
    address: "",
});

// populate form when editing
watch(
    () => props.supplier,
    (val) => {
        if (val) Object.assign(form, val);
    },
    { immediate: true },
);

// animation
const isClosing = ref(false);

function closeWithAnimation() {
    isClosing.value = true;
    setTimeout(() => {
        emit("close");
        isClosing.value = false;
    }, 200);
}

function submit() {
    if (props.mode === "edit") {
        form.put(route("suppliers.update", form.id), {
            onSuccess: () => {
                toast.add({
                    severity: "success",
                    summary: "Updated",
                    detail: "Supplier updated successfully.",
                    life: 3000,
                });

                emit("close");
                emit("submit", form);
            },
            onError: (errors) => {
                const firstError = Object.values(errors)[0];

                toast.add({
                    severity: "error",
                    summary: "Validation Failed",
                    detail: firstError,
                    life: 4000,
                });
            },
        });
    } else {
        form.post(route("suppliers.store"), {
            onSuccess: () => {
                toast.add({
                    severity: "success",
                    summary: "Created",
                    detail: "Supplier added successfully.",
                    life: 3000,
                });

                form.reset();
                emit("submit", form);
                emit("close");
            },
            onError: (errors) => {
                const firstError = Object.values(errors)[0];

                toast.add({
                    severity: "error",
                    summary: "Validation Failed",
                    detail: firstError,
                    life: 4000,
                });
            },
        });
    }
}
</script>

<template>
    <div
        class="fixed inset-0 bg-black/50 flex items-center justify-center p-4 z-50" @click="closeWithAnimation"
    >
        <div
            :class="[
                'bg-white rounded-2xl w-full max-w-lg shadow-2xl overflow-hidden flex flex-col max-h-[92vh]',
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
                        {{ mode === "edit" ? "Edit Supplier" : "Add Supplier" }}
                    </h3>
                    <p class="text-xs text-white/70 mt-0.5">
                        {{
                            mode === "edit"
                                ? "Update this supplier's details."
                                : "Add a new supplier to your records."
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

            <!-- FORM -->
            <form
                @submit.prevent="submit"
                class="flex flex-col flex-1 overflow-hidden"
            >
                <div class="p-6 overflow-y-auto space-y-4">
                    <div v-for="supF in supplierFields" :key="supF.model">
                        <label
                            class="block text-xs font-semibold text-gray-500 uppercase tracking-wide mb-1.5"
                        >
                            {{ supF.label }}
                            <span v-if="supF.required" class="text-red-500"
                                >*</span
                            >
                        </label>

                        <input
                            v-model="form[supF.model]"
                            :type="supF.type || 'text'"
                            :placeholder="supF.placeholder"
                            :class="[
                                'w-full rounded-md px-3 py-3 text-[#3B3B3B] bg-[#F8F8F8] text-sm focus:ring-1 focus:outline-none border',
                                form.errors[supF.model]
                                    ? 'border-red-500 focus:ring-red-500 focus:border-red-500'
                                    : 'border-gray-300 focus:ring-[#005740] focus:border-[#005740]',
                            ]"
                        />
                        <p
                            v-if="form.errors[supF.model]"
                            class="text-red-500 text-xs mt-1"
                        >
                            {{ form.errors[supF.model] }}
                        </p>
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
                        {{ mode === "edit" ? "Update" : "Add" }}
                    </button>
                </div>
            </form>
        </div>
    </div>
</template>
