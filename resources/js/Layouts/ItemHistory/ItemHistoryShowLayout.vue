<script setup>
import { ref } from "vue";
import { router } from "@inertiajs/vue3";
import NavHeader from "@/Components/NavHeader.vue";
import SideBar from "@/Components/SideBar.vue";
import PageHeader from "@/Components/PageHeader.vue";
import LoadingOverlay from "@/Components/LoadingOverlay.vue";
import { useLoading } from "@/Composables/useLoading";

const props = defineProps({
    item: Object,
});

const { isLoading, loadingTitle, loadingMessage, startLoading, stopLoading } =
    useLoading();

const isSidebarOpen = ref(true);
const toggleSidebar = () => {
    isSidebarOpen.value = !isSidebarOpen.value;
};

function goBack() {
    startLoading("Going back", "Loading item list...");
    router.visit(route("item-histories.index"), {
        onFinish: () => stopLoading(),
    });
}

function formatDate(dateStr) {
    if (!dateStr) return "—";
    return new Date(dateStr).toLocaleDateString("en-PH", {
        year: "numeric",
        month: "short",
        day: "numeric",
        hour: "2-digit",
        minute: "2-digit",
    });
}
</script>

<template>
    <LoadingOverlay
        :show="isLoading"
        :title="loadingTitle"
        :message="loadingMessage"
    />

    <div class="h-screen flex flex-col bg-gray-100">
        <div class="flex flex-1 overflow-hidden">
            <aside
                class="h-full transition-all duration-300 ease-in-out flex-shrink-0"
            >
                <SideBar
                    :isOpen="isSidebarOpen"
                    @toggleSidebar="toggleSidebar"
                />
            </aside>

            <div class="flex flex-col flex-1 overflow-hidden">
                <NavHeader
                    :isSidebarOpen="isSidebarOpen"
                    @toggleSidebar="toggleSidebar"
                />

                <main class="flex-1 overflow-y-auto sm:p-5 md:p-6 m-2">
                    <PageHeader title="Item Location History" />

                    <!-- Back button -->
                    <div class="mt-6 mb-4">
                        <button
                            class="flex items-center gap-1.5 text-sm text-gray-500 hover:text-[#850038] transition-colors"
                            @click="goBack"
                        >
                            <svg
                                class="w-4 h-4"
                                fill="none"
                                stroke="currentColor"
                                stroke-width="1.5"
                                viewBox="0 0 24 24"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    d="M10.5 19.5 3 12m0 0 7.5-7.5M3 12h18"
                                />
                            </svg>
                            Back to list
                        </button>
                    </div>

                    <!-- Item summary card -->
                    <div
                        class="mb-5 bg-white rounded-xl border border-gray-200 p-5"
                    >
                        <div
                            class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-4"
                        >
                            <div class="flex-1">
                                <div class="flex items-center gap-2 mb-1">
                                    <h1
                                        class="text-lg font-semibold text-gray-900"
                                    >
                                        {{ item.item_name }}
                                    </h1>
                                    <span
                                        class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium"
                                        :class="{
                                            'bg-green-100 text-green-700':
                                                item.status == 1,
                                            'bg-red-100 text-red-700':
                                                item.status == 0,
                                            'bg-gray-100 text-gray-500':
                                                item.status == null,
                                        }"
                                    >
                                        {{
                                            item.status == 1
                                                ? "Active"
                                                : item.status == 0
                                                  ? "Inactive"
                                                  : "Unknown"
                                        }}
                                    </span>
                                </div>
                                <p
                                    v-if="item.description"
                                    class="text-sm text-gray-500 mb-3"
                                >
                                    {{ item.description }}
                                </p>

                                <div
                                    class="grid grid-cols-2 sm:grid-cols-4 gap-x-6 gap-y-2 text-sm"
                                >
                                    <div>
                                        <p class="text-xs text-gray-400 mb-0.5">
                                            Property No.
                                        </p>
                                        <p
                                            class="font-mono text-gray-700 text-xs"
                                        >
                                            {{ item.property_number ?? "—" }}
                                        </p>
                                    </div>
                                    <div>
                                        <p class="text-xs text-gray-400 mb-0.5">
                                            Serial No.
                                        </p>
                                        <p
                                            class="font-mono text-gray-700 text-xs"
                                        >
                                            {{ item.serial_number ?? "—" }}
                                        </p>
                                    </div>
                                    <div>
                                        <p class="text-xs text-gray-400 mb-0.5">
                                            Classification
                                        </p>
                                        <p class="text-gray-700 text-xs">
                                            {{
                                                item.item_classification
                                                    ?.classification_name ??
                                                "—"
                                            }}
                                        </p>
                                    </div>
                                    <div>
                                        <p class="text-xs text-gray-400 mb-0.5">
                                            Unit cost
                                        </p>
                                        <p class="text-gray-700 text-xs">
                                            {{
                                                item.unit_cost != null
                                                    ? `₱${Number(
                                                          item.unit_cost
                                                      ).toLocaleString(
                                                          "en-PH",
                                                          {
                                                              minimumFractionDigits: 2,
                                                          }
                                                      )}`
                                                    : "—"
                                            }}
                                        </p>
                                    </div>
                                </div>
                            </div>

                            <!-- Current location pill -->
                            <div class="flex-shrink-0">
                                <p class="text-xs text-gray-400 mb-1">
                                    Current location
                                </p>
                                <div
                                    v-if="
                                        item.room_name &&
                                        item.room_name !== 'N/A'
                                    "
                                    class="inline-flex items-center gap-2 px-3 py-1.5 bg-green-50 border border-green-100 rounded-lg"
                                >
                                    <svg
                                        class="w-4 h-4 text-[#0E6021] flex-shrink-0"
                                        fill="none"
                                        stroke="currentColor"
                                        stroke-width="1.5"
                                        viewBox="0 0 24 24"
                                    >
                                        <path
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                            d="M15 10.5a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z"
                                        />
                                        <path
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                            d="M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1 1 15 0Z"
                                        />
                                    </svg>
                                    <span
                                        class="text-sm font-medium text-[#0E6021]"
                                    >
                                        {{ item.room_name }}
                                    </span>
                                </div>
                                <span
                                    v-else
                                    class="text-sm text-gray-400 italic"
                                >
                                    No location set
                                </span>
                            </div>
                        </div>
                    </div>

                    <!-- Location history timeline -->
                    <div>
                        <h2 class="text-sm font-semibold text-gray-700 mb-4">
                            Location history
                            <span
                                class="ml-1.5 text-xs font-normal text-gray-400"
                            >
                                {{ item.history_locations?.length ?? 0 }}
                                {{
                                    (item.history_locations?.length ?? 0) ===
                                    1
                                        ? "record"
                                        : "records"
                                }}
                            </span>
                        </h2>

                        <div
                            v-if="
                                item.history_locations &&
                                item.history_locations.length > 0
                            "
                            class="relative"
                        >
                            <!-- Vertical line -->
                            <div
                                class="absolute left-[11px] top-2 bottom-2 w-px bg-gray-200"
                            ></div>

                            <ul class="space-y-0">
                                <li
                                    v-for="(entry, index) in item.history_locations"
                                    :key="entry.id"
                                    class="relative flex gap-4 pb-6 last:pb-0"
                                >
                                    <!-- Dot -->
                                    <div class="relative z-10 flex-shrink-0 mt-0.5">
                                        <div
                                            class="w-6 h-6 rounded-full border-2 flex items-center justify-center"
                                            :class="
                                                index === 0
                                                    ? 'bg-[#0E6021] border-[#0E6021]'
                                                    : 'bg-white border-gray-300'
                                            "
                                        >
                                            <svg
                                                class="w-3 h-3"
                                                :class="
                                                    index === 0
                                                        ? 'text-white'
                                                        : 'text-gray-400'
                                                "
                                                fill="none"
                                                stroke="currentColor"
                                                stroke-width="2"
                                                viewBox="0 0 24 24"
                                            >
                                                <path
                                                    stroke-linecap="round"
                                                    stroke-linejoin="round"
                                                    d="M15 10.5a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z"
                                                />
                                                <path
                                                    stroke-linecap="round"
                                                    stroke-linejoin="round"
                                                    d="M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1 1 15 0Z"
                                                />
                                            </svg>
                                        </div>
                                    </div>


                                    <!-- Content card -->
                                    <div
                                        class="flex-1 bg-white rounded-xl border border-gray-200 px-4 py-3"
                                    >
                                        <div
                                            class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-1"
                                        >
                                            <div>
                                                <div class="flex items-center gap-2">
                                                    <span
                                                        class="font-medium text-gray-900 text-sm"
                                                    >
                                                        {{
                                                            entry.room_name ??
                                                            "Unknown room"
                                                        }}
                                                    </span>
                                                    <span
                                                        v-if="index === 0"
                                                        class="inline-flex items-center px-2 py-0.5 rounded-full text-xs bg-green-100 text-green-700 font-medium"
                                                    >
                                                        Current
                                                    </span>
                                                </div>
                                                <p
                                                    v-if="entry.building_name"
                                                    class="text-xs text-gray-400 mt-0.5"
                                                >
                                                    {{ entry.building_name }}
                                                    <span
                                                        v-if="entry.description"
                                                    >
                                                        · {{ entry.description }}
                                                    </span>
                                                </p>
                                            </div>
                                            <p
                                                class="text-xs text-gray-400 flex-shrink-0"
                                            >
                                                {{ formatDate(entry.created_at) }}
                                            </p>
                                        </div>
                                    </div>
                                </li>
                            </ul>
                        </div>

                        <!-- Empty state -->
                        <div
                            v-else
                            class="bg-white rounded-xl border border-gray-200 px-5 py-16 text-center"
                        >
                            <i
                                class="fa-solid fa-box-archive text-2xl text-gray-300 mb-2"
                            ></i>
                            <p class="text-sm text-gray-400">
                                No location history recorded for this item.
                            </p>
                        </div>
                    </div>
                </main>
            </div>
        </div>
    </div>
</template>