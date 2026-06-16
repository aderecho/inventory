<script setup>
defineEmits(['click'])
</script>

<template>
    <button
        @click="$emit('click')"
        class="flex rounded-md px-3 py-2 bg-[#0E6021] hover:bg-[#19703a] w-full sm:w-auto items-center mt-2 justify-center"
    >
        <slot />
    </button>
</template>