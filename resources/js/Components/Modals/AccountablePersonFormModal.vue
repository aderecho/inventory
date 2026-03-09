<script setup>
import { ref, watch } from "vue";
import { useForm } from "@inertiajs/vue3";
import Toast from "primevue/toast";
import { useToast } from 'primevue/usetoast';

const toast = useToast();

const props = defineProps({
  mode: { type: String, default: "create" },
  accountablePersonFields: { type: Array, default: () => [] },
  accountablePerson: { type: Object, default: () => ({}) },
});

const emit = defineEmits(["submit", "close"]);

const form = useForm({
  id: null,
  full_name: "",
  department: "",
  position: "",
});

// populate form when editing
watch(
  () => props.accountablePerson,
  (val) => {
    if (val) Object.assign(form, val);
  },
  { immediate: true }
);

// animation
const isClosing = ref(false);

function closeWithAnimation() {
  isClosing.value = true;
  setTimeout(() => {
    emit("close");
    isClosing.value = false;
  }, 200);
}

function submitForm() {
  if (props.mode === "edit") {
    form.put(route("accountable.update", form.id), {
      onSuccess: () => {
        toast.add({
          severity: "success",
          summary: "Updated",
          detail: "Accountable Person updated successfully.",
          life: 3000,
        });

        emit("close");
      },
      onError: (errors) => {
        const firstError = Object.values(errors)[0];

        toast.add({
          severity: "error",
          summary: "Validation Failed",
          detail: firstError,
          life: 4000,
        });
      },
    });
  } else {
    form.post(route("accountable.store"), {
      onSuccess: () => {
        toast.add({
          severity: "success",
          summary: "Created",
          detail: "Accountable Person added successfully.",
          life: 3000,
        });

        form.reset();
        emit("close");
      },
      onError: (errors) => {
        const firstError = Object.values(errors)[0];

        toast.add({
          severity: "error",
          summary: "Validation Failed",
          detail: firstError,
          life: 4000,
        });
      },
    });
  }
}
</script>

<template>
  <div class="fixed inset-0 bg-black/40 flex items-center justify-center p-4">
    <div
      :class="[
        'bg-white rounded-lg w-full max-w-lg p-6 overflow-y-auto max-h-[90vh]',
        isClosing ? 'animate-pop-out' : 'animate-pop-in',
      ]"
      @click.stop
    >
      <h3 class="text-2xl font-bold text-[#850038] mb-6">
        {{
          mode === "edit" ? "Edit Accountable Person" : "Add Accountable Person"
        }}
      </h3>

    <Toast />

      <!-- FORM -->
      <form @submit.prevent="submitForm">
        <div class="space-y-4">
          <div v-for="accPerF in accountablePersonFields" :key="accPerF.model">
            <label class="block text-sm text-[#3B3B3B] font-bold mb-1">
              {{ accPerF.label }}
            </label>

            <input
              v-model="form[accPerF.model]"
              :type="accPerF.type || 'text'"
              :placeholder="accPerF.placeholder"
              :class="[
                'w-full rounded-md px-3 py-3 text-[#3B3B3B] bg-[#F8F8F8]  text-sm focus:ring-1 focus:outline-none',
                form.errors[accPerF.model]
                  ? 'border-red-500 focus:ring-red-500 focus:border-red-500'
                  : 'border-gray-300 focus:ring-[#850038] focus:border-[#850038]',
              ]"/>
            <div v-if="form.errors[accPerF.model]" class="text-red-500 text-sm">
              {{ form.errors[accPerF.model] }}
            </div>
          </div>
        </div>

        <!-- BUTTONS -->
        <div class="flex justify-end gap-3 mt-6">
          <button
            type="button"
            @click="closeWithAnimation"
            class="border border-gray-400 px-6 py-3 rounded-full text-sm text-[#3B3B3B] font-semibold hover:bg-gray-100"
          >
            Cancel
          </button>

          <button
            type="submit"
            class="bg-[#0E6021] text-white px-8 py-3 rounded-full text-sm font-semibold hover:bg-green-800"
          >
            {{ mode === "edit" ? "Update" : "Add" }}
          </button>
        </div>
      </form>
    </div>
  </div>
</template>
