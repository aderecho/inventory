<script setup>
defineEmits(['click'])
</script>

<template>
    <button
        @click="$emit('click')"
        class="flex gap-2 bg-[#0E6021] rounded-md text-white px-3 py-2 text-xs sm:text-sm hover:bg-[#19703a] w-full sm:w-auto items-center mt-2 justify-center"
    >
        <slot></slot>
    </button>
</template>