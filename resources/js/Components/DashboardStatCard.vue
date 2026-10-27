<script setup>
import { computed, onBeforeUnmount, onMounted, ref, watch } from "vue";
import { Link } from "@inertiajs/vue3";
import { motion } from "motion-v";
import { ChevronRight, HelpCircle } from "lucide-vue-next";

/**
 * One headline number, the split behind it, and a way into the records that
 * make it up. The top of the card opens the module; each legend entry opens
 * that slice of it, unless the user lacks the permission for that screen.
 */
const props = defineProps({
    item: { type: Object, required: true },
    index: { type: Number, default: 0 },
});

const TONES = {
    brand: "bg-[#0E6021]",
    amber: "bg-amber-400",
    gray: "bg-gray-300",
};

const reducedMotion =
    typeof window !== "undefined" &&
    window.matchMedia?.("(prefers-reduced-motion: reduce)").matches;

const total = computed(() => Number(props.item.value ?? 0));
const segments = computed(() => props.item.support?.segments ?? []);
const barTotal = computed(() =>
    segments.value.reduce((sum, segment) => sum + Number(segment.value ?? 0), 0),
);

const shown = ref(reducedMotion ? total.value : 0);
let frame = null;

function runCountUp(target) {
    if (frame) {
        cancelAnimationFrame(frame);
    }

    if (reducedMotion) {
        shown.value = target;

        return;
    }

    const started = performance.now();
    const duration = 900;

    const step = (now) => {
        const progress = Math.min(1, (now - started) / duration);
        const eased = 1 - Math.pow(1 - progress, 3);

        shown.value = Math.round(target * eased);

        if (progress < 1) {
            frame = requestAnimationFrame(step);
        }
    };

    frame = requestAnimationFrame(step);
}

onMounted(() => runCountUp(total.value));
watch(total, (value) => runCountUp(value));
onBeforeUnmount(() => frame && cancelAnimationFrame(frame));

function share(value) {
    if (!barTotal.value) {
        return 0;
    }

    return Math.max(0, Math.round((Number(value ?? 0) / barTotal.value) * 100));
}
</script>

<template>
    <motion.div
        :initial="reducedMotion ? false : { opacity: 0, y: 18 }"
        :animate="{ opacity: 1, y: 0 }"
        :transition="{ duration: 0.5, delay: index * 0.08, ease: 'easeOut' }"
        :while-hover="reducedMotion ? undefined : { y: -4 }"
        class="group flex h-full flex-col overflow-hidden rounded-2xl border border-gray-100 bg-white shadow-sm transition-shadow duration-300 hover:shadow-lg"
    >
        <component
            :is="item.href ? Link : 'div'"
            v-bind="item.href ? { href: item.href } : {}"
            class="flex flex-1 flex-col focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-inset focus-visible:ring-[#005740]/40"
        >
            <div
                class="relative flex items-center gap-3 overflow-hidden bg-gradient-to-br from-[#005740] via-[#004d38] to-[#003d2d] px-4 py-3.5 sm:px-5"
            >
                <div
                    class="absolute -right-6 -top-8 h-24 w-24 rounded-full bg-white/10 blur-2xl transition-transform duration-500 group-hover:scale-125"
                ></div>

                <div
                    class="relative flex h-9 w-9 flex-shrink-0 items-center justify-center rounded-xl border border-white/20 bg-white/10 text-white shadow-inner"
                >
                    <component
                        v-if="item.icon && typeof item.icon !== 'string'"
                        :is="item.icon"
                        class="h-4 w-4"
                    />
                    <i v-else-if="item.icon" :class="[item.icon, 'text-sm']"></i>
                </div>

                <div class="relative min-w-0">
                    <h3
                        class="truncate text-[11px] font-semibold uppercase tracking-wider text-white/90"
                    >
                        {{ item.title }}
                    </h3>
                    <p class="truncate text-[11px] font-medium text-white/60">
                        {{ item.meta.subtitle }}
                    </p>
                </div>

                <div class="relative ml-auto flex flex-shrink-0 items-center gap-1.5">
                    <span
                        v-if="item.support?.badge"
                        class="rounded-full bg-white/15 px-2.5 py-1 text-[10px] font-semibold text-white tabular-nums"
                    >
                        {{ item.support.badge }}
                    </span>

                    <ChevronRight
                        v-if="item.href"
                        class="h-4 w-4 text-white/50 transition-transform duration-300 group-hover:translate-x-0.5 group-hover:text-white"
                    />
                </div>
            </div>

            <div class="flex flex-1 flex-col justify-center px-4 py-4 sm:px-5">
                <div v-if="total > 0" class="flex items-baseline gap-2">
                    <span
                        class="text-3xl font-extrabold leading-none tracking-tight tabular-nums text-gray-900 sm:text-4xl"
                    >
                        {{ shown.toLocaleString() }}
                    </span>
                </div>

                <div v-else class="flex items-start gap-3">
                    <div
                        class="flex flex-shrink-0 items-center justify-center rounded-full bg-gray-50 p-2 text-gray-400"
                    >
                        <HelpCircle class="h-4 w-4" />
                    </div>
                    <div>
                        <p class="text-sm font-semibold text-gray-900">
                            {{ item.meta.emptyTitle }}
                        </p>
                        <p class="mt-0.5 text-xs text-gray-500">
                            {{ item.meta.emptyMessage }}
                        </p>
                    </div>
                </div>
            </div>
        </component>

        <div v-if="total > 0 && segments.length" class="px-4 pb-4 sm:px-5">
            <div class="flex h-1.5 overflow-hidden rounded-full bg-gray-100">
                <motion.span
                    v-for="segment in segments"
                    :key="segment.label"
                    class="h-full"
                    :class="TONES[segment.tone] ?? TONES.gray"
                    :initial="{ width: 0 }"
                    :animate="{ width: share(segment.value) + '%' }"
                    :transition="{
                        duration: 0.7,
                        delay: 0.25 + index * 0.08,
                        ease: 'easeOut',
                    }"
                />
            </div>

            <div class="mt-2.5 flex flex-wrap items-center gap-x-3 gap-y-1">
                <component
                    v-for="segment in segments"
                    :key="segment.label"
                    :is="segment.href ? Link : 'span'"
                    v-bind="segment.href ? { href: segment.href } : {}"
                    class="inline-flex items-center gap-1.5 rounded-md text-[11px] text-gray-500 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[#005740]/40"
                    :class="
                        segment.href
                            ? 'px-1.5 py-0.5 transition-colors hover:bg-[#005740]/[0.06] hover:text-[#005740]'
                            : ''
                    "
                >
                    <span
                        class="h-1.5 w-1.5 rounded-full"
                        :class="TONES[segment.tone] ?? TONES.gray"
                    ></span>
                    {{ segment.label }}
                    <b class="font-semibold text-gray-700 tabular-nums">
                        {{ Number(segment.value ?? 0).toLocaleString() }}
                    </b>
                </component>
            </div>
        </div>
    </motion.div>
</template>
