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
    router.visit(route("inspection.index"), {
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

function conditionBadgeClass(conditionName) {
    const name = (conditionName ?? "").toLowerCase();
    if (name.includes("good") || name.includes("excellent")) {
        return "bg-green-100 text-green-700";
    }
    if (name.includes("fair") || name.includes("repair")) {
        return "bg-yellow-100 text-yellow-700";
    }
    if (
        name.includes("poor") ||
        name.includes("damaged") ||
        name.includes("unserviceable")
    ) {
        return "bg-red-100 text-red-700";
    }
    return "bg-gray-100 text-gray-600";
}

function inspectorName(user) {
    if (!user) return "Unknown";

    return user.user_profiles?.full_name || "Unknown";
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
                    <PageHeader title="Item Inspection History" />

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
                                                    ?.classification_name ?? "—"
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
                                                          item.unit_cost,
                                                      ).toLocaleString(
                                                          "en-PH",
                                                          {
                                                              minimumFractionDigits: 2,
                                                          },
                                                      )}`
                                                    : "—"
                                            }}
                                        </p>
                                    </div>
                                </div>
                            </div>

                            <!-- Current condition pill -->
                            <div class="flex-shrink-0">
                                <p class="text-xs text-gray-400 mb-1">
                                    Current condition
                                </p>
                                <div
                                    v-if="
                                        item.inspections?.[0]?.asset_condition
                                            ?.condition_name
                                    "
                                    class="inline-flex items-center gap-2 px-3 py-1.5 rounded-lg border"
                                    :class="
                                        conditionBadgeClass(
                                            item.inspections[0].asset_condition
                                                .condition_name,
                                        )
                                            .replace('text-', 'border-')
                                            .split(' ')[0] +
                                        ' ' +
                                        conditionBadgeClass(
                                            item.inspections[0].asset_condition
                                                .condition_name,
                                        )
                                    "
                                >
                                    <svg
                                        class="w-4 h-4 flex-shrink-0"
                                        fill="none"
                                        stroke="currentColor"
                                        stroke-width="1.5"
                                        viewBox="0 0 24 24"
                                    >
                                        <path
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                            d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"
                                        />
                                    </svg>
                                    <span class="text-sm font-medium">
                                        {{
                                            item.inspections[0].asset_condition
                                                .condition_name
                                        }}
                                    </span>
                                </div>
                                <span
                                    v-else
                                    class="text-sm text-gray-400 italic"
                                >
                                    Not yet inspected
                                </span>
                            </div>
                        </div>
                    </div>

                    <!-- Inspection history timeline -->
                    <div>
                        <h2 class="text-sm font-semibold text-gray-700 mb-4">
                            Inspection history
                            <span
                                class="ml-1.5 text-xs font-normal text-gray-400"
                            >
                                {{ item.inspections?.length ?? 0 }}
                                {{
                                    (item.inspections?.length ?? 0) === 1
                                        ? "record"
                                        : "records"
                                }}
                            </span>
                        </h2>

                        <div
                            v-if="
                                item.inspections && item.inspections.length > 0
                            "
                            class="relative"
                        >
                            <!-- Vertical line -->
                            <div
                                class="absolute left-[11px] top-2 bottom-2 w-px bg-gray-200"
                            ></div>

                            <ul class="space-y-0">
                                <li
                                    v-for="(entry, index) in item.inspections"
                                    :key="entry.id"
                                    class="relative flex gap-4 pb-6 last:pb-0"
                                >
                                    <!-- Dot -->
                                    <div
                                        class="relative z-10 flex-shrink-0 mt-0.5"
                                    >
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
                                                    d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"
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
                                                <div
                                                    class="flex items-center gap-2"
                                                >
                                                    <span
                                                        class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium"
                                                        :class="
                                                            conditionBadgeClass(
                                                                entry
                                                                    .asset_condition
                                                                    ?.condition_name,
                                                            )
                                                        "
                                                    >
                                                        {{
                                                            entry
                                                                .asset_condition
                                                                ?.condition_name ??
                                                            "Unknown condition"
                                                        }}
                                                    </span>
                                                    <span
                                                        v-if="index === 0"
                                                        class="inline-flex items-center px-2 py-0.5 rounded-full text-xs bg-green-100 text-green-700 font-medium"
                                                    >
                                                        Latest
                                                    </span>
                                                </div>
                                                <p
                                                    class="text-xs text-gray-400 mt-1"
                                                >
                                                    Inspected by
                                                    {{
                                                        inspectorName(
                                                            entry.inspected_by_user,
                                                        )
                                                    }}
                                                    <span v-if="entry.remarks">
                                                        · {{ entry.remarks }}
                                                    </span>
                                                </p>
                                            </div>
                                            <p
                                                class="text-xs text-gray-400 flex-shrink-0"
                                            >
                                                {{
                                                    formatDate(
                                                        entry.inspection_date,
                                                    )
                                                }}
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
                                class="fa-solid fa-clipboard-check text-2xl text-gray-300 mb-2"
                            ></i>
                            <p class="text-sm text-gray-400">
                                No inspection history recorded for this item.
                            </p>
                        </div>
                    </div>
                </main>
            </div>
        </div>
    </div>
</template>
