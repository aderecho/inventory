<script setup>
import { computed } from "vue";
import { Doughnut } from "vue-chartjs";
import { Chart as ChartJS, Tooltip, Legend, ArcElement } from "chart.js";
import { FilePieChart, Info } from "lucide-vue-next";

ChartJS.register(Tooltip, Legend, ArcElement);

const props = defineProps({
    icsParChartData: {
        type: Array,
        default: () => [],
    },
});

const COLORS = ["#0E6021", "#2346A6"];

const total = computed(() =>
    props.icsParChartData.reduce((sum, row) => sum + Number(row.count || 0), 0),
);

const rows = computed(() =>
    props.icsParChartData.map((row, index) => {
        const value = Number(row.count || 0);

        return {
            label: row.label,
            count: value,
            percent: total.value ? (value / total.value) * 100 : 0,
            color: COLORS[index % COLORS.length],
        };
    }),
);

const chartData = computed(() => ({
    labels: rows.value.map((row) => row.label),
    datasets: [
        {
            data: rows.value.map((row) => row.count),
            backgroundColor: rows.value.map((row) => row.color),
            borderWidth: 0,
            hoverOffset: 6,
        },
    ],
}));

const chartOptions = {
    responsive: true,
    maintainAspectRatio: false,
    cutout: "74%",
    plugins: {
        legend: { display: false },
        tooltip: {
            backgroundColor: "#0f172a",
            padding: 12,
            displayColors: false,
            callbacks: {
                label: (ctx) => {
                    const percent = total.value
                        ? ((Number(ctx.raw) / total.value) * 100).toFixed(1)
                        : "0.0";

                    return " " + ctx.label + ": " + ctx.raw + " (" + percent + "%)";
                },
            },
        },
    },
};

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
                    <FilePieChart class="h-5 w-5" />
                </span>

                <div class="min-w-0">
                    <h3 class="truncate text-sm font-semibold text-white">
                        Receipt Types
                    </h3>
                    <p class="truncate text-xs text-white/70">
                        Every acknowledgement receipt, split by form used
                    </p>
                </div>
            </div>
        </div>

        <!-- BODY -->
        <div class="flex min-h-0 flex-1 flex-col p-4 sm:p-5">
            <div
                v-if="total > 0"
                class="flex flex-col items-center gap-5 sm:flex-row sm:gap-6"
            >
                <div
                    class="relative h-[168px] w-[168px] flex-shrink-0 sm:h-[150px] sm:w-[150px] lg:h-[168px] lg:w-[168px]"
                >
                    <Doughnut :data="chartData" :options="chartOptions" />

                    <div
                        class="pointer-events-none absolute inset-0 flex flex-col items-center justify-center"
                    >
                        <span class="text-2xl font-bold text-gray-900">
                            {{ total.toLocaleString() }}
                        </span>
                        <span
                            class="text-[10px] font-semibold uppercase tracking-wide text-gray-400"
                        >
                            receipts
                        </span>
                    </div>
                </div>

                <div class="flex w-full min-w-0 flex-1 flex-col gap-3">
                    <div
                        v-for="row in rows"
                        :key="row.label"
                        class="rounded-lg border border-gray-100 px-3 py-2"
                    >
                        <div
                            class="flex items-center justify-between gap-3 text-sm"
                        >
                            <span class="flex min-w-0 items-center gap-2">
                                <span
                                    class="h-2.5 w-2.5 flex-shrink-0 rounded-full"
                                    :style="{ backgroundColor: row.color }"
                                ></span>
                                <span class="truncate font-medium text-gray-700">
                                    {{ row.label }}
                                </span>
                            </span>

                            <span class="flex-shrink-0 text-gray-500">
                                <span class="font-semibold text-gray-900">
                                    {{ row.count }}
                                </span>
                                ({{ percentLabel(row.percent) }})
                            </span>
                        </div>

                        <div class="mt-2 h-1.5 w-full rounded-full bg-gray-100">
                            <div
                                class="h-1.5 rounded-full transition-all"
                                :style="{
                                    width: percentLabel(row.percent),
                                    backgroundColor: row.color,
                                }"
                            ></div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- EMPTY -->
            <div
                v-else
                class="flex flex-1 flex-col items-center justify-center py-8 text-center"
            >
                <span class="mb-3 rounded-full bg-gray-50 p-3 text-gray-400">
                    <Info class="h-5 w-5" />
                </span>
                <p class="text-sm font-semibold text-gray-800">
                    No Receipts Found
                </p>
                <p class="mt-1 max-w-xs text-xs text-gray-500">
                    No PAR or ICS receipts have been created yet.
                </p>
            </div>
        </div>
    </div>
</template>

