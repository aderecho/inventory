<script setup>
import { router } from '@inertiajs/vue3';
import { watch, ref, computed } from 'vue';
import { debounce } from 'lodash';

const props = defineProps({
    unitCostOptions: { type: Array, default: () => [] },
    filterStatus: { type: Array, default: () => [] },
    search: { type: String, default: '' },
    status: { type: String, default: '' },
    cost_range: { type: String, default: '' },
    mode: { type: String, default: 'inventory' },
});

const search = ref(props.search || '');
const cost_range = ref(props.cost_range || '');
const status = ref(props.status || '');

const emit = defineEmits(['update:search', 'update:status', 'update:cost_range']);

//-----------------INVENTORY---------------------------
function fetchInventory(params = {}) {
    router.get('/inventory/items', params, {
        preserveState: true,
        preserveScroll: true,
        only: ['items'],
    });
}
const debouncedFetchInventory = debounce(fetchInventory, 300);

//-----------------DASHBOARD----------------------------
function fetchDashboardSearch(searchValue) {
    router.get('/dashboard', { search: searchValue }, {
        preserveState: true,
        replace: true,
        preserveScroll: true,
    });
}
const debouncedFetchDashboard = debounce(fetchDashboardSearch, 300);

//----------------ACKNOWLEDGEMENT FETCH-----------------
function fetchAcknowledgmentSearch(searchValue, cost, stat) {
    router.get(
        '/inventory/acknowledgements',
        {
            search: searchValue,
            cost_range: cost,
            status: stat,
        },
        {
            preserveState: true,
            replace: true,
            preserveScroll: true,
        }
    );
}
const debouncedFetchAcknowledgement = debounce(fetchAcknowledgmentSearch, 300);

//------------------TRANSACTIONS----------------------
function fetchTransactionSearch(params = {}) {
    router.get('/inventory/transactions', params, {
        preserveState: true,
        replace: true,
        preserveScroll: true,
    });
}
const debouncedFetchTransaction = debounce(fetchTransactionSearch, 300);

//------------------REPORTS----------------------
function fetchReportSearch(params = {}) {
    router.get('/report', params, {
        preserveState: true,
        replace: true,
        preserveScroll: true,
    });
}

const debouncedFetchReport = debounce(fetchReportSearch, 300);

//------------------SUPPLIERS----------------------
function fetchSuppliersSearch(searchValue) {
    router.get('/suppliers', { search: searchValue }, { preserveState: true, replace: true, preserveScroll: true });
}
const debouncedFetchSuppliers = debounce(fetchSuppliersSearch, 300);

//------------------CATEGORIES----------------------
function fetchCategoriesSearch(searchValue) {
    router.get('/categories', { search: searchValue }, { preserveState: true, replace: true, preserveScroll: true });
}
const debouncedFetchCategories = debounce(fetchCategoriesSearch, 300);

//------------------ACCOUNTABLE PERSON----------------------
function fetchAccountablePersonSearch(searchValue) {
    router.get('/accountable-person', { search: searchValue }, { preserveState: true, replace: true, preserveScroll: true });
}
const debouncedFetchAccountablePerson = debounce(fetchAccountablePersonSearch, 300);

//------------------SEARCH WATCHER---------------------
watch(search, (value) => {

    if (props.mode === "inventory") {
        debouncedFetchInventory({
            search: value,
            cost_range: cost_range.value,
            status: status.value
        });
    }

    else if (props.mode === "dashboard") {
        debouncedFetchDashboard(value);
    }

    else if (props.mode === "acknowledgements") {
        debouncedFetchAcknowledgement(value, cost_range.value, status.value);
    }

    else if (props.mode === "transactions") {
        debouncedFetchTransaction({
            search: value,
            cost_range: cost_range.value,
            status: status.value
        });
    }

    else if (props.mode === "reports") {
        debouncedFetchReport({
            search: value,
            cost_range: cost_range.value,
            status: status.value
        });
    }

    else if (props.mode === "suppliers") {
        debouncedFetchSuppliers(value);
    }

    else if (props.mode === "accountable-person") {
        debouncedFetchAccountablePerson(value);
    }

    else if (props.mode === "categories") {
        debouncedFetchCategories(value);
    }

    emit("update:search", value);
});

//--------------------STATUS WATCHER----------------------
watch(status, (value) => {

    if (props.mode === "inventory") {
        debouncedFetchInventory({   
            search: search.value,
            cost_range: cost_range.value,
            status: value
        });
    }

    else if (props.mode === "transactions") {
        debouncedFetchTransaction({
            search: search.value,
            cost_range: cost_range.value,
            status: value
        });
    }

    else if (props.mode === "acknowledgements") {
        debouncedFetchAcknowledgement(search.value, cost_range.value, value);
    }

    emit("update:status", value);
});

//-----------------COST RANGE WATCHER-------------------------
watch(cost_range, (value) => {

    if (props.mode === "inventory") {
        debouncedFetchInventory({
            search: search.value,
            cost_range: value,
            status: status.value
        });
    }

    else if (props.mode === "transactions") {
        debouncedFetchTransaction({
            search: search.value,
            cost_range: value,
            status: status.value
        });
    }

    else if (props.mode === "acknowledgements") {
        debouncedFetchAcknowledgement(search.value, value, status.value);
    }

    emit("update:cost_range", value);
});

//-------------------DYNAMIC PLACEHOLDER---------------------
const searchPlaceholder = computed(() => {
    switch (props.mode) {
        case 'inventory':
            return 'Search Item, Property Number, Serial Number...';
        case 'acknowledgements':
            return 'Search Item to assign';
        case 'transactions':
            return 'Search';
        default:
            return 'Search item';
    }
});

</script>


<template>
    <div class="flex flex-col sm:flex-row items-start sm:items-center justify-end gap-4 w-full sm:w-auto">

        <!-- UNIT COST -->
        <div class="flex flex-col w-full sm:w-auto" v-for="(group, gIndex) in unitCostOptions" :key="gIndex">
            <label class="text-xs font-bold text-[#3B3B3B] mb-1 sm:mb-0">{{ group.label }}</label>
            <select
                v-model="cost_range"
                class="h-8 sm:h-9 w-full sm:w-36 text-xs rounded-md text-gray-600 border focus:ring-[#850038] focus:outline-none focus:border-[#850038]">
                
                <option
                    v-for="(option, uIndex) in group.options"
                    :key="uIndex"
                    :value="option.value"
                >
                    {{ option.label }}
                </option>
            </select>
        </div>

        <!-- STATUS -->
        <div class="flex flex-col w-full sm:w-auto" v-for="(stats, gIndex) in filterStatus" :key="gIndex">
            <label class="text-xs text-[#3B3B3B] font-bold mb-1 sm:mb-0">{{ stats.label }}</label>
            <select
                v-model="status"
                class="h-8 sm:h-9 w-full sm:w-36 text-xs rounded-md text-gray-600 border focus:ring-[#850038] focus:outline-none focus:border-[#850038]"
            >
                <option value="">Select All</option>
                <option
                    v-for="(option, sIndex) in stats.options"
                    :key="sIndex"
                    :value="option.value"
                >
                    {{ option.label }}
                </option>
            </select>
        </div>

        <!-- SEARCH BAR -->
        <div class="w-full sm:w-auto mt-3 relative">
            <span class="absolute inset-y-0 left-3 flex items-center text-gray-400">
                <i class="fa-solid fa-magnifying-glass"></i>
            </span>
            <input
                v-model="search"
                type="search"
                :placeholder="searchPlaceholder"
                class="w-full sm:w-64 md:w-96 h-9 sm:h-10 text-[#3B3B3B] rounded-full pl-10 pr-3 border text-sm 
                focus:ring-[#850038] focus:outline-none focus:border-[#850038]"
            />
        </div>

    </div>
</template>
