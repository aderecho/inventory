<template>
    <div
        v-if="show"
        class="fixed inset-0 bg-black/35 backdrop-blur-sm flex items-center justify-center z-[9999]"
    >
        <div class="flex flex-col items-center gap-3 rounded-2xl px-10 py-8">
            <img src="/images/uplogo-1.png" alt="Logo" class="w-64 h-64 object-contain mb-1" />
            <p class="text-white font-semibold text-[2rem]">{{ title }}</p>
            <p class="text-white text[1.5rem]">{{ message }}</p>
            <div class="flex gap-2 mt-1">
                <span class="w-5 h-5 rounded-full bg-white animate-bounce [animation-delay:0ms]"></span>
                <span class="w-5 h-5 rounded-full bg-white animate-bounce [animation-delay:150ms]"></span>
                <span class="w-5 h-5 rounded-full bg-white animate-bounce [animation-delay:300ms]"></span>
            </div>
        </div>
    </div>
</template>

<script setup>
defineProps({
    show: {
        type: Boolean,
        default: false,
    },
    title: {
        type: String,
        default: "Loading...",
    },
    message: {
        type: String,
        default: "Please wait.",
    },
});
</script>