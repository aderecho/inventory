<script setup>
import { useForm, router } from "@inertiajs/vue3";
import { computed, ref, watch } from "vue";
import Toast from "primevue/toast";
import { useToast } from "primevue/usetoast";

const props = defineProps({
  mode: { type: String, default: "create" },
  accountableField: { type: Array, default: () => [] },
  inputFields: { type: Array, default: () => [] },
  itemSelectedField: { type: Array, default: () => [] },
  selectedIDs: { type: Array, default: () => [] },
  items: { type: Object, default: () => ({ data: [] }) },
  accPerson: { type: Object, default: () => ({ data: [] }) },
  users: { type: Array, default: () => [] },
  userProfiles: { type: Array, default: () => [] },
  viewItem: { type: Array, default: () => [] },
  item: { type: Object, default: () => ({}) },
});

const selectedCategory = ref("");
const firstNumberError = ref("");
const emit = defineEmits(["submit", "close", "created"]); // <-- remove stray backticks
const toast = useToast();

const form = useForm({
  inventory_item_id: [],
  accountable_persons_id: "",
  issued_by_id: "",
  category: "",
  created_by: "",
  par_date: "",
  remarks: "",
});

const itemMap = computed(() => {
  const map = {};
  props.items.data?.forEach((item) => {
    map[item.id] = item;
  });
  return map;
});

function submit() {
  if (props.selectedIDs.length > 0) {
    const firstNumbers = props.selectedIDs.map((id) => {
      const item = itemMap.value[id];
      if (!item?.property_number) return null;
      return item.property_number.split("-")[0];
    });

    const uniqueFirstNumbers = [...new Set(firstNumbers)];

    if (uniqueFirstNumbers.length > 1) {
      firstNumberError.value =
        "All selected items must have the same Category in Property Number.";
      return;
    }
  }

  form.inventory_item_id = props.selectedIDs;
  form.created_by = props.users[0]?.id ?? null;

  let updateCategoryPromise;

  if (selectedCategory.value && props.selectedIDs.length) {
    updateCategoryPromise = new Promise((resolve) => {
      router.put(
        route("inventory.items.update-category"),
        {
          inventory_item_ids: props.selectedIDs,
          category: selectedCategory.value,
        },
        {
          onSuccess: () => resolve(),
          onError: () => resolve(),
        }
      );
    });
  } else {
    updateCategoryPromise = Promise.resolve();
  }

  updateCategoryPromise.then(() => {
    if (props.mode === "edit") {
      if (!form.id) {
        console.error("Edit mode but form.id is missing", form);
        return;
      }

      form.put(route("items.update", form.id), {
        onSuccess: () => {
          toast.add({
            severity: "success",
            summary: "Updated",
            detail: "Item updated successfully.",
            life: 5000,
          });

          emit("close");
          emit("submit", form);
        },
        onError: (errors) => {
          toast.add({
            severity: "warn",
            summary: "Validation Failed",
            detail: "Please check the highlighted fields.",
            life: 5000,
          });

          console.error("Update failed", errors);
        },
      });
    } else {
      form.post(route("inventory.acknowledgements.store"), {
        onSuccess: () => {
          toast.add({
            severity: "success",
            summary: "Created",
            detail: "Item assigned successfully.",
            life: 5000,
          });

          emit("close");
          emit("created");
          emit("submit", form);
          props.selectedIDs.splice(0);
          selectedCategory.value = "";
          form.reset();
        },
        onError: (errors) => {
          toast.add({
            severity: "error",
            summary: "Validation Failed",
            detail: "Please fill up required fields.",
            life: 5000,
          });

          console.error("Create failed", errors);
        },
      });
    }
  });
}

// Generates a category like "233-2025-12" from the first selected item's property_number
function generateCategoryFromFirstSelected() {
  if (!props.selectedIDs.length) return "";

  const firstID = props.selectedIDs[0];
  const item = itemMap.value[firstID];
  if (!item) return "";

  // Extract the first number before the dash
  const propertyNumber = item.property_number || "";
  const firstNumber = propertyNumber.split("-")[0] || "";

  const now = new Date();
  const year = now.getFullYear();
  const month = String(now.getMonth() + 1).padStart(2, "0");

  return `${firstNumber}-${year}-${month}-`;
}

// Watch selectedIDs prop and initialize selectedCategory from first selected item
watch(
  () => props.selectedIDs,
  (newVal) => {
    console.log("selectedIDs changed", newVal); // debug
    if (newVal && newVal.length > 0) {
      selectedCategory.value = itemMap.value[newVal[0]]?.category || "";
      form.category = generateCategoryFromFirstSelected();
    } else {
      selectedCategory.value = "";
      form.category = "";
    }
  },
  { immediate: true }
);

// Also watch items in case they load/refresh after selectedIDs
watch(
  () => props.items,
  () => {
    if (props.selectedIDs.length > 0) {
      selectedCategory.value =
        itemMap.value[props.selectedIDs[0]]?.category || "";
    }
  }
);

const isClosing = ref(false);

function closeWithAnimation() {
  isClosing.value = true;

  // Match your animation duration: popOut = 0.5s
  setTimeout(() => {
    emit("close");
    isClosing.value = false; // reset for next open
  }, 200);
}

function getNestedValue(obj, path) {
  if (!obj || !path) return null;
  return path.split(".").reduce((o, k) => (o ? o[k] : null), obj);
}

function getViewValue(view) {
  const rawValue = getNestedValue(props.item, view.key);

  if (view.format) {
    return view.format(rawValue);
  }
  return rawValue ?? "N/A";
}
</script>


<template>
  <!-- <pre>{{ form }}</pre> -->
  <div class="fixed inset-0 bg-black/40 flex items-center justify-center p-4" @click="$emit('close')">
    <div :class="[
      'bg-white rounded-lg w-full max-w-6xl p-4 overflow-y-auto max-h-[90vh]',
      isClosing ? 'animate-pop-out' : 'animate-pop-in',
    ]" @click.stop>
      <div class="flex justify-between items-center mb-4">
        <h3 class="text-2xl font-bold text-[#850038] mb-6">
          {{
            mode === "edit"
              ? "Edit Item"
              : mode === "view"
                ? "Item Details"
                : "Assign"
          }}
        </h3>
      </div>

      <Toast />

      <form @submit.prevent="submit" v-if="mode !== 'view'">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
          <div class="space-y-6">
            <!-- PAR/ICS NUMBER -->
            <div>
              <label class="block text-sm text-[#3B3B3B] font-bold mb-1">PAR/ICS Number</label>
              <input type="text" v-model="form.category" placeholder="Enter new category" :class="[
                'w-full rounded-md px-3 py-3 bg-[#F8F8F8] text-[#3B3B3B] text-sm focus:ring-1 focus:outline-none',
                form.errors.category
                  ? 'border-red-500 focus:ring-red-500 focus:border-red-500'
                  : 'border-gray-300 focus:ring-[#850038] focus:border-[#850038]',
              ]" />
              <div v-if="form.errors.category" class="text-red-500 text-sm">
                {{ form.errors.category }}
              </div>
            </div>

            <!-- ACCOUNTABLE FIELD -->
            <div class="flex flex-col sm:flex-row gap-4">
              <div class="grid grid-cols-2 gap-4 sm:grid-cols-3 sm:gap-4 flex-grow">
                <div v-for="accf in accountableField" :key="accf.name" class="flex flex-col">
                  <label class="block text-sm text-[#3B3B3B] font-bold mb-1">{{
                    accf.label
                    }}</label>
                  <select v-model="form[accf.model]" :key="accf.model" :class="[
                    'w-full rounded-md px-3 py-3 bg-[#F8F8F8] text-gray-500 text-sm focus:ring-1 focus:outline-none',
                    form.errors[accf.model]
                      ? 'border-red-500 focus:ring-red-500 focus:border-red-500'
                      : 'border-gray-300 focus:ring-[#850038] focus:border-[#850038]',
                  ]">
                    <option value="">Select</option>
                    <option v-for="item in props[accf.name]" :key="item.id" :value="item[accf.value]">
                      {{ item[accf.option] || "N/A" }}
                    </option>
                  </select>
                  <div v-if="form.errors[accf.model]" class="text-red-500 text-sm">
                    {{ form.errors[accf.model] }}
                  </div>
                </div>

                <div>
                  <label class="block text-sm text-[#3B3B3B] font-bold mb-1">Date Acquired</label>
                  <input v-model="form.par_date" :class="[
                    'w-full rounded-md px-3 py-3 bg-[#F8F8F8] text-gray-500 text-sm focus:ring-1 focus:outline-none',
                    form.errors.par_date
                      ? 'border-red-500 focus:ring-red-500 focus:border-red-500'
                      : 'border-gray-300 focus:ring-[#850038] focus:border-[#850038]',
                  ]" type="date" />
                  <div v-if="form.errors.par_date" class="text-red-500 text-sm">
                    {{ form.errors.par_date }}
                  </div>
                </div>
              </div>
            </div>

            <!-- ROOMS -->
            <div v-for="ip in inputFields" :key="ip.model">
              <label class="block text-sm text-[#3B3B3B] font-bold mb-1">{{ ip.label }}</label>
              <input :placeholder="ip.placeholder"
                class="w-full rounded-md border border-gray-300 px-3 py-3 bg-[#F8F8F8] text-[#3B3B3B] text-sm focus:ring-1 focus:ring-[#850038] focus:outline-none focus:border-[#850038]" />
              <div v-if="form.errors[ip.model]" class="text-red-500 text-sm">
                {{ form.errors[ip.model] }}
              </div>
            </div>

            <!-- REMARKS -->
            <div>
              <label class="block text-sm text-[#3B3B3B] font-semibold mb-1">Remarks</label>
              <textarea v-model="form.remarks" placeholder="Input a remarks"
                class="w-full h-56 rounded-md border border-gray-300 px-3 py-2 bg-[#F8F8F8] text-[#3B3B3B] text-sm focus:ring-1 focus:ring-[#850038] focus:outline-none focus:border-[#850038]"></textarea>
              <div v-if="form.errors.remarks" class="text-red-500 text-sm">
                {{ form.errors.remarks }}
              </div>
            </div>
          </div>

          <div class="space-y-4">
            <!-- ITEM SELECTED -->
            <div v-if="firstNumberError" class="text-red-500 text-sm mb-2">
              {{ firstNumberError }}
            </div>
            <div v-for="select in itemSelectedField" :key="select.model">
              <div>
                <label class="block text-sm font-bold mb-1">{{
                  select.label
                  }}</label>
                <div
                  class="bg-[#F8F8F8] border border-gray-300 rounded-md h-[calc(100vh-320px)] md:h-[calc(100vh-380px)] p-3 overflow-y-auto">
                  <div v-if="selectedIDs.length === 0">
                    <p class="text-gray-500 text-sm">No items selected</p>
                  </div>
                  <div>
                    <ul class="space-y-2">
                      <li v-for="id in selectedIDs" :key="id" class="bg-white border p-2 rounded-md shadow-sm text-sm">
                        <p class="gap-1 flex">
                          <strong class="text-[#850038]">Item Name:</strong>
                          <span class="text-[#3B3B3B]">{{ itemMap[id]?.item_name }}</span>
                        </p>

                        <p class="gap-1 flex">
                          <strong class="text-[#850038]">Property Number:</strong>
                          <span class="text-[#3B3B3B]">{{ itemMap[id]?.property_number }}</span>
                        </p>

                        <p class="gap-1 flex">
                          <strong class="text-[#850038]">Quantity:</strong>
                          <span class="text-[#3B3B3B]">{{ itemMap[id]?.quantity }}</span>
                        </p>
                      </li>
                    </ul>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- BUTTON -->
        <div class="mt-4 flex justify-end gap-4">
          <button type="button" @click="closeWithAnimation"
            class="border border-gray-400 px-6 py-4 rounded-full text-sm text-[#3B3B3B] font-semibold hover:bg-gray-100">
            Cancel
          </button>
          <button type="submit"
            class="bg-[#0E6021] text-white px-8 py-4 rounded-full text-sm font-semibold hover:bg-green-800">
            {{ mode === "edit" ? "Update" : "Assign" }}
          </button>
        </div>
      </form>

      <!-- VIEW MODAL -->
      <div v-else>
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-x-6 gap-y-3">
          <div v-for="view in viewItem" :key="view.key" class="flex items-start justify-between gap-4 border-b pb-2">
            <!-- LABEL -->
            <div class="text-sm font-semibold text-[#000000] w-1/2">
              {{ view.label }}:
            </div>

            <!-- VALUE -->
            <div class="text-sm font-medium text-gray-600 w-1/2 text-right" v-html="getViewValue(view)" />
          </div>
        </div>

        <!-- BUTTON -->
        <div class="mt-6 flex justify-end">
          <button @click="closeWithAnimation"
            class="px-6 py-3 border border-[#8d8a8a] rounded-md hover:bg-gray-100 text-sm font-medium">
            Back
          </button>
        </div>
      </div>
    </div>
  </div>
</template>