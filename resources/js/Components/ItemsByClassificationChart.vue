<script setup>
import { computed } from 'vue'
import { Pie } from 'vue-chartjs'
import { Layers, HelpCircle } from 'lucide-vue-next'
import {
  Chart as ChartJS,
  Title,
  Tooltip,
  Legend,
  ArcElement,
} from 'chart.js'

const props = defineProps({
  classificationChartData: {
    type: Array,
    required: true,
    default: () => [],
  },
})

const palette = ['#2a78d6', '#1baf7a', '#eda100', '#e34948', '#4a3aa7', '#e87ba4', '#eb6834', '#008300']

ChartJS.register(Title, Tooltip, Legend, ArcElement)

const colorMap = computed(() => {
  const map = {}
  props.classificationChartData.forEach((c, i) => {
    map[c.id] = palette[i % palette.length]
  })
  return map
})

const visibleData = computed(() =>
  [...props.classificationChartData].sort((a, b) => b.total_items - a.total_items)
)

const totalItems = computed(() =>
  visibleData.value.reduce((sum, c) => sum + c.total_items, 0)
)

const legendData = computed(() =>
  visibleData.value.map(c => ({
    name: c.classification_name,
    color: colorMap.value[c.id],
    total: c.total_items,
    percent: totalItems.value ? ((c.total_items / totalItems.value) * 100).toFixed(1) : 0,
  }))
)

const chartData = computed(() => ({
  labels: visibleData.value.map(c => c.classification_name),
  datasets: [
    {
      label: 'Items',
      backgroundColor: visibleData.value.map(c => colorMap.value[c.id]),
      data: visibleData.value.map(c => c.total_items),
      borderWidth: 2,
      borderColor: '#ffffff',
      hoverOffset: 8,
    },
  ],
}))

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
      callbacks: {
        label: (ctx) => {
          const total = ctx.dataset.data.reduce((a, b) => a + b, 0)
          const pct = total ? ((ctx.raw / total) * 100).toFixed(1) : 0
          return ` ${ctx.label}: ${ctx.raw.toLocaleString()} item${ctx.raw === 1 ? '' : 's'} (${pct}%)`
        },
      },
    },
  },
}
</script>

<template>
  <div class="rounded-xl border border-gray-100 shadow-sm w-full bg-white h-full flex flex-col relative">

    <div class="relative px-6 py-5 rounded-t-xl overflow-visible flex-shrink-0 bg-gradient-to-br from-[#005740] via-[#004d38] to-[#003d2d]">
      <div class="absolute -right-4 -top-8 w-32 h-32 bg-white/10 rounded-full blur-2xl pointer-events-none"></div>

      <div class="relative flex items-center gap-3">
        <div class="p-2.5 rounded-xl bg-white/10 backdrop-blur-md border border-white/20 text-white shadow-inner">
          <Layers class="w-5 h-5" />
        </div>
        <div>
          <h3 class="text-base font-semibold text-white tracking-tight">Inventory Items</h3>
          <p class="text-xs text-white/70">Breakdown metrics aggregated by classification profile</p>
        </div>
      </div>
    </div>

    <div class="p-6 flex-1 min-h-0 flex flex-col overflow-hidden rounded-b-xl">
      <div v-if="visibleData.length" class="flex-1 min-h-0 flex items-center gap-4">
        <div class="relative h-[180px] w-[180px] flex-shrink-0">
          <Pie :data="chartData" :options="chartOptions" />
        </div>

        <div class="flex-1 min-w-0 flex flex-col gap-1.5 max-h-[220px] overflow-y-auto pr-1">
          <div
            v-for="item in legendData"
            :key="item.name"
            class="flex items-center justify-between gap-2 py-1"
          >
            <div class="flex items-center gap-1.5 min-w-0">
              <span
                class="w-2 h-2 rounded-full flex-shrink-0"
                :style="{ backgroundColor: item.color }"
              ></span>
              <span class="text-[11px] font-medium text-gray-600 truncate">{{ item.name }}</span>
            </div>
            <span class="text-[11px] font-semibold text-gray-900 flex-shrink-0">
              {{ item.total.toLocaleString() }}
              <span class="text-gray-400 font-normal">({{ item.percent }}%)</span>
            </span>
          </div>
        </div>
      </div>

      <div v-else class="flex flex-col items-center justify-center flex-1 py-12 text-center">
        <div class="p-3 rounded-full bg-red-50 text-red-600 mb-3">
          <HelpCircle class="w-6 h-6" />
        </div>
        <p class="text-sm font-semibold text-gray-900">No Classification Data</p>
        <p class="text-xs text-gray-500 max-w-xs mt-1">
          There is currently no classification data to display.
        </p>
      </div>
    </div>
  </div>
</template>