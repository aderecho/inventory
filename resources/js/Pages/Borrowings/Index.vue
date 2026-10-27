<script setup>
import { ref, computed, watch } from "vue";
import { usePage, router } from "@inertiajs/vue3";
import { Head } from "@inertiajs/vue3";
import Layout from "@/Layouts/Layout.vue";
import PageHeader from "@/Components/PageHeader.vue";
import InventoryTable from "@/Components/InventoryTable.vue";
import SearchFilterBar from "@/Components/Filters/SearchFilterBar.vue";
import ReturnBorrowingItemsModal from "@/Components/Modals/ReturnBorrowingItemsModal.vue";
import Toast from "primevue/toast";
import { useToast } from "primevue/usetoast";
import { useLoading } from "@/Composables/useLoading";
import { usePermissions } from "@/Composables/usePermissions";

const { startLoading, stopLoading } = useLoading();
const { canReturnItems } = usePermissions();
const page = usePage();
const toast = useToast();

const borrowings = computed(() => page.props.borrowings || { data: [] });
const assetConditions = computed(() => page.props.assetConditions || []);
const counts = computed(() => page.props.counts || { borrowed: 0, overdue: 0 });

const search = ref("");
const status = ref("");

const statusFilter = [
    {
        label: "Status",
        options: [
            { label: "All loans", value: "" },
            { label: "Still borrowed", value: "borrowed" },
            { label: "Overdue", value: "overdue" },
            { label: "Returned", value: "returned" },
        ],
    },
];

const shortDate = (value) => {
    if (!value) return "No due date";

    const date = new Date(value);

    if (Number.isNaN(date.getTime())) return "—";

    return date.toLocaleDateString("en-PH", {
        year: "numeric",
        month: "short",
        day: "numeric",
    });
};

const statusBadge = (label) => {
    if (label === "Overdue") {
        return "text-red-700";
    }

    if (label === "Partly returned") {
        return "text-amber-700";
    }

    if (label === "Returned") {
        return "text-gray-600";
    }

    return "text-[#2E7D32]";
};

const typeBadge = (type) => {
    if (type === "staff") return "Staff (PUSO)";
    if (type === "student") return "Student";

    return "Outsider";
};

const columns = [
    { label: "", key: "select_all" },
    { label: "Reference", key: "reference_code" },
    { label: "Borrower", key: "borrower_name" },
    {
        label: "Type",
        key: "borrower_type",
        format: (value) => typeBadge(value),
    },
    { label: "Borrowed from", key: "borrowed_from_label" },
    {
        label: "Items",
        key: "items",
        format: (value) => {
            const lines = value ?? [];
            const total = lines.length;
            const out = lines.filter((line) => !line.returned_at).length;

            // Returned lines are not "out" any more: show what is left on the
            // loan, and the original count only when they differ.
            if (out === total) {
                return total + (total === 1 ? " item" : " items");
            }

            return out + " of " + total + " out";
        },
    },
    {
        label: "Borrowed",
        key: "borrowed_at",
        format: (value) => shortDate(value),
    },
    {
        label: "Due",
        key: "due_at",
        format: (value) => shortDate(value),
    },
    {
        label: "Status",
        key: "status_label",
        format: (value) =>
            '<span class="text-[11px] font-semibold ' +
            statusBadge(value) +
            '">' +
            value +
            "</span>",
    },
    { label: "Action", key: "action" },
];

const rowActions = computed(() => {
    const actions = [];

    if (canReturnItems.value) actions.push("return");

    return actions;
});

// SELECTION
const selectedMap = ref(new Map());

const selectedIds = computed(() => Array.from(selectedMap.value.keys()));
const selectedDetails = computed(() => Array.from(selectedMap.value.values()));

function handleSelectionChanged(ids, currentPageRows = []) {
    const idSet = new Set(ids);
    const next = new Map(selectedMap.value);

    currentPageRows.forEach((row) => {
        if (idSet.has(row.id)) next.set(row.id, row);
    });

    for (const key of next.keys()) {
        if (!idSet.has(key)) next.delete(key);
    }

    idSet.forEach((id) => {
        if (!next.has(id) && selectedMap.value.has(id)) {
            next.set(id, selectedMap.value.get(id));
        }
    });

    selectedMap.value = next;
}

function clearSelection() {
    selectedMap.value = new Map();
}

// Open lines of a ticket: everything that has not come back yet.
function openLines(rows) {
    return rows.flatMap((row) =>
        (row.items ?? [])
            .filter((line) => !line.returned_at)
            .map((line) => ({
                id: line.id,
                property_number: line.inventory_item?.property_number ?? "—",
                item_name: line.inventory_item?.item_name ?? "",
                reference_code: row.reference_code,
                borrowing_id: row.id,
                condition_out_id: line.condition_out_id,
            })),
    );
}

// RETURN
const showReturnModal = ref(false);
const returnLines = ref([]);

function openReturn(rows) {
    returnLines.value = openLines(rows);

    if (!returnLines.value.length) {
        toast.add({
            severity: "info",
            summary: "Nothing to take back",
            detail: "Every item on that loan has already been returned.",
            life: 3000,
        });

        return;
    }

    showReturnModal.value = true;
}

// A row action takes back that loan's open lines; the modal lets you untick
// the ones staying on the reference.
function openReturnFor(row) {
    openReturn([row]);
}

function confirmReturn(payload) {
    startLoading(
        "Taking items back...",
        "Updating each loan and putting the items back to available.",
    );

    router.patch(route("borrowings.return-items"), payload, {
        preserveScroll: true,
        onSuccess: () => {
            showReturnModal.value = false;
            clearSelection();
        },
        onFinish: () => stopLoading(),
    });
}

// The selection can span references: the modal lists every open line and the
// endpoint settles each ticket on its own.
function openBatchReturn() {
    if (!selectedDetails.value.length) return;

    openReturn(selectedDetails.value);
}

watch(
    () => page.props.flash,
    (flash) => {
        if (!flash) return;

        if (flash.success) {
            toast.add({
                severity: "success",
                summary: "Success",
                detail: flash.success,
                life: 3500,
            });
        }

        if (flash.error) {
            toast.add({
                severity: "error",
                summary: "Error",
                detail: flash.error,
                life: 5000,
            });
        }
    },
    { immediate: true },
);
</script>

<template>
    <Head title="UP | Borrowings" />

    <Layout>
        <Toast />

        <ReturnBorrowingItemsModal
            :show="showReturnModal"
            :lines="returnLines"
            :assetConditions="assetConditions"
            @submit="confirmReturn"
            @close="showReturnModal = false"
        />

        <PageHeader title="Borrowings" />

        <div class="w-full h-full">
            <div class="mt-6 rounded-xl border border-gray-200 bg-white p-3 shadow-sm">
                <div class="flex flex-col gap-3 xl:flex-row xl:items-end xl:justify-between">
                    <div class="flex flex-wrap items-center gap-2">
                        <button
                            v-if="canReturnItems"
                            type="button"
                            :disabled="selectedIds.length === 0"
                            @click="openBatchReturn"
                            class="flex items-center gap-2 rounded-md bg-[#0E6021] px-3 py-2 text-xs text-white transition-colors hover:bg-[#19703a] disabled:cursor-not-allowed disabled:opacity-40 sm:text-sm"
                        >
                            <i class="fa-solid fa-box-open"></i>
                            <span>Take Back Selected</span>
                            <span
                                class="inline-flex h-5 w-7 shrink-0 items-center justify-center rounded-full bg-white/25 text-[11px] tabular-nums"
                            >
                                {{ selectedIds.length }}
                            </span>
                        </button>

                        <span
                            class="flex items-center gap-2 rounded-full border border-[#005740]/20 bg-[#005740]/[0.06] px-3 py-1.5 text-xs font-semibold text-[#005740]"
                        >
                            {{ counts.borrowed }} out on loan
                        </span>

                        <span
                            v-if="counts.overdue"
                            class="flex items-center gap-2 rounded-full border border-red-200 bg-red-50 px-3 py-1.5 text-xs font-semibold text-red-600"
                        >
                            {{ counts.overdue }} overdue
                        </span>
                    </div>

                    <div class="w-full xl:max-w-xl">
                        <SearchFilterBar
                            :search="search"
                            :acknowledgement_status="status"
                            :acknowledgementFilter="statusFilter"
                            @update:search="search = $event"
                            @update:acknowledgement_status="status = $event"
                            :mode="'borrowings'"
                        />
                    </div>
                </div>
            </div>

            <InventoryTable
                :columns="columns"
                :rows="borrowings"
                :module="'borrowings'"
                :actions="rowActions"
                :selected="selectedIds"
                @selection-changed="handleSelectionChanged"
                @return="openReturnFor($event)"
            />
        </div>
    </Layout>
</template>
