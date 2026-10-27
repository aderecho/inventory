<script setup>
import { computed, ref } from "vue";
import {
    Eye,
    FileDown,
    FileSpreadsheet,
    Info,
    Upload,
    X,
} from "lucide-vue-next";

const props = defineProps({
    // Shows the "Download Sample" link inside the instructions.
    canDownloadSample: { type: Boolean, default: true },

    // Wording for the module using this modal (items by default).
    content: {
        type: Object,
        default: () => ({
            title: "Import Items",
            subtitle: "Add many items at once from an Excel or CSV file.",
            introTitle: "Follow the sample Excel columns to avoid errors.",
            introBody:
                "Your file must use the same column headers as the sample, in the first row. Each row below the headers is one item. Download the sample, replace the example rows with your items, and keep the headers as they are.",
            requiredColumns:
                "Property Number, Item Description, Quantity, Unit Cost, Supplier Name, Room / Location, Accountable Person, Receipt No., Receipt Date.",
            requiredNote:
                "Leave Accountable Person and Receipt No. blank to import an item without assigning it.",
            mustExist: [
                "Supplier Name, as named on the Suppliers page",
                "Room / Location, as a room name or code (e.g. ADM 201)",
                "Accountable Person, as a user's full name",
                "Classification, read from the first number of the Property Number (221-905-001 → 221)",
            ],
            values: [
                "Dates as YYYY-MM-DD, for example 2025-06-16.",
                "For several units, write the property range like 223-918-(001-003) and list one serial per unit, separated by commas.",
                "Condition: Good or Disposal · Approval Status: Pending, Approved, or Rejected · Private: Yes or No.",
                "The names in the sample are examples and will be rejected if imported as they are.",
                "Item Remarks are saved on the item. Receipt Remarks are saved on its receipt (PAR / ICS).",
            ],
            footer:
                "Rows with problems are skipped. After importing, you will see each skipped row's Excel row number and the reason, so you can fix and import them again.",
            importLabel: "Import",
        }),
    },
});

const emit = defineEmits(["close", "import", "review", "download-sample"]);

const MAX_BYTES = 20 * 1024 * 1024;
const ACCEPTED = [".xlsx", ".xls", ".csv"];

const fileInput = ref(null);
const selectedFile = ref(null);
const fileError = ref("");
const isDragging = ref(false);
const isClosing = ref(false);

const fileSize = computed(() => {
    const bytes = selectedFile.value?.size ?? 0;
    return bytes >= 1024 * 1024
        ? (bytes / (1024 * 1024)).toFixed(1) + " MB"
        : Math.max(1, Math.round(bytes / 1024)) + " KB";
});

function pickFile(file) {
    fileError.value = "";

    if (!file) return;

    const name = file.name.toLowerCase();
    if (!ACCEPTED.some((ext) => name.endsWith(ext))) {
        fileError.value = "Please choose an .xlsx, .xls, or .csv file.";
        return;
    }
    if (file.size > MAX_BYTES) {
        fileError.value = "The file is larger than 20 MB.";
        return;
    }

    selectedFile.value = file;
}

function onFileChange(event) {
    pickFile(event.target.files?.[0]);
    event.target.value = ""; // allow choosing the same file again
}

function onDrop(event) {
    isDragging.value = false;
    pickFile(event.dataTransfer?.files?.[0]);
}

function clearFile() {
    selectedFile.value = null;
    fileError.value = "";
}

function submit() {
    if (selectedFile.value) {
        emit("import", selectedFile.value);
    }
}

// Review parses the file and reports what would happen, without saving.
function review() {
    if (selectedFile.value) {
        emit("review", selectedFile.value);
    }
}

function closeWithAnimation() {
    isClosing.value = true;
    setTimeout(() => {
        emit("close");
        isClosing.value = false;
    }, 200);
}
</script>

<template>
    <div
        class="fixed inset-0 modal-backdrop flex items-center justify-center p-4 z-50"
    >
        <div
            :class="[
                'bg-white rounded-2xl w-full max-w-3xl shadow-2xl overflow-hidden flex flex-col max-h-[92vh]',
                isClosing ? 'animate-pop-out' : 'animate-pop-in',
            ]"
        >
            <!-- Header -->
            <div
                class="bg-gradient-to-r from-[#003d2c] via-[#005740] to-[#00795a] px-6 py-5 flex items-center justify-between flex-shrink-0"
            >
                <div>
                    <h3 class="text-lg font-bold text-white">
                        {{ content.title }}
                    </h3>
                    <p class="text-xs text-white/70 mt-0.5">
                        {{ content.subtitle }}
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

            <div class="p-6 overflow-y-auto flex-1 space-y-5">
                <!-- Instructions -->
                <div
                    class="rounded-lg border border-amber-200 bg-amber-50 p-4 text-sm text-amber-900"
                >
                    <div class="flex gap-3">
                        <Info class="w-5 h-5 shrink-0 text-amber-600 mt-0.5" />
                        <div class="space-y-1.5">
                            <p class="font-semibold">
                                {{ content.introTitle }}
                            </p>
                            <p>{{ content.introBody }}</p>
                            <button
                                v-if="canDownloadSample"
                                type="button"
                                @click="emit('download-sample')"
                                class="mt-1 inline-flex items-center gap-1.5 rounded-md border border-amber-300 bg-white px-3 py-1.5 text-xs font-semibold text-[#005740] hover:bg-amber-100 transition-colors"
                            >
                                <FileDown class="w-4 h-4" />
                                Download Sample (Excel)
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Rules -->
                <div class="grid gap-4 md:grid-cols-2 text-sm text-gray-700">
                    <div>
                        <h4
                            class="text-xs font-semibold text-gray-500 uppercase tracking-wide mb-1.5"
                        >
                            Required columns
                        </h4>
                        <p class="leading-relaxed">
                            {{ content.requiredColumns }}
                        </p>
                        <p
                            v-if="content.requiredNote"
                            class="mt-1 text-xs text-gray-500"
                        >
                            {{ content.requiredNote }}
                        </p>
                    </div>

                    <div v-if="content.mustExist?.length">
                        <h4
                            class="text-xs font-semibold text-gray-500 uppercase tracking-wide mb-1.5"
                        >
                            Must already exist in the system
                        </h4>
                        <ul class="list-disc pl-5 space-y-0.5">
                            <li
                                v-for="(item, index) in content.mustExist"
                                :key="'exist-' + index"
                            >
                                {{ item }}
                            </li>
                        </ul>
                    </div>

                    <div class="md:col-span-2">
                        <h4
                            class="text-xs font-semibold text-gray-500 uppercase tracking-wide mb-1.5"
                        >
                            Values
                        </h4>
                        <ul class="list-disc pl-5 space-y-0.5">
                            <li
                                v-for="(item, index) in content.values"
                                :key="'value-' + index"
                            >
                                {{ item }}
                            </li>
                        </ul>
                    </div>
                </div>

                <p class="text-xs text-gray-500">{{ content.footer }}</p>

                <!-- File picker -->
                <div>
                    <input
                        ref="fileInput"
                        type="file"
                        accept=".xlsx,.xls,.csv"
                        class="hidden"
                        @change="onFileChange"
                    />

                    <div
                        v-if="!selectedFile"
                        @click="fileInput?.click()"
                        @dragover.prevent="isDragging = true"
                        @dragleave.prevent="isDragging = false"
                        @drop.prevent="onDrop"
                        :class="[
                            'flex flex-col items-center justify-center gap-2 rounded-lg border-2 border-dashed px-4 py-8 text-center cursor-pointer transition-colors',
                            isDragging
                                ? 'border-[#005740] bg-[#005740]/5'
                                : 'border-gray-300 bg-gray-50 hover:border-[#005740]/60',
                        ]"
                    >
                        <Upload class="w-7 h-7 text-[#005740]" />
                        <p class="text-sm font-medium text-gray-700">
                            Click to choose a file, or drag it here
                        </p>
                        <p class="text-xs text-gray-500">
                            .xlsx, .xls, or .csv · up to 20 MB
                        </p>
                    </div>

                    <div
                        v-else
                        class="flex items-center gap-3 rounded-lg border border-green-200 bg-green-50 px-4 py-3"
                    >
                        <FileSpreadsheet
                            class="w-6 h-6 text-[#005740] shrink-0"
                        />
                        <div class="min-w-0 flex-1">
                            <p
                                class="truncate text-sm font-medium text-gray-800"
                            >
                                {{ selectedFile.name }}
                            </p>
                            <p class="text-xs text-gray-500">{{ fileSize }}</p>
                        </div>
                        <button
                            type="button"
                            @click="clearFile"
                            class="text-gray-500 hover:text-red-600 rounded-full p-1"
                            title="Remove file"
                        >
                            <X class="w-4 h-4" />
                        </button>
                    </div>

                    <p v-if="fileError" class="text-red-500 text-xs mt-1.5">
                        {{ fileError }}
                    </p>
                </div>
            </div>

            <!-- Footer -->
            <div
                class="flex flex-col gap-3 px-6 py-4 border-t border-gray-100 flex-shrink-0 sm:flex-row sm:items-center sm:justify-between"
            >
                <p class="text-xs text-gray-500">
                    Review checks the file and shows the report without saving
                    anything. Import writes it to the database.
                </p>

                <div class="flex flex-wrap items-center justify-end gap-3">
                    <button
                        type="button"
                        @click="closeWithAnimation"
                        class="px-6 py-2.5 rounded-lg text-sm font-medium text-gray-600 hover:bg-gray-100 transition-colors"
                    >
                        Cancel
                    </button>

                    <button
                        type="button"
                        @click="review"
                        :disabled="!selectedFile"
                        class="flex items-center gap-2 rounded-lg border border-[#005740]/30 px-6 py-2.5 text-sm font-semibold text-[#005740] transition-colors hover:bg-[#005740]/5 disabled:cursor-not-allowed disabled:opacity-50 disabled:hover:bg-transparent"
                        title="Show the report without importing"
                    >
                        <Eye class="w-4 h-4" />
                        Review
                    </button>

                    <button
                        type="button"
                        @click="submit"
                        :disabled="!selectedFile"
                        class="bg-gradient-to-r from-[#005740] to-[#00795a] text-white px-8 py-2.5 rounded-lg text-sm font-semibold hover:shadow-md hover:from-[#00432f] hover:to-[#006548] transition-all disabled:opacity-50 disabled:cursor-not-allowed"
                    >
                        {{ content.importLabel }}
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>
