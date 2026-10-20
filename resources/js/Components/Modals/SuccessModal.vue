<script setup>
import { ref } from "vue";

const props = defineProps({
  icon: String,
  title: String,
  message: String,
  assignEditButton: String,
  buttonConfirm: { type: String, default: "Confirm" },
  actionButtonLabel: { type: String, default: "" },
  actionButtonType: { type: String, default: "primary" },
});

const emit = defineEmits(["close", "action"]);

const isClosing = ref(false);

function closeWithAnimation() {
  isClosing.value = true;
  setTimeout(() => {
    isClosing.value = false;
    emit("close");
  }, 200);
}
</script>

<template>
  <div class="fixed inset-0 modal-backdrop flex items-center justify-center z-50">
    <div :class="['bg-white rounded-2xl p-6 w-full max-w-[600px] text-center shadow-lg',
      isClosing ? 'animate-pop-out' : 'animate-pop-in']">
      <!-- Dynamic Icon -->
      <div v-if="icon" class="flex justify-center mb-4" v-html="icon"></div>

      <!-- Dynamic Title -->
      <h1 class="text-2xl font-bold">{{ title }}</h1>

      <!-- Dynamic Message -->
      <p class="text-xl mt-2 text-gray-600">{{ message }}</p>

      <div class="space-x-4">
        <button v-if="actionButtonLabel" @click="$emit('action') "
          class="mt-6 bg-[#54B3AB] text-white px-8 py-3 rounded-md font-semibold hover:bg-[#40a79e]">
          {{ actionButtonLabel }}
        </button>
        <!-- Confirm Button -->
        <button @click="closeWithAnimation"
          class="mt-6 bg-[#41BD66] text-white px-8 py-3 rounded-md font-semibold hover:bg-[#37ac5a]">
          {{ buttonConfirm }}
        </button>
      </div>
      <slot></slot>
    </div>
  </div>
</template>
