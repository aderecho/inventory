import { onBeforeUnmount, ref } from "vue";

const SUCCESS_LINGER = 3200;
const ERROR_LINGER = 8000;

/**
 * The session middleware rotates the CSRF token when it refreshes the session
 * every 30 minutes, so the meta tag baked into the page can already be stale.
 * The XSRF cookie is re-issued on every response, and Laravel accepts it in the
 * X-XSRF-TOKEN header, which is what axios sends too.
 */
function csrfHeaders() {
    const cookie = document.cookie
        .split("; ")
        .find((part) => part.startsWith("XSRF-TOKEN="))
        ?.slice("XSRF-TOKEN=".length);

    if (cookie) {
        return { "X-XSRF-TOKEN": decodeURIComponent(cookie) };
    }

    const meta = document.querySelector('meta[name="csrf-token"]')?.content;

    return meta ? { "X-CSRF-TOKEN": meta } : {};
}

/**
 * Runs a streamed download: the server sends one ndjson line per finished stage
 * and then the file itself, so the card only ever shows what the server really
 * reported. Used by the IIRUP print and the inventory stickers.
 */
export function usePrintDownload() {
    const job = ref(null);
    let dismissTimer = null;

    function clearDismissTimer() {
        if (dismissTimer) {
            clearTimeout(dismissTimer);
            dismissTimer = null;
        }
    }

    function dismiss() {
        clearDismissTimer();
        job.value = null;
    }

    function dismissAfter(delay) {
        clearDismissTimer();
        dismissTimer = setTimeout(() => {
            job.value = null;
        }, delay);
    }

    function fail(message, errorTitle) {
        job.value = {
            state: "error",
            title: errorTitle,
            message,
            percent: 0,
            step: "Nothing was downloaded",
        };

        dismissAfter(ERROR_LINGER);
    }

    function saveFile(event) {
        const binary = window.atob(event.file ?? "");
        const bytes = new Uint8Array(binary.length);

        for (let index = 0; index < binary.length; index++) {
            bytes[index] = binary.charCodeAt(index);
        }

        const url = window.URL.createObjectURL(
            new Blob([bytes], { type: event.contentType ?? "application/octet-stream" }),
        );
        const link = document.createElement("a");
        link.href = url;
        link.download = event.filename || "download";
        document.body.appendChild(link);
        link.click();
        link.remove();
        window.URL.revokeObjectURL(url);
    }

    function handleEvent(event, copy) {
        if (event.state === "working") {
            job.value = {
                ...job.value,
                state: "generating",
                percent: event.percent ?? job.value?.percent ?? 0,
                step: event.step ?? job.value?.step ?? "",
            };

            return true;
        }

        if (event.state === "completed") {
            saveFile(event);

            job.value = {
                state: "success",
                title: copy.successTitle,
                message: event.filename ?? "",
                percent: 100,
                step: "Saved to your device",
            };
            dismissAfter(SUCCESS_LINGER);

            return false;
        }

        if (event.state === "error") {
            fail(event.message || copy.errorMessage, copy.errorTitle);

            return false;
        }

        return true;
    }

    async function download({ url, payload, title, successTitle, errorTitle, errorMessage }) {
        const copy = { successTitle, errorTitle, errorMessage };

        clearDismissTimer();

        job.value = {
            state: "generating",
            title,
            message: "",
            percent: 0,
            step: "Sending the request",
            startedAt: Date.now(),
        };

        let response;

        try {
            response = await window.fetch(url, {
                method: "POST",
                headers: {
                    "Content-Type": "application/json",
                    Accept: "application/x-ndjson",
                    "X-Requested-With": "XMLHttpRequest",
                    ...csrfHeaders(),
                },
                body: JSON.stringify(payload),
            });
        } catch (error) {
            fail("The server could not be reached.", errorTitle);

            return;
        }

        if (!response.ok || !response.body) {
            fail(
                response.status === 419
                    ? "Your session expired. Reload the page and try again."
                    : "The request was rejected.",
                errorTitle,
            );

            return;
        }

        const reader = response.body.getReader();
        const decoder = new TextDecoder();
        let buffer = "";

        try {
            while (true) {
                const { value, done } = await reader.read();

                if (done) {
                    break;
                }

                buffer += decoder.decode(value, { stream: true });

                let breakAt;

                while ((breakAt = buffer.indexOf("\n")) !== -1) {
                    const line = buffer.slice(0, breakAt).trim();
                    buffer = buffer.slice(breakAt + 1);

                    if (line && !handleEvent(JSON.parse(line), copy)) {
                        await reader.cancel();

                        return;
                    }
                }
            }
        } catch (error) {
            fail("The download stream was interrupted.", errorTitle);

            return;
        }

        if (job.value?.state === "generating") {
            fail("The download stream ended before the file was ready.", errorTitle);
        }
    }

    onBeforeUnmount(clearDismissTimer);

    return { job, download, dismiss };
}
