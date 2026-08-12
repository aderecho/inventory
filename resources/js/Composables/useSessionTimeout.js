import { ref, computed, onMounted, onUnmounted } from "vue";
import { router, usePage } from "@inertiajs/vue3";
import axios from "axios";

export function useSessionTimeout() {
    const page = usePage();

    // =========================================================
    // SESSION CONFIG
    // =========================================================

    const IDLE_TIMEOUT =
        page.props.sessionConfig?.idleTimeout ?? 15 * 60 * 1000;

    const WARNING_TIME = page.props.sessionConfig?.warningTime ?? 30 * 1000;

    const IDLE_SECONDS = IDLE_TIMEOUT / 1000;

    // =========================================================
    // SESSION STATE
    // =========================================================

    const timeoutWarning = ref(false);
    const timeRemaining = ref(WARNING_TIME / 1000);

    let inactivityTimer = null;
    let warningTimer = null;
    let countdownInterval = null;
    let debugInterval = null;

    // =========================================================
    // DEBUG STATE
    // =========================================================

    const idleSeconds = ref(0);
    const idlePercentage = ref(0);

    const lastActivity = ref(null);
    const lastPing = ref(null);

    const pingStatus = ref("Not tested");

    const stayLoggedInCount = ref(0);
    const timerResetCount = ref(0);

    // =========================================================
    // FORMAT TIME
    // =========================================================

    const formatSeconds = (seconds) => {
        seconds = Math.max(0, Math.floor(seconds));

        const minutes = Math.floor(seconds / 60);
        const remainingSeconds = seconds % 60;

        return `${String(minutes).padStart(2, "0")}:${String(
            remainingSeconds,
        ).padStart(2, "0")}`;
    };

    const formatTime = computed(() => {
        return formatSeconds(timeRemaining.value);
    });

    // =========================================================
    // UPDATE DEBUG IDLE TIME
    // =========================================================

    const updateDebugIdle = () => {
        if (!lastActivity.value) {
            idleSeconds.value = 0;
            idlePercentage.value = 0;
            return;
        }

        const elapsed = Math.floor((Date.now() - lastActivity.value) / 1000);

        idleSeconds.value = Math.min(elapsed, IDLE_SECONDS);

        idlePercentage.value = Math.min(
            100,
            (idleSeconds.value / IDLE_SECONDS) * 100,
        );
    };

    // =========================================================
    // RESET TIMERS
    // =========================================================

    const resetTimers = () => {
        clearTimeout(inactivityTimer);
        clearTimeout(warningTimer);
        clearInterval(countdownInterval);

        timeoutWarning.value = false;

        // Record latest activity
        lastActivity.value = Date.now();

        // Debug
        timerResetCount.value++;

        updateDebugIdle();

        // =====================================================
        // WARNING TIMER
        // =====================================================

        warningTimer = setTimeout(() => {
            timeoutWarning.value = true;

            startCountdown();
        }, IDLE_TIMEOUT - WARNING_TIME);

        // =====================================================
        // LOGOUT TIMER
        // =====================================================

        inactivityTimer = setTimeout(() => {
            logout();
        }, IDLE_TIMEOUT);
    };

    // =========================================================
    // WARNING COUNTDOWN
    // =========================================================

    const startCountdown = () => {
        clearInterval(countdownInterval);

        let timeLeft = Math.floor(WARNING_TIME / 1000);

        timeRemaining.value = timeLeft;

        countdownInterval = setInterval(() => {
            timeLeft--;

            timeRemaining.value = Math.max(0, timeLeft);

            if (timeLeft <= 0) {
                clearInterval(countdownInterval);
            }
        }, 1000);
    };

    // =========================================================
    // LOGOUT
    // =========================================================

    const logout = () => {
        clearTimeout(inactivityTimer);
        clearTimeout(warningTimer);
        clearInterval(countdownInterval);

        timeoutWarning.value = false;

        router.post("/logout");
    };

    // =========================================================
    // STAY LOGGED IN
    // =========================================================

    const dismissWarning = async () => {
        clearInterval(countdownInterval);

        pingStatus.value = "Pinging server...";

        try {
            const response = await axios.get("/session/ping");

            lastPing.value = new Date();

            if (response.data?.success === true) {
                pingStatus.value = "SUCCESS";
            } else {
                pingStatus.value = "SUCCESS";
            }

            stayLoggedInCount.value++;

            // Hide warning
            timeoutWarning.value = false;

            // Reset frontend timeout
            resetTimers();

        } catch (error) {
            console.error("[Session] Stay Logged In failed:", error);

            pingStatus.value = "FAILED";

            // Do NOT reset the timer if server ping failed.
            // The existing timeout will continue.
        }
    };

    const events = ["pointerdown", "scroll", "click"];

    let lastScrollActivity = 0;

    const handleScroll = () => {
        const now = Date.now();

        // Only reset once every second while scrolling
        if (now - lastScrollActivity < 1000) {
            return;
        }

        lastScrollActivity = now;

        resetTimers();
    };

    const setupEventListeners = () => {
        document.addEventListener("pointerdown", resetTimers, true);
        document.addEventListener("keydown", resetTimers, true);

        // Throttled scroll
        document.addEventListener("scroll", handleScroll, true);

        resetTimers();

        debugInterval = setInterval(() => {
            updateDebugIdle();
        }, 1000);
    };

    const removeEventListeners = () => {
        document.removeEventListener("pointerdown", resetTimers, true);
        document.removeEventListener("keydown", resetTimers, true);

        document.removeEventListener("scroll", handleScroll, true);

        clearTimeout(inactivityTimer);
        clearTimeout(warningTimer);
        clearInterval(countdownInterval);
        clearInterval(debugInterval);
    };

    // =========================================================
    // LIFECYCLE
    // =========================================================

    onMounted(setupEventListeners);

    onUnmounted(removeEventListeners);

    // =========================================================
    // RETURN
    // =========================================================

    return {
        // Session
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

        idleTimeoutSeconds: IDLE_SECONDS,
    };
}
