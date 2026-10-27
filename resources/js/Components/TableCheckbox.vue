<script setup>
import { computed } from "vue";

const props = defineProps({
    checked: { type: Boolean, default: false },
    indeterminate: { type: Boolean, default: false },
    // "brand" for rows on white, "onDark" for the green table header.
    tone: { type: String, default: "brand" },
    // sm | md | lg
    size: { type: String, default: "md" },
    title: { type: String, default: "" },
    // Accessible name for the real input. Preferred over `title` where a
    // tooltip of our own is shown, so the browser's tooltip does not double up.
    ariaLabel: { type: String, default: "" },
});

const emit = defineEmits(["toggle"]);

const sizeClass = computed(() =>
    props.size === "lg" ? "h-6 w-6" : props.size === "sm" ? "h-4 w-4" : "h-5 w-5",
);

const stateClass = computed(() => {
    const active = props.checked || props.indeterminate;

    if (props.tone === "onDark") {
        return active
            ? "border-white bg-white shadow-[0_1px_6px_rgba(255,255,255,0.35)] focus-within:ring-white/50"
            : "border-white/50 bg-white/10 hover:border-white hover:bg-white/25 focus-within:ring-white/50";
    }

    return active
        ? "border-[#0E6021] bg-[#0E6021] shadow-[0_1px_6px_rgba(14,96,33,0.35)] focus-within:ring-[#0E6021]/30"
        : "border-gray-300 bg-white hover:border-[#0E6021]/70 hover:bg-[#0E6021]/5 focus-within:ring-[#0E6021]/30";
});

const tickClass = computed(() =>
    props.tone === "onDark"
        ? "h-4 w-4 text-[#0E6021]"
        : "h-3.5 w-3.5 text-white",
);

const dashClass = computed(() =>
    props.tone === "onDark"
        ? "h-[3px] w-3 rounded-full bg-[#0E6021]"
        : "h-[3px] w-2.5 rounded-full bg-white",
);
</script>

<template>
    <label
        class="inline-flex cursor-pointer select-none items-center justify-center align-middle"
        :title="title"
    >
        <span
            :class="[
                'relative inline-flex items-center justify-center rounded-md border-2 transition-all duration-150 ease-out focus-within:ring-2',
                sizeClass,
                stateClass,
            ]"
        >
            <!-- checked -->
            <svg
                v-if="checked && !indeterminate"
                viewBox="0 0 20 20"
                fill="none"
                stroke="currentColor"
                stroke-width="3"
                stroke-linecap="round"
                stroke-linejoin="round"
                :class="['pointer-events-none', tickClass]"
            >
                <path d="M4 10.5l4 4 8-9" />
            </svg>

            <!-- partly selected -->
            <span
                v-else-if="indeterminate"
                :class="['pointer-events-none', dashClass]"
            ></span>

            <!-- the real control, kept for keyboard and screen readers -->
            <input
                type="checkbox"
                class="absolute inset-0 h-full w-full cursor-pointer opacity-0"
                :checked="checked"
                :aria-label="ariaLabel || undefined"
                :aria-checked="indeterminate ? 'mixed' : checked"
                @click.stop="emit('toggle')"
            />
        </span>
    </label>
</template>
