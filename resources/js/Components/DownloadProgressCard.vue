<script setup>
import { computed, onBeforeUnmount, ref, watch } from "vue";
import { AnimatePresence, motion } from "motion-v";
import { Check, TriangleAlert, X } from "lucide-vue-next";

/**
 * Shared progress card for streamed downloads (IIRUP prints, inventory
 * stickers). Every number is what the server last reported, so it never
 * guesses: the ring moves when a stage of the generator really finishes.
 */
const props = defineProps({
    job: { type: Object, default: null },
});

const emit = defineEmits(["dismiss"]);

const status = computed(() => props.job?.state ?? "idle");

const isGenerating = computed(() => status.value === "generating");
const isSuccess = computed(() => status.value === "success");
const isError = computed(() => status.value === "error");

const RADIUS = 45.5;
const CIRCUMFERENCE = 2 * Math.PI * RADIUS;
const gradientId = `download-ring-${Math.random().toString(36).slice(2, 8)}`;

const percent = computed(() => {
    if (isSuccess.value) {
        return 100;
    }

    return Math.min(100, Math.max(0, Math.round(props.job?.percent ?? 0)));
});

const dashOffset = computed(() => CIRCUMFERENCE * (1 - percent.value / 100));

const ringStroke = computed(() =>
    isError.value ? "#D71D1D" : `url(#${gradientId})`,
);

// A live reading of how long the server has been working, since the renderer
// stage is one call that can report nothing until it returns.
const now = ref(Date.now());
let ticker = null;

watch(
    isGenerating,
    (generating) => {
        if (ticker) {
            clearInterval(ticker);
            ticker = null;
        }

        if (!generating) {
            return;
        }

        now.value = Date.now();
        ticker = setInterval(() => {
            now.value = Date.now();
        }, 100);
    },
    { immediate: true },
);

onBeforeUnmount(() => {
    if (ticker) {
        clearInterval(ticker);
    }
});

const elapsed = computed(() => {
    const startedAt = props.job?.startedAt;

    if (!isGenerating.value || !startedAt) {
        return "";
    }

    return ((now.value - startedAt) / 1000).toFixed(1) + "s";
});

const pillText = computed(() => {
    const step = props.job?.step ?? "";

    return elapsed.value ? `${step} · ${elapsed.value}` : step;
});

const cardEnter = { type: "spring", stiffness: 300, damping: 26, mass: 0.9 };
const haloPulse = { duration: 1.9, repeat: Infinity, ease: "easeInOut" };

// Same convention as the form modals: the backdrop swallows clicks, and closes
// the card only once the report is finished or has failed.
function onBackdropClick() {
    if (!isGenerating.value) {
        emit("dismiss");
    }
}
</script>

<template>
    <AnimatePresence>
        <motion.div
            v-if="job"
            key="download-job"
            :initial="{ opacity: 0 }"
            :animate="{ opacity: 1 }"
            :exit="{ opacity: 0 }"
            :transition="{ duration: 0.18 }"
            class="pointer-events-none fixed inset-0 z-[9998] flex items-center justify-center px-4 py-6"
        >
            <div
                class="modal-backdrop pointer-events-auto absolute inset-0"
                @click="onBackdropClick"
            ></div>

            <div
                class="download-ambient pointer-events-none absolute left-1/2 top-1/2 h-72 w-72 -translate-x-1/2 -translate-y-1/2 rounded-full"
            ></div>

            <motion.div
                :initial="{ opacity: 0, y: 22, scale: 0.94 }"
                :animate="{ opacity: 1, y: 0, scale: 1 }"
                :exit="{ opacity: 0, y: 10, scale: 0.96 }"
                :transition="cardEnter"
                class="download-card pointer-events-auto relative w-full max-w-sm overflow-hidden px-6 py-7 text-center"
            >
                <div
                    class="download-halo pointer-events-none absolute inset-x-0 -top-24 h-44"
                ></div>

                <button
                    v-if="!isGenerating"
                    type="button"
                    class="absolute right-4 top-4 rounded-full p-1.5 text-gray-400 transition-colors hover:bg-black/5 hover:text-gray-600"
                    @click="emit('dismiss')"
                >
                    <X class="h-4 w-4" />
                </button>

                <div class="relative mx-auto h-32 w-32">
                    <div
                        v-if="isError"
                        class="absolute inset-0 flex items-center justify-center"
                    >
                        <div
                            class="download-error-disc flex h-24 w-24 items-center justify-center rounded-full"
                        >
                            <TriangleAlert class="h-10 w-10 text-[#D71D1D]" />
                        </div>
                    </div>

                    <motion.span
                        v-if="isGenerating"
                        class="download-pulse absolute -inset-3 rounded-full"
                        :animate="{ scale: [1, 1.08, 1], opacity: [0.7, 0.2, 0.7] }"
                        :transition="haloPulse"
                    />

                    <svg
                        v-if="!isError"
                        viewBox="0 0 100 100"
                        class="relative h-full w-full -rotate-90"
                    >
                        <defs>
                            <linearGradient
                                :id="gradientId"
                                x1="0"
                                y1="0"
                                x2="1"
                                y2="1"
                            >
                                <stop offset="0%" stop-color="#0E6021" />
                                <stop offset="100%" stop-color="#54B3AB" />
                            </linearGradient>
                        </defs>

                        <circle
                            cx="50"
                            cy="50"
                            :r="RADIUS"
                            fill="none"
                            stroke="rgba(17, 24, 39, 0.08)"
                            stroke-width="6"
                        />
                        <circle
                            cx="50"
                            cy="50"
                            :r="RADIUS"
                            fill="none"
                            stroke-width="6"
                            stroke-linecap="round"
                            :stroke="ringStroke"
                            :stroke-dasharray="CIRCUMFERENCE"
                            :stroke-dashoffset="dashOffset"
                            style="
                                transition:
                                    stroke-dashoffset 0.3s ease-out,
                                    stroke 0.3s ease;
                            "
                        />
                    </svg>

                    <div
                        v-if="!isError"
                        class="absolute inset-0 flex flex-col items-center justify-center"
                    >
                        <span class="flex items-baseline gap-0.5 text-[#12211c]">
                            <span
                                class="text-[40px] font-semibold leading-none tabular-nums tracking-tight"
                            >
                                {{ percent }}
                            </span>
                            <span class="text-sm font-semibold text-gray-400">
                                %
                            </span>
                        </span>
                    </div>

                    <AnimatePresence>
                        <motion.span
                            v-if="isSuccess"
                            key="check"
                            class="download-badge absolute -bottom-1 -right-1 flex h-10 w-10 items-center justify-center rounded-full bg-[#0E6021] text-white"
                            :initial="{ scale: 0, rotate: -25 }"
                            :animate="{ scale: 1, rotate: 0 }"
                            :exit="{ scale: 0, opacity: 0 }"
                            :transition="{
                                type: 'spring',
                                stiffness: 480,
                                damping: 18,
                            }"
                        >
                            <Check class="h-5 w-5" />
                        </motion.span>
                    </AnimatePresence>
                </div>

                <p class="mt-5 text-[15px] font-semibold tracking-tight text-[#12211c]">
                    {{ job.title }}
                </p>

                <div v-if="job.step" class="mt-2.5 flex items-center justify-center">
                    <span
                        class="inline-flex items-center gap-1.5 rounded-full px-3 py-1 text-[11px] font-semibold"
                        :class="
                            isError ? 'download-pill download-pill-error' : 'download-pill'
                        "
                    >
                        <span
                            v-if="isGenerating"
                            class="relative flex h-1.5 w-1.5"
                        >
                            <span
                                class="download-dot absolute inline-flex h-full w-full animate-ping rounded-full opacity-60"
                            ></span>
                            <span
                                class="download-dot relative inline-flex h-1.5 w-1.5 rounded-full"
                            ></span>
                        </span>
                        {{ pillText }}
                    </span>
                </div>

                <p
                    v-if="job.message"
                    class="mt-3 break-words text-xs leading-relaxed text-gray-500"
                    :class="isError ? 'line-clamp-4' : 'truncate'"
                >
                    {{ job.message }}
                </p>
            </motion.div>
        </motion.div>
    </AnimatePresence>
</template>

<style scoped>
/* No border and no outline: the card reads as a soft translucent surface. */
.download-card {
    border: 0;
    border-radius: 28px;
    background: linear-gradient(
        180deg,
        rgba(255, 255, 255, 0.82) 0%,
        rgba(255, 255, 255, 0.58) 100%
    );
    box-shadow:
        0 20px 55px -26px rgba(11, 29, 23, 0.45),
        0 2px 10px -6px rgba(11, 29, 23, 0.18);
    -webkit-backdrop-filter: blur(22px) saturate(140%);
    backdrop-filter: blur(22px) saturate(140%);
}

.download-ambient {
    background: rgba(14, 96, 33, 0.14);
    filter: blur(52px);
}

.download-halo {
    background: radial-gradient(
        closest-side,
        rgba(14, 96, 33, 0.18),
        transparent
    );
}

.download-error-disc {
    background: rgba(215, 29, 29, 0.1);
    box-shadow: inset 0 0 0 1px rgba(215, 29, 29, 0.18);
}

.download-pulse {
    background: rgba(14, 96, 33, 0.14);
}

.download-badge {
    border: 4px solid rgba(255, 255, 255, 0.92);
    box-shadow: 0 10px 24px -12px rgba(11, 29, 23, 0.6);
}

.download-pill {
    color: #0e6021;
    background: rgba(14, 96, 33, 0.1);
}

.download-pill-error {
    color: #d71d1d;
    background: rgba(215, 29, 29, 0.1);
}

.download-dot {
    background: currentColor;
}
</style>
