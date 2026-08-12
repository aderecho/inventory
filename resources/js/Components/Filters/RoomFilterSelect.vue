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
    <div class="flex flex-col w-full sm:w-auto relative">
        <label class="text-xs text-[#3B3B3B] font-bold mb-1 sm:mb-0">
            {{ label }}
        </label>

        <div class="relative">
            <input
                v-model="query"
                type="text"
                :placeholder="selectedRoom ? selectedRoom.room_name : placeholder"
                @focus="onFocus"
                @blur="onBlur"
                class="h-8 sm:h-9 w-full sm:w-48 text-xs rounded-md text-gray-600 border focus:ring-[#850038] focus:outline-none focus:border-[#850038] px-2"
            />

            <button
                v-if="selectedRoom"
                type="button"
                @mousedown.prevent="clearSelection"
                class="absolute right-2 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600 text-xs"
            >
                ✕
            </button>
        </div>

        <ul
            v-if="isOpen && filteredRooms.length"
            class="absolute top-full mt-1 z-10 w-full sm:w-48 max-h-48 overflow-y-auto bg-white border rounded-md shadow-sm text-xs"
        >
            <li
                v-for="room in filteredRooms"
                :key="room.id"
                @mousedown.prevent="selectRoom(room)"
                class="px-2 py-1.5 hover:bg-gray-100 cursor-pointer"
            >
                <div class="font-medium text-gray-700">{{ room.room_name }}</div>
                <div class="text-gray-400">{{ room.description }}</div>
            </li>
        </ul>

        <div
            v-else-if="isOpen && query"
            class="absolute top-full mt-1 z-10 w-full sm:w-48 bg-white border rounded-md shadow-sm text-xs px-2 py-1.5 text-gray-400"
        >
            No rooms found
        </div>
    </div>
</template>