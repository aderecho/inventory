<script setup>
import { usePage, router } from "@inertiajs/vue3";
import { defineProps, computed, ref } from "vue";
import TableCell from "./TableCell.vue";
import PrintButton from "@/Components/Buttons/PrintButton.vue";
import { useAuth } from "@/Composables/useAuth";

const { isAdmin, isStaff, can } = useAuth();

const props = defineProps({
  // rooms: Array,
  columns: Array,
  rows: Object,
  users: Array,
  actions: {
    type: Array,
    default: () => [],
  },
});

const emit = defineEmits([
  "view",
  "edit",
  "delete",
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
  return path.split(".").reduce((acc, key) => acc?.[key], obj) ?? "N/A";
}

// SELECTABLE ROWS/IDS
const selectedIDs = ref([]);

const allSelected = computed(() => {
  return (
    props.rows?.data?.length > 0 &&
    props.rows.data.every((i) => selectedIDs.value.includes(i.id))
  );
});

function toggleSelectAll() {
  if (allSelected.value) {
    selectedIDs.value = [];
  } else {
    selectedIDs.value = props.rows.data.map((i) => i.id);
  }

  emit("update:selected", selectedIDs.value);
  emit("selection-changed", selectedIDs.value);
}
function toggleCheck(item) {
  const id = item.id;

  if (selectedIDs.value.includes(id)) {
    selectedIDs.value = selectedIDs.value.filter((x) => x !== id);
  } else {
    selectedIDs.value.push(id);
  }

  emit("update:selected", selectedIDs.value);
  emit("selection-changed", selectedIDs.value);
}
</script>

<template>
  <!-- Table (horizontal scroll on small screens) -->
  <div class="overflow-x-auto mt-3">
    <table class="w-full table-auto border-collapse text-left bg-white text-xs sm:text-sm">
      <thead class="bg-[#850038]">
        <tr class="text-white">
          <th v-for="col in props.columns" :key="col.key"
            class="p-2 sm:p-3 md:p-4 first:rounded-tl-lg last:rounded-tr-lg">
            <!-- SELECT ALL CHECKBOX -->
            <template v-if="col.key === 'select_all'">
              <input type="checkbox" class="w-4 h-4" :checked="allSelected" @change="toggleSelectAll" />
            </template>

            <!-- NORMAL HEADER -->
            <template v-else>
              {{ col.label }}
            </template>
          </th>
        </tr>
      </thead>

      <tbody class="text-gray-700">
        <tr v-for="item in props.rows.data" :key="item.id" class="even:bg-gray-200">
          <TableCell v-for="col in props.columns" :key="col.key">
            <!-- ROW CHECKBOX -->
            <template v-if="col.key === 'select_all'">
              <input type="checkbox" class="w-4 h-4 text-[#0E6021]" :checked="selectedIDs.includes(item.id)"
                @click="toggleCheck(item)" />
            </template>

            <!-- NORMAL CELLS -->
            <template v-else-if="col.key !== 'action'">
              <span v-if="col.format" v-html="col.format(getValue(item, col.key))"></span>
              <span v-else>{{ getValue(item, col.key) ?? "N/A" }}</span>
            </template>

            <!-- ACTION BUTTONS -->
            <template v-else>
              <div class="flex items-center gap-2">
                <PrintButton v-if="actions.includes('print')" :item="item" @print="$emit('print', item.id)" />

                <button v-if="actions.includes('view') && can('view')" @click="$emit('view', item)"
                  class="text-[#3F3F3F] hover:text-[#191818]" title="View">
                  <i class="fa-solid fa-eye"></i>
                </button>

                <button v-if="actions.includes('edit') && can('edit')" @click="$emit('edit', item)"
                  class="text-[#54B3AB] hover:text-[#38a69d]" title="Edit">
                  <i class="fa-solid fa-pen-to-square"></i>
                </button>

                <button v-if="
                  actions.includes('delete') &&
                  (isAdmin || can('delete'))
                " @click="$emit('delete', item)" class="text-[#D71D1D] hover:text-[#c50e0e]" title="Delete">
                  <i class="fa-solid fa-trash"></i>
                </button>
              </div>
            </template>
          </TableCell>
        </tr>
      </tbody>
    </table>
  </div>

  <!-- Pagination -->
  <div class="mt-2 flex justify-between items-center mx-2">
    <div>
      <p class="text-base font-bold text-[#3B3B3B]">
        Results:
        <span class="text-[#850038]">{{ rows.from }}-{{ rows.to }} of {{ rows.total }}</span>
      </p>
    </div>
    <div class="flex justify-center">
      <div class="flex items-center gap-1 sm:gap-2 py-2">
        <span v-for="link in rows.links" :key="link.label">
          <span v-if="link.url" @click="goToPage(link.url)"
            class="flex items-center justify-center min-w-[32px] h-8 px-2 text-base rounded-full transition-all duration-200 cursor-pointer"
            :class="{
              'text-[#000000] hover:bg-[#e7e7e7]': !link.active,
              'bg-[#850038] text-white font-semibold shadow-sm': link.active,
            }">
            <i v-if="link.label.includes('Previous')" class="fa-solid fa-chevron-left"></i>
            <i v-else-if="link.label.includes('Next')" class="fa-solid fa-chevron-right"></i>
            <span v-else>{{ link.label }}</span>
          </span>
        </span>
      </div>
    </div>
  </div>
</template>