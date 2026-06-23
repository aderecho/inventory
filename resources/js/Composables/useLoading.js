import { ref } from "vue";
import { router } from "@inertiajs/vue3";

const isLoading = ref(false);
const loadingTitle = ref("Fetching Data...");
const loadingMessage = ref("Please wait while we load the results.");

let loadingTimeout = null;

router.on("start", (event) => {
    const url = event.detail.visit.url.href ?? "";

    loadingTitle.value = url.includes("logout")
        ? "Signing Out"
        : "Fetching Data...";

    loadingMessage.value = url.includes("logout")
        ? "Logging you out safely..."
        : "Please wait while we load the results.";

    clearTimeout(loadingTimeout);
    loadingTimeout = setTimeout(() => {
        isLoading.value = true;
    }, 0);
});

router.on("finish", () => {
    clearTimeout(loadingTimeout);
    isLoading.value = false;
});

function startLoading(title = "Loading...", message = "Please wait...") {
    loadingTitle.value = title;
    loadingMessage.value = message;
    isLoading.value = true;
}

function stopLoading() {
    isLoading.value = false;
}

export function useLoading() {
    return {
        isLoading,
        loadingTitle,
        loadingMessage,
        startLoading,
        stopLoading,
    };
}