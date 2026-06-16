<script setup>
import { ref } from "vue";

const props = defineProps({ item: Object });
const emit = defineEmits(['close', 'confirm']);

const isClosing = ref(false);

function closeWithAnimation() {
    isClosing.value = true;
    setTimeout(() => {
        emit('close');
        isClosing.value = false;
    }, 200);
}

function confirmRestore() {
    emit('confirm', props.item);
}
</script>

<template>
    <div class="fixed inset-0 bg-black/40 flex items-center justify-center p-4 z-50">
        <div :class="[
            'bg-white rounded-lg p-6 max-w-[600px] w-full flex flex-col items-center text-center',
            isClosing ? 'animate-pop-out' : 'animate-pop-in'
        ]">
            <div class="w-14 h-14 rounded-full flex items-center justify-center mb-4"
                 style="background-color: #C8EFD4;">
                <i class="fa-solid fa-rotate-left text-[#2E7D32] text-3xl"></i>
            </div>

            <h4 class="font-semibold mb-2 text-xl">Confirm Restore</h4>

            <p class="text-lg font-bold mb-4 text-[#888484]">
                Are you sure you want to restore
                <strong class="text-[#2E7D32]">
                    {{ item?.item_name }}
                </strong>?
            </p>

            <div class="flex justify-center gap-3 mt-2">
                <button @click="closeWithAnimation"
                        class="px-8 py-3 rounded-md bg-[#D9D9D9] hover:bg-[#cfcece]">
                    Cancel
                </button>

                <button @click="confirmRestore"
                        class="px-8 py-3 rounded-md bg-[#2E7D32] hover:bg-[#1b5e20] text-white flex items-center gap-2">
                    <i class="fa-solid fa-rotate-left"></i>
                    Restore
                </button>
            </div>
        </div>
    </div>
</template>