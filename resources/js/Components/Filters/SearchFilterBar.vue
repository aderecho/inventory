<script setup>
import { watch, ref, computed } from "vue";
import { useModeFetcher } from "../../Composables/useModeFetcher";
import FilterSelect from "./FilterSelect.vue";
import RoomFilterSelect from "./RoomFilterSelect.vue";

const props = defineProps({
    unitCostOptions: { type: Array, default: () => [] },
    filterCondition: { type: Array, default: () => [] },
    acknowledgementFilter: { type: Array, default: () => [] },
    rooms: { type: Array, default: () => [] },
    search: { type: String, default: "" },
    asset_condition_id: { type: [String, Number], default: "" },
    cost_range: { type: String, default: "" },
    acknowledgement_status: { type: String, default: "" },
    room_id: { type: [String, Number], default: "" },
    mode: { type: String, default: "inventory" },

    // Filters sit behind a "more filters" button instead of filling the row.
    collapsibleFilters: { type: Boolean, default: false },
});

const emit = defineEmits([
    "update:search",
    "update:asset_condition_id",
    "update:cost_range",
    "update:acknowledgement_status",
    "update:room_id",
]);

// Filters and search used to be restored from localStorage. They are not
// persisted any more, so seed the controls from the current URL instead —
// that way a refresh keeps showing the filters that produced the results.
function initialValue(field, propValue) {
    const urlValue = new URLSearchParams(window.location.search).get(field);

    if (urlValue !== null && urlValue !== "") {
        return urlValue;
    }

    return propValue ?? "";
}

const search = ref(initialValue("search", props.search));
const asset_condition_id = ref(
    initialValue("asset_condition_id", props.asset_condition_id),
);
const cost_range = ref(initialValue("cost_range", props.cost_range));
const acknowledgement_status = ref(
    initialValue("acknowledgement_status", props.acknowledgement_status),
);
const room_id = ref(initialValue("room_id", props.room_id));

const refs = {
    search,
    asset_condition_id,
    cost_range,
    acknowledgement_status,
    room_id,
};

const { fetchNow, debouncedFetch, placeholder, activeFields } = useModeFetcher(
    props.mode,
    refs,
);

function watchField(fieldRef, fieldName, emitName) {
    watch(fieldRef, (value) => {
        if (activeFields.includes(fieldName)) {
            debouncedFetch();
        }
        emit(emitName, value);
    });
}

// ------------------------------------------------------------------
// Search runs on Enter, not while typing
// ------------------------------------------------------------------
// The last value that was actually searched, so the field can tell the user
// something is typed but not applied yet.
const appliedSearch = ref(search.value);
const searchDirty = computed(() => search.value !== appliedSearch.value);

watch(search, (value) => {
    emit("update:search", value);
});

function submitSearch() {
    appliedSearch.value = search.value;

    if (activeFields.includes("search")) {
        fetchNow();
    }
}

function clearSearch() {
    search.value = "";
    submitSearch();
}

const searchInputClass = computed(() => {
    const base =
        "h-10 w-64 max-w-full rounded-lg border bg-white pl-10 pr-9 text-sm text-[#3B3B3B] transition-colors focus:outline-none focus:ring-1 sm:w-72 [&::-webkit-search-cancel-button]:hidden [&::-webkit-search-decoration]:hidden";

    return searchDirty.value
        ? base +
              " border-[#005740] ring-1 ring-[#005740]/25 focus:border-[#005740] focus:ring-[#005740]"
        : base +
              " border-gray-300 focus:border-[#005740] focus:ring-[#005740]";
});

watchField(
    asset_condition_id,
    "asset_condition_id",
    "update:asset_condition_id",
);
watchField(cost_range, "cost_range", "update:cost_range");
watchField(
    acknowledgement_status,
    "acknowledgement_status",
    "update:acknowledgement_status",
);
watchField(room_id, "room_id", "update:room_id");

const searchPlaceholder = computed(() => placeholder);
const showRoomFilter = computed(() => activeFields.includes("room_id"));

// ------------------------------------------------------------------
// Filters behind the "more filters" button
// ------------------------------------------------------------------
const showFilters = ref(false);

const activeFilterCount = computed(() => {
    let count = 0;

    if (cost_range.value) count++;
    if (acknowledgement_status.value) count++;
    if (
        asset_condition_id.value !== "" &&
        asset_condition_id.value !== null &&
        asset_condition_id.value !== undefined
    ) {
        count++;
    }

    return count;
});

function clearFilters() {
    cost_range.value = "";
    acknowledgement_status.value = "";
    asset_condition_id.value = "";
    room_id.value = "";
}
</script>

<template>
    <div
        class="flex w-full flex-wrap items-end justify-end gap-3"
    >
        <!-- ROOM SEARCH (always inline, never inside the filters dropdown) -->
        <RoomFilterSelect
            v-if="showRoomFilter"
            v-model="room_id"
            :rooms="rooms"
        />

        <!-- SEARCH (always visible) -->
        <div class="flex flex-col">
            <div class="mb-1 flex items-center justify-between gap-3">
                <label
                    class="text-[11px] font-semibold uppercase tracking-wide text-gray-500"
                >
                    Search
                </label>

                <span
                    v-if="searchDirty"
                    class="text-[10px] font-semibold uppercase tracking-wide text-[#005740]"
                >
                    Press Enter
                </span>
            </div>

            <div class="flex items-center gap-2">
                <div class="relative overflow-hidden">
                    <span
                        class="pointer-events-none absolute inset-y-0 left-3 flex items-center text-gray-400"
                    >
                        <i class="fa-solid fa-magnifying-glass text-sm"></i>
                    </span>

                    <input
                        v-model="search"
                        type="search"
                        :placeholder="searchPlaceholder"
                        :class="searchInputClass"
                        @keydown.enter.prevent="submitSearch"
                    />

                    <button
                        v-if="search"
                        type="button"
                        @click="clearSearch"
                        class="absolute inset-y-0 right-2 my-auto flex h-6 w-6 items-center justify-center rounded-full text-gray-400 transition-colors hover:bg-gray-100 hover:text-gray-600"
                        title="Clear search"
                    >
                        <i class="fa-solid fa-xmark text-xs"></i>
                    </button>
                </div>
            </div>
        </div>

        <!-- FILTERS: inline -->
        <div
            v-if="!collapsibleFilters"
            class="flex flex-wrap items-end gap-2 lg:ml-auto lg:justify-end"
        >
            <FilterSelect
                v-for="(group, gIndex) in acknowledgementFilter"
                :key="'ack-' + gIndex"
                v-model="acknowledgement_status"
                :label="group.label"
                :options="group.options"
                width-class="sm:w-40"
            />

            <FilterSelect
                v-for="(group, gIndex) in unitCostOptions"
                :key="'cost-' + gIndex"
                v-model="cost_range"
                :label="group.label"
                :options="group.options"
            />

            <FilterSelect
                v-for="(stats, gIndex) in filterCondition"
                :key="'status-' + gIndex"
                v-model="asset_condition_id"
                :label="stats.label"
                :options="stats.options"
                include-select-all
            />
        </div>

        <!-- FILTERS: behind the more-filters button -->
        <div v-else class="relative flex flex-col">
            <label
                class="mb-1 text-[11px] font-semibold uppercase tracking-wide text-gray-500"
            >
                Filters
            </label>

            <button
                type="button"
                @click="showFilters = !showFilters"
                class="relative flex h-10 w-10 items-center justify-center rounded-lg border transition-colors"
                :class="
                    showFilters || activeFilterCount
                        ? 'border-[#005740]/40 bg-[#005740]/5 text-[#005740]'
                        : 'border-gray-300 bg-white text-gray-500 hover:border-[#005740]/40 hover:bg-[#005740]/5 hover:text-[#005740]'
                "
                title="More filters"
            >
                <i class="fa-solid fa-ellipsis-vertical"></i>

                <span
                    v-if="activeFilterCount"
                    class="absolute -right-1 -top-1 flex h-4 min-w-[16px] items-center justify-center rounded-full bg-[#005740] px-1 text-[10px] font-bold text-white"
                >
                    {{ activeFilterCount }}
                </span>
            </button>

            <div
                v-if="showFilters"
                class="fixed inset-0 z-30"
                @click="showFilters = false"
            ></div>

            <div
                v-if="showFilters"
                class="absolute right-0 z-40 mt-2 w-72 rounded-xl border border-gray-200 bg-white p-3 shadow-lg"
            >
                <div class="mb-2 flex items-center justify-between">
                    <p
                        class="text-[11px] font-semibold uppercase tracking-wider text-gray-500"
                    >
                        More filters
                    </p>
                    <button
                        v-if="activeFilterCount"
                        type="button"
                        @click="clearFilters"
                        class="text-[11px] font-semibold text-[#005740] hover:underline"
                    >
                        Clear all
                    </button>
                </div>

                <div class="flex flex-col gap-2.5">
                    <FilterSelect
                        v-for="(group, gIndex) in acknowledgementFilter"
                        :key="'ack-' + gIndex"
                        v-model="acknowledgement_status"
                        :label="group.label"
                        :options="group.options"
                        width-class="w-full"
                    />

                    <FilterSelect
                        v-for="(group, gIndex) in unitCostOptions"
                        :key="'cost-' + gIndex"
                        v-model="cost_range"
                        :label="group.label"
                        :options="group.options"
                        width-class="w-full"
                    />

                    <FilterSelect
                        v-for="(stats, gIndex) in filterCondition"
                        :key="'status-' + gIndex"
                        v-model="asset_condition_id"
                        :label="stats.label"
                        :options="stats.options"
                        width-class="w-full"
                        include-select-all
                    />
                </div>
            </div>
        </div>
    </div>
</template>
