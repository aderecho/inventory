<script setup>
import { computed } from "vue";

const props = defineProps({
    user: {
        type: Object,
        required: true,
    },
});

const greeting = computed(() => {
    const hour = new Date().getHours();
    if (hour < 12) return "Good morning";
    if (hour < 18) return "Good afternoon";
    return "Good evening";
});
</script>

<template>
    <div
        class="relative overflow-hidden bg-gradient-to-r from-[#005740] via-[#006B4F] to-[#0E6021] rounded-2xl px-9 py-8 font-['Poppins']"
    >
        <!-- Decorative background circles -->
        <div class="pointer-events-none absolute -top-10 -right-10 w-56 h-56 rounded-full bg-white/5"></div>
        <div class="pointer-events-none absolute -bottom-16 right-24 w-40 h-40 rounded-full bg-white/5"></div>

        <div class="relative">
            <p class="text-[11px] font-semibold tracking-[0.12em] uppercase text-white/50 mb-2">
                UNIVERSITY OF THE PHILIPPINES CEBU
            </p>

            <h1
                class="text-[28px] font-bold text-white leading-snug"
                style="font-family: Palatino, 'Palatino Linotype', 'Book Antiqua', Georgia, serif;"
            >
                {{ greeting }}, {{ user?.user_profiles?.full_name ?? "there" }}.
            </h1>
        </div>
    </div>
</template>