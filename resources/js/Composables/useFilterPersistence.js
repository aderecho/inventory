import { watch, onMounted, nextTick } from "vue";

export function useFilterPersistence(mode, refs, onRestore) {
    const storageKey = `filters-${mode}`;
    const fieldNames = Object.keys(refs);

    let isRestoring = false;

    function currentValues() {
        const values = {};
        for (const field of fieldNames) {
            values[field] = refs[field].value ?? "";
        }
        return values;
    }

    function saveFilters() {
        localStorage.setItem(storageKey, JSON.stringify(currentValues()));
    }

    onMounted(async () => {
        const saved = localStorage.getItem(storageKey);
        if (!saved) return;

        const filters = JSON.parse(saved);
        const restored = {};
        for (const field of fieldNames) {
            restored[field] = filters[field] ?? "";
        }

        const current = currentValues();
        const unchanged = fieldNames.every(
            (field) => restored[field] === current[field],
        );

        if (unchanged) return;

        isRestoring = true;
        for (const field of fieldNames) {
            refs[field].value = restored[field];
        }

        await nextTick();
        isRestoring = false;

        onRestore(restored);
    });

    watch(
        fieldNames.map((field) => refs[field]),
        () => {
            if (isRestoring) return;
            saveFilters();
        },
        { deep: true },
    );

    return {
        isRestoring: () => isRestoring,
    };
}