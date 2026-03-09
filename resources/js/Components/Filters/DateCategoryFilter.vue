<script setup>
import { usePage, router } from '@inertiajs/vue3';
import { ref, watch } from 'vue';

const props = defineProps({
    selectedStatus: String,
});

// --- Define search so watchers do not break ---
const search = ref('');

// --- Status ---
const selectedStatus = ref(props.selectedStatus || '');
watch(() => props.selectedStatus, value => {
    selectedStatus.value = value;
});

// --- Dates from backend ---
const page = usePage();
const fromDate = ref(page.props.fromDate || '');
const toDate = ref(page.props.toDate || '');


// --- STATUS WATCHER ---
watch(selectedStatus, (status) => {
    let quantityFilter = '';

    if (status === '1') quantityFilter = 'in_stock';         
    else if (status === '0') quantityFilter = 'out_of_stock';
    else if (status === '2') quantityFilter = 'low_stock';  

    router.get(route('reports.index'), {
        search: search.value,
        quantity: quantityFilter,
        from: fromDate.value,
        to: toDate.value,
    }, {
        preserveState: true,    
        replace: true,
        preserveScroll: true,
    });
});


// --- DATE WATCHER ---
watch([fromDate, toDate], () => {
    router.get(route('reports.index'), {
        search: search.value,
        quantity: resolveQuantityFilter(selectedStatus.value),
        from: fromDate.value,
        to: toDate.value,
    }, {
        preserveState: true,
        replace: true,
        preserveScroll: true,
    });
});

</script>

<template>
   <div class="flex flex-col lg:flex-row flex-wrap items-start lg:items-end gap-4">
         <!-- FROM -->
        <div class="flex flex-col w-full sm:w-1/2 md:w-auto">
            <label class="text-sm text-[#3B3B3B] font-bold mb-1">From</label>
            <input type="date" v-model="fromDate"
                class="h-[2.8rem] w-full sm:w-48 px-3 text-sm rounded-md text-black border cursor-pointer focus:ring-2 focus:ring-blue-400 focus:outline-none" />
        </div>

        <!-- TO -->
        <div class="flex flex-col w-full sm:w-1/2 md:w-auto">
            <label class="text-sm text-[#3B3B3B] font-bold mb-1">To</label>
            <input type="date" v-model="toDate"
                class="h-[2.8rem] w-full sm:w-48 px-3 text-sm rounded-md text-black border cursor-pointer focus:ring-2 focus:ring-blue-400 focus:outline-none" />
        </div>

        <!--STOCK STATUS -->
        <div class="flex flex-col w-full sm:w-1/2 md:w-auto">
            <label class="text-sm text-[#3B3B3B] font-bold mb-1">Stock Status</label>
            <select v-model="selectedStatus"
                class="h-[2.8rem] w-full sm:w-40 px-3 text-sm rounded-md text-black border cursor-pointer focus:ring-2 focus:ring-blue-400 focus:outline-none">
                <option value="">All</option>
                <option value="1">In Stock</option>
                <option value="0">Out of Stock</option>
                <option value="2">Low Stock</option>
            </select>
        </div>
   </div>
</template>