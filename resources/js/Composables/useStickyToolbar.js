import { onBeforeUnmount, onMounted, ref } from "vue";

/**
 * For a page toolbar that sticks to the top (sticky top-0): publishes its
 * height as --table-sticky-top, which InventoryTable reads to pin its column
 * header right underneath instead of sliding under the toolbar.
 *
 * const toolbarRef = useStickyToolbar();  ->  <div ref="toolbarRef" class="sticky top-0 ...">
 */
export function useStickyToolbar() {
    const toolbarRef = ref(null);
    let observer = null;

    function publishHeight() {
        if (!toolbarRef.value) return;

        document.documentElement.style.setProperty(
            "--table-sticky-top",
            toolbarRef.value.offsetHeight + "px",
        );
    }

    onMounted(() => {
        publishHeight();

        if (typeof ResizeObserver !== "undefined" && toolbarRef.value) {
            observer = new ResizeObserver(publishHeight);
            observer.observe(toolbarRef.value);
        }

        window.addEventListener("resize", publishHeight);
    });

    onBeforeUnmount(() => {
        observer?.disconnect();
        window.removeEventListener("resize", publishHeight);
        document.documentElement.style.removeProperty("--table-sticky-top");
    });

    return toolbarRef;
}
