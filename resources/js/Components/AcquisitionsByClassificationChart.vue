<script setup>
import { ref, computed, watch } from "vue";
import { router } from "@inertiajs/vue3";
import { Line } from "vue-chartjs";
import Select from "primevue/select";
import { LineChart, Info } from "lucide-vue-next";
import {
    Chart as ChartJS,
    Tooltip,
    Legend,
    LineElement,
    PointElement,
    CategoryScale,
    LinearScale,
    Filler,
} from "chart.js";

ChartJS.register(
    Tooltip,
    Legend,
    LineElement,
    PointElement,
    CategoryScale,
    LinearScale,
    Filler,
);

const props = defineProps({
    acquisitionsByClassification: {
        type: Array,
        default: () => [],
    },
    availableYears: {
        type: Array,
        default: () => [],
    },
    selectedYear: {
        type: Number,
        default: null,
    },
    embedToken: {
        type: String,
        default: null,
    },
});

const months = [
    "Jan",
    "Feb",
    "Mar",
    "Apr",
    "May",
    "Jun",
    "Jul",
    "Aug",
    "Sep",
    "Oct",
    "Nov",
    "Dec",
];

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

const yearOptions = computed(() =>
    props.availableYears.map((year) => ({ value: year, label: String(year) })),
);

const selectedYearModel = ref(props.selectedYear);

const series = computed(() =>
    props.acquisitionsByClassification.map((row, index) => ({
        name: row.classification_name,
        color: palette[index % palette.length],
        totals: row.monthly_totals ?? [],
        sum: (row.monthly_totals ?? []).reduce(
            (total, value) => total + Number(value || 0),
            0,
        ),
    })),
);

const totalForYear = computed(() =>
    series.value.reduce((total, row) => total + row.sum, 0),
);

const chartData = computed(() => ({
    labels: months,
    datasets: series.value.map((row) => ({
        label: row.name,
        data: row.totals,
        borderColor: row.color,
        // Soft area under the line: reads as a trend without hiding the others.
        backgroundColor: row.color + "1A",
        fill: true,
        borderWidth: 2,
        tension: 0.35,
        pointRadius: 0,
        pointHoverRadius: 5,
        pointBackgroundColor: row.color,
        pointBorderColor: "#ffffff",
        pointBorderWidth: 2,
    })),
}));

const chartOptions = {
    responsive: true,
    maintainAspectRatio: false,
    interaction: { mode: "index", intersect: false },
    plugins: {
        legend: { display: false },
        tooltip: {
            backgroundColor: "#0f172a",
            padding: 12,
            displayColors: true,
            boxPadding: 4,
            callbacks: {
                label: (ctx) =>
                    " " + ctx.dataset.label + ": " + ctx.parsed.y + " item(s)",
            },
        },
    },
    scales: {
        x: {
            grid: { display: false },
            border: { display: false },
            ticks: {
                color: "#94a3b8",
                font: { size: 11 },
                autoSkip: true,
                maxRotation: 0,
                minRotation: 0,
            },
        },
        y: {
            beginAtZero: true,
            grid: { color: "#f1f5f9" },
            border: { display: false },
            ticks: {
                precision: 0,
                color: "#94a3b8",
                font: { size: 11 },
                maxTicksLimit: 5,
            },
        },
    },
};

watch(selectedYearModel, (year) => {
    const url = props.embedToken
        ? route("embed.dashboard", props.embedToken)
        : route("dashboard.index");

    router.get(
        url,
        { acquisition_year: year },
        { preserveScroll: true, preserveState: true },
    );
});
</script>

<template>
    <div
        class="flex h-full w-full flex-col overflow-hidden rounded-xl border border-gray-100 bg-white shadow-sm"
    >
        <!-- HEADER -->
        <div
            class="relative flex flex-shrink-0 flex-col gap-3 overflow-hidden bg-gradient-to-br from-[#005740] via-[#004d38] to-[#003d2d] px-5 py-4 sm:flex-row sm:items-center sm:justify-between sm:px-6"
        >
            <div
                class="absolute -right-6 -top-10 h-28 w-28 rounded-full bg-white/10 blur-2xl"
            ></div>

            <div class="relative flex items-center gap-3">
                <span
                    class="flex h-10 w-10 items-center justify-center rounded-xl border border-white/20 bg-white/10 text-white"
                >
                    <LineChart class="h-5 w-5" />
                </span>

                <div class="min-w-0">
                    <h3 class="truncate text-sm font-semibold text-white">
                        Acquisitions Over Time
                    </h3>
                    <p class="truncate text-xs text-white/70">
                        {{ totalForYear.toLocaleString() }} item(s) recorded in
                        {{ selectedYear ?? "this period" }}
                    </p>
                </div>
            </div>

            <div
                v-if="availableYears.length"
                class="relative z-10 flex items-center"
            >
                <Select
                    v-model="selectedYearModel"
                    :options="yearOptions"
                    optionValue="value"
                    optionLabel="label"
                    placeholder="Select Year"
                    class="year-select-custom w-full text-sm sm:w-32"
                    pt:root:class="bg-white/10 backdrop-blur-md border-white/20 text-white shadow-sm"
                    pt:label:class="text-white font-medium"
                    pt:dropdown:class="text-white/70"
                />
            </div>
        </div>

        <!-- BODY -->
        <div class="flex min-h-0 flex-1 flex-col gap-4 p-4 sm:gap-5 sm:p-5">
            <template v-if="series.length">
                <div
                    class="relative w-full flex-1"
                    :style="{ minHeight: '260px' }"
                >
                    <Line :data="chartData" :options="chartOptions" />
                </div>

                <div class="flex-shrink-0 border-t border-gray-100 pt-4">
                    <div
                        class="flex max-h-[92px] flex-wrap gap-2 overflow-y-auto pr-1"
                    >
                        <span
                            v-for="row in series"
                            :key="row.name"
                            class="flex items-center gap-2 rounded-full border border-gray-100 bg-gray-50 px-3 py-1.5"
                        >
                            <span
                                class="h-2.5 w-2.5 flex-shrink-0 rounded-full"
                                :style="{ backgroundColor: row.color }"
                            ></span>
                            <span class="max-w-[160px] truncate text-xs font-medium text-gray-600">
                                {{ row.name }}
                            </span>
                            <span class="text-xs font-bold text-gray-900">
                                {{ row.sum.toLocaleString() }}
                            </span>
                        </span>
                    </div>
                </div>
            </template>

            <!-- EMPTY -->
            <div
                v-else
                class="flex flex-1 flex-col items-center justify-center py-10 text-center"
            >
                <span class="mb-3 rounded-full bg-gray-50 p-3 text-gray-400">
                    <Info class="h-5 w-5" />
                </span>
                <p class="text-sm font-semibold text-gray-800">
                    No Acquisitions Found
                </p>
                <p class="mt-1 max-w-xs text-xs text-gray-500">
                    No items were recorded with a date acquired in
                    {{ selectedYear ?? "the selected year" }}.
                </p>
            </div>
        </div>
    </div>
</template>

<style scoped>
.year-select-custom :deep(.p-select-overlay) {
    background-color: #ffffff !important;
    border: 1px solid #e2e8f0 !important;
    box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1) !important;
}
</style>

