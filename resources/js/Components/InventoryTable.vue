<script setup>
import { router } from "@inertiajs/vue3";
import {
    defineProps,
    computed,
    ref,
    watch,
    onMounted,
    onBeforeUnmount,
    nextTick,
} from "vue";
import TableCell from "./TableCell.vue";
import TableCheckbox from "./TableCheckbox.vue";
import PrintButton from "@/Components/Buttons/PrintButton.vue";

const props = defineProps({
    rooms: Array,
    columns: Array,
    rows: Object,
    users: Array,
    module: {
        type: String,
        default: "",
    },
    actions: {
        type: Array,
        default: () => [],
    },
    selected: {
        type: Array,
        default: () => [],
    },
    // Emit this action when a row is clicked, for tables that have no
    // checkbox column. Leave empty on tables where clicking selects rows.
    rowAction: {
        type: String,
        default: "",
    },
    // Tooltip shown while hovering a row that opens something. Falls back to
    // "Click to <action>", e.g. "Click to view".
    rowActionTooltip: {
        type: String,
        default: "",
    },
    // Tooltip shown while hovering a row that ticks a checkbox.
    selectTooltip: {
        type: String,
        default: "",
    },
    // Tooltip shown while hovering the select-all checkbox in the header.
    selectAllTooltip: {
        type: String,
        default: "",
    },
});

const emit = defineEmits([
    "view",
    "edit",
    "delete",
    "print",
    "restore",
    "return",
    "force-delete",
    "update:selected",
    "selection-changed",
]);

// PAGINATE
const goToPage = (url) => {
    if (!url) return;
    router.visit(url, {
        preserveState: true,
        replace: true,
        preserveScroll: true,
    });
};

function getValue(obj, path) {
    const result = path.split(".").reduce((acc, key) => acc?.[key], obj);
    return result;
}

function pageLabel(label) {
    return label
        .replace("&laquo;", "")
        .replace("&raquo;", "")
        .replace("Previous", "Prev")
        .trim();
}

// SELECTABLE ROWS/IDS
const selectedIDs = ref([...props.selected]);

watch(
    () => props.selected,
    (val) => {
        const incoming = val ?? [];
        const same =
            incoming.length === selectedIDs.value.length &&
            incoming.every((id) => selectedIDs.value.includes(id));

        if (!same) {
            selectedIDs.value = [...incoming];
        }
    },
);

const allSelected = computed(() => {
    return (
        props.rows?.data?.length > 0 &&
        props.rows.data.every((i) => selectedIDs.value.includes(i.id))
    );
});

const someSelected = computed(() => {
    return selectedIDs.value.length > 0 && !allSelected.value;
});

// Clicking a row ticks it when the table has a checkbox column.
const selectionEnabled = computed(() =>
    (props.columns ?? []).some((col) => col.key === "select_all"),
);

// Tables without checkboxes can make the whole row open something.
const rowIsClickable = computed(
    () => Boolean(props.rowAction) && !selectionEnabled.value,
);

// What clicking a row does, as a hint for the cursor tooltip. Rows that tick
// a checkbox say so; rows that open something name the action.
const clickHint = computed(() => {
    if (selectionEnabled.value) {
        return props.selectTooltip || "Click to select";
    }

    if (!props.rowAction) {
        return "";
    }

    if (props.rowActionTooltip) {
        return props.rowActionTooltip;
    }

    return (
        "Click to " +
        props.rowAction.charAt(0).toLowerCase() +
        props.rowAction.slice(1).toLowerCase()
    );
});

// The hint follows the cursor. It renders through a Teleport at the end of
// <body> so neither a scrolling table wrapper nor the sticky action column
// can clip it. Rows and the select-all box share it.
const tooltipText = ref("");
const tooltipVisible = ref(false);
const tooltipX = ref(0);
const tooltipY = ref(0);

const selectAllHint = computed(() => props.selectAllTooltip || "Select all");

// Rough box size, only used to keep the pill inside the viewport near an edge.
const TOOLTIP_WIDTH = 130;
const TOOLTIP_HEIGHT = 30;
const TOOLTIP_GAP = 16;

const tooltipStyle = computed(() => {
    const viewportWidth = window.innerWidth;
    const viewportHeight = window.innerHeight;

    let left = tooltipX.value + TOOLTIP_GAP;
    let top = tooltipY.value + TOOLTIP_GAP;

    if (left + TOOLTIP_WIDTH > viewportWidth - 8) {
        left = tooltipX.value - TOOLTIP_WIDTH - 8;
    }

    if (top + TOOLTIP_HEIGHT > viewportHeight - 8) {
        top = tooltipY.value - TOOLTIP_HEIGHT - 8;
    }

    return {
        left: `${Math.max(8, left)}px`,
        top: `${Math.max(8, top)}px`,
    };
});

function showTooltip(text, event) {
    if (!text) {
        return;
    }

    tooltipText.value = text;
    tooltipX.value = event.clientX;
    tooltipY.value = event.clientY;
    tooltipVisible.value = true;
}

function onRowMouseMove(event) {
    // The action buttons have their own titles; showing both at once is noise.
    if (!clickHint.value || event.target?.closest?.("[data-row-actions]")) {
        hideTooltip();
        return;
    }

    showTooltip(clickHint.value, event);
}

function onSelectAllMouseMove(event) {
    showTooltip(selectAllHint.value, event);
}

function hideTooltip() {
    tooltipVisible.value = false;
}

function onRowClick(item) {
    if (selectionEnabled.value) {
        toggleCheck(item);
        return;
    }

    if (rowIsClickable.value) {
        hideTooltip();
        emit(props.rowAction, item);
    }
}

function isSelected(item) {
    return selectedIDs.value.includes(item.id);
}

// Opaque row colours, so the pinned Action column can match the row behind it
// (a translucent tint would let the scrolled columns show through).
function rowSurface(item, index) {
    if (isSelected(item)) {
        return "bg-[#EDF3F2]";
    }

    return index % 2 === 0 ? "bg-white" : "bg-gray-50";
}

// Zebra striping for a light, readable table; selected rows keep the brand tint.
function rowClass(item, index) {
    return rowSurface(item, index);
}

// Keeps the Action column in view while the other columns scroll behind it.
function cellExtra(col, item, index) {
    const classes = [col.cellClass ?? ""];

    if (col.key === "action") {
        classes.push(
            "sticky right-0 z-10 transition-colors group-hover:bg-[#F2F7F5] shadow-[-8px_0_10px_-10px_rgba(15,23,42,0.35)]",
            rowSurface(item, index),
        );
    }

    return classes.filter(Boolean).join(" ");
}

// ------------------------------------------------------------------
// COLUMN VISIBILITY (remembered per module)
// ------------------------------------------------------------------
// The checkbox and action columns are always shown.
const ALWAYS_VISIBLE = ["select_all", "action"];

// Hidden on a user's first visit; the picker lets them bring any of them back.
const DEFAULT_HIDDEN = ["serial_number", "po_number", "unit"];

const hideableColumns = computed(() =>
    (props.columns ?? []).filter(
        (col) => col.label && !ALWAYS_VISIBLE.includes(col.key),
    ),
);

const storageKey = computed(
    () => "table-columns:" + (props.module || "default"),
);

function readHiddenColumns() {
    try {
        const saved = window.localStorage.getItem(storageKey.value);

        if (saved) {
            const parsed = JSON.parse(saved);

            if (Array.isArray(parsed)) {
                return parsed;
            }
        }
    } catch (error) {
        // Unreadable storage: fall back to the defaults below.
    }

    return DEFAULT_HIDDEN.filter((key) =>
        (props.columns ?? []).some((col) => col.key === key),
    );
}

const hiddenColumns = ref(readHiddenColumns());
const showColumnPicker = ref(false);

function persistColumns(keys) {
    try {
        window.localStorage.setItem(storageKey.value, JSON.stringify(keys));
    } catch (error) {
        // Storage is optional; the picker still works for this page view.
    }
}

function toggleColumn(key) {
    const next = hiddenColumns.value.includes(key)
        ? hiddenColumns.value.filter((hidden) => hidden !== key)
        : [...hiddenColumns.value, key];

    hiddenColumns.value = next;
    persistColumns(next);
}

function showAllColumns() {
    hiddenColumns.value = [];
    persistColumns([]);
}

const visibleColumns = computed(() =>
    (props.columns ?? []).filter(
        (col) =>
            !hiddenColumns.value.includes(col.key) ||
            ALWAYS_VISIBLE.includes(col.key),
    ),
);

// ------------------------------------------------------------------
// HORIZONTAL OVERFLOW
// ------------------------------------------------------------------
// A horizontally scrollable wrapper is a scroll container, and a sticky
// header inside one can never reach the page scroller — so the wrapper is
// only switched on when the table really does not fit the card.
const tableAreaRef = ref(null);
const needsHorizontalScroll = ref(false);
let tableAreaObserver = null;

// A sideways-scrolling wrapper is its own scroll container, so a sticky header
// inside it measures its offset from the wrapper and drops below the rows
// instead of pinning under the toolbar. While the wrapper is on, the header is
// moved to where an invisible sticky marker just outside the wrapper sits:
// the browser pins that marker under the toolbar exactly as it would the header.
const theadRef = ref(null);
const stickyMarkerRef = ref(null);
const headerShift = ref(0);
let headerShiftFrame = null;

function updateHeaderShift() {
    headerShiftFrame = null;

    const area = tableAreaRef.value;
    const head = theadRef.value;
    const marker = stickyMarkerRef.value;

    if (!needsHorizontalScroll.value || !area || !head || !marker) {
        headerShift.value = 0;
        return;
    }

    const areaRect = area.getBoundingClientRect();
    const wanted = marker.getBoundingClientRect().top - areaRect.top;
    const lowest = areaRect.height - head.offsetHeight;

    headerShift.value = Math.max(0, Math.min(wanted, lowest));
}

function scheduleHeaderShift() {
    if (headerShiftFrame === null) {
        headerShiftFrame = window.requestAnimationFrame(updateHeaderShift);
    }
}

const headerCellTop = computed(() =>
    needsHorizontalScroll.value ? "0px" : "var(--table-sticky-top, 0px)",
);

watch(needsHorizontalScroll, scheduleHeaderShift);

function measureTableArea() {
    const el = tableAreaRef.value;

    if (!el) return;

    needsHorizontalScroll.value = el.scrollWidth > el.clientWidth + 1;
}

onMounted(() => {
    measureTableArea();
    nextTick(measureTableArea);

    if (typeof ResizeObserver !== "undefined" && tableAreaRef.value) {
        tableAreaObserver = new ResizeObserver(measureTableArea);
        tableAreaObserver.observe(tableAreaRef.value);
    }

    window.addEventListener("resize", measureTableArea);
    // Capture catches whichever element scrolls the page.
    document.addEventListener("scroll", scheduleHeaderShift, {
        capture: true,
        passive: true,
    });
    window.addEventListener("resize", scheduleHeaderShift);
});

onBeforeUnmount(() => {
    tableAreaObserver?.disconnect();
    window.removeEventListener("resize", measureTableArea);
    document.removeEventListener("scroll", scheduleHeaderShift, {
        capture: true,
    });
    window.removeEventListener("resize", scheduleHeaderShift);

    if (headerShiftFrame !== null) {
        window.cancelAnimationFrame(headerShiftFrame);
    }
});

// Column visibility and new rows both change how wide the table needs to be.
watch(
    [visibleColumns, () => props.rows],
    () => {
        nextTick(measureTableArea);
    },
    { deep: false },
);

const hiddenCount = computed(
    () =>
        hideableColumns.value.filter((col) =>
            hiddenColumns.value.includes(col.key),
        ).length,
);

function toggleSelectAll() {
    if (allSelected.value) {
        // Deselect only the items on the current page, keep selections
        // made on other pages intact.
        const currentPageIds = new Set(props.rows.data.map((i) => i.id));
        selectedIDs.value = selectedIDs.value.filter(
            (id) => !currentPageIds.has(id),
        );
    } else {
        // Merge current page ids into existing selection (avoid duplicates)
        const merged = new Set(selectedIDs.value);
        props.rows.data.forEach((i) => merged.add(i.id));
        selectedIDs.value = Array.from(merged);
    }

    emit("update:selected", selectedIDs.value);
    // Pass along the full current-page row objects so the parent can
    // build/maintain a details map without needing to look items up itself.
    emit("selection-changed", selectedIDs.value, props.rows.data);
}

function toggleCheck(item) {
    const id = item.id;

    if (selectedIDs.value.includes(id)) {
        selectedIDs.value = selectedIDs.value.filter((x) => x !== id);
    } else {
        selectedIDs.value.push(id);
    }

    emit("update:selected", selectedIDs.value);
    // Pass along the full row object(s) currently visible so the parent
    // can keep a details map in sync even across pagination.
    emit("selection-changed", selectedIDs.value, props.rows.data);
}
</script>

<template>
    <div
        class="mt-3 rounded-xl border border-gray-200 bg-white shadow-sm"
    >
        <!-- Toolbar -->
        <div
            class="flex items-center justify-between gap-3 border-b border-gray-100 px-4 py-2.5"
        >
            <p class="hidden text-[11px] text-gray-400 sm:block">
                <template v-if="selectionEnabled">
                    Click a row to select it.
                </template>
            </p>

            <div class="relative ml-auto">
                <button
                    type="button"
                    @click="showColumnPicker = !showColumnPicker"
                    class="flex items-center gap-2 rounded-lg border border-gray-200 px-3 py-1.5 text-xs font-medium text-gray-600 transition-colors hover:border-[#005740]/40 hover:bg-[#005740]/5 hover:text-[#005740]"
                >
                    <i class="fa-solid fa-table-columns"></i>
                    Columns
                    <span
                        v-if="hiddenCount"
                        class="rounded-full bg-[#005740]/10 px-1.5 text-[10px] font-semibold text-[#005740]"
                    >
                        {{ hiddenCount }} hidden
                    </span>
                </button>

                <!-- click-away layer -->
                <div
                    v-if="showColumnPicker"
                    class="fixed inset-0 z-20"
                    @click="showColumnPicker = false"
                ></div>

                <div
                    v-if="showColumnPicker"
                    class="absolute right-0 z-30 mt-2 w-64 overflow-hidden rounded-xl border border-gray-200 bg-white shadow-lg"
                >
                    <div
                        class="flex items-center justify-between border-b border-gray-100 px-4 py-2.5"
                    >
                        <p
                            class="text-[11px] font-semibold uppercase tracking-wider text-gray-500"
                        >
                            Show columns
                        </p>
                        <button
                            type="button"
                            @click="showAllColumns"
                            class="text-[11px] font-semibold text-[#005740] hover:underline"
                        >
                            Show all
                        </button>
                    </div>

                    <ul class="max-h-72 overflow-y-auto py-1">
                        <li
                            v-for="col in hideableColumns"
                            :key="col.key"
                            class="flex items-center gap-3 px-4 py-2 transition-colors hover:bg-gray-50"
                        >
                            <TableCheckbox
                                :checked="!hiddenColumns.includes(col.key)"
                                tone="brand"
                                size="sm"
                                :title="'Show ' + col.label"
                                @toggle="toggleColumn(col.key)"
                            />
                            <button
                                type="button"
                                class="flex-1 text-left text-sm text-gray-700"
                                @click="toggleColumn(col.key)"
                            >
                                {{ col.label }}
                            </button>
                        </li>
                    </ul>
                </div>
            </div>
        </div>

        <div
            ref="stickyMarkerRef"
            aria-hidden="true"
            class="pointer-events-none sticky h-0"
            style="top: var(--table-sticky-top, 0px)"
        ></div>

        <!-- No scroll container unless the table really overflows: a sticky
             header inside one can never reach the page scroller. -->
        <div
            ref="tableAreaRef"
            :class="needsHorizontalScroll ? 'overflow-x-auto' : ''"
        >
            <table
                class="w-full table-auto border-collapse text-left text-xs sm:text-sm"
            >
                <thead
                    ref="theadRef"
                    class="relative z-20 bg-[#005740]"
                    :style="
                        headerShift
                            ? { transform: 'translateY(' + headerShift + 'px)' }
                            : null
                    "
                >
                    <tr>
                        <th
                            v-for="col in visibleColumns"
                            :key="col.key"
                            :class="[
                                'sticky z-10 bg-[#005740] px-3 py-3 text-[11px] font-semibold uppercase tracking-wider text-white/90 sm:px-4',
                                col.key === 'select_all'
                                    ? 'w-14'
                                    : col.key === 'action'
                                      ? 'w-40'
                                      : '',
                                col.key === 'action'
                                    ? 'right-0 z-20 shadow-[-8px_0_10px_-10px_rgba(15,23,42,0.45)]'
                                    : '',
                            ]"
                            :style="{ top: headerCellTop }"
                        >
                            <!-- SELECT ALL CHECKBOX -->
                            <template v-if="col.key === 'select_all'">
                                <span
                                    class="inline-flex"
                                    @mousemove="onSelectAllMouseMove"
                                    @mouseleave="hideTooltip"
                                >
                                    <TableCheckbox
                                        :checked="allSelected"
                                        :indeterminate="someSelected"
                                        tone="onDark"
                                        size="lg"
                                        aria-label="Select all on this page"
                                        @toggle="toggleSelectAll"
                                    />
                                </span>
                            </template>

                            <!-- NORMAL HEADER -->
                            <template v-else>
                                {{ col.label }}
                            </template>
                        </th>
                    </tr>
                </thead>

                <tbody class="text-gray-700">
                    <tr
                        v-for="(item, index) in props.rows?.data ?? []"
                        :key="item.id"
                        :class="[
                            'group border-b border-gray-100 transition-colors duration-150 last:border-b-0',
                            rowClass(item, index),
                            selectionEnabled || rowIsClickable
                                ? 'cursor-pointer hover:bg-[#005740]/[0.05]'
                                : 'hover:bg-[#005740]/[0.03]',
                        ]"
                        @click="onRowClick(item)"
                        @mousemove="onRowMouseMove"
                        @mouseleave="hideTooltip"
                    >
                        <TableCell
                            v-for="col in visibleColumns"
                            :key="col.key"
                            :extra="cellExtra(col, item, index)"
                        >
                            <!-- ROW CHECKBOX -->
                            <template v-if="col.key === 'select_all'">
                                <TableCheckbox
                                    :checked="isSelected(item)"
                                    tone="brand"
                                    size="md"
                                    aria-label="Select this row"
                                    @toggle="toggleCheck(item)"
                                />
                            </template>

                            <!-- NORMAL CELLS -->
                            <template v-else-if="col.key !== 'action'">
                                <span
                                    v-if="col.format"
                                    v-html="col.format(getValue(item, col.key))"
                                ></span>
                                <span v-else>{{
                                    getValue(item, col.key) ?? "N/A"
                                }}</span>
                            </template>

                            <!-- ACTION BUTTONS -->
                            <template v-else>
                                <div
                                    class="flex items-center gap-1"
                                    data-row-actions
                                    @click.stop
                                >
                                    <PrintButton
                                        v-if="actions.includes('print')"
                                        :item="item"
                                        class="rounded-md p-1.5 transition-colors hover:bg-[#0E6021]/10"
                                        @print="$emit('print', item.id)"
                                    />

                                    <button
                                        v-if="actions.includes('restore')"
                                        @click="$emit('restore', item)"
                                        class="rounded-md p-1.5 text-[#2E7D32] transition-colors hover:bg-[#2E7D32]/10 hover:text-[#1b5e20]"
                                        title="Restore"
                                    >
                                        <i class="fa-solid fa-rotate-left"></i>
                                    </button>

                                    <button
                                        v-if="actions.includes('return')"
                                        @click="$emit('return', item)"
                                        class="rounded-md p-1.5 text-[#0E6021] transition-colors hover:bg-[#0E6021]/10"
                                        title="Take back"
                                    >
                                        <i class="fa-solid fa-box-open"></i>
                                    </button>

                                    <button
                                        v-if="actions.includes('view')"
                                        @click="$emit('view', item)"
                                        class="rounded-md p-1.5 text-[#3F3F3F] transition-colors hover:bg-gray-100 hover:text-[#191818]"
                                        aria-label="View"
                                        :title="rowIsClickable ? undefined : 'View'"
                                    >
                                        <i class="fa-solid fa-eye"></i>
                                    </button>

                                    <button
                                        v-if="actions.includes('edit')"
                                        @click="$emit('edit', item)"
                                        class="rounded-md p-1.5 text-[#54B3AB] transition-colors hover:bg-[#54B3AB]/10 hover:text-[#38a69d]"
                                        title="Edit"
                                    >
                                        <i class="fa-solid fa-pen-to-square"></i>
                                    </button>

                                    <button
                                        v-if="actions.includes('delete')"
                                        @click="$emit('delete', item)"
                                        class="rounded-md p-1.5 text-[#D71D1D] transition-colors hover:bg-[#D71D1D]/10 hover:text-[#c50e0e]"
                                        title="Archive"
                                    >
                                        <i class="fa-solid fa-box-archive"></i>
                                    </button>

                                    <button
                                        v-if="actions.includes('force-delete')"
                                        @click="$emit('force-delete', item)"
                                        class="rounded-md p-1.5 text-[#D71D1D] transition-colors hover:bg-[#D71D1D]/10 hover:text-[#c50e0e]"
                                        title="Delete permanently"
                                    >
                                        <i class="fa-solid fa-trash-can"></i>
                                    </button>
                                </div>
                            </template>
                        </TableCell>
                    </tr>

                    <!-- EMPTY STATE -->
                    <tr v-if="!(props.rows?.data ?? []).length">
                        <td
                            :colspan="visibleColumns.length"
                            class="px-6 py-16 text-center"
                        >
                            <div
                                class="flex flex-col items-center gap-2 text-gray-400"
                            >
                                <i
                                    class="fa-solid fa-folder-open text-3xl text-[#005740]/30"
                                ></i>
                                <p class="text-sm font-semibold text-gray-500">
                                    Nothing here yet
                                </p>
                                <p class="text-xs text-gray-400">
                                    Try a different search or clear the filters.
                                </p>
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <div
            class="flex flex-col gap-2 rounded-b-xl border-t border-gray-100 bg-gray-50/70 px-4 py-3 sm:flex-row sm:items-center sm:justify-between"
        >
            <p class="text-xs text-gray-500 sm:text-sm">
                Showing
                <span class="font-semibold text-[#005740]">
                    {{ props.rows?.from ?? 0 }}-{{ props.rows?.to ?? 0 }}
                </span>
                of
                <span class="font-semibold text-[#005740]">
                    {{ props.rows?.total ?? 0 }}
                </span>
            </p>

            <div class="flex items-center gap-1 sm:gap-1.5">
                <template v-for="link in props.rows?.links ?? []" :key="link.label">
                    <span
                        v-if="link.url"
                        @click="goToPage(link.url)"
                        class="flex h-8 min-w-[32px] cursor-pointer items-center justify-center rounded-lg px-2 text-sm transition-colors"
                        :class="
                            link.active
                                ? 'bg-[#005740] font-semibold text-white shadow-sm'
                                : 'text-gray-600 hover:bg-[#005740]/10 hover:text-[#005740]'
                        "
                    >
                        <i
                            v-if="link.label.includes('Previous')"
                            class="fa-solid fa-chevron-left text-xs"
                        ></i>
                        <i
                            v-else-if="link.label.includes('Next')"
                            class="fa-solid fa-chevron-right text-xs"
                        ></i>
                        <span v-else>{{ pageLabel(link.label) }}</span>
                    </span>

                    <span
                        v-else
                        class="flex h-8 min-w-[32px] cursor-not-allowed items-center justify-center rounded-lg px-2 text-sm text-gray-300"
                    >
                        <i
                            v-if="link.label.includes('Previous')"
                            class="fa-solid fa-chevron-left text-xs"
                        ></i>
                        <i
                            v-else-if="link.label.includes('Next')"
                            class="fa-solid fa-chevron-right text-xs"
                        ></i>
                        <span v-else>{{ pageLabel(link.label) }}</span>
                    </span>
                </template>
            </div>
        </div>

        <!-- Cursor-following hint for clickable rows. Teleported to <body> so
             nothing in the table can clip it. -->
        <Teleport to="body">
            <div
                v-if="tooltipVisible"
                class="pointer-events-none fixed z-[60] whitespace-nowrap rounded-md bg-[#1f2937] px-2.5 py-1 text-[11px] font-semibold text-white shadow-lg"
                :style="tooltipStyle"
            >
                {{ tooltipText }}
            </div>
        </Teleport>
    </div>
</template>
