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

function confirmDelete() {
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
                 style="background-color: #F5CCCC;">
                <i class="fa-solid fa-box-archive text-red-600 text-3xl"></i>
            </div>

            <h4 class="font-semibold mb-2 text-xl">Confirm Archive</h4>

            <p class="text-lg font-bold mb-4 text-[#888484]">
                Are you sure you want to archive
                <strong class="text-[#D71D1D]">
                    {{ item?.item_name ?? item?.supplier_name }}
                </strong>?
            </p>

            <div class="flex justify-center gap-3 mt-2">
                <button @click="closeWithAnimation"
                        class="px-8 py-3 rounded-md bg-[#D9D9D9] hover:bg-[#cfcece]">
                    Cancel
                </button>

                <button @click="confirmDelete"
                        class="px-8 py-3 rounded-md bg-[#D71D1D] hover:bg-[#d00d0d] text-white flex items-center gap-2">
                    <i class="fa-solid fa-box-archive"></i>
                    Archive
                </button>
            </div>
        </div>
    </div>
</template>
