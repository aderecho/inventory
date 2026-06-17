<script setup>
const props = defineProps({
    user: {
        type: Object,
        required: true,
    },
});

const greeting = () => {
    const hour = new Date().getHours();
    if (hour < 12) return 'Good morning';
    if (hour < 18) return 'Good afternoon';
    return 'Good evening';
};

const today = new Date().toLocaleDateString('en-US', {
    weekday: 'long',
    year: 'numeric',
    month: 'long',
    day: 'numeric',
});
</script>

<template>
    <div class="bg-[#850038] rounded-2xl px-9 py-8 flex items-center justify-between gap-6 font-['Poppins']">

        <!-- Left -->
        <div>
            <!-- Eyebrow -->
            <p class="text-[11px] font-semibold tracking-[0.12em] uppercase text-white/50 mb-2">
                UNIVERSITY OF THE PHILIPPINES CEBU
            </p>

            <!-- Name -->
            <h1
                class="text-[28px] font-bold text-white leading-snug"
                style="font-family: Palatino, 'Palatino Linotype', 'Book Antiqua', Georgia, serif;"
            >
                {{ greeting() }}, {{ user?.user_profiles?.full_name ?? 'there' }}.
            </h1>

            <!-- Date -->
            <p
                class="mt-3 text-[12px] text-white/40 italic"
                style="font-family: Palatino, 'Palatino Linotype', 'Book Antiqua', Georgia, serif;"
            >
                {{ today }}
            </p>
        </div>

    </div>
</template>