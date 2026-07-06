<script setup>
import { computed } from "vue";
import { Doughnut } from "vue-chartjs";
import { FilePieChart, HelpCircle } from "lucide-vue-next";
import { Chart as ChartJS, Title, Tooltip, Legend, ArcElement } from "chart.js";

ChartJS.register(Title, Tooltip, Legend, ArcElement);

const props = defineProps({
    icsParChartData: {
        type: Array,
        default: () => [],
    },
});

const totalCount = computed(() =>
    props.icsParChartData.reduce(
        (sum, item) => sum + Number(item.count || 0),
        0,
    ),
);

// Consistent charting color schemes matching your original array order
const sliceColors = ["#1B8A5C", "#2346A6"];

const chartData = computed(() => ({
    labels: props.icsParChartData.map((item) => item.label),
    datasets: [
        {
            data: props.icsParChartData.map((item) => Number(item.count || 0)),
            backgroundColor: sliceColors,
            borderColor: ["#ffffff", "#ffffff"],
            borderWidth: 3,
            borderAlign: "inner",
            cutout: "70%", // Creates a cleaner, thinner doughnut ring
            hoverOffset: 12,
        },
    ],
}));

const chartOptions = {
    responsive: true,
    maintainAspectRatio: false,
    plugins: {
        legend: { display: false }, // Relying on the clean custom legend to the right
        title: { display: false },
        tooltip: {
            backgroundColor: "#1e293b",
            padding: 12,
            bodyFont: { size: 13 },
            titleFont: { size: 13, weight: "bold" },
            callbacks: {
                label: (ctx) => {
                    const value = Number(ctx.raw || 0);
                    const percent = totalCount.value
                        ? ((value / totalCount.value) * 100).toFixed(1)
                        : "0.0";
                    return ` ${ctx.label}: ${value} (${percent}%)`;
                },
            },
        },
    },
};

const legendData = computed(() =>
    props.icsParChartData.map((item, index) => {
        const value = Number(item.count || 0);
        const percent = totalCount.value
            ? ((value / totalCount.value) * 100).toFixed(1)
            : "0";
        return {
            label: item.label,
            count: value,
            percent: percent,
            color: sliceColors[index % sliceColors.length],
        };
    }),
);
</script>

<template>
    <div
        class="rounded-xl border border-gray-100 shadow-sm w-full bg-white h-full flex flex-col overflow-hidden"
    >
        <div
            class="relative px-6 py-7 overflow-hidden flex-shrink-0 bg-gradient-to-br from-[#005740] via-[#004d38] to-[#003d2d]"
        >
            <div
                class="absolute -right-4 -top-8 w-32 h-32 bg-white/10 rounded-full blur-2xl"
            ></div>

            <div class="relative flex items-center gap-3">
                <div
                    class="p-2.5 rounded-xl bg-white/10 backdrop-blur-md border border-white/20 text-white shadow-inner"
                >
                    <FilePieChart class="w-5 h-5" />
                </div>
                <div>
                    <h3
                        class="text-base font-semibold text-white tracking-tight"
                    >
                        Receipt Types
                    </h3>
                    <p class="text-xs text-white/70">
                        Proportional breakdown of ICS vs PAR allocations
                    </p>
                </div>
            </div>
        </div>

        <div class="p-6 flex-1 min-h-0 flex flex-col overflow-hidden">
            <div
                v-if="icsParChartData.length"
                class="flex-1 min-h-0 flex items-center gap-4"
            >
                <div class="relative h-[180px] w-[180px] flex-shrink-0">
                    <Doughnut :data="chartData" :options="chartOptions" />
                </div>

                <div class="flex-1 min-w-0 flex flex-col gap-2">
                    <div
                        v-for="item in legendData"
                        :key="item.label"
                        class="flex items-center justify-between gap-2"
                    >
                        <div class="flex items-center gap-1.5 min-w-0">
                            <span
                                class="w-2 h-2 rounded-full flex-shrink-0"
                                :style="{ backgroundColor: item.color }"
                            ></span>
                            <span class="text-xs font-medium text-gray-600 truncate">{{ item.label }}</span>
                        </div>
                        <span class="text-xs font-semibold text-gray-900 flex-shrink-0">
                            {{ item.count }}
                            <span class="text-gray-400 font-normal">({{ item.percent }}%)</span>
                        </span>
                    </div>
                </div>
            </div>

            <div
                v-else
                class="flex flex-col items-center justify-center flex-1 py-12 text-center"
            >
                <div class="p-3 rounded-full bg-gray-50 text-gray-400 mb-3">
                    <HelpCircle class="w-6 h-6" />
                </div>
                <p class="text-sm font-semibold text-gray-900">
                    No Receipts Found
                </p>
                <p class="text-xs text-gray-500 max-w-xs mt-1">
                    There are currently no active clearance or inventory
                    assignment receipts logged within this dashboard slice.
                </p>
            </div>
        </div>
    </div>
</template>