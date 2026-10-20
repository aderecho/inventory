<script setup>
import { computed, ref } from "vue";
import {
    Copy,
    Check,
    CircleAlert,
    Eye,
    TriangleAlert,
    Upload,
} from "lucide-vue-next";

const props = defineProps({
    // Either the import report from the server or { fatal: "message" }.
    result: { type: Object, required: true },
    // "item" for inventory, "supplier" for the Suppliers module.
    entityLabel: { type: String, default: "item" },
    // The inventory report counts receipts; other modules do not.
    showReceipts: { type: Boolean, default: true },
    // Shows the "Import now" button on a review report.
    canImport: { type: Boolean, default: false },
});

const plural = (count) => (count === 1 ? "" : "s");

const emit = defineEmits(["close", "import"]);

const isClosing = ref(false);
const copied = ref(false);
const reportBox = ref(null);

const isFatal = computed(() => Boolean(props.result.fatal));
// A review report comes back with dry_run: true - it describes what would
// happen, and nothing has been written yet.
const isPreview = computed(() => Boolean(props.result.dry_run));
const failed = computed(() => props.result.failed ?? []);
const warnings = computed(() => props.result.warnings ?? []);
const skipped = computed(() => props.result.skipped_rows ?? []);
const notices = computed(() => props.result.notices ?? []);

// [778, 855, 909, 910, 911] -> "778, 855, 909–911"
function compactRows(rows) {
    const sorted = [...rows].sort((a, b) => a - b);
    const parts = [];

    for (let i = 0; i < sorted.length; i++) {
        const start = sorted[i];
        while (i + 1 < sorted.length && sorted[i + 1] === sorted[i] + 1) i++;
        parts.push(start === sorted[i] ? String(start) : start + "–" + sorted[i]);
    }

    return parts.join(", ");
}

function rowHeading(entry) {
    const reference = entry.property_number ?? entry.reference;

    return reference
        ? "Row " + entry.row + " (" + reference + ")"
        : "Row " + entry.row;
}

const reportText = computed(() => {
    if (isFatal.value) {
        return "Import failed: " + props.result.fatal;
    }

    const r = props.result;
    const lines = [
        (isPreview.value ? "Import review: " : "Inventory import: ") +
            r.file + " (sheet \"" + r.sheet + "\"), rows " + r.start_row + "–" + r.end_row,
        (isPreview.value ? "Would import: " : "Imported: ") +
            r.imported_rows + " row(s), " + r.imported_items + " item(s), " + r.receipts_created + " new receipt(s), " + r.disposal_items + " Disposal item(s)",
        "Not imported: " + failed.value.length + " row(s)",
        (isPreview.value ? "Would import with warnings: " : "Imported with warnings: ") +
            warnings.value.length + " row(s)",
    ];

    if (skipped.value.length) {
        lines.push("Skipped, no item data: rows " + compactRows(skipped.value));
    }
    notices.value.forEach((notice) => lines.push("Note: " + notice));

    if (failed.value.length) {
        lines.push("", "NOT IMPORTED");
        failed.value.forEach((entry) =>
            lines.push(rowHeading(entry) + ": " + entry.messages.join(" ")),
        );
    }

    if (warnings.value.length) {
        lines.push("", "IMPORTED WITH WARNINGS");
        warnings.value.forEach((entry) =>
            lines.push(rowHeading(entry) + ": " + entry.messages.join(" ")),
        );
    }

    return lines.join("\n");
});

async function copyReport() {
    try {
        await navigator.clipboard.writeText(reportText.value);
    } catch {
        // Clipboard API needs a secure context; fall back to selecting the text.
        reportBox.value?.select();
        document.execCommand("copy");
    }

    copied.value = true;
    setTimeout(() => (copied.value = false), 2000);
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
                'bg-white rounded-2xl w-full max-w-4xl shadow-2xl overflow-hidden flex flex-col max-h-[92vh]',
                isClosing ? 'animate-pop-out' : 'animate-pop-in',
            ]"
        >
            <!-- Header -->
            <div
                class="bg-gradient-to-r from-[#003d2c] via-[#005740] to-[#00795a] px-6 py-5 flex items-center justify-between flex-shrink-0"
            >
                <div>
                    <h3 class="text-lg font-bold text-white">
                        {{ isPreview ? "Import Review" : "Import Results" }}
                    </h3>
                    <p v-if="!isFatal" class="text-xs text-white/70 mt-0.5">
                        {{ result.file }} · sheet "{{ result.sheet }}" · rows
                        {{ result.start_row }}–{{ result.end_row }}
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
                <!-- Whole import failed -->
                <div
                    v-if="isFatal"
                    class="flex gap-3 rounded-lg border border-red-200 bg-red-50 p-4 text-sm text-red-700"
                >
                    <CircleAlert class="w-5 h-5 shrink-0" />
                    <p>{{ result.fatal }}</p>
                </div>

                <template v-else>
                    <!-- Review banner: nothing has been saved yet -->
                    <div
                        v-if="isPreview"
                        class="flex gap-3 rounded-lg border border-[#005740]/25 bg-[#005740]/[0.06] p-4 text-sm text-[#005740]"
                    >
                        <Eye class="w-5 h-5 shrink-0" />
                        <p>
                            <span class="font-semibold">Review only.</span>
                            Nothing has been saved. Check the rows below, then
                            press <span class="font-semibold">Import now</span>
                            to write the file to the database.
                        </p>
                    </div>

                    <!-- Summary -->
                    <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
                        <div class="rounded-lg border border-green-200 bg-green-50 p-3">
                            <p class="text-xs font-semibold uppercase tracking-wide text-green-700">
                                {{ isPreview ? "Will import" : "Imported" }}
                            </p>
                            <p class="text-2xl font-bold text-green-800">
                                {{ result.imported_rows }}
                            </p>
                            <p class="text-xs text-green-700">
                                rows · {{ result.imported_items }}
                                {{ entityLabel }}{{ plural(result.imported_items) }}
                            </p>
                        </div>
                        <div class="rounded-lg border border-red-200 bg-red-50 p-3">
                            <p class="text-xs font-semibold uppercase tracking-wide text-red-700">
                                Not imported
                            </p>
                            <p class="text-2xl font-bold text-red-800">
                                {{ failed.length }}
                            </p>
                            <p class="text-xs text-red-700">rows</p>
                        </div>
                        <div class="rounded-lg border border-amber-200 bg-amber-50 p-3">
                            <p class="text-xs font-semibold uppercase tracking-wide text-amber-700">
                                Warnings
                            </p>
                            <p class="text-2xl font-bold text-amber-800">
                                {{ warnings.length }}
                            </p>
                            <p class="text-xs text-amber-700">
                                {{
                                    isPreview
                                        ? "rows with warnings"
                                        : "rows imported"
                                }}
                            </p>
                        </div>
                        <div
                            v-if="showReceipts"
                            class="rounded-lg border border-gray-200 bg-gray-50 p-3"
                        >
                            <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">
                                Receipts
                            </p>
                            <p class="text-2xl font-bold text-gray-800">
                                {{ result.receipts_created }}
                            </p>
                            <p class="text-xs text-gray-500">
                                new · {{ result.disposal_items }} Disposal items
                            </p>
                        </div>
                    </div>

                    <p v-if="skipped.length" class="text-xs text-gray-500">
                        Skipped rows with no item data (section labels or empty
                        totals): {{ compactRows(skipped) }}
                    </p>
                    <p
                        v-for="notice in notices"
                        :key="notice"
                        class="text-xs text-amber-700"
                    >
                        {{ notice }}
                    </p>

                    <!-- Not imported -->
                    <section v-if="failed.length">
                        <h4 class="text-xs font-semibold text-gray-500 uppercase tracking-wide mb-2">
                            Not imported
                        </h4>
                        <ul class="space-y-2 max-h-72 overflow-y-auto pr-1">
                            <li
                                v-for="entry in failed"
                                :key="'f' + entry.row"
                                class="rounded-lg border border-red-200 bg-white px-4 py-2.5"
                            >
                                <p class="text-sm font-semibold text-red-700">
                                    {{ rowHeading(entry) }}
                                </p>
                                <ul class="mt-1 list-disc pl-5 text-sm text-gray-700">
                                    <li v-for="(message, i) in entry.messages" :key="i">
                                        {{ message }}
                                    </li>
                                </ul>
                            </li>
                        </ul>
                    </section>

                    <!-- Imported with warnings -->
                    <section v-if="warnings.length">
                        <h4 class="text-xs font-semibold text-gray-500 uppercase tracking-wide mb-2">
                            Imported with warnings
                        </h4>
                        <ul class="space-y-2 max-h-56 overflow-y-auto pr-1">
                            <li
                                v-for="entry in warnings"
                                :key="'w' + entry.row"
                                class="rounded-lg border border-amber-200 bg-white px-4 py-2.5"
                            >
                                <p class="flex items-center gap-1.5 text-sm font-semibold text-amber-700">
                                    <TriangleAlert class="w-4 h-4" />
                                    {{ rowHeading(entry) }}
                                </p>
                                <ul class="mt-1 list-disc pl-5 text-sm text-gray-700">
                                    <li v-for="(message, i) in entry.messages" :key="i">
                                        {{ message }}
                                    </li>
                                </ul>
                            </li>
                        </ul>
                    </section>
                </template>

                <!-- Copy-ready report -->
                <section>
                    <h4 class="text-xs font-semibold text-gray-500 uppercase tracking-wide mb-2">
                        Report
                    </h4>
                    <textarea
                        ref="reportBox"
                        readonly
                        :value="reportText"
                        class="w-full h-40 rounded-md border border-gray-300 bg-[#F8F8F8] px-3 py-2 font-mono text-xs text-[#3B3B3B] focus:ring-1 focus:ring-[#005740] focus:border-[#005740] focus:outline-none"
                        @focus="$event.target.select()"
                    ></textarea>
                </section>
            </div>

            <!-- Footer -->
            <div
                class="flex justify-end gap-3 px-6 py-4 border-t border-gray-100 flex-shrink-0"
            >
                <button
                    type="button"
                    @click="copyReport"
                    class="flex items-center gap-2 px-5 py-2.5 rounded-lg text-sm font-medium text-[#005740] border border-[#005740]/30 hover:bg-[#005740]/5 transition-colors"
                >
                    <Check v-if="copied" class="w-4 h-4" />
                    <Copy v-else class="w-4 h-4" />
                    {{ copied ? "Copied" : "Copy report" }}
                </button>
                <button
                    v-if="isPreview && canImport"
                    type="button"
                    @click="emit('import')"
                    class="flex items-center gap-2 bg-gradient-to-r from-[#005740] to-[#00795a] text-white px-6 py-2.5 rounded-lg text-sm font-semibold hover:shadow-md hover:from-[#00432f] hover:to-[#006548] transition-all"
                >
                    <Upload class="w-4 h-4" />
                    Import now
                </button>
                <button
                    type="button"
                    @click="closeWithAnimation"
                    class="bg-gradient-to-r from-[#005740] to-[#00795a] text-white px-8 py-2.5 rounded-lg text-sm font-semibold hover:shadow-md hover:from-[#00432f] hover:to-[#006548] transition-all"
                >
                    Close
                </button>
            </div>
        </div>
    </div>
</template>
