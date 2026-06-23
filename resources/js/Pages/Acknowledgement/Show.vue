<script setup>
import { ref } from "vue";
import { useForm, router } from "@inertiajs/vue3";
import { useToast } from "primevue/usetoast";
import Toast from "primevue/toast";
import { usePermissions } from "@/Composables/usePermissions";

const props = defineProps({
    receipt: Object,
    groupedByPerson: Array,
});

const {
    canViewAcknowledgements,
    canCreateAcknowledgements,
    canShowAcknowledgements,
    canUploadAcknowledgements,
} = usePermissions();

const emit = defineEmits(["close"]);

const toast = useToast();
const isClosing = ref(false);

function closeWithAnimation() {
    isClosing.value = true;
    setTimeout(() => emit("close"), 200);
}

const selectedItems = ref({});

function toggleItem(personId, itemId) {
    if (!selectedItems.value[personId]) {
        selectedItems.value[personId] = [];
    }
    const list = selectedItems.value[personId];
    const idx = list.indexOf(itemId);
    if (idx === -1) {
        list.push(itemId);
    } else {
        list.splice(idx, 1);
    }
}

function isChecked(personId, itemId) {
    return selectedItems.value[personId]?.includes(itemId) ?? false;
}

function hasSelection(personId) {
    return (selectedItems.value[personId]?.length ?? 0) > 0;
}

const uploadForms = ref({});

function getForm(personId) {
    if (!uploadForms.value[personId]) {
        uploadForms.value[personId] = useForm({
            file: null,
            acknowledgement_item_ids: [],
        });
    }
    return uploadForms.value[personId];
}

function onFileChange(personId, event) {
    getForm(personId).file = event.target.files[0];
}

function submitUpload(personId) {
    const form = getForm(personId);
    form.acknowledgement_item_ids = selectedItems.value[personId] ?? [];

    if (!form.file) {
        toast.add({
            severity: "warn",
            summary: "No file",
            detail: "Please select a file first.",
            life: 3000,
        });
        return;
    }

    if (form.acknowledgement_item_ids.length === 0) {
        toast.add({
            severity: "warn",
            summary: "No items",
            detail: "Please select at least one item.",
            life: 3000,
        });
        return;
    }

    form.post(route("acknowledgements.upload-file"), {
        forceFormData: true,
        onSuccess: () => {
            toast.add({
                severity: "success",
                summary: "Uploaded",
                detail: "Receipt uploaded successfully.",
                life: 3000,
            });
            selectedItems.value[personId] = [];
            form.reset();

            // Reload only the receipts prop, then rebuild groupedByPerson
            router.reload({ only: ["receipts"] });
        },
        onError: (errors) => {
            const message = errors.file ?? Object.values(errors)[0];
            toast.add({
                severity: "error",
                summary: "Failed",
                detail: message,
                life: 4000,
            });
        },
    });
}

function viewFile(filePath) {
    window.open(`/storage/${filePath}`, "_blank");
}
</script>

<template>
    <Toast />

    <div
        class="fixed inset-0 bg-black/40 flex items-center justify-center p-4"
        @click="closeWithAnimation"
    >
        <div
            :class="[
                'bg-white rounded-lg w-full max-w-2xl p-6 overflow-y-auto max-h-[90vh]',
                isClosing ? 'animate-pop-out' : 'animate-pop-in',
            ]"
            @click.stop
        >
            <!-- Header -->
            <div class="flex items-start justify-between mb-6">
                <div>
                    <h3 class="text-2xl font-bold text-[#850038]">
                        {{ receipt.category }} — {{ receipt.par_date }}
                    </h3>
                    <p class="text-sm text-gray-500 mt-1">
                        Issued by:
                        {{
                            receipt.issued_by?.user_profiles?.full_name ?? "N/A"
                        }}
                    </p>
                </div>
                <button
                    @click="closeWithAnimation"
                    class="text-gray-400 hover:text-gray-600 text-xl mt-1"
                >
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>

            <!-- Grouped by person -->
            <div
                v-for="group in groupedByPerson"
                :key="group.person.id"
                class="border rounded-xl p-5 mb-5"
            >
                <!-- Person header -->
                <div class="flex items-center gap-3 mb-4">
                    <div
                        class="w-9 h-9 rounded-full bg-[#FAECE7] text-[#993C1D] flex items-center justify-center text-sm font-medium"
                    >
                        {{ group.person.full_name?.charAt(0) ?? "?" }}
                    </div>
                    <div>
                        <p class="font-medium">{{ group.person.full_name }}</p>
                        <p class="text-xs text-gray-400">
                            {{ group.items.length }} item(s)
                        </p>
                    </div>
                </div>

                <!-- Items list with checkboxes -->
                <div class="divide-y mb-4">
                    <div
                        v-for="item in group.items"
                        :key="item.id"
                        class="flex items-center gap-3 py-3"
                        :class="{ 'opacity-50': item.file }"
                    >
                        <input
                            type="checkbox"
                            :disabled="!!item.file"
                            :checked="isChecked(group.person.id, item.id)"
                            @change="toggleItem(group.person.id, item.id)"
                            class="w-4 h-4 accent-[#850038]"
                        />

                        <div class="flex-1">
                            <p class="text-sm font-medium">
                                {{ item.inventory_items?.item_name ?? "N/A" }}
                            </p>
                            <p class="text-xs text-gray-400">
                                {{
                                    item.inventory_items?.property_number ??
                                    "N/A"
                                }}
                            </p>
                        </div>

                        <div v-if="item.file" class="flex items-center gap-2">
                            <span class="text-xs text-green-600 font-medium">{{
                                item.file.file_path.split("/").pop()
                            }}</span>
                            <button
                                @click="viewFile(item.file.file_path)"
                                class="text-xs text-[#185FA5] underline"
                            >
                                View
                            </button>
                        </div>
                        <span v-else class="text-xs text-gray-400"
                            >No file</span
                        >
                    </div>
                </div>

                <!-- Upload zone -->
                <div
                    v-if="hasSelection(group.person.id) && canUploadAcknowledgements"
                    class="border border-dashed rounded-lg p-4 flex items-center gap-4 bg-gray-50"
                >
                    <div class="flex-1">
                        <p class="text-sm font-medium">
                            Upload receipt for
                            {{
                                selectedItems[group.person.id]?.length
                            }}
                            selected item(s)
                        </p>
                        <input
                            type="file"
                            accept=".pdf,.jpg,.jpeg,.png"
                            class="mt-2 text-sm"
                            @change="onFileChange(group.person.id, $event)"
                        />
                    </div>
                    <button
                        @click="submitUpload(group.person.id)"
                        :disabled="getForm(group.person.id).processing"
                        class="bg-[#0E6021] text-white px-6 py-2 rounded-full text-sm font-semibold hover:bg-green-800 disabled:opacity-50"
                    >
                        {{
                            getForm(group.person.id).processing
                                ? "Uploading..."
                                : "Upload"
                        }}
                    </button>
                </div>

                <!-- All done state -->
                <div
                    v-else-if="group.items.every((i) => i.file)"
                    class="rounded-lg p-3 bg-green-50 text-green-700 text-sm text-center"
                >
                    All items have receipts
                </div>

                <!-- Prompt to select -->
                <div v-else class="text-xs text-gray-400 mt-2">
                    Check items above that share the same receipt, then upload.
                </div>
            </div>

            <!-- Footer -->
            <div class="flex justify-end mt-6">
                <button
                    @click="closeWithAnimation"
                    class="border border-gray-400 text-[#3B3B3B] px-6 py-3 rounded-full text-sm font-semibold hover:bg-gray-100"
                >
                    Close
                </button>
            </div>
        </div>
    </div>
</template>
