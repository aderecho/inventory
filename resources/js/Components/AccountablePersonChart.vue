<script setup>
import { computed } from "vue";
import { UserCheck, UserX } from "lucide-vue-next";

const props = defineProps({
    accountablePersonChartData: {
        type: Array,
        default: () => [],
    },
});

const COLORS = {
    Assigned: "#0E6021",
    Unassigned: "#D97706",
};

const rows = computed(() =>
    props.accountablePersonChartData.map((row) => ({
        label: row.label,
        count: Number(row.count || 0),
        color: COLORS[row.label] ?? "#64748B",
    })),
);

const total = computed(() =>
    rows.value.reduce((sum, row) => sum + row.count, 0),
);

const assigned = computed(
    () => rows.value.find((row) => row.label === "Assigned")?.count ?? 0,
);

const assignedPercent = computed(() =>
    total.value ? (assigned.value / total.value) * 100 : 0,
);

const percentLabel = (value) => value.toFixed(1) + "%";
</script>

<template>
    <div
        class="flex h-full w-full flex-col overflow-hidden rounded-xl border border-gray-100 bg-white shadow-sm"
    >
        <!-- HEADER -->
        <div
            class="relative flex-shrink-0 overflow-hidden bg-gradient-to-br from-[#005740] via-[#004d38] to-[#003d2d] px-5 py-4 sm:px-6"
        >
            <div
                class="absolute -right-6 -top-10 h-28 w-28 rounded-full bg-white/10 blur-2xl"
            ></div>

            <div class="relative flex items-center gap-3">
                <span
                    class="flex h-10 w-10 items-center justify-center rounded-xl border border-white/20 bg-white/10 text-white"
                >
                    <UserCheck class="h-5 w-5" />
                </span>

                <div class="min-w-0">
                    <h3 class="truncate text-sm font-semibold text-white">
                        Accountability
                    </h3>
                    <p class="truncate text-xs text-white/70">
                        How much of the inventory is assigned to a person
                    </p>
                </div>
            </div>
        </div>

        <!-- BODY -->
        <div class="flex min-h-0 flex-1 flex-col p-4 sm:p-5">
            <div v-if="total > 0" class="flex flex-1 flex-col justify-center gap-4">
                <div class="flex items-end gap-3">
                    <span
                        class="text-4xl font-bold leading-none tracking-tight text-[#005740]"
                    >
                        {{ percentLabel(assignedPercent) }}
                    </span>

                    <span class="pb-0.5 text-xs text-gray-500">
                        of {{ total.toLocaleString() }} items have an accountable
                        person
                    </span>
                </div>

                <!-- Segmented share bar -->
                <div class="flex h-3 w-full overflow-hidden rounded-full bg-gray-100">
                    <div
                        v-for="row in rows"
                        :key="'bar-' + row.label"
                        class="h-full transition-all"
                        :style="{
                            width: percentLabel(
                                total ? (row.count / total) * 100 : 0,
                            ),
                            backgroundColor: row.color,
                        }"
                        :title="row.label + ': ' + row.count"
                    ></div>
                </div>

                <div class="grid grid-cols-1 gap-2.5 sm:grid-cols-2">
                    <div
                        v-for="row in rows"
                        :key="row.label"
                        class="flex items-center justify-between gap-3 rounded-lg border border-gray-100 px-3 py-2"
                    >
                        <span class="flex min-w-0 items-center gap-2">
                            <span
                                class="flex h-7 w-7 flex-shrink-0 items-center justify-center rounded-full"
                                :style="{
                                    backgroundColor: row.color + '1A',
                                    color: row.color,
                                }"
                            >
                                <UserX
                                    v-if="row.label === 'Unassigned'"
                                    class="h-3.5 w-3.5"
                                />
                                <UserCheck v-else class="h-3.5 w-3.5" />
                            </span>

                            <span class="min-w-0">
                                <span
                                    class="block truncate text-xs font-semibold text-gray-700"
                                >
                                    {{ row.label }}
                                </span>
                                <span class="block text-[11px] text-gray-400">
                                    {{
                                        percentLabel(
                                            total
                                                ? (row.count / total) * 100
                                                : 0,
                                        )
                                    }}
                                </span>
                            </span>
                        </span>

                        <span class="flex-shrink-0 text-lg font-bold text-gray-900">
                            {{ row.count.toLocaleString() }}
                        </span>
                    </div>
                </div>
            </div>

            <!-- EMPTY -->
            <div
                v-else
                class="flex flex-1 flex-col items-center justify-center py-8 text-center"
            >
                <span class="mb-3 rounded-full bg-gray-50 p-3 text-gray-400">
                    <UserCheck class="h-5 w-5" />
                </span>
                <p class="text-sm font-semibold text-gray-800">
                    Nothing to Measure Yet
                </p>
                <p class="mt-1 max-w-xs text-xs text-gray-500">
                    Items appear here once they have been recorded, assigned or
                    not.
                </p>
            </div>
        </div>
    </div>
</template>

