<script setup>
import { ref, computed } from "vue";
import { useForm, router } from "@inertiajs/vue3";
import { useToast } from "primevue/usetoast";
import Toast from "primevue/toast";
import { usePermissions } from "@/Composables/usePermissions";

const props = defineProps({
    receipt: Object,
    groupedByPerson: Array,
});

const { canUploadAcknowledgements } = usePermissions();
const emit = defineEmits(["close"]);
const toast = useToast();
const isClosing = ref(false);

function closeWithAnimation() {
    isClosing.value = true;
    setTimeout(() => emit("close"), 200);
}

const uploadForm = useForm({
    files: [],
    acknowledgement_id: null,
});

// Collect all unique files across all items (same group_id, so deduplicate by file id)
const receiptFiles = computed(() => {
    const seen = new Set();
    const files = [];
    props.groupedByPerson.forEach((group) => {
        group.items.forEach((item) => {
            (item.files ?? []).forEach((file) => {
                if (!seen.has(file.file_path)) {
                    seen.add(file.file_path);
                    files.push(file);
                }
            });
        });
    });
    return files;
});

const hasFiles = computed(() => receiptFiles.value.length > 0);

function onFileChange(event) {
    uploadForm.files = Array.from(event.target.files);
}

function submitUpload() {
    uploadForm.acknowledgement_id = props.receipt.id;

    if (!uploadForm.files.length) {
        toast.add({
            severity: "warn",
            summary: "No files",
            detail: "Please select at least one file.",
            life: 3000,
        });
        return;
    }

    uploadForm.post(route("acknowledgements.upload-file"), {
        forceFormData: true,
        onSuccess: () => {
            toast.add({
                severity: "success",
                summary: "Uploaded",
                detail: "Files uploaded successfully.",
                life: 3000,
            });
            uploadForm.reset();
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

function getOrdinal(n) {
    const v = n % 100;
    const suffix =
        v >= 11 && v <= 13 ? "th" : (["th", "st", "nd", "rd"][n % 10] ?? "th");
    return `${n}${suffix} file`;
}
</script>

<template>
    <Toast />

    <div
        class="fixed inset-0 bg-black/50 flex items-center justify-center p-4 z-50"
        @click="closeWithAnimation"
    >
        <div
            :class="[
                'bg-white rounded-2xl w-full max-w-2xl shadow-2xl overflow-hidden flex flex-col max-h-[92vh]',
                isClosing ? 'animate-pop-out' : 'animate-pop-in',
            ]"
            @click.stop
        >
            <!-- Header -->
            <div class="bg-gradient-to-r from-[#003d2c] via-[#005740] to-[#00795a] px-6 py-5 flex items-center justify-between flex-shrink-0">
                <div>
                    <h3 class="text-lg font-bold text-white">
                        {{ receipt.category }} — {{ receipt.par_date }}
                    </h3>
                    <p class="text-xs text-white/70 mt-0.5">
                        Issued by:
                        {{
                            receipt.issued_by?.user_profiles?.full_name ?? "N/A"
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

            <div class="p-6 overflow-y-auto flex-1">
                <!-- Grouped by person -->
                <div
                    v-for="group in groupedByPerson"
                    :key="group.person.id"
                    class="border border-gray-200 rounded-xl p-5 mb-5"
                >
                    <!-- Person header -->
                    <div class="flex items-center gap-3 mb-4">
                        <div
                            class="w-9 h-9 rounded-full bg-[#005740]/10 text-[#005740] flex items-center justify-center text-sm font-medium"
                        >
                            {{
                                group.person.first_name?.charAt(0) ??
                                group.person.full_name?.charAt(0) ??
                                ""
                            }}
                        </div>
                        <div>
                            <p class="font-medium text-[#1f2d27]">
                                {{ group.person.full_name ?? group.person.name }}
                            </p>
                            <p class="text-xs text-gray-400">
                                {{ group.items.length }} item(s)
                            </p>
                        </div>
                    </div>

                    <hr class="mb-5 border-gray-200" />

                    <!-- Items list (display only, no file info per item) -->
                    <div class="divide-y max-h-60 overflow-y-auto">
                        <div
                            v-for="item in group.items"
                            :key="item.id"
                            class="flex items-center gap-3 py-3"
                        >
                            <div class="flex-1">
                                <p class="text-sm font-medium text-[#1f2d27]">
                                    {{ item.inventory_item?.item_name ?? "N/A" }}
                                </p>
                                <p class="text-xs text-gray-400">
                                    {{
                                        item.inventory_item?.property_number ??
                                        "N/A"
                                    }}
                                </p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Receipt-level files -->
                <div class="mb-5">
                    <p class="text-xs font-semibold text-gray-500 uppercase tracking-wide mb-2">
                        Uploaded Files
                    </p>
                    <div
                        v-if="hasFiles"
                        class="flex flex-col gap-2 max-h-40 overflow-y-auto pr-1"
                    >
                        <div
                            v-for="(file, index) in receiptFiles"
                            :key="file.id"
                            class="flex items-center justify-between bg-gray-50 border border-gray-200 rounded-lg px-4 py-2"
                        >
                            <div class="flex items-center gap-2 text-sm">
                                <i class="fa-solid fa-paperclip text-gray-400"></i>
                                <span class="text-gray-700">
                                    {{ getOrdinal(index + 1) }}
                                </span>
                            </div>
                            <button
                                @click="viewFile(file.file_path)"
                                class="text-xs text-[#005740] font-medium underline shrink-0 ml-4"
                            >
                                View
                            </button>
                        </div>
                    </div>
                    <p v-else class="text-xs text-gray-400">
                        No files uploaded yet.
                    </p>
                </div>

                <!-- Upload zone -->
                <div v-if="canUploadAcknowledgements">
                    <div
                        v-if="hasFiles"
                        class="rounded-lg p-3 bg-green-50 text-green-700 text-sm text-center border border-green-200"
                    >
                        All items have receipts uploaded
                    </div>
                    <div
                        v-else
                        class="border border-dashed border-gray-300 rounded-lg p-4 flex items-center gap-4 bg-gray-50"
                    >
                        <div class="flex-1">
                            <p class="text-sm font-medium text-[#1f2d27]">
                                Upload receipts for all items
                            </p>
                            <input
                                type="file"
                                accept=".pdf,.jpg,.jpeg,.png"
                                multiple
                                class="mt-2 text-sm"
                                @change="onFileChange"
                            />
                        </div>
                        <button
                            @click="submitUpload"
                            :disabled="uploadForm.processing"
                            class="bg-gradient-to-r from-[#005740] to-[#00795a] text-white px-6 py-2.5 rounded-lg text-sm font-semibold hover:shadow-md hover:from-[#00432f] hover:to-[#006548] transition-all disabled:opacity-60"
                        >
                            {{ uploadForm.processing ? "Uploading..." : "Upload" }}
                        </button>
                    </div>
                </div>
            </div>

            <!-- Footer -->
            <div class="flex justify-end px-6 py-4 border-t border-gray-100 flex-shrink-0">
                <button
                    @click="closeWithAnimation"
                    class="px-6 py-2.5 rounded-lg text-sm font-medium text-gray-600 hover:bg-gray-100 transition-colors"
                >
                    Close
                </button>
            </div>
        </div>
    </div>
</template>
