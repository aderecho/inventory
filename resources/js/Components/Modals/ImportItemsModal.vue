<script setup>
import { computed, ref } from "vue";
import axios from "axios";
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

    // When set, the modal lists the workbook's sheets before importing.
    sheetEndpoint: { type: String, default: null },

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
                "PAR/ICS Number, PAR/ICS Date, New Property No., Property No., Qty, Unit, Unit Cost, Total Amount, Description, Date Acquired, Accountable Person, Fund Source, PR#, PO#, Location, Supplier, Invoice, Remarks, Issued By",
            requiredNote:
                "Only New Property No. (or Property No.) and Qty are required. Leave any other cell blank to import it as empty. Accountable Person and PAR/ICS Number must both be filled to create an assignment.",
            mustExist: [
                "accountable person, location, supplier, and issued by are numeric IDs, not names",
                "supplier is the id from the Suppliers page",
                "location is the room id supplied by the rooms API",
                "accountable person and issued by are user profile ids",
                "Classification is optional; it is used when the PAR/ICS or property-number prefix matches an existing classification",
            ],
            values: [
                "PAR/ICS Date and Date Acquired use MM-DD-YYYY, for example 06-16-2025. Excel date cells are accepted in any display format.",
                "Property No. is limited to 19 characters including hyphens, for example 2016-50-30-0031-161.",
                "For several units, write the property number range like 223-918-(001-003).",
                "accountable person, location, supplier, and issued by expect numeric IDs from the database.",
                "The IDs in the sample are examples and may not exist in your database; replace them before importing.",
                "Approval status, private flag, lifespan, sales invoice date, PO date, and receipt remarks are not imported.",
                "remarks are saved on the item.",
            ],
            footer:
                "Rows with problems are skipped. After importing, you will see each skipped row's Excel row number and the reason, so you can fix and import them again.",
            importLabel: "Import",
        }),
    },
});

const emit = defineEmits(["close", "import", "review", "download-sample"]);

const MAX_MB = 20;
const MAX_BYTES = MAX_MB * 1024 * 1024;
const ACCEPTED = [".xlsx", ".xls", ".csv"];

const fileInput = ref(null);
const selectedFile = ref(null);
const fileError = ref("");
const isDragging = ref(false);
const isClosing = ref(false);
const sheets = ref([]);
const selectedSheet = ref(null);
const loadingSheets = ref(false);
const sheetError = ref("");
const canSubmit = computed(
    () => Boolean(selectedFile.value) && !fileError.value && !loadingSheets.value,
);

const fileSize = computed(() => {
    const bytes = selectedFile.value?.size ?? 0;
    return bytes >= 1024 * 1024
        ? (bytes / (1024 * 1024)).toFixed(1) + " MB"
        : Math.max(1, Math.round(bytes / 1024)) + " KB";
});

async function pickFile(file) {
    fileError.value = "";
    sheets.value = [];
    selectedSheet.value = null;
    sheetError.value = "";

    if (!file) return;

    const name = file.name.toLowerCase();
    if (!ACCEPTED.some((ext) => name.endsWith(ext))) {
        selectedFile.value = null;
        fileError.value = "Please choose an .xlsx, .xls, or .csv file.";
        return;
    }
    if (file.size > MAX_BYTES) {
        selectedFile.value = null;
        fileError.value = `The file is larger than ${MAX_MB} MB.`;
        return;
    }

    selectedFile.value = file;
    await loadSheets(file);
}

async function loadSheets(file) {
    if (!props.sheetEndpoint || !/\.(xlsx|xls)$/i.test(file.name)) return;

    loadingSheets.value = true;

    try {
        const formData = new FormData();
        formData.append("file", file);

        const { data } = await axios.post(props.sheetEndpoint, formData);
        const list = Array.isArray(data.sheets) ? data.sheets : [];
        sheets.value = list;
        selectedSheet.value =
            list.find((sheet) => sheet.active)?.name ?? list[0]?.name ?? null;
    } catch (error) {
        sheetError.value =
            error.response?.data?.message ??
            "Could not read the sheet names from this file.";
    } finally {
        loadingSheets.value = false;
    }
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
    sheets.value = [];
    selectedSheet.value = null;
    sheetError.value = "";
}

function submit() {
    if (selectedFile.value) {
        emit("import", selectedFile.value, selectedSheet.value);
    }
}

// Review parses the file and reports what would happen, without saving.
function review() {
    if (selectedFile.value) {
        emit("review", selectedFile.value, selectedSheet.value);
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

                <div v-if="loadingSheets" class="text-xs text-gray-500">
                    Reading worksheet names…
                </div>

                <div
                    v-else-if="sheets.length > 1"
                    class="rounded-lg border border-gray-200 bg-gray-50 p-3"
                >
                    <p
                        class="mb-2 text-[11px] font-semibold uppercase tracking-wide text-gray-500"
                    >
                        Choose worksheet
                    </p>

                    <div class="flex flex-wrap gap-1.5">
                        <button
                            v-for="(sheet, index) in sheets"
                            :key="sheet.name"
                            type="button"
                            @click="selectedSheet = sheet.name"
                            :title="sheet.name"
                            :class="[
                                'flex items-center gap-1.5 rounded-md border px-2 py-1 text-[11px] transition-colors',
                                selectedSheet === sheet.name
                                    ? 'border-[#005740] bg-[#005740] text-white'
                                    : 'border-gray-300 bg-white text-gray-600 hover:border-[#005740]/40',
                            ]"
                        >
                            <span class="font-bold">{{ index + 1 }}</span>
                            <span class="max-w-[7rem] truncate">
                                {{ sheet.name }}
                            </span>
                        </button>
                    </div>

                    <select
                        v-model="selectedSheet"
                        class="mt-2 w-full rounded-md border border-gray-300 bg-white px-2 py-1.5 text-xs text-gray-700 focus:border-[#005740] focus:outline-none"
                    >
                        <option
                            v-for="(sheet, index) in sheets"
                            :key="'sheet-' + sheet.name"
                            :value="sheet.name"
                        >
                            {{ index + 1 }} — {{ sheet.name }}
                            {{ sheet.active ? " (active)" : "" }}
                        </option>
                    </select>
                </div>

                <p v-if="sheetError" class="text-xs text-red-500">
                    {{ sheetError }}
                </p>

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
                            .xlsx, .xls, or .csv · maximum {{ MAX_MB }} MB
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
                            <p class="text-xs text-gray-500">
                                {{ fileSize }} · maximum {{ MAX_MB }} MB
                            </p>
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
                    Maximum file size: {{ MAX_MB }} MB. Review checks the file
                    and shows the report without saving anything. Import writes
                    it to the database.
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
                        :disabled="!canSubmit"
                        class="flex items-center gap-2 rounded-lg border border-[#005740]/30 px-6 py-2.5 text-sm font-semibold text-[#005740] transition-colors hover:bg-[#005740]/5 disabled:cursor-not-allowed disabled:opacity-50 disabled:hover:bg-transparent"
                        title="Show the report without importing"
                    >
                        <Eye class="w-4 h-4" />
                        Review
                    </button>

                    <button
                        type="button"
                        @click="submit"
                        :disabled="!canSubmit"
                        class="bg-gradient-to-r from-[#005740] to-[#00795a] text-white px-8 py-2.5 rounded-lg text-sm font-semibold hover:shadow-md hover:from-[#00432f] hover:to-[#006548] transition-all disabled:opacity-50 disabled:cursor-not-allowed"
                    >
                        {{ content.importLabel }}
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>
