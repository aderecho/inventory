<script setup>
import { computed, ref, watch } from "vue";
import {
    Check,
    Download,
    FileSpreadsheet,
    FileText,
    X,
} from "lucide-vue-next";

const props = defineProps({
    show: { type: Boolean, default: false },
    title: { type: String, default: "Export" },
    subtitle: {
        type: String,
        default: "Choose the file format you want to download.",
    },
    // Which option is highlighted when the modal opens.
    defaultFormat: { type: String, default: "xlsx" },

    // Optional copy overrides, so other modules can reword the two options.
    xlsxHint: { type: String, default: "" },
    csvHint: { type: String, default: "" },
});

const emit = defineEmits(["close", "confirm"]);

const format = ref(props.defaultFormat);

watch(
    () => props.show,
    (open) => {
        if (open) format.value = props.defaultFormat;
    },
);

// Defaults are deliberately module agnostic; a page can override either hint.
const options = computed(() => [
    {
        value: "xlsx",
        label: "Excel Workbook",
        extension: ".xlsx",
        description:
            props.xlsxHint ||
            "A formatted workbook with the column widths and the green header row. Best if you will open or edit the file in Excel.",
        icon: FileSpreadsheet,
    },
    {
        value: "csv",
        label: "CSV File",
        extension: ".csv",
        description:
            props.csvHint ||
            "Plain text, one row per record. Opens in any spreadsheet app and is the easiest to feed into another system.",
        icon: FileText,
    },
]);

const selected = computed(
    () =>
        options.value.find((option) => option.value === format.value) ??
        options.value[0],
);

const confirm = () => emit("confirm", format.value);
</script>

<template>
    <div
        v-if="show"
        class="fixed inset-0 modal-backdrop flex items-center justify-center p-4 z-50"
    >
        <div
            class="w-full max-w-xl overflow-hidden rounded-2xl bg-white shadow-2xl"
        >
            <!-- HEADER -->
            <div
                class="flex items-center justify-between bg-gradient-to-r from-[#003d2c] via-[#005740] to-[#00795a] px-6 py-5"
            >
                <div>
                    <h2 class="text-lg font-bold text-white">{{ title }}</h2>

                    <p class="mt-0.5 text-xs text-white/75">{{ subtitle }}</p>
                </div>

                <button
                    type="button"
                    @click="emit('close')"
                    class="flex h-9 w-9 items-center justify-center rounded-full text-white/80 transition-colors hover:bg-white/10 hover:text-white"
                    title="Close"
                >
                    <X class="w-5 h-5" />
                </button>
            </div>

            <!-- FORMAT OPTIONS -->
            <div class="space-y-3 bg-gray-50 px-6 py-6">
                <button
                    v-for="option in options"
                    :key="option.value"
                    type="button"
                    @click="format = option.value"
                    class="flex w-full items-start gap-3 rounded-xl border bg-white px-4 py-3 text-left transition-colors"
                    :class="
                        format === option.value
                            ? 'border-[#005740] ring-1 ring-[#005740]/30'
                            : 'border-gray-200 hover:border-[#005740]/40 hover:bg-[#005740]/[0.03]'
                    "
                >
                    <span
                        class="flex h-9 w-9 flex-shrink-0 items-center justify-center rounded-full"
                        :class="
                            format === option.value
                                ? 'bg-[#005740] text-white'
                                : 'bg-gray-100 text-gray-500'
                        "
                    >
                        <component :is="option.icon" class="w-4 h-4" />
                    </span>

                    <span class="flex-1">
                        <span class="flex flex-wrap items-center gap-2">
                            <span class="text-sm font-semibold text-gray-800">
                                {{ option.label }}
                            </span>
                            <span
                                class="rounded-md bg-gray-100 px-1.5 py-0.5 text-[11px] font-semibold text-gray-500"
                            >
                                {{ option.extension }}
                            </span>
                        </span>

                        <span
                            class="mt-1 block text-xs leading-relaxed text-gray-500"
                        >
                            {{ option.description }}
                        </span>
                    </span>

                    <span
                        v-if="format === option.value"
                        class="flex h-5 w-5 flex-shrink-0 items-center justify-center rounded-full bg-[#005740] text-white"
                    >
                        <Check class="w-3 h-3" />
                    </span>
                </button>
            </div>

            <!-- FOOTER -->
            <div
                class="flex items-center justify-end gap-3 border-t border-gray-100 bg-white px-6 py-4"
            >
                <button
                    type="button"
                    @click="emit('close')"
                    class="rounded-lg px-5 py-2.5 text-sm font-medium text-gray-600 transition-colors hover:bg-gray-100"
                >
                    Cancel
                </button>

                <button
                    type="button"
                    @click="confirm"
                    class="flex items-center gap-2 rounded-lg bg-[#005740] px-6 py-2.5 text-sm font-semibold text-white transition-colors hover:bg-[#00452f]"
                >
                    <Download class="w-4 h-4" />
                    Download {{ selected.extension.slice(1).toUpperCase() }}
                </button>
            </div>
        </div>
    </div>
</template>
