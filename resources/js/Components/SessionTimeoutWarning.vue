<script setup>
import { ref, computed } from "vue";
import { useSessionTimeout } from "@/Composables/useSessionTimeout";

const {
    timeoutWarning,
    timeRemaining,
    formatTime,

    logout,
    dismissWarning,

    // Debug
    idleSeconds,
    idlePercentage,
    lastActivity,
    lastPing,
    pingStatus,
    stayLoggedInCount,
    timerResetCount,

    idleTimeoutSeconds,
} = useSessionTimeout();

const isDismissing = ref(false);

/*
|--------------------------------------------------------------------------
| Stay Logged In
|--------------------------------------------------------------------------
*/

const handleStayLoggedIn = async () => {
    if (isDismissing.value) {
        return;
    }

    isDismissing.value = true;

    try {
        await dismissWarning();
    } finally {
        isDismissing.value = false;
    }
};

/*
|--------------------------------------------------------------------------
| Debug Formatting
|--------------------------------------------------------------------------
*/

const formatIdleTime = computed(() => {
    const totalSeconds = Math.floor(idleSeconds.value);

    const minutes = Math.floor(totalSeconds / 60);
    const seconds = totalSeconds % 60;

    return `${String(minutes).padStart(2, "0")}:${String(seconds).padStart(
        2,
        "0",
    )}`;
});

const formatRemainingTime = computed(() => {
    const totalSeconds = Math.max(
        0,
        idleTimeoutSeconds - Math.floor(idleSeconds.value),
    );

    const minutes = Math.floor(totalSeconds / 60);
    const seconds = totalSeconds % 60;

    return `${String(minutes).padStart(2, "0")}:${String(seconds).padStart(
        2,
        "0",
    )}`;
});

const formattedLastActivity = computed(() => {
    if (!lastActivity.value) {
        return "None";
    }

    return new Date(lastActivity.value).toLocaleTimeString();
});

const formattedLastPing = computed(() => {
    if (!lastPing.value) {
        return "None";
    }

    return new Date(lastPing.value).toLocaleTimeString();
});
</script>

<template>
    <!-- ========================================================= -->
    <!-- ALWAYS VISIBLE SESSION DEBUG PANEL                        -->
    <!-- ========================================================= -->

    <!-- <div
        class="fixed bottom-4 right-4 z-[9998] w-80 rounded-xl border border-gray-300 bg-white p-4 shadow-2xl"
    >
        <div class="mb-4 flex items-center justify-between">
            <div>
                <h3 class="font-bold text-gray-800">Session Debug</h3>

                <p class="text-xs text-gray-500">Real-time idle monitoring</p>
            </div>

            <span
                class="rounded-full px-2.5 py-1 text-xs font-bold"
                :class="
                    timeoutWarning
                        ? 'bg-red-100 text-red-700'
                        : 'bg-green-100 text-green-700'
                "
            >
                {{ timeoutWarning ? "WARNING" : "ACTIVE" }}
            </span>
        </div>

        <div class="mb-4">
            <div class="mb-1 flex items-center justify-between">
                <span class="text-xs font-medium text-gray-500">
                    Idle Time
                </span>

                <span class="text-sm font-bold text-gray-800">
                    {{ formatIdleTime }}
                    <span>
                        /
                        {{
                            Math.floor(idleTimeoutSeconds / 60)
                                .toString()
                                .padStart(2, "0")
                        }}:00
                    </span>
                </span>
            </div>

            <div class="h-2.5 w-full overflow-hidden rounded-full bg-gray-200">
                <div
                    class="h-full rounded-full transition-all duration-500"
                    :class="
                        idlePercentage >= 90
                            ? 'bg-red-500'
                            : idlePercentage >= 70
                              ? 'bg-orange-500'
                              : 'bg-green-500'
                    "
                    :style="{
                        width: `${idlePercentage}%`,
                    }"
                ></div>
            </div>
        </div>

        <div class="mb-4 rounded-lg bg-gray-50 p-3 text-center">
            <p class="text-xs font-medium text-gray-500">
                Time Until Automatic Logout
            </p>

            <p
                class="mt-1 text-2xl font-bold"
                :class="
                    idlePercentage >= 90
                        ? 'text-red-600'
                        : idlePercentage >= 70
                          ? 'text-orange-600'
                          : 'text-gray-800'
                "
            >
                {{ formatRemainingTime }}
            </p>
        </div>

        <div class="space-y-2 border-t border-gray-200 pt-3 text-xs">
            <div class="flex items-center justify-between">
                <span class="text-gray-500"> Warning </span>

                <span
                    class="font-bold"
                    :class="timeoutWarning ? 'text-red-600' : 'text-green-600'"
                >
                    {{ timeoutWarning ? "YES" : "NO" }}
                </span>
            </div>

            <div class="flex items-center justify-between">
                <span class="text-gray-500"> Warning Countdown </span>

                <span class="font-semibold text-gray-800">
                    {{ timeRemaining }}s
                </span>
            </div>

            <div class="flex items-center justify-between gap-3">
                <span class="text-gray-500"> Last Activity </span>

                <span class="truncate font-semibold text-gray-800">
                    {{ formattedLastActivity }}
                </span>
            </div>

            <div class="flex items-center justify-between gap-3">
                <span class="text-gray-500"> Last Ping </span>

                <span class="truncate font-semibold text-gray-800">
                    {{ formattedLastPing }}
                </span>
            </div>

            <div class="flex items-center justify-between">
                <span class="text-gray-500"> Ping Status </span>

                <span
                    class="font-bold"
                    :class="{
                        'text-green-600': pingStatus === 'SUCCESS',

                        'text-red-600': pingStatus === 'FAILED',

                        'text-blue-600': pingStatus === 'Pinging server...',

                        'text-gray-600': pingStatus === 'Not tested',
                    }"
                >
                    {{ pingStatus }}
                </span>
            </div>

            <div class="flex items-center justify-between">
                <span class="text-gray-500"> Timer Resets </span>

                <span class="font-semibold text-gray-800">
                    {{ timerResetCount }}
                </span>
            </div>

            <div class="flex items-center justify-between">
                <span class="text-gray-500"> Stay Logged In </span>

                <span class="font-bold text-blue-600">
                    {{ stayLoggedInCount }} time(s)
                </span>
            </div>
        </div>
    </div> -->

    <!-- ========================================================= -->
    <!-- SESSION WARNING MODAL                                     -->
    <!-- ========================================================= -->

    <div
        v-if="timeoutWarning"
        class="fixed inset-0 z-[9999] flex items-center justify-center bg-black/50 px-4"
    >
        <div class="w-full max-w-md rounded-xl bg-white p-6 shadow-2xl">
            <!-- Warning Icon -->
            <div
                class="mx-auto mb-4 flex h-14 w-14 items-center justify-center rounded-full bg-red-100"
            >
                <svg
                    xmlns="http://www.w3.org/2000/svg"
                    class="h-7 w-7 text-red-600"
                    fill="none"
                    viewBox="0 0 24 24"
                    stroke="currentColor"
                    stroke-width="2"
                >
                    <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        d="M12 9v2m0 4h.01M5.07 19h13.86c1.54 0 2.5-1.67 1.73-3L13.73 4c-.77-1.33-2.69-1.33-3.46 0L3.34 16c-.77 1.33.19 3 1.73 3z"
                    />
                </svg>
            </div>

            <!-- Title -->
            <h2 class="mb-3 text-center text-xl font-bold text-gray-800">
                Session Expiring
            </h2>

            <!-- Message -->
            <p class="mb-6 text-center text-gray-600">
                Your session will expire in
                <span class="font-bold text-red-600">
                    {{ formatTime }}
                </span>
            </p>

            <!-- Countdown -->
            <div class="mb-6 rounded-lg bg-red-50 p-4 text-center">
                <p
                    class="text-xs font-medium uppercase tracking-wide text-red-500"
                >
                    Automatic Logout In
                </p>

                <p class="mt-1 text-3xl font-bold text-red-600">
                    {{ formatTime }}
                </p>
            </div>

            <!-- Buttons -->
            <div class="flex gap-4">
                <!-- Stay Logged In -->
                <button
                    type="button"
                    @click="handleStayLoggedIn"
                    :disabled="isDismissing"
                    class="flex-1 rounded-lg bg-blue-600 px-4 py-2.5 font-bold text-white transition hover:bg-blue-700 disabled:cursor-not-allowed disabled:opacity-50"
                >
                    {{ isDismissing ? "Refreshing..." : "Stay Logged In" }}
                </button>

                <!-- Logout -->
                <button
                    type="button"
                    @click="logout"
                    class="flex-1 rounded-lg bg-red-600 px-4 py-2.5 font-bold text-white transition hover:bg-red-700"
                >
                    Logout
                </button>
            </div>
        </div>
    </div>
</template>
