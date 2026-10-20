<script setup>
import { computed } from "vue";
import { Bar } from "vue-chartjs";
import {
    Chart as ChartJS,
    BarElement,
    CategoryScale,
    LinearScale,
    Tooltip,
    Legend,
} from "chart.js";
import { Layers, Info } from "lucide-vue-next";

ChartJS.register(BarElement, CategoryScale, LinearScale, Tooltip, Legend);

const props = defineProps({
    classificationChartData: {
        type: Array,
        default: () => [],
    },
});

const palette = [
    "#0E6021",
    "#1B8A5C",
    "#2A78D6",
    "#2346A6",
    "#EDA100",
    "#EB6834",
    "#4A3AA7",
    "#E34948",
];

// Biggest first, so the shape of the inventory reads top to bottom.
const rows = computed(() =>
    [...(props.classificationChartData || [])]
        .map((row, index) => ({
            name: row.classification_name,
            total: Number(row.total_items || 0),
            color: palette[index % palette.length],
        }))
        .sort((a, b) => b.total - a.total),
);

const totalItems = computed(() =>
    rows.value.reduce((sum, row) => sum + row.total, 0),
);

const withItems = computed(() => rows.value.filter((row) => row.total > 0));

// Every bar gets its own row height, so nothing is cut off.
const chartHeight = computed(() =>
    Math.max(180, withItems.value.length * 34 + 24),
);

const chartData = computed(() => ({
    labels: withItems.value.map((row) => row.name),
    datasets: [
        {
            label: "Items",
            data: withItems.value.map((row) => row.total),
            backgroundColor: withItems.value.map((row) => row.color),
            borderRadius: 6,
            borderSkipped: false,
            barPercentage: 0.72,
            categoryPercentage: 0.9,
        },
    ],
}));

const chartOptions = {
    indexAxis: "y",
    responsive: true,
    maintainAspectRatio: false,
    layout: { padding: { right: 8 } },
    plugins: {
        legend: { display: false },
        tooltip: {
            backgroundColor: "#0f172a",
            padding: 12,
            displayColors: false,
            callbacks: {
                label: (ctx) => {
                    const value = Number(ctx.raw || 0);
                    const percent = totalItems.value
                        ? ((value / totalItems.value) * 100).toFixed(1)
                        : "0.0";

                    return (
                        " " +
                        value.toLocaleString() +
                        " item" +
                        (value === 1 ? "" : "s") +
                        " (" +
                        percent +
                        "%)"
                    );
                },
            },
        },
    },
    scales: {
        x: {
            beginAtZero: true,
            grid: { color: "#f1f5f9" },
            border: { display: false },
            ticks: { precision: 0, color: "#94a3b8", font: { size: 11 } },
        },
        y: {
            grid: { display: false },
            border: { display: false },
            ticks: {
                color: "#475569",
                font: { size: 11, weight: "500" },
                autoSkip: false,
            },
        },
    },
};
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

            <div
                class="relative flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between"
            >
                <div class="flex items-center gap-3">
                    <span
                        class="flex h-10 w-10 items-center justify-center rounded-xl border border-white/20 bg-white/10 text-white"
                    >
                        <Layers class="h-5 w-5" />
                    </span>

                    <div class="min-w-0">
                        <h3 class="truncate text-sm font-semibold text-white">
                            Items by Classification
                        </h3>
                        <p class="truncate text-xs text-white/70">
                            {{ withItems.length }} classifications holding
                            {{ totalItems.toLocaleString() }} items
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <!-- BODY -->
        <div class="flex min-h-0 flex-1 flex-col p-4 sm:p-5">
            <div
                v-if="withItems.length"
                class="relative w-full"
                :style="{ height: chartHeight + 'px' }"
            >
                <Bar :data="chartData" :options="chartOptions" />
            </div>

            <!-- EMPTY -->
            <div
                v-else
                class="flex flex-1 flex-col items-center justify-center py-10 text-center"
            >
                <span class="mb-3 rounded-full bg-gray-50 p-3 text-gray-400">
                    <Info class="h-5 w-5" />
                </span>
                <p class="text-sm font-semibold text-gray-800">
                    No Classification Data
                </p>
                <p class="mt-1 max-w-xs text-xs text-gray-500">
                    Items appear here once they carry a classification.
                </p>
            </div>
        </div>
    </div>
</template>

