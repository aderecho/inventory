<script setup>
import { onMounted, ref } from "vue";
import {
    Chart,
    LineController,
    LineElement,
    PointElement,
    LinearScale,
    Title,
    CategoryScale,
} from "chart.js/auto";

Chart.register(
    LineController,
    LineElement,
    PointElement,
    LinearScale,
    Title,
    CategoryScale,
);

const props = defineProps({
    stats: {
        type: Object,
        default: () => ({
            items: 0,
            receipts: 0,
            items_per_month: {},
            receipts_per_month: {},
        }),
    },
});

const chartCanvasOrder = ref(null);
const chartCanvasRequest = ref(null);
const labels = [
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
const monthlyItems = Array.from(
    { length: 7 },
    (_, i) => props.stats.items_per_month[i + 1] ?? 0,
);
const monthlyReceipts = Array.from(
    { length: 7 },
    (_, i) => props.stats.receipts_per_month[i + 1] ?? 0,
);

onMounted(() => {
    if (chartCanvasOrder.value) {
        const ctx = chartCanvasOrder.value.getContext("2d");

        new Chart(ctx, {
            type: "line",
            data: {
                labels: labels,
                datasets: [
                    {
                        label: `Total Items: ${props.stats.items}`,
                        data: monthlyItems,
                        backgroundColor: "rgba(0, 87, 64, 0.1)",
                        borderColor: "rgb(0, 87, 64)",
                        borderWidth: 2,
                        fill: true,
                        tension: 0.3,
                    },
                ],
            },
            options: {
                responsive: true,
                plugins: {
                    title: {
                        display: true,
                        text: `Total Items: ${props.stats.items}`,
                        color: "#005740",
                        font: { size: 14, weight: "bold" },
                    },
                },
                scales: {
                    y: { beginAtZero: true, ticks: { color: "#333" } },
                    x: { ticks: { color: "#333" } },
                },
            },
        });
    }
});

onMounted(() => {
    if (chartCanvasRequest.value) {
        const ctx = chartCanvasRequest.value.getContext("2d");

        new Chart(ctx, {
            type: "line",
            data: {
                labels: labels,
                datasets: [
                    {
                        label: `Total Receipts: ${props.stats.receipts}`,
                        data: monthlyReceipts,
                        backgroundColor: "rgba(0, 87, 64, 0.1)",
                        borderColor: "rgb(0, 87, 64)",
                        borderWidth: 2,
                        fill: true,
                        tension: 0.3,
                    },
                ],
            },
            options: {
                responsive: true,
                plugins: {
                    title: {
                        display: true,
                        text: `Total Receipts: ${props.stats.receipts}`,
                        color: "#005740",
                        font: { size: 14, weight: "bold" },
                    },
                },
                scales: {
                    y: { beginAtZero: true, ticks: { color: "#333" } },
                    x: { ticks: { color: "#333" } },
                },
            },
        });
    }
});
</script>

<template>
    <div
        class="w-full mx-auto flex flex-col lg:flex-row flex-wrap gap-4 sm:gap-5 h-auto"
    >
        <!-- Items Card -->
        <div
            class="flex-1 min-w-[300px] bg-white p-4 sm:p-5 md:p-6 rounded-lg shadow-lg w-full"
        >
            <div class="flex items-center justify-between mb-2">
                <h1 class="font-bold text-lg text-gray-800">Items</h1>
                <span
                    class="text-sm font-semibold text-white bg-[#005740] px-3 py-1 rounded-full"
                >
                    {{ stats.items }} Total
                </span>
            </div>
            <div
                class="relative w-full h-[220px] sm:h-[260px] md:h-[300px] lg:h-[320px] mt-4"
            >
                <canvas ref="chartCanvasOrder" class="w-full h-full"></canvas>
            </div>
        </div>

        <!-- Receipts Card -->
        <div
            class="flex-1 min-w-[300px] bg-white p-4 sm:p-5 md:p-6 rounded-lg shadow-lg w-full"
        >
            <div class="flex items-center justify-between mb-2">
                <h1 class="font-bold text-lg text-gray-800">Receipts</h1>
                <span
                    class="text-sm font-semibold text-white bg-[#005740] px-3 py-1 rounded-full"
                >
                    {{ stats.receipts }} Total
                </span>
            </div>
            <div
                class="relative w-full h-[220px] sm:h-[260px] md:h-[300px] lg:h-[320px] mt-4"
            >
                <canvas ref="chartCanvasRequest" class="w-full h-full"></canvas>
            </div>
        </div>
    </div>
</template>
