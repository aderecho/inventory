<script setup>
import { watch, ref, computed } from "vue";
import { useModeFetcher } from "../../Composables/useModeFetcher";
import { useFilterPersistence } from "../../Composables/useFilterPersistence";
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
});

const emit = defineEmits([
    "update:search",
    "update:asset_condition_id",
    "update:cost_range",
    "update:acknowledgement_status",
    "update:room_id",
]);

const search = ref(props.search || "");
const asset_condition_id = ref(props.asset_condition_id || "");
const cost_range = ref(props.cost_range || "");
const acknowledgement_status = ref(props.acknowledgement_status || "");
const room_id = ref(props.room_id || "");

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

const { isRestoring } = useFilterPersistence(props.mode, refs, () => {
    fetchNow();
});

function watchField(fieldRef, fieldName, emitName) {
    watch(fieldRef, (value) => {
        if (!isRestoring() && activeFields.includes(fieldName)) {
            debouncedFetch();
        }
        emit(emitName, value);
    });
}

watchField(search, "search", "update:search");
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
</script>

<template>
    <div
        class="flex flex-col sm:flex-row items-start sm:items-center justify-end gap-4 w-full sm:w-auto"
    >
        <RoomFilterSelect
            v-if="showRoomFilter"
            v-model="room_id"
            :rooms="rooms"
        />

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

    <!-- SEARCH BAR -->
    <div class="w-full sm:w-auto mt-3 relative">
        <span class="absolute inset-y-0 left-3 flex items-center text-gray-400">
            <i class="fa-solid fa-magnifying-glass"></i>
        </span>
        <input
            v-model="search"
            type="search"
            :placeholder="searchPlaceholder"
            class="w-full sm:w-64 md:w-96 h-9 sm:h-10 text-[#3B3B3B] rounded-full pl-10 pr-3 border text-sm focus:ring-[#850038] focus:outline-none focus:border-[#850038]"
        />
    </div>
</template>
