<script setup>
import { ref } from "vue";

const props = defineProps({
  title: { type: String },
  message: { type: String },
  icon: String,
  buttonText: String
});

const emit = defineEmits(["close"]);

const isClosing = ref(false);

function closeWithAnimation() {
  isClosing.value = true;

  setTimeout(() => {
    isClosing.value = false;
    emit("close");
  }, 300);
}
</script>

<template>
  <div class="fixed inset-0 modal-backdrop flex items-center justify-center z-50">
    <div :class="[
      'bg-white rounded-2xl p-6 w-full max-w-[600px] text-center shadow-lg',
      isClosing ? 'animate-pop-out' : 'animate-pop-in']">
      <div class="flex justify-center mb-4" v-html="icon"></div>
      <h1 class="text-2xl font-bold">{{ title }}</h1>
      <p class="text-xl mt-2 text-gray-600">{{ message }}</p>
      <button @click="closeWithAnimation"
        class="mt-6 bg-[#41BD66] text-white px-8 py-3 rounded-md font-semibold hover:bg-[#37ac5a]">
        {{ buttonText }}
      </button>

    </div>
  </div>
</template>
