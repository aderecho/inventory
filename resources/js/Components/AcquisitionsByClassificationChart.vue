<script setup>
import { ref, computed, watch } from "vue";
import { router } from "@inertiajs/vue3";
import { Line } from "vue-chartjs";
import Select from "primevue/select";
import { LineChart, HelpCircle } from "lucide-vue-next";
import {
    Chart as ChartJS,
    Title,
    Tooltip,
    Legend,
    LineElement,
    PointElement,
    CategoryScale,
    LinearScale,
} from "chart.js";

ChartJS.register(Title, Tooltip, Legend, LineElement, PointElement, CategoryScale, LinearScale);

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

const months = ["Jan", "Feb", "Mar", "Apr", "May", "Jun", "Jul", "Aug", "Sep", "Oct", "Nov", "Dec"];
const palette = ["#2a78d6", "#1baf7a", "#eda100", "#e34948", "#4a3aa7", "#e87ba4", "#eb6834", "#008300"];

const yearOptions = computed(() =>
    props.availableYears.map((y) => ({ value: y, label: String(y) })),
);

const selectedYearModel = ref(props.selectedYear);

const legendData = computed(() =>
    props.acquisitionsByClassification.map((c, i) => ({
        name: c.classification_name,
        color: palette[i % palette.length],
        total: c.monthly_totals.reduce((sum, n) => sum + n, 0),
    })),
);

const chartData = computed(() => ({
    labels: months,
    datasets: props.acquisitionsByClassification.map((c, i) => ({
        label: c.classification_name,
        data: c.monthly_totals,
        borderColor: palette[i % palette.length],
        backgroundColor: "transparent",
        borderWidth: 2.5,
        tension: 0.3,
        pointRadius: 3,
        pointHoverRadius: 6,
        pointBackgroundColor: palette[i % palette.length],
        pointBorderColor: "#fff",
        pointBorderWidth: 1.5,
    })),
}));

const chartOptions = {
    responsive: true,
    maintainAspectRatio: false,
    plugins: {
        legend: { display: false },
        title: { display: false },
        tooltip: {
            backgroundColor: '#1e293b',
            padding: 12,
            bodyFont: { size: 13 },
            titleFont: { size: 13, weight: 'bold' },
            mode: "index",
            intersect: false,
        },
    },
    interaction: { mode: "index", intersect: false },
    scales: {
        x: {
            grid: { display: false },
            ticks: { color: '#64748b', font: { size: 11 } }
        },
        y: { 
            beginAtZero: true, 
            border: { dash: [4, 4] },
            grid: { color: '#f1f5f9' },
            ticks: { precision: 0, color: '#64748b', font: { size: 11 } } 
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
  <div class="rounded-xl border border-gray-100 shadow-sm w-full bg-white h-full flex flex-col overflow-hidden">
    <div class="relative px-6 py-5 overflow-hidden flex-shrink-0 bg-gradient-to-br from-[#005740] via-[#004d38] to-[#003d2d] flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
      <div class="absolute -right-4 -top-8 w-32 h-32 bg-white/10 rounded-full blur-2xl"></div>
      
      <div class="relative flex items-center gap-3">
        <div class="p-2.5 rounded-xl bg-white/10 backdrop-blur-md border border-white/20 text-white shadow-inner">
          <LineChart class="w-5 h-5" />
        </div>
        <div>
          <h3 class="text-base font-semibold text-white tracking-tight">Acquisitions Over Time</h3>
          <p class="text-xs text-white/70">Monthly breakdown metrics aggregated by asset classification</p>
        </div>
      </div>

      <div class="relative flex items-center gap-2 z-10" v-if="availableYears.length">
        <Select
          v-model="selectedYearModel"
          :options="yearOptions"
          optionValue="value"
          optionLabel="label"
          placeholder="Select Year"
          class="w-full sm:w-36 text-sm year-select-custom"
          pt:root:class="bg-white/10 backdrop-blur-md border-white/20 text-white shadow-sm"
          pt:label:class="text-white font-medium"
          pt:dropdown:class="text-white/70"
        />
      </div>
    </div>

    <div class="p-6 flex-1 min-h-0 flex flex-col justify-between gap-6">
      <div v-if="acquisitionsByClassification.length" class="flex flex-col flex-1 min-h-0 gap-6">
        <div class="relative w-full flex-1 min-h-[260px]">
          <Line :data="chartData" :options="chartOptions" />
        </div>

        <div class="border-t border-gray-100 pt-4 flex-shrink-0">
          <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-3">
            <div
              v-for="item in legendData"
              :key="item.name"
              class="flex items-center justify-between p-2.5 rounded-lg border border-gray-100 bg-white hover:shadow-sm transition-all duration-200"
            >
              <div class="flex items-center gap-2.5 min-w-0">
                <span
                  class="w-3 h-3 rounded-full flex-shrink-0 ring-2 ring-offset-1"
                  :style="{ backgroundColor: item.color, '--tw-ring-color': item.color + '33' }"
                ></span>
                <span class="text-xs font-medium text-gray-600 truncate">{{ item.name }}</span>
              </div>
              <span class="text-xs font-bold text-gray-900 bg-gray-50 px-2 py-0.5 rounded-md ml-2 border border-gray-100">
                {{ item.total.toLocaleString() }}
              </span>
            </div>
          </div>
        </div>
      </div>

      <div v-else class="flex flex-col items-center justify-center flex-1 py-12 text-center">
        <div class="p-3 rounded-full bg-red-50 text-red-600 mb-3">
          <HelpCircle class="w-6 h-6" />
        </div>
        <p class="text-sm font-semibold text-gray-900">No Acquisitions Found</p>
        <p class="text-xs text-gray-500 max-w-xs mt-1">
          No records or asset values were safely logged during the operational tracking window of {{ selectedYear }}.
        </p>
      </div>
    </div>
  </div>
</template>

<style scoped>
/* Scoped brand color design overrides for PrimeVue select component in glass header context */
.year-select-custom :deep(.p-select-overlay) {
  background-color: #ffffff !important;
  border: 1px solid #e2e8f0 !important;
  box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1) !important;
}
</style>