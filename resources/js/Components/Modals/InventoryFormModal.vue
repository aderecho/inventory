<script setup>
import { useForm, usePage } from "@inertiajs/vue3";
import { watch, ref } from "vue";
import Toast from "primevue/toast";
import { useToast } from 'primevue/usetoast'
import MultiSelect from 'primevue/multiselect';

const props = defineProps({
  mode: { type: String, default: "create" },
  initialValues: { type: Object, default: () => ({}) },
  inputFields: { type: Array, default: () => [] },
  firstDropdown: { type: Array, default: () => [] },
  firstInputField: { type: Array, default: () => [] },
  secondDropdown: { type: Array, default: () => [] },
  invoicesFundFields: { type: Array, default: () => [] },
  quantityCostFields: { type: Array, default: () => [] },
  supplierOptions: { type: Array, default: () => [] },
  requestFields: { type: Array, default: () => [] },
  totalCost: { type: Array, default: () => [] },
  itemClass: { type: Array, default: () => [] },
  suppliers: { type: Array, default: () => [] },
  inputFieldsEdit: { type: Array, default: () => [] },
  viewItem: { type: Array, default: () => [] },
  item: { type: Object, default: () => ({}) },
});

const toast = useToast();

const emit = defineEmits(["submit", "close", "created"]);

const form = useForm({
  id: null,
  item_classification_id: "",
  supplier_id: "",
  invoice: "",
  fund_source: "",
  item_name: "",
  description: "",
  quantity: "",
  unit: "",
  unit_cost: "",
  total_amount: 0,
  property_number: "",
  serial_numbers: [], // FOR ADD ITEM
  serial_number: "", // FOR EDIT ITEM
  pr_number: "",
  po_number: "",
  remarks: "",
  date_acquired: "",
  status: "",
});

const isEditing = ref(false);

const calculateTotalAmount = () => {
  const qty = Number(form.quantity) || 0;
  const cost = Number(form.unit_cost) || 0;
  const total = qty * cost;

  form.total_amount = total.toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ",");
};

watch(() => [form.quantity, form.unit_cost], calculateTotalAmount);

watch(
  () => form.item_classification_id,
  (newVal) => {
    if (isEditing.value || !newVal) return;
    const selectedClass = props.itemClass.find((c) => c.id === newVal);

    if (selectedClass) {
      const code = selectedClass.classification_code;
      const now = new Date();
      const year = now.getFullYear();
      const month = String(now.getMonth() + 1).padStart(2, "0");

      if (code) {
        const formatted = `${code}-${year}-${month}-`;
        form.category = formatted;
        form.property_number = `${code}-`;
      } else {
        form.category = "";
        form.property_number = "";
      }
    }
  }
);

// --- EDIT MODE ---
watch(
  () => props.initialValues,
  (item) => {
    if (props.mode !== "edit" || !item) return;

    isEditing.value = true;
    form.id = item.id ?? null;
    form.item_classification_id = item.item_classification_id ?? "";
    form.supplier_id = item.supplier_id ?? "";
    form.invoice = item.invoice ?? "";
    form.fund_source = item.fund_source ?? "";
    form.item_name = item.item_name ?? "";
    form.description = item.description ?? "";
    form.quantity = item.quantity ?? 1;
    form.unit = item.unit ?? "";
    form.unit_cost = item.unit_cost ?? 0;
    form.total_amount = item.total_amount ?? 0;
    form.property_number = item.property_number ?? "";
    form.serial_number = item.serial_number ?? [];
    form.pr_number = item.pr_number ?? "";
    form.po_number = item.po_number ?? "";
    form.remarks = item.remarks ?? "";
    form.date_acquired = item.date_acquired
      ? item.date_acquired.split("T")[0]
      : "";
    form.status = item.status ?? "";
  },
  { immediate: true }
);

watch(
  () => form.quantity,
  (newVal) => {
    const qty = parseInt(newVal);

    if (!qty || qty <= 0) {
      form.serial_numbers = [];
      return;
    }
    form.serial_numbers = Array.from(
      { length: qty },
      (_, i) => form.serial_numbers[i] || ""
    );
  }
);

function submit() {
  form.total_amount = parseFloat(form.total_amount);

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
        const firstError = Object.values(errors)[0];

        toast.add({
          severity: "warn",
          summary: "Validation Failed",
          detail: firstError,
          life: 5000,
        });

        console.error("Update failed", errors);
      },
    });
  } else {
    form.post(route("items.store"), {
      onSuccess: () => {
        toast.add({
          severity: "success",
          summary: "Created",
          detail: "Item added successfully.",
          life: 5000,
        });

        emit("close");
        emit("submit", form);
        form.reset();
      },

      onError: (errors) => {
        const firstError = Object.values(errors)[0];

        toast.add({
          severity: "error",
          summary: "Validation Failed",
          detail: firstError,
          life: 5000,
        });

        console.error("Create failed", errors);
      },
    });
  }
}

const isClosing = ref(false);

// ANIMATION FOR CLOSE
function closeWithAnimation() {
  isClosing.value = true;
  setTimeout(() => {
    emit("close");
    isClosing.value = false;
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
                : "Add Item"
          }}
        </h3>
      </div>

      <Toast />

      <form @submit.prevent="submit" v-if="mode !== 'view'">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
          <!-- LEFT -->
          <div class="space-y-4 col-span-1 md:col-span-1">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-x-5">
              <div class="space-y-4">
                <!-- FIRST DOWN -->
                <div class="flex flex-col md:flex-row gap-4 mb-4">
                  <div v-for="fdp in firstDropdown" :key="fdp.model">
                    <label class="block text-[#3B3B3B] text-sm font-bold mb-1">{{
                      fdp.label
                      }}</label>
                    <select v-model="form[fdp.model]" :class="[
                      'w-full sm:w-[10rem] rounded-md px-3 py-3 text-gray-500 bg-[#F8F8F8] text-sm focus:ring-1 focus:outline-none',
                      form.errors[fdp.model]
                        ? 'border-red-500 focus:ring-red-500 focus:border-red-500'
                        : 'border-gray-300 focus:ring-[#850038] focus:border-[#850038]',
                    ]">
                      <option value="" disabled hidden>Select</option>
                      <option v-for="item in props[fdp.name]" :key="item.id" :value="item[fdp.value]">
                        {{ item[fdp.option] || "N/A" }}
                      </option>
                    </select>
                    <div v-if="form.errors[fdp.model]" class="text-red-500 text-sm">
                      {{ form.errors[fdp.model] }}
                    </div>
                  </div>

                  <!-- FIRST INPUT FIELD SECTION -->
                  <div v-for="fif in firstInputField" :key="fif.model" class="flex flex-col">
                    <label class="block text-[#3B3B3B] text-sm font-bold mb-1">{{
                      fif.label
                      }}</label>
                    <input :type="fif.type || 'text'" v-model="form[fif.model]" :placeholder="fif.placeholder"
                      :required="fif.required" :class="[
                        'w-full sm:w-[21rem] rounded-md px-3 py-3 text-[#3B3B3B] bg-[#F8F8F8] text-sm focus:ring-1 focus:outline-none',
                        form.errors[fif.model]
                          ? 'border-red-500 focus:ring-red-500 focus:border-red-500'
                          : 'border-gray-300 focus:ring-[#850038] focus:border-[#850038]',
                      ]" />
                    <div v-if="form.errors[fif.model]" class="text-red-500 text-sm">
                      {{ form.errors[fif.model] }}
                    </div>
                  </div>
                </div>

                <!-- SECOND DROP DOWN -->
                <div class="w-full flex md:flex-row gap-4 mb-8">
                  <div v-for="sdf in secondDropdown" :key="sdf.label" class="flex gap-3">
                    <div>
                      <label class="block text-sm text-[#3B3B3B] font-bold mb-1">{{
                        sdf.label
                        }}</label>
                      <select v-model="form[sdf.model]" :class="[
                        'w-full sm:w-[10rem] rounded-md px-3 py-3 bg-[#F8F8F8] text-gray-500 text-sm focus:ring-1 focus:outline-none',
                        form.errors[sdf.model]
                          ? 'border-red-500 focus:ring-red-500 focus:border-red-500'
                          : 'border-gray-300 focus:ring-[#850038] focus:border-[#850038]',
                      ]">
                        <option value="">Select</option>
                        <option v-for="op in sdf.options" :key="op.value" :value="op.value">
                          {{ op.label }}
                        </option>
                      </select>
                      <div v-if="form.errors[sdf.model]" class="text-red-500 text-sm">
                        {{ form.errors[sdf.model] }}
                      </div>
                    </div>
                  </div>
                  <div>
                    <label class="block text-sm text-[#3B3B3B] font-bold mb-1">Date Acquired</label>
                    <input v-model="form.date_acquired" :class="[
                      'w-full sm:w-[10rem] rounded-md border border-gray-300 px-3 py-3 bg-[#F8F8F8] text-gray-500 text-sm focus:ring-1 focus:outline-none',
                      form.errors.date_acquired
                        ? 'border-red-500 focus:ring-red-500 focus:border-red-500'
                        : 'border-gray-300 focus:ring-[#dfa8bf] focus:border-[#850038]',
                    ]" type="date" />
                    <div v-if="form.errors.date_acquired" class="text-red-500 text-sm">
                      {{ form.errors.date_acquired }}
                    </div>
                  </div>
                </div>

                <!-- QUANTITY/UNIT COST -->
                <div class="flex gap-4 sm:gap-8 w-full">
                  <div v-for="qcf in quantityCostFields" :key="qcf.quantityCostFields"
                    class="flex flex-col flex-1 min-w-[8rem] sm:min-w-[10rem] md:min-w-[15rem] lg:min-w-[14.3rem]">
                    <label class="block text-sm text-[#3B3B3B] font-bold mb-1">{{
                      qcf.label
                      }}</label>
                    <input v-model="form[qcf.model]" :key="qcf.model" :type="qcf.type" :placeholder="qcf.placeholder"
                      step="any" :class="[
                        'w-full sm:w-[15.7rem] rounded-md border border-gray-300 px-3 py-3 bg-[#F8F8F8] text-[#3B3B3B] text-sm focus:ring-1 focus:outline-none',
                        form.errors[qcf.model]
                          ? 'border-red-500 focus:ring-red-500 focus:border-red-500'
                          : 'border-gray-300 focus:ring-[#dfa8bf] focus:border-[#850038]',
                      ]" />
                    <div v-if="form.errors[qcf.model]" class="text-red-500 text-sm">
                      {{ form.errors[qcf.model] }}
                    </div>
                  </div>
                </div>

                <!-- SERIAL NUMBERS -->
                <div v-if="form.quantity > 0" class="flex flex-col gap-2">
                  <div v-for="(sn, index) in form.serial_numbers" :key="index">
                    <input v-model="form.serial_numbers[index]" type="text"
                      :placeholder="`SER-${String(index + 1).padStart(3, '0')}`"
                      class="w-full sm:w-[32rem] rounded-md border border-gray-300 px-3 py-3 bg-[#F8F8F8] text-[#3B3B3B] text-sm focus:ring-1 focus:ring-[#850038] focus:outline-none focus:border-[#850038]" />
                  </div>
                </div>

                <!-- ITEM NAME -->
                <div v-for="ip in mode === 'edit' ? inputFieldsEdit : inputFields" :key="ip.model"
                  class="flex flex-col">
                  <label class="block text-sm text-[#3B3B3B] font-bold mb-1">{{
                    ip.label
                    }}</label>
                  <input v-model="form[ip.model]" :key="ip.model" :readonly="ip.readonly" type="text"
                    :placeholder="ip.placeholder" :class="[
                      'w-full sm:w-[32rem] rounded-md border border-gray-300 px-3 py-3 bg-[#F8F8F8] text-[#3B3B3B] text-sm focus:ring-1 focus:outline-none',
                      form.errors[ip.model]
                        ? 'border-red-500 focus:ring-red-500 focus:border-red-500'
                        : 'border-gray-300 focus:ring-[#dfa8bf] focus:border-[#850038]',
                    ]" />
                  <div v-if="form.errors[ip.model]" class="text-red-500 text-sm">
                    {{ form.errors[ip.model] }}
                  </div>
                </div>

                <!-- DESCRIPTION -->
                <div>
                  <label class="block text-sm text-[#3B3B3B] font-semibold mb-1">Description</label>
                  <textarea v-model="form.description" placeholder="Input a description"
                    class="w-full sm:w-[32rem] h-32 rounded-md border border-gray-300 px-3 py-2 bg-[#F8F8F8] text-[#3B3B3B] text-sm focus:ring-1 focus:ring-[#850038] focus:outline-none focus:border-[#850038]"></textarea>
                  <div v-if="form.errors.description" class="text-red-500 text-sm">
                    {{ form.errors.description }}
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- RIGHT -->
          <div class="space-y-4">
            <!-- SUPPLIER OPTIONS -->
            <div class="space-y-4">
              <div v-for="sup in supplierOptions" :key="sup.label" class="flex flex-col">
                <label class="block text-sm text-[#3B3B3B] font-bold mb-1">
                  {{ sup.label }}
                </label>
                <select v-model="form[sup.model]" :class="[
                  'w-full sm:w-[34rem] rounded-md px-3 py-3 bg-[#F8F8F8] text-gray-500 text-sm focus:ring-1 focus:outline-none',
                  form.errors[sup.model]
                    ? 'border-red-500 focus:ring-red-500 focus:border-red-500'
                    : 'border-gray-300 focus:ring-[#850038] focus:border-[#850038]',
                ]">
                  <option value="">Select</option>
                  <option v-for="item in props[sup.name]" :key="item.id" :value="item[sup.value]">
                    {{ item[sup.option] || "N/A" }}
                  </option>
                </select>
                <div v-if="form.errors[sup.model]" class="text-red-500 text-sm">
                  {{ form.errors[sup.model] }}
                </div>
              </div>
            </div>

            <!-- REQUEST FIELDS -->
            <div class="space-y-4">
              <div v-for="rf in requestFields" :key="rf.model" class="flex flex-col">
                <label class="block text-sm text-[#3B3B3B] font-bold mb-1">{{
                  rf.label
                  }}</label>
                <input v-model="form[rf.model]" :key="rf.model" type="text" :placeholder="rf.placeholder" :class="[
                  'w-full sm:w-[34rem] rounded-md px-3 py-3 bg-[#F8F8F8] text-[#3B3B3B] text-sm focus:ring-1 focus:outline-none',
                  form.errors[rf.model]
                    ? 'border-red-500 focus:ring-red-500 focus:border-red-500'
                    : 'border-gray-300 focus:ring-[#850038] focus:border-[#850038]',
                ]" />
                <div v-if="form.errors[rf.model]" class="text-red-500 text-sm">
                  {{ form.errors[rf.model] }}
                </div>
              </div>
            </div>

            <!-- Invoices + Fund Sources -->
            <div class="flex flex-col md:flex-row gap-4 mb-4">
              <div v-for="inv in invoicesFundFields" :key="inv.model">
                <label class="block text-sm text-[#3B3B3B] font-bold mb-1">{{
                  inv.label
                  }}</label>
                <input v-model="form[inv.model]" :key="inv.model" type="text" :placeholder="inv.placeholder" :class="[
                  'w-full sm:w-[16.5rem] rounded-md px-3 py-3 bg-[#F8F8F8] text-[#3B3B3B] text-sm focus:ring-1 focus:outline-none',
                  form.errors[inv.model]
                    ? 'border-red-500 focus:ring-red-500 focus:border-red-500'
                    : 'border-gray-300 focus:ring-[#850038] focus:border-[#850038]',
                ]" />
                <div v-if="form.errors[inv.model]" class="text-red-500 text-sm">
                  {{ form.errors[inv.model] }}
                </div>
              </div>
            </div>
          </div>
        </div>

        <div class="flex justify-between items-center gap-4">
          <!-- TOTAL COST -->
          <div class="flex items-center gap-4">
            <div v-for="total in totalCost" :key="total.label" class="flex items-center gap-3">
              <label class="text-base text-[#3B3B3B] font-bold"> {{ total.label }}: </label>

              <input v-model="form.total_amount" readonly placeholder="0.00"
                class="block text-lg font-semibold text-gray-700 border border-none pointer-events-none" />
            </div>
          </div>

          <!-- BUTTONS -->
          <div class="flex items-center gap-3">
            <button type="button" @click="closeWithAnimation"
              class="border border-gray-400 px-6 py-4 rounded-full text-sm text-[#3B3B3B] font-semibold hover:bg-gray-100">
              Cancel
            </button>
            <button type="submit"
              class="bg-[#0E6021] text-white px-8 py-4 rounded-full text-sm font-semibold hover:bg-green-800">
              {{ mode === "edit" ? "Update" : "Add" }}
            </button>
          </div>
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
