<script setup>
import { computed, ref, onMounted, onBeforeUnmount, watch } from 'vue'
import { Bar } from 'vue-chartjs'
import Multiselect from '@vueform/multiselect'
import { Building2, HelpCircle, Filter, ChevronDown, CheckSquare, Square, ChevronLeft, ChevronRight } from 'lucide-vue-next'
import {
  Chart as ChartJS,
  Title,
  Tooltip,
  Legend,
  BarElement,
  CategoryScale,
  LinearScale,
} from 'chart.js'
import '/node_modules/@vueform/multiselect/themes/default.css'

const props = defineProps({
  organizationChartData: {
    type: Array,
    required: true,
    default: () => [],
  },
})

const palette = ['#2a78d6', '#1baf7a', '#eda100', '#e34948', '#4a3aa7', '#e87ba4', '#eb6834', '#008300']
const STORAGE_KEY = 'organizationChart:selectedIds'
const PAGE_SIZE = 10

ChartJS.register(Title, Tooltip, Legend, BarElement, CategoryScale, LinearScale)

const isDropdownOpen = ref(false)
const dropdownRef = ref(null)
const selectedIds = ref([])
const currentPage = ref(1)

const colorMap = computed(() => {
  const map = {}
  props.organizationChartData.forEach((o, i) => {
    map[o.id] = palette[i % palette.length]
  })
  return map
})

const multiselectOptions = computed(() => {
  return [...props.organizationChartData]
    .sort((a, b) => a.organization_name.localeCompare(b.organization_name))
    .map(o => ({
      value: o.id,
      label: o.organization_name
    }))
})
const isAllSelected = computed(() => {
  return props.organizationChartData.length > 0 &&
         selectedIds.value.length === props.organizationChartData.length
})

// All organizations matching the current filter, sorted largest first.
const filteredData = computed(() =>
  props.organizationChartData
    .filter(o => selectedIds.value.includes(o.id))
    .sort((a, b) => b.total_user_profiles - a.total_user_profiles)
)

const totalPages = computed(() =>
  Math.max(1, Math.ceil(filteredData.value.length / PAGE_SIZE))
)

// Only the current page's slice actually gets rendered in the chart/legend.
const visibleData = computed(() => {
  const start = (currentPage.value - 1) * PAGE_SIZE
  return filteredData.value.slice(start, start + PAGE_SIZE)
})

const legendData = computed(() =>
  visibleData.value.map(o => ({
    name: o.organization_name,
    color: colorMap.value[o.id],
    total: o.total_user_profiles,
  }))
)

const chartData = computed(() => ({
  labels: visibleData.value.map(o => o.organization_name),
  datasets: [
    {
      label: 'User Profiles',
      backgroundColor: visibleData.value.map(o => colorMap.value[o.id]),
      borderRadius: 6,
      data: visibleData.value.map(o => o.total_user_profiles),
      minBarLength: 6,
      barPercentage: 0.5,
      categoryPercentage: 0.7,
    },
  ],
}))

const chartOptions = {
  responsive: true,
  maintainAspectRatio: false,
  interaction: {
    mode: 'index',
    intersect: false,
  },
  plugins: {
    legend: { display: false },
    title: { display: false },
    tooltip: {
      backgroundColor: '#1e293b',
      padding: 12,
      bodyFont: { size: 13 },
      titleFont: { size: 13, weight: 'bold' },
      callbacks: {
        label: (ctx) => ` ${ctx.raw} user profile${ctx.raw === 1 ? '' : 's'}`,
      },
    },
  },
  scales: {
    x: {
      ticks: { display: false },
      grid: { display: false },
    },
    y: {
      beginAtZero: true,
      border: { dash: [4, 4] },
      grid: { color: '#f1f5f9' },
      ticks: {
        precision: 0,
        color: '#64748b',
        font: { size: 11 }
      },
    },
  },
}

const toggleDropdown = () => {
  isDropdownOpen.value = !isDropdownOpen.value
}

const toggleSelectAll = () => {
  if (isAllSelected.value) {
    selectedIds.value = []
  } else {
    selectedIds.value = props.organizationChartData.map(o => o.id)
  }
}

const goToPage = (page) => {
  if (page < 1 || page > totalPages.value) return
  currentPage.value = page
}

const handleClickOutside = (event) => {
  if (dropdownRef.value && !dropdownRef.value.contains(event.target)) {
    isDropdownOpen.value = false
  }
}

const loadSavedSelection = () => {
  try {
    const saved = sessionStorage.getItem(STORAGE_KEY)
    return saved ? JSON.parse(saved) : null
  } catch (e) {
    return null
  }
}

const saveSelection = (ids) => {
  try {
    sessionStorage.setItem(STORAGE_KEY, JSON.stringify(ids))
  } catch (e) {
    // Fail silently
  }
}

onMounted(() => {
  document.addEventListener('click', handleClickOutside)
  const saved = loadSavedSelection()
  if (saved && saved.length) {
    const validIds = props.organizationChartData.map(o => o.id)
    selectedIds.value = saved.filter(id => validIds.includes(id))
  } else {
    // Default to ALL organizations: pagination (not a top-5 cap) is what
    // keeps this readable with 50+ orgs.
    selectedIds.value = props.organizationChartData.map(o => o.id)
  }
})

onBeforeUnmount(() => {
  document.removeEventListener('click', handleClickOutside)
})

watch(selectedIds, (newIds) => {
  saveSelection(newIds)
}, { deep: true })

// Whenever the filter changes, the old page number may no longer make
// sense (e.g. page 4 of a now-shorter list), so snap back to page 1.
watch(filteredData, () => {
  currentPage.value = 1
})
</script>

<template>
  <div class="rounded-xl border border-gray-100 shadow-sm w-full bg-white h-full flex flex-col relative">

    <div class="relative px-6 py-5 rounded-t-xl overflow-visible flex-shrink-0 bg-gradient-to-br from-[#005740] via-[#004d38] to-[#003d2d] flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
      <div class="absolute -right-4 -top-8 w-32 h-32 bg-white/10 rounded-full blur-2xl pointer-events-none"></div>

      <div class="relative flex items-center gap-3">
        <div class="p-2.5 rounded-xl bg-white/10 backdrop-blur-md border border-white/20 text-white shadow-inner">
          <Building2 class="w-5 h-5" />
        </div>
        <div>
          <h3 class="text-base font-semibold text-white tracking-tight">Organizations</h3>
          <p class="text-xs text-white/70">User profile count aggregated by organization</p>
        </div>
      </div>

      <div ref="dropdownRef" class="relative inline-block text-left z-30">
        <button
          type="button"
          @click.stop="toggleDropdown"
          class="inline-flex items-center justify-between w-full sm:w-auto gap-2 px-4 py-2 text-sm font-medium text-white bg-white/10 border border-white/20 rounded-lg shadow-sm hover:bg-white/20 focus:outline-none transition-colors backdrop-blur-sm"
        >
          <Filter class="w-4 h-4 text-white/80" />
          <span>Organizations ({{ selectedIds.length }})</span>
          <ChevronDown class="w-4 h-4 text-white/80 transition-transform duration-200" :class="{ 'rotate-180': isDropdownOpen }" />
        </button>

        <div
          v-if="isDropdownOpen"
          class="absolute right-0 z-50 mt-2 w-max min-w-[280px] max-w-[40vw] origin-top-right rounded-xl bg-white p-4 shadow-xl border border-gray-100 focus:outline-none flex flex-col gap-3 transform translate-y-0"
          style="box-shadow: 0 20px 25px -5px rgb(0 0 0 / 0.1), 0 8px 10px -6px rgb(0 0 0 / 0.1);"
        >
          <button
            type="button"
            @click="toggleSelectAll"
            class="w-full flex items-center gap-2.5 px-2 py-1.5 rounded-lg text-left text-xs font-semibold text-gray-700 hover:bg-gray-50 transition-colors border border-dashed border-gray-100"
          >
            <component
              :is="isAllSelected ? CheckSquare : Square"
              class="w-4 h-4"
              :class="isAllSelected ? 'text-[#005740]' : 'text-gray-400'"
            />
            <span>{{ isAllSelected ? 'Deselect All Records' : 'Select All Organizations' }}</span>
          </button>

          <div class="text-sm">
            <Multiselect
              v-model="selectedIds"
              :options="multiselectOptions"
              mode="tags"
              :searchable="true"
              :close-on-select="false"
              :create-option="false"
              placeholder="Search organizations"
              class="custom-multiselect-override"
            />
          </div>
        </div>
      </div>
    </div>

    <div class="p-6 flex-1 min-h-0 flex flex-col justify-between gap-6 overflow-hidden rounded-b-xl">
      <div v-if="visibleData.length" class="flex flex-col flex-1 min-h-0 gap-6">
        <div class="relative w-full flex-1 min-h-[260px]">
          <Bar :data="chartData" :options="chartOptions" />
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
                  class="w-3 h-3 rounded-md flex-shrink-0 ring-2 ring-offset-1"
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

        <div class="flex items-center justify-between flex-shrink-0 pt-1">
          <p class="text-xs text-gray-500">
            Page {{ currentPage }} of {{ totalPages }}
            <span class="text-gray-400">&middot; {{ filteredData.length }} organization{{ filteredData.length === 1 ? '' : 's' }} total</span>
          </p>

          <div class="flex items-center gap-2">
            <button
              type="button"
              @click="goToPage(currentPage - 1)"
              :disabled="currentPage === 1"
              class="inline-flex items-center justify-center w-8 h-8 rounded-lg border border-gray-200 text-gray-500 hover:bg-gray-50 hover:text-gray-700 disabled:opacity-40 disabled:cursor-not-allowed disabled:hover:bg-transparent transition-colors"
              aria-label="Previous page"
            >
              <ChevronLeft class="w-4 h-4" />
            </button>

            <button
              type="button"
              @click="goToPage(currentPage + 1)"
              :disabled="currentPage === totalPages"
              class="inline-flex items-center justify-center w-8 h-8 rounded-lg border border-gray-200 text-gray-500 hover:bg-gray-50 hover:text-gray-700 disabled:opacity-40 disabled:cursor-not-allowed disabled:hover:bg-transparent transition-colors"
              aria-label="Next page"
            >
              <ChevronRight class="w-4 h-4" />
            </button>
          </div>
        </div>
      </div>

      <div v-else class="flex flex-col items-center justify-center flex-1 py-12 text-center">
        <div class="p-3 rounded-full bg-red-50 text-red-600 mb-3">
          <HelpCircle class="w-6 h-6" />
        </div>
        <p class="text-sm font-semibold text-gray-900">No Organization Active</p>
        <p class="text-xs text-gray-500 max-w-xs mt-1">
          Please click the filter dropdown menu above to map operational records.
        </p>
      </div>
    </div>
  </div>
</template>

<style scoped>
/* Scoped brand color design overrides for Vueform Multiselect */
.custom-multiselect-override :deep(.multiselect) {
  border-color: #e2e8f0;
  box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
  border-radius: 0.375rem;
}
.custom-multiselect-override :deep(.multiselect.is-active) {
  border-color: #005740;
  box-shadow: 0 0 0 1px #005740;
}
.custom-multiselect-override :deep(.multiselect-tag) {
  background: #005740;
  color: #ffffff;
  font-weight: 500;
  border-radius: 0.25rem;
}
.custom-multiselect-override :deep(.multiselect-tag-remove-icon) {
  color: #ffffff;
}
.custom-multiselect-override :deep(.multiselect-option.is-selected) {
  background: #005740;
  color: #ffffff;
}
.custom-multiselect-override :deep(.multiselect-option.is-pointed) {
  background: rgba(0, 87, 64, 0.1);
  color: #1e293b;
}
.custom-multiselect-override :deep(.multiselect-option.is-selected.is-pointed) {
  background: #005740;
  color: #ffffff;
}

/* Let the dropdown grow to fit its content instead of matching the
   (narrower) search input's width above it. */
.custom-multiselect-override :deep(.multiselect-dropdown) {
  width: max-content;
  min-width: 100%;
  max-width: 85vw;
}

/* Lay the option list out in 3 columns so every organization name is
   readable in full without truncation, and the list doesn't run too tall. */
.custom-multiselect-override :deep(.multiselect-options) {
  display: grid;
  grid-template-columns: repeat(3, max-content);
  column-gap: 0.5rem;
  row-gap: 0.125rem;
  max-height: 320px;
  overflow-y: auto;
}

.custom-multiselect-override :deep(.multiselect-option) {
  white-space: nowrap;
}
</style>