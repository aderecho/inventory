<script setup>
import { computed } from 'vue';
import { ArrowUpRight, ShieldCheck, Activity, Zap } from 'lucide-vue-next';

const props = defineProps({
  itemOverview: {
    type: Array,
    required: true,
    default: () => [],
  }
});

// Appends contextual meta details dynamically based on your main layout titles
const getExtendedCardData = (title) => {
  const metaMap = {
    'Total Items': {
      subtitle: 'Active Tracked Assets',
    },
    'Total Suppliers': {
      subtitle: 'Vetted Logistics Channels',
    },
    'Total Users': {
      subtitle: 'Registered Accounts',
    }
  };

  return metaMap[title] || {
    subtitle: 'System Parameter',
  };
};

const processedOverview = computed(() => {
  return props.itemOverview.map(card => ({
    ...card,
    meta: getExtendedCardData(card.title)
  }));
});
</script>

<template>
  <div
    v-for="overview in processedOverview"
    :key="overview.title"
    class="bg-white rounded-xl border border-gray-100 shadow-sm flex flex-col justify-between transition-all duration-300 hover:shadow-md hover:-translate-y-0.5 overflow-hidden h-full"
  >
    <div class="relative px-5 py-4 overflow-hidden flex-shrink-0 bg-gradient-to-br from-[#005740] via-[#004d38] to-[#003d2d] flex items-center justify-between gap-4">
      <div class="absolute -right-2 -top-6 w-20 h-20 bg-white/10 rounded-full blur-xl"></div>
      
      <div class="relative flex items-center gap-3">
        <div class="p-2 rounded-lg bg-white/10 backdrop-blur-md border border-white/20 text-white shadow-inner flex items-center justify-center">
          <component
            v-if="overview.icon && typeof overview.icon !== 'string'"
            :is="overview.icon"
            class="w-4 h-4"
          />
          <i v-else-if="overview.icon" :class="[overview.icon, 'text-sm']"></i>
        </div>
        
        <div>
          <h1 class="text-white text-xs font-semibold tracking-wider uppercase text-white/90">
            {{ overview.title }}
          </h1>
          <p class="text-[11px] text-white/70 font-medium">
            {{ overview.meta.subtitle }}
          </p>
        </div>
      </div>
    </div>

    <div class="p-5 flex-1 flex flex-col justify-between gap-4">
      <div class="flex items-baseline justify-between gap-2">
        <span class="font-extrabold text-gray-900 text-2xl sm:text-3xl tracking-tight">
          {{ overview.value ? overview.value.toLocaleString() : '0' }}
        </span>
      </div>
    </div>
  </div>
</template>