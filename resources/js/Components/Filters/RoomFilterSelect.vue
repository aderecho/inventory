<script setup>
import { ref, computed } from "vue";

const props = defineProps({
    modelValue: { type: [String, Number], default: "" },
    label: { type: String, default: "Room" },
    rooms: { type: Array, default: () => [] },
    searchKeys: {
        type: Array,
        default: () => ["room_name", "building_name", "description"],
    },
    placeholder: { type: String, default: "Search room..." },
});

const emit = defineEmits(["update:modelValue"]);

const query = ref("");
const isOpen = ref(false);

const selectedRoom = computed(() =>
    props.rooms.find((r) => String(r.id) === String(props.modelValue)),
);

const filteredRooms = computed(() => {
    if (!query.value) return props.rooms;

    const needle = query.value.toLowerCase();

    return props.rooms.filter((room) =>
        props.searchKeys.some((key) =>
            String(room[key] ?? "").toLowerCase().includes(needle),
        ),
    );
});

function selectRoom(room) {
    emit("update:modelValue", room.id);
    query.value = "";
    isOpen.value = false;
}

function clearSelection() {
    emit("update:modelValue", "");
    query.value = "";
    isOpen.value = false;
}

function onFocus() {
    isOpen.value = true;
}

// Slight delay so a click on an option registers before the list closes
function onBlur() {
    setTimeout(() => {
        isOpen.value = false;
    }, 150);
}
</script>

<template>
    <div class="relative flex w-full flex-col sm:w-56">
        <label
            class="mb-1 text-[11px] font-semibold uppercase tracking-wide text-gray-500"
        >
            {{ label }}
        </label>

        <div class="relative">
            <span
                class="pointer-events-none absolute inset-y-0 left-3 flex items-center text-gray-400"
            >
                <i class="fa-solid fa-location-dot text-xs"></i>
            </span>

            <input
                v-model="query"
                type="text"
                :placeholder="selectedRoom ? selectedRoom.room_name : placeholder"
                @focus="onFocus"
                @blur="onBlur"
                class="h-10 w-full rounded-lg border border-gray-300 bg-white pl-8 pr-8 text-xs text-gray-600 transition-colors focus:border-[#005740] focus:outline-none focus:ring-1 focus:ring-[#005740]"
            />

            <button
                v-if="selectedRoom"
                type="button"
                @mousedown.prevent="clearSelection"
                class="absolute right-2 top-1/2 flex h-6 w-6 -translate-y-1/2 items-center justify-center rounded-full text-gray-400 transition-colors hover:bg-gray-100 hover:text-gray-600"
                title="Clear room"
            >
                <i class="fa-solid fa-xmark text-xs"></i>
            </button>
        </div>

        <ul
            v-if="isOpen && filteredRooms.length"
            class="absolute top-full z-20 mt-1 max-h-48 w-full overflow-y-auto rounded-lg border border-gray-200 bg-white text-xs shadow-lg"
        >
            <li
                v-for="room in filteredRooms"
                :key="room.id"
                @mousedown.prevent="selectRoom(room)"
                class="cursor-pointer px-3 py-2 transition-colors hover:bg-[#005740]/5"
            >
                <div class="font-medium text-gray-700">
                    {{ room.room_name }}
                </div>
                <div class="text-gray-400">{{ room.description }}</div>
            </li>
        </ul>

        <div
            v-else-if="isOpen && query"
            class="absolute top-full z-20 mt-1 w-full rounded-lg border border-gray-200 bg-white px-3 py-2 text-xs text-gray-400 shadow-lg"
        >
            No rooms found
        </div>
    </div>
</template>
