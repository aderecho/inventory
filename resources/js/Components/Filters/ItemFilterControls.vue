<script setup>
import { router } from "@inertiajs/vue3";
import { watch, ref, computed, onMounted, nextTick } from "vue";
import { debounce } from "lodash";

const props = defineProps({
    unitCostOptions: { type: Array, default: () => [] },
    filterStatus: { type: Array, default: () => [] },
    acknowledgementFilter: { type: Array, default: () => [] },
    search: { type: String, default: "" },
    status: { type: String, default: "" },
    cost_range: { type: String, default: "" },
    acknowledgement_status: { type: String, default: "" },
    mode: { type: String, default: "inventory" },
});

const search = ref(props.search || "");
const cost_range = ref(props.cost_range || "");
const status = ref(props.status || "");
const acknowledgement_status = ref(props.acknowledgement_status || "");

const emit = defineEmits([
    "update:search",
    "update:status",
    "update:cost_range",
    "update:acknowledgement_status",
]);

// Local storage for filters & searching
const storageKey = `filters-${props.mode}`;

// While true, ref assignments below are restoring state (from localStorage)
// rather than a user edit, so the per-field watchers must not fire a fetch
// for them — otherwise every restore triggers a *second*, debounced request
// on top of the explicit one issued right after restoring.
let isRestoring = false;

onMounted(async () => {
    const saved = localStorage.getItem(storageKey);

    if (!saved) return;

    const filters = JSON.parse(saved);
    const restored = {
        search: filters.search ?? "",
        status: filters.status ?? "",
        cost_range: filters.cost_range ?? "",
        acknowledgement_status: filters.acknowledgement_status ?? "",
    };

    // Inertia already rendered `items`/`disposal` for the current URL. If the
    // saved filters are identical to what's already showing, there is
    // nothing to restore — skip the fetch entirely instead of firing a
    // redundant request on every plain page load.
    const unchanged =
        restored.search === (search.value ?? "") &&
        restored.status === (status.value ?? "") &&
        restored.cost_range === (cost_range.value ?? "") &&
        restored.acknowledgement_status ===
            (acknowledgement_status.value ?? "");

    if (unchanged) return;

    isRestoring = true;
    search.value = restored.search;
    status.value = restored.status;
    cost_range.value = restored.cost_range;
    acknowledgement_status.value = restored.acknowledgement_status;

    // watch() callbacks are queued and flushed as a microtask, not run
    // synchronously on assignment — nextTick() waits for that flush before
    // we drop the guard, otherwise it's already false by the time the
    // watchers actually execute and the double-fetch comes right back.
    await nextTick();
    isRestoring = false;

    if (props.mode === "inventory") {
        fetchInventory({
            search: search.value,
            cost_range: cost_range.value,
            status: status.value,
            acknowledgement_status: acknowledgement_status.value,
        });
    } else if (props.mode === "disposal") {
        fetchDisposalSearch({
            search: search.value,
            cost_range: cost_range.value,
            status: status.value,
        });
    } else if (props.mode === "inspection") {
        fetchInspectionSearch({
            search: search.value,
            cost_range: cost_range.value,
            status: status.value,
        });
    }
});

function saveFilters() {
    localStorage.setItem(
        storageKey,
        JSON.stringify({
            search: search.value,
            status: status.value,
            cost_range: cost_range.value,
            acknowledgement_status: acknowledgement_status.value,
        }),
    );
}

watch(
    [search, status, cost_range, acknowledgement_status],
    () => {
        if (isRestoring) return;
        saveFilters();
    },
    { deep: true },
);
// End local storage

//-----------------INVENTORY---------------------------
function fetchInventory(params = {}) {
    router.get("/inventory/items", params, {
        preserveState: true,
        preserveScroll: true,
        replace: true,
        only: ["items"],
    });
}
const debouncedFetchInventory = debounce(fetchInventory, 1000);

//----------------ACKNOWLEDGEMENT FETCH-----------------
function fetchAcknowledgmentSearch(searchValue, cost, stat) {
    router.get(
        "/acknowledgements",
        {
            search: searchValue,
            cost_range: cost,
            status: stat,
        },
        {
            preserveState: true,
            replace: true,
            preserveScroll: true,
        },
    );
}
const debouncedFetchAcknowledgement = debounce(fetchAcknowledgmentSearch, 1000);

//------------------TRANSACTIONS----------------------
function fetchTransactionSearch(params = {}) {
    router.get("/inventory/transactions", params, {
        preserveState: true,
        replace: true,
        preserveScroll: true,
    });
}
const debouncedFetchTransaction = debounce(fetchTransactionSearch, 1000);

//------------------REPORTS----------------------
function fetchReportSearch(params = {}) {
    router.get("/report", params, {
        preserveState: true,
        replace: true,
        preserveScroll: true,
    });
}

const debouncedFetchReport = debounce(fetchReportSearch, 1000);

//------------------DISPOSAL----------------------
function fetchDisposalSearch(params = {}) {
    router.get("/disposal", params, {
        preserveState: true,
        replace: true,
        preserveScroll: true,
    });
}
const debouncedFetchDisposal = debounce(fetchDisposalSearch, 1000);

//------------------INSPECTION----------------------
function fetchInspectionSearch(params = {}) {
    router.get("/inspection", params, {
        preserveState: true,
        replace: true,
        preserveScroll: true,
    });
}
const debouncedFetchInspection = debounce(fetchInspectionSearch, 1000);

//------------------SUPPLIERS----------------------
function fetchSuppliersSearch(searchValue) {
    router.get(
        "/suppliers",
        { search: searchValue },
        { preserveState: true, replace: true, preserveScroll: true },
    );
}
const debouncedFetchSuppliers = debounce(fetchSuppliersSearch, 1000);

//------------------CATEGORIES----------------------
function fetchCategoriesSearch(searchValue) {
    router.get(
        "/categories",
        { search: searchValue },
        { preserveState: true, replace: true, preserveScroll: true },
    );
}
const debouncedFetchCategories = debounce(fetchCategoriesSearch, 1000);

//------------------ACCOUNTABLE PERSON----------------------
function fetchAccountablePersonSearch(searchValue) {
    router.get(
        "/accountable-person",
        { search: searchValue },
        { preserveState: true, replace: true, preserveScroll: true },
    );
}
const debouncedFetchAccountablePerson = debounce(
    fetchAccountablePersonSearch,
    300,
);

//------------------USERS----------------------
function fetchUsersSearch(searchValue, statusValue) {
    router.get(
        route("user_management.index"),
        {
            search: searchValue,
            status: statusValue !== "" ? statusValue : undefined,
        },
        { preserveState: true, replace: true, preserveScroll: true },
    );
}
const debouncedFetchUsers = debounce(fetchUsersSearch, 1000);

//------------------SEARCH WATCHER---------------------
watch(search, (value) => {
    if (!isRestoring) {
        if (props.mode === "inventory") {
            debouncedFetchInventory({
                search: value,
                cost_range: cost_range.value,
                status: status.value,
                acknowledgement_status: acknowledgement_status.value,
            });
        } else if (props.mode === "acknowledgements") {
            debouncedFetchAcknowledgement(
                value,
                cost_range.value,
                status.value,
            );
        } else if (props.mode === "transactions") {
            debouncedFetchTransaction({
                search: value,
                cost_range: cost_range.value,
                status: status.value,
            });
        } else if (props.mode === "reports") {
            debouncedFetchReport({
                search: value,
                cost_range: cost_range.value,
                status: status.value,
            });
        } else if (props.mode === "disposal") {
            debouncedFetchDisposal({
                search: value,
                cost_range: cost_range.value,
                status: status.value,
            });
        } else if (props.mode === "suppliers") {
            debouncedFetchSuppliers(value);
        } else if (props.mode === "accountable-person") {
            debouncedFetchAccountablePerson(value);
        } else if (props.mode === "categories") {
            debouncedFetchCategories(value);
        } else if (props.mode === "users") {
            debouncedFetchUsers(value, status.value);
        } else if (props.mode === "inspection") {
            debouncedFetchInspection({
                search: value,
                cost_range: cost_range.value,
                status: status.value,
                acknowledgement_status: acknowledgement_status.value,
            });
        }
    }

    emit("update:search", value);
});

//--------------------STATUS WATCHER----------------------
watch(status, (value) => {
    if (!isRestoring) {
        if (props.mode === "inventory") {
            debouncedFetchInventory({
                search: search.value,
                cost_range: cost_range.value,
                status: value,
                acknowledgement_status: acknowledgement_status.value,
            });
        } else if (props.mode === "transactions") {
            debouncedFetchTransaction({
                search: search.value,
                cost_range: cost_range.value,
                status: value,
            });
        } else if (props.mode === "acknowledgements") {
            debouncedFetchAcknowledgement(
                search.value,
                cost_range.value,
                value,
            );
        } else if (props.mode === "disposal") {
            debouncedFetchDisposal({
                search: search.value,
                cost_range: cost_range.value,
                status: value,
            });
        } else if (props.mode === "users") {
            debouncedFetchUsers(search.value, value);
        } else if (props.mode === "inspection") {
            debouncedFetchInspection({
                search: search.value,
                cost_range: cost_range.value,
                status: value,
                acknowledgement_status: acknowledgement_status.value,
            });
        }
    }

    emit("update:status", value);
});

//-----------------COST RANGE WATCHER-------------------------
watch(cost_range, (value) => {
    if (!isRestoring) {
        if (props.mode === "inventory") {
            debouncedFetchInventory({
                search: search.value,
                cost_range: value,
                status: status.value,
                acknowledgement_status: acknowledgement_status.value,
            });
        } else if (props.mode === "transactions") {
            debouncedFetchTransaction({
                search: search.value,
                cost_range: value,
                status: status.value,
            });
        } else if (props.mode === "acknowledgements") {
            debouncedFetchAcknowledgement(search.value, value, status.value);
        } else if (props.mode === "disposal") {
            debouncedFetchDisposal({
                search: search.value,
                cost_range: value,
                status: status.value,
            });
        } else if (props.mode === "inspection") {
            debouncedFetchInspection({
                search: search.value,
                cost_range: value,
                status: status.value,
                acknowledgement_status: acknowledgement_status.value,
            });
        }
    }

    emit("update:cost_range", value);
});

//-----------------ACKNOWLEDGEMENT RANGE WATCHER-------------------------
watch(acknowledgement_status, (value) => {
    if (!isRestoring && props.mode === "inventory") {
        debouncedFetchInventory({
            search: search.value,
            cost_range: cost_range.value,
            status: status.value,
            acknowledgement_status: value,
        });
    } else if (!isRestoring && props.mode === "inspection") {
        debouncedFetchInspection({
            search: search.value,
            cost_range: cost_range.value,
            status: status.value,
            acknowledgement_status: value,
        });
    }

    emit("update:acknowledgement_status", value);
});

//-------------------DYNAMIC PLACEHOLDER---------------------
const searchPlaceholder = computed(() => {
    switch (props.mode) {
        case "inventory":
            return "Search Item, Property Number, Serial Number...";
        case "acknowledgements":
            return "Search receipt...";
        case "transactions":
            return "Search";
        case "disposal":
            return "Search Item...";
        case "users":
            return "Search user...";
        case "suppliers":
            return "Search supplier...";
        case "categories":
            return "Search categories...";
        case "inspection":
            return "Search item...";
        default:
            return "Search item";
    }
});
</script>

<template>
    <div
        class="flex flex-col sm:flex-row items-start sm:items-center justify-end gap-4 w-full sm:w-auto"
    >
        <div
            class="flex flex-col w-full sm:w-auto"
            v-for="(group, gIndex) in acknowledgementFilter"
            :key="'ack-' + gIndex"
        >
            <label class="text-xs text-[#3B3B3B] font-bold mb-1 sm:mb-0">
                {{ group.label }}
            </label>

            <select
                v-model="acknowledgement_status"
                class="h-8 sm:h-9 w-full sm:w-40 text-xs rounded-md text-gray-600 border focus:ring-[#850038] focus:outline-none focus:border-[#850038]"
            >
                <option
                    v-for="(option, index) in group.options"
                    :key="index"
                    :value="option.value"
                >
                    {{ option.label }}
                </option>
            </select>
        </div>
        <!-- UNIT COST -->
        <div
            class="flex flex-col w-full sm:w-auto"
            v-for="(group, gIndex) in unitCostOptions"
            :key="gIndex"
        >
            <label class="text-xs font-bold text-[#3B3B3B] mb-1 sm:mb-0">{{
                group.label
            }}</label>
            <select
                v-model="cost_range"
                class="h-8 sm:h-9 w-full sm:w-36 text-xs rounded-md text-gray-600 border focus:ring-[#850038] focus:outline-none focus:border-[#850038]"
            >
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
        <div
            class="flex flex-col w-full sm:w-auto"
            v-for="(stats, gIndex) in filterStatus"
            :key="gIndex"
        >
            <label class="text-xs text-[#3B3B3B] font-bold mb-1 sm:mb-0">{{
                stats.label
            }}</label>
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
            class="w-full sm:w-64 md:w-96 h-9 sm:h-10 text-[#3B3B3B] rounded-full pl-10 pr-3 border text-sm focus:ring-[#850038] focus:outline-none focus:border-[#850038]"
        />
    </div>
</template>
