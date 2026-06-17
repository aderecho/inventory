import { ref } from "vue";
import { router } from "@inertiajs/vue3";

const isLoading = ref(false);
const loadingTitle = ref("Fetching Data...");
const loadingMessage = ref("Please wait while we load the results.");

let loadingTimeout = null;

const startLoading = (title = "Loading...", message = "Please wait.") => {
    loadingTitle.value = title;
    loadingMessage.value = message;
    isLoading.value = true;
};

const stopLoading = () => {
    isLoading.value = false;
};

export function useLoading() {
    router.on("start", (event) => {
        const url = event.detail.visit.url.href ?? "";

        if (url.includes("logout")) {
            loadingTitle.value = "Signing Out";
            loadingMessage.value = "Logging you out safely...";
        } else {
            loadingTitle.value = "Fetching Data...";
            loadingMessage.value = "Please wait while we load the results.";
        }

        loadingTimeout = setTimeout(() => {
            isLoading.value = true;
        }, 250);
    });

    router.on("finish", () => {
        clearTimeout(loadingTimeout);
        isLoading.value = false;
    });

    return {
        isLoading,
        loadingTitle,
        loadingMessage,
        startLoading,
        stopLoading,
    };
}
