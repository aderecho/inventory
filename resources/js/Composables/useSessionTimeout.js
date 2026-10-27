import { computed, ref } from "vue";
import { router } from "@inertiajs/vue3";
import axios from "axios";

/**
 * One session watcher for the whole application.
 *
 * The state lives at module scope, so every component that calls this shares a
 * single set of deadlines. Rendering the modal in more than one place can no
 * longer race itself into signing the user out after they chose to stay.
 *
 * Deadlines are recomputed from the wall clock on every tick instead of being
 * counted down with stacked timeouts: browsers throttle timers in background
 * tabs, and a frozen tab would otherwise drift out of step with the server.
 */

const DEFAULTS = {
    idleTimeout: 900 * 1000,
    warningTime: 30 * 1000,
    absoluteTimeout: 0,
    absoluteWarning: 120 * 1000,
    startedAt: null,
};

const config = { ...DEFAULTS };

const idleModal = ref(false);
const absoluteModal = ref(false);
const secondsLeft = ref(0);
const refreshing = ref(false);
const notice = ref("");
const watching = ref(false);

let ticker = null;
let idleDeadline = 0;
let absoluteDeadline = 0;
let lastServerTouch = 0;
let lastActivityHandled = 0;
let absoluteDismissedUntil = 0;
let eventsBound = false;
let signingOut = false;

/** How often activity is allowed to refresh the server's own idle clock. */
function serverTouchInterval() {
    return Math.max(10_000, Math.min(60_000, Math.round(config.idleTimeout / 2)));
}

function idleCopy() {
    const seconds = Math.round(config.idleTimeout / 1000);

    if (seconds < 60) {
        return `${seconds} seconds`;
    }

    const minutes = Math.round(seconds / 60);

    return minutes === 1 ? "1 minute" : `${minutes} minutes`;
}

function absoluteCopy() {
    const seconds = Math.round(config.absoluteTimeout / 1000);

    if (seconds < 60) {
        return `${seconds} seconds`;
    }

    const minutes = Math.round(seconds / 60);

    if (minutes < 60) {
        return minutes === 1 ? "1 minute" : `${minutes} minutes`;
    }

    const hours = Math.round(minutes / 60);

    return hours === 1 ? "1 hour" : `${hours} hours`;
}

function clearTicker() {
    if (ticker) {
        clearInterval(ticker);
        ticker = null;
    }
}

function touchServer() {
    const now = Date.now();

    if (now - lastServerTouch < serverTouchInterval()) {
        return;
    }

    lastServerTouch = now;

    axios
        .get("/session/ping", { headers: { Accept: "application/json" } })
        .catch(() => {
            // An expired session surfaces on the next real request.
        });
}

function armIdle() {
    idleDeadline = Date.now() + config.idleTimeout;
    idleModal.value = false;
    notice.value = "";
}

function signOut() {
    if (signingOut) {
        return;
    }

    signingOut = true;
    clearTicker();
    watching.value = false;
    idleModal.value = false;
    absoluteModal.value = false;

    router.post("/logout");
}

function tick() {
    if (!watching.value) {
        return;
    }

    const now = Date.now();

    if (absoluteDeadline && now >= absoluteDeadline) {
        signOut();

        return;
    }

    if (idleDeadline && now >= idleDeadline) {
        signOut();

        return;
    }

    const absoluteDue =
        absoluteDeadline &&
        absoluteDeadline - now <= config.absoluteWarning &&
        now >= absoluteDismissedUntil;

    absoluteModal.value = Boolean(absoluteDue);

    const idleDue = idleDeadline && idleDeadline - now <= config.warningTime;

    idleModal.value = Boolean(idleDue) && !absoluteModal.value;

    const deadline = absoluteModal.value ? absoluteDeadline : idleDeadline;

    secondsLeft.value = Math.max(0, Math.ceil((deadline - now) / 1000));
}

function start() {
    clearTicker();
    ticker = setInterval(tick, 1000);
    tick();
}

function onActivity() {
    const now = Date.now();

    // Scroll fires in bursts; one reset per second is plenty.
    if (now - lastActivityHandled < 1000) {
        return;
    }

    lastActivityHandled = now;

    if (!watching.value || idleModal.value || absoluteModal.value) {
        // While a warning is on screen the clock keeps running until the user
        // answers it, otherwise a stray mouse move would silently dismiss it.
        return;
    }

    armIdle();
    touchServer();
}

function bindEvents() {
    if (eventsBound) {
        return;
    }

    eventsBound = true;

    document.addEventListener("pointerdown", onActivity, true);
    document.addEventListener("keydown", onActivity, true);
    document.addEventListener("scroll", onActivity, true);
    document.addEventListener("visibilitychange", tick);
    window.addEventListener("focus", tick);
}

async function stayLoggedIn() {
    if (refreshing.value) {
        return;
    }

    refreshing.value = true;
    notice.value = "";

    try {
        const { data } = await axios.get("/session/ping", {
            headers: { Accept: "application/json" },
        });

        if (data?.success !== true) {
            throw new Error("Session is no longer valid.");
        }

        lastServerTouch = Date.now();
        armIdle();
        tick();
    } catch (error) {
        notice.value = "Your session already ended. Sending you to the login page...";

        setTimeout(() => {
            window.location.href = "/login";
        }, 1200);
    } finally {
        refreshing.value = false;
    }
}

function signOutNow() {
    signOut();
}

function dismissAbsoluteWarning() {
    // Comes back a minute later (or when the cap is due) so the user gets a
    // second reminder instead of losing it for good.
    absoluteDismissedUntil = Math.min(
        absoluteDeadline || Date.now() + 60_000,
        Date.now() + 60_000,
    );

    absoluteModal.value = false;
    tick();
}

function stop() {
    clearTicker();
    watching.value = false;
    idleModal.value = false;
    absoluteModal.value = false;
}

/**
 * Push the server's numbers in. Called from the component on every Inertia
 * visit, which is also the moment the server refreshed its own idle clock.
 */
function applySessionConfig(next, authenticated) {
    if (next) {
        Object.assign(config, next);
    }

    bindEvents();

    if (!authenticated) {
        stop();

        return;
    }

    signingOut = false;
    watching.value = true;

    absoluteDeadline = config.startedAt
        ? config.startedAt + config.absoluteTimeout
        : 0;

    armIdle();
    start();
}

export function useSessionTimeout() {
    return {
        idleModal,
        absoluteModal,
        secondsLeft,
        refreshing,
        notice,
        applySessionConfig,
        stayLoggedIn,
        signOutNow,
        dismissAbsoluteWarning,
        idleWindow: computed(() => idleCopy()),
        absoluteWindow: computed(() => absoluteCopy()),
    };
}
