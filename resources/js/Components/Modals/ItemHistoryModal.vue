<script setup>
import { computed, onBeforeUnmount, onMounted, ref } from "vue";
import axios from "axios";
import { X, MapPin, RefreshCw } from "lucide-vue-next";

const props = defineProps({
    item: {
        type: Object,
        required: true,
    },
});

const emit = defineEmits(["close"]);

// The row already carries what the list shows, so the header renders straight
// away and only the movement list waits for the request.
const details = ref(null);
const loading = ref(true);
const error = ref("");

const record = computed(() => details.value ?? props.item);
const history = computed(() => record.value?.history_locations ?? []);

const accountablePerson = computed(
    () => record.value?.latest_acknowledgement_item?.accountable_person ?? null,
);

const personName = computed(() => {
    const person = accountablePerson.value;

    if (!person) return null;

    return (
        `${person.first_name ?? ""} ${person.last_name ?? ""}`.trim() ||
        person.full_name ||
        null
    );
});

const currentRoom = computed(() => {
    const room = record.value?.room_name;

    return room && room !== "N/A" ? room : null;
});

const recordCount = computed(() => history.value.length);

function currency(value) {
    if (value === null || value === undefined || value === "") return "—";

    return `₱${Number(value).toLocaleString("en-PH", {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2,
    })}`;
}

function formatDate(value) {
    if (!value) return "—";

    return new Date(value).toLocaleString("en-PH", {
        year: "numeric",
        month: "short",
        day: "numeric",
        hour: "numeric",
        minute: "2-digit",
    });
}

async function load() {
    loading.value = true;
    error.value = "";

    try {
        const { data } = await axios.get(
            route("item-histories.show", props.item.id),
        );

        if (!data?.item) {
            throw new Error("Unexpected response");
        }

        details.value = data.item;
    } catch (requestError) {
        error.value =
            requestError?.response?.status === 403
                ? "You do not have permission to view this item's location history."
                : "The location history could not be loaded. Please try again.";
    } finally {
        loading.value = false;
    }
}

function onKeydown(event) {
    if (event.key === "Escape") emit("close");
}

let previousOverflow = "";

onMounted(() => {
    load();

    document.addEventListener("keydown", onKeydown);

    previousOverflow = document.body.style.overflow;
    document.body.style.overflow = "hidden";
});

onBeforeUnmount(() => {
    document.removeEventListener("keydown", onKeydown);
    document.body.style.overflow = previousOverflow;
});
</script>

<template>
    <div
        class="fixed inset-0 modal-backdrop flex items-center justify-center p-4 z-50"
        @click.self="$emit('close')"
    >
        <div
            class="flex max-h-[90vh] w-full max-w-3xl flex-col overflow-hidden rounded-2xl bg-white shadow-2xl"
        >
            <!-- HEADER -->
            <div
                class="flex items-start justify-between gap-4 bg-gradient-to-r from-[#003d2c] via-[#005740] to-[#00795a] px-6 py-4"
            >
                <div class="min-w-0">
                    <p
                        class="text-[11px] font-semibold uppercase tracking-wide text-white/60"
                    >
                        Location History
                    </p>

                    <h2 class="truncate text-lg font-bold text-white">
                        {{ record.item_name }}
                    </h2>

                    <p class="mt-0.5 font-mono text-xs text-white/70">
                        {{ record.property_number ?? "—" }}
                    </p>
                </div>

                <button
                    type="button"
                    class="flex h-9 w-9 flex-shrink-0 items-center justify-center rounded-full text-white/80 transition-colors hover:bg-white/10 hover:text-white"
                    title="Close"
                    @click="$emit('close')"
                >
                    <X class="h-5 w-5" />
                </button>
            </div>

            <!-- SUMMARY -->
            <div
                class="grid grid-cols-2 gap-x-6 gap-y-3 border-b border-gray-200 bg-gray-50 px-6 py-4 sm:grid-cols-4"
            >
                <div>
                    <p
                        class="text-[11px] font-semibold uppercase tracking-wide text-gray-500"
                    >
                        Classification
                    </p>
                    <p class="mt-0.5 text-xs text-gray-700">
                        {{
                            record.item_classification?.classification_name ??
                            "—"
                        }}
                    </p>
                </div>

                <div>
                    <p
                        class="text-[11px] font-semibold uppercase tracking-wide text-gray-500"
                    >
                        Unit Cost
                    </p>
                    <p class="mt-0.5 text-xs text-gray-700">
                        {{ currency(record.unit_cost) }}
                    </p>
                </div>

                <div>
                    <p
                        class="text-[11px] font-semibold uppercase tracking-wide text-gray-500"
                    >
                        Accountable Person
                    </p>
                    <p
                        class="mt-0.5 truncate text-xs"
                        :class="
                            personName
                                ? 'text-gray-700'
                                : 'font-bold text-[#D32F2F]'
                        "
                        :title="personName ?? 'Unassigned'"
                    >
                        {{ personName ?? "Unassigned" }}
                    </p>
                </div>

                <div>
                    <p
                        class="text-[11px] font-semibold uppercase tracking-wide text-gray-500"
                    >
                        Current Location
                    </p>
                    <p
                        v-if="currentRoom"
                        class="mt-0.5 flex items-center gap-1.5 text-xs text-gray-700"
                    >
                        <span
                            class="inline-block h-1.5 w-1.5 flex-shrink-0 rounded-full bg-[#0E6021]"
                        ></span>
                        {{ currentRoom }}
                    </p>
                    <p v-else class="mt-0.5 text-xs italic text-gray-400">
                        No location set
                    </p>
                </div>
            </div>

            <!-- BODY -->
            <div class="flex-1 overflow-y-auto px-6 py-5">
                <div class="mb-4 flex items-center justify-between">
                    <h3 class="text-sm font-semibold text-gray-700">
                        Movement history
                    </h3>

                    <span class="text-xs text-gray-400">
                        {{ recordCount }}
                        {{ recordCount === 1 ? "record" : "records" }}
                    </span>
                </div>

                <!-- LOADING -->
                <div
                    v-if="loading"
                    class="flex flex-col items-center gap-3 py-14 text-gray-400"
                >
                    <i
                        class="fa-solid fa-circle-notch fa-spin text-2xl text-[#005740]/60"
                    ></i>
                    <p class="text-sm">Loading location history...</p>
                </div>

                <!-- ERROR -->
                <div
                    v-else-if="error"
                    class="flex flex-col items-center gap-3 rounded-xl border border-dashed border-gray-200 px-5 py-12 text-center"
                >
                    <i
                        class="fa-solid fa-triangle-exclamation text-2xl text-amber-400"
                    ></i>
                    <p class="text-sm text-gray-500">{{ error }}</p>
                    <button
                        type="button"
                        class="flex items-center gap-2 rounded-full bg-[#005740] px-4 py-2 text-xs font-semibold text-white transition-colors hover:bg-[#004530]"
                        @click="load"
                    >
                        <RefreshCw class="h-3.5 w-3.5" />
                        Try again
                    </button>
                </div>

                <!-- EMPTY -->
                <div
                    v-else-if="recordCount === 0"
                    class="flex flex-col items-center gap-3 rounded-xl border border-dashed border-gray-200 px-5 py-14 text-center"
                >
                    <i class="fa-solid fa-box-archive text-2xl text-gray-300"></i>
                    <p class="text-sm text-gray-400">
                        No location history recorded for this item.
                    </p>
                </div>

                <!-- TIMELINE -->
                <div v-else class="relative">
                    <div
                        class="absolute bottom-3 left-[15px] top-3 w-px bg-gray-200"
                    ></div>

                    <ul class="space-y-0">
                        <li
                            v-for="(entry, index) in history"
                            :key="entry.id"
                            class="relative flex gap-4 pb-5 last:pb-0"
                        >
                            <div class="relative z-10 flex-shrink-0">
                                <div
                                    class="flex h-8 w-8 items-center justify-center rounded-full border-2"
                                    :class="
                                        index === 0
                                            ? 'border-[#0E6021] bg-[#0E6021]'
                                            : 'border-gray-300 bg-white'
                                    "
                                >
                                    <MapPin
                                        class="h-3.5 w-3.5"
                                        :class="
                                            index === 0
                                                ? 'text-white'
                                                : 'text-gray-400'
                                        "
                                    />
                                </div>
                            </div>

                            <div
                                class="flex-1 rounded-xl border border-gray-200 bg-white px-4 py-3 shadow-sm"
                            >
                                <div
                                    class="flex flex-col gap-1 sm:flex-row sm:items-center sm:justify-between"
                                >
                                    <div class="min-w-0">
                                        <div
                                            class="flex flex-wrap items-center gap-2"
                                        >
                                            <span
                                                class="truncate text-sm font-medium text-gray-900"
                                            >
                                                {{
                                                    entry.room_name ??
                                                    "Unknown room"
                                                }}
                                            </span>

                                            <span
                                                v-if="index === 0"
                                                class="inline-flex items-center rounded-full bg-green-100 px-2 py-0.5 text-[11px] font-medium text-green-700"
                                            >
                                                Current
                                            </span>
                                        </div>

                                        <p
                                            v-if="
                                                entry.building_name &&
                                                entry.building_name !== 'N/A'
                                            "
                                            class="mt-0.5 text-xs text-gray-400"
                                        >
                                            {{ entry.building_name }}
                                            <span
                                                v-if="
                                                    entry.description &&
                                                    entry.description !== 'N/A'
                                                "
                                            >
                                                · {{ entry.description }}
                                            </span>
                                        </p>
                                    </div>

                                    <p
                                        class="flex-shrink-0 text-xs text-gray-400"
                                    >
                                        {{ formatDate(entry.created_at) }}
                                    </p>
                                </div>
                            </div>
                        </li>
                    </ul>
                </div>
            </div>

            <!-- FOOTER -->
            <div
                class="flex flex-shrink-0 items-center justify-end gap-2 border-t border-gray-200 bg-gray-50 px-6 py-3"
            >
                <button
                    type="button"
                    class="rounded-lg border border-gray-300 bg-white px-4 py-2 text-xs font-semibold text-gray-600 transition-colors hover:bg-gray-100"
                    @click="$emit('close')"
                >
                    Close
                </button>
            </div>
        </div>
    </div>
</template>
