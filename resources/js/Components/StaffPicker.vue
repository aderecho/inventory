<script setup>
import { ref, watch } from "vue";
import axios from "axios";
import { Search } from "lucide-vue-next";

/**
 * PUSO staff picker. Searches the staff list that is already synced into the
 * system, so the same names used for accountability are reused here.
 */
const props = defineProps({
    modelValue: { type: String, default: "" },
    placeholder: {
        type: String,
        default: "Search PUSO staff by name, employee number or department...",
    },
});

const emit = defineEmits(["picked"]);

const query = ref(props.modelValue ?? "");
const results = ref([]);
const loading = ref(false);
const open = ref(false);

let timer = null;

watch(
    () => props.modelValue,
    (value) => {
        if ((value ?? "") !== query.value) query.value = value ?? "";
    },
);

watch(query, () => {
    clearTimeout(timer);

    timer = setTimeout(async () => {
        loading.value = true;

        try {
            const { data } = await axios.get(route("borrowings.staff"), {
                params: { search: query.value || undefined },
            });

            results.value = data.data ?? [];
            open.value = true;
        } catch {
            results.value = [];
        } finally {
            loading.value = false;
        }
    }, 250);
});

function pick(row) {
    emit("picked", row);
    open.value = false;
}

function onBlur() {
    setTimeout(() => {
        open.value = false;
    }, 150);
}
</script>

<template>
    <div class="relative">
        <Search
            class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-gray-400"
        />

        <input
            v-model="query"
            type="text"
            :placeholder="placeholder"
            class="w-full rounded-lg border border-gray-300 bg-white py-2.5 pl-9 pr-3 text-sm focus:border-[#005740] focus:outline-none focus:ring-1 focus:ring-[#005740]"
            @focus="open = true"
            @blur="onBlur"
        />

        <div
            v-if="open && (results.length || loading)"
            class="absolute z-30 mt-1 max-h-56 w-full overflow-y-auto rounded-xl border border-gray-200 bg-white shadow-lg"
        >
            <p v-if="loading" class="px-3 py-2 text-xs text-gray-400">
                Searching...
            </p>

            <button
                v-for="row in results"
                :key="row.id"
                type="button"
                @mousedown.prevent="pick(row)"
                class="flex w-full items-center justify-between gap-3 px-3 py-2 text-left transition-colors hover:bg-[#005740]/5"
            >
                <span class="min-w-0">
                    <span class="block truncate text-sm text-gray-800">
                        {{ row.name }}
                    </span>
                    <span class="block truncate text-[11px] text-gray-400">
                        {{ row.department || "No department" }}
                    </span>
                </span>
                <span class="flex-shrink-0 text-[11px] font-medium text-gray-500">
                    {{ row.employee_number || "—" }}
                </span>
            </button>
        </div>
    </div>
</template>

