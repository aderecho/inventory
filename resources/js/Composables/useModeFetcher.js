import { router } from "@inertiajs/vue3";
import { debounce } from "lodash";
import {
    filterModes,
    DEFAULT_DEBOUNCE_MS,
    DEFAULT_PLACEHOLDER,
} from "./filterModes";

export function useModeFetcher(mode, refs) {
    const config = filterModes[mode] ?? {};

    function buildParams() {
        const fields = config.fields ?? [];
        const params = {};

        for (const field of fields) {
            params[field] = refs[field]?.value ?? "";
        }

        return config.transformParams ? config.transformParams(params) : params;
    }

    function fetchNow(overrides = {}) {
        if (!config.url) return;

        const url = typeof config.url === "function" ? config.url() : config.url;
        const params = { ...buildParams(), ...overrides };

        router.get(url, params, {
            preserveState: true,
            preserveScroll: true,
            replace: true,
            ...(config.requestOptions ?? {}),
        });
    }

    const debouncedFetch = debounce(
        fetchNow,
        config.debounceMs ?? DEFAULT_DEBOUNCE_MS,
    );

    const placeholder = config.placeholder ?? DEFAULT_PLACEHOLDER;

    const activeFields = config.fields ?? [];

    return { fetchNow, debouncedFetch, placeholder, activeFields };
}