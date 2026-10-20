<script setup>
import { computed, watch } from "vue";
import { usePage } from "@inertiajs/vue3";
import { AnimatePresence, motion } from "motion-v";
import { Clock3, TimerReset } from "lucide-vue-next";
import { useSessionTimeout } from "@/Composables/useSessionTimeout";

/**
 * Both session warnings live here: the inactivity prompt and the absolute cap.
 * It is mounted once for the whole app (see resources/js/app.js), so every
 * module gets the same behaviour and only one timer is ever running.
 */
const page = usePage();

const {
    idleModal,
    absoluteModal,
    secondsLeft,
    refreshing,
    notice,
    applySessionConfig,
    stayLoggedIn,
    signOutNow,
    dismissAbsoluteWarning,
    idleWindow,
    absoluteWindow,
} = useSessionTimeout();

watch(
    () => [page.props.sessionConfig, page.props.auth?.user?.id ?? null],
    ([sessionConfig, userId]) =>
        applySessionConfig(sessionConfig, Boolean(userId)),
    { immediate: true },
);

const countdown = computed(() => {
    const total = Math.max(0, secondsLeft.value);
    const minutes = String(Math.floor(total / 60)).padStart(2, "0");
    const seconds = String(total % 60).padStart(2, "0");

    return `${minutes}:${seconds}`;
});

const cardEnter = { type: "spring", stiffness: 300, damping: 26, mass: 0.9 };
</script>

<template>
    <AnimatePresence>
        <motion.div
            v-if="idleModal || absoluteModal"
            key="session-warning"
            :initial="{ opacity: 0 }"
            :animate="{ opacity: 1 }"
            :exit="{ opacity: 0 }"
            :transition="{ duration: 0.18 }"
            class="fixed inset-0 z-[9999] flex items-center justify-center p-4"
        >
            <div
                class="modal-backdrop absolute inset-0"
                @click="absoluteModal && dismissAbsoluteWarning()"
            ></div>

            <motion.div
                :initial="{ opacity: 0, y: 22, scale: 0.95 }"
                :animate="{ opacity: 1, y: 0, scale: 1 }"
                :exit="{ opacity: 0, y: 10, scale: 0.97 }"
                :transition="cardEnter"
                class="relative w-full max-w-md overflow-hidden rounded-3xl bg-white shadow-[0_30px_80px_-30px_rgba(0,0,0,0.6)]"
            >
                <div
                    class="h-1.5 w-full"
                    :class="
                        absoluteModal
                            ? 'bg-gradient-to-r from-[#D71D1D] to-[#f08a5d]'
                            : 'bg-gradient-to-r from-amber-400 to-[#0E6021]'
                    "
                ></div>

                <div class="px-6 pb-6 pt-7 text-center sm:px-8">
                    <div
                        class="mx-auto flex h-14 w-14 items-center justify-center rounded-2xl"
                        :class="
                            absoluteModal
                                ? 'bg-[#D71D1D]/10 text-[#D71D1D]'
                                : 'bg-amber-100 text-amber-600'
                        "
                    >
                        <TimerReset v-if="absoluteModal" class="h-6 w-6" />
                        <Clock3 v-else class="h-6 w-6" />
                    </div>

                    <h2
                        class="mt-4 text-lg font-semibold tracking-tight text-[#12211c]"
                    >
                        {{
                            absoluteModal
                                ? "Session limit reached"
                                : "Are you still there?"
                        }}
                    </h2>

                    <p class="mx-auto mt-2 max-w-sm text-sm leading-relaxed text-gray-500">
                        <template v-if="absoluteModal">
                            Your {{ absoluteWindow }} session cannot be extended.
                            Save your work now, you will be signed out when the
                            timer ends.
                        </template>
                        <template v-else>
                            You have been inactive. For your security we sign
                            you out after {{ idleWindow }} without activity.
                        </template>
                    </p>

                    <div
                        class="mt-5 rounded-2xl px-4 py-3"
                        :class="
                            absoluteModal ? 'bg-[#D71D1D]/5' : 'bg-amber-50'
                        "
                    >
                        <p
                            class="text-[11px] font-semibold uppercase tracking-[0.14em]"
                            :class="
                                absoluteModal
                                    ? 'text-[#D71D1D]/70'
                                    : 'text-amber-600/80'
                            "
                        >
                            Signing out in
                        </p>

                        <p
                            class="mt-1 text-4xl font-bold leading-none tabular-nums tracking-tight"
                            :class="
                                absoluteModal ? 'text-[#D71D1D]' : 'text-amber-700'
                            "
                        >
                            {{ countdown }}
                        </p>
                    </div>

                    <p
                        v-if="notice"
                        class="mt-3 text-xs font-medium text-[#D71D1D]"
                    >
                        {{ notice }}
                    </p>

                    <div class="mt-5 flex flex-col gap-2 sm:flex-row">
                        <template v-if="absoluteModal">
                            <button
                                type="button"
                                class="inline-flex flex-1 items-center justify-center rounded-xl bg-[#D71D1D] px-4 py-2.5 text-sm font-semibold text-white transition-colors hover:bg-[#c50e0e]"
                                @click="signOutNow"
                            >
                                Sign out now
                            </button>

                            <button
                                type="button"
                                class="inline-flex flex-1 items-center justify-center rounded-xl border border-gray-200 px-4 py-2.5 text-sm font-semibold text-gray-600 transition-colors hover:bg-gray-50"
                                @click="dismissAbsoluteWarning"
                            >
                                Keep working
                            </button>
                        </template>

                        <template v-else>
                            <button
                                type="button"
                                :disabled="refreshing"
                                class="inline-flex flex-1 items-center justify-center rounded-xl bg-[#0E6021] px-4 py-2.5 text-sm font-semibold text-white transition-colors hover:bg-[#19703a] disabled:cursor-not-allowed disabled:opacity-60"
                                @click="stayLoggedIn"
                            >
                                {{ refreshing ? "Refreshing..." : "Stay logged in" }}
                            </button>

                            <button
                                type="button"
                                class="inline-flex flex-1 items-center justify-center rounded-xl border border-gray-200 px-4 py-2.5 text-sm font-semibold text-gray-600 transition-colors hover:bg-gray-50"
                                @click="signOutNow"
                            >
                                Log out now
                            </button>
                        </template>
                    </div>
                </div>
            </motion.div>
        </motion.div>
    </AnimatePresence>
</template>
