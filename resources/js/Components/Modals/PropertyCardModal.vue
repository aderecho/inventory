<script setup>
import { computed, ref, watch } from "vue";
import { AnimatePresence, motion } from "motion-v";
import axios from "axios";
import { Download, LoaderCircle, X } from "lucide-vue-next";
import PropertyCardSheet from "@/Components/PropertyCardSheet.vue";

/**
 * Loads one article's ledger and shows it as the printed Property Card.
 */
const props = defineProps({
    show: { type: Boolean, default: false },
    itemId: { type: [Number, String], default: null },
});

const emit = defineEmits(["close"]);

const card = ref(null);
const loading = ref(false);
const error = ref("");

watch(
    () => [props.show, props.itemId],
    async ([show, id]) => {
        if (!show || !id) return;

        if (card.value?.item?.id === Number(id)) return;

        loading.value = true;
        error.value = "";

        try {
            const { data } = await axios.get(route("property-cards.show", id));
            card.value = data.card;
        } catch (e) {
            error.value =
                e?.response?.data?.message ??
                "Could not load this property card. Please try again.";
        } finally {
            loading.value = false;
        }
    },
    { immediate: true },
);

const pdfUrl = computed(() =>
    props.itemId ? route("property-cards.pdf", props.itemId) : "#",
);
</script>

<template>
    <Teleport to="body">
        <AnimatePresence>
            <motion.div
                v-if="show"
                class="fixed inset-0 z-[9997] flex items-center justify-center bg-black/45 p-3 backdrop-blur-sm sm:p-6"
                :initial="{ opacity: 0 }"
                :animate="{ opacity: 1 }"
                :exit="{ opacity: 0 }"
                @click.self="emit('close')"
            >
                <motion.div
                    class="flex max-h-[94vh] w-full max-w-6xl flex-col overflow-hidden rounded-2xl bg-white shadow-2xl"
                    :initial="{ opacity: 0, y: 24, scale: 0.97 }"
                    :animate="{ opacity: 1, y: 0, scale: 1 }"
                    :exit="{ opacity: 0, y: 16, scale: 0.98 }"
                    :transition="{ type: 'spring', stiffness: 300, damping: 28 }"
                >
                    <!-- Window bar -->
                    <div
                        class="flex items-start justify-between gap-3 border-b border-gray-200 px-5 py-3"
                    >
                        <div class="min-w-0">
                            <p
                                class="text-[10px] font-bold uppercase tracking-[0.14em] text-gray-400"
                            >
                                Property Card
                            </p>
                            <h3
                                class="truncate text-sm font-bold text-[#1b1b1b] sm:text-base"
                            >
                                {{ card?.item?.item_name ?? "Loading…" }}
                                <span
                                    v-if="card?.item?.property_number"
                                    class="font-medium text-gray-500"
                                >
                                    · {{ card.item.property_number }}
                                </span>
                            </h3>
                        </div>

                        <div class="flex flex-shrink-0 items-center gap-2">
                            <a
                                :href="pdfUrl"
                                class="flex h-9 items-center gap-2 rounded-lg bg-[#0E6021] px-3 text-xs font-semibold text-white transition-colors hover:bg-[#19703a]"
                            >
                                <Download class="h-4 w-4" />
                                <span class="hidden sm:inline">Download PDF</span>
                            </a>

                            <button
                                type="button"
                                class="flex h-9 w-9 items-center justify-center rounded-lg text-gray-400 transition-colors hover:bg-gray-100 hover:text-gray-600"
                                @click="emit('close')"
                            >
                                <X class="h-4 w-4" />
                            </button>
                        </div>
                    </div>

                    <!-- Sheet -->
                    <div class="flex-1 overflow-auto bg-gray-50 p-3 sm:p-5">
                        <div
                            v-if="loading"
                            class="flex min-h-[320px] flex-col items-center justify-center gap-3 text-gray-500"
                        >
                            <LoaderCircle class="h-6 w-6 animate-spin" />
                            <p class="text-xs font-medium">
                                Building the card from this item's history…
                            </p>
                        </div>

                        <p
                            v-else-if="error"
                            class="mx-auto max-w-md rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-center text-sm text-red-700"
                        >
                            {{ error }}
                        </p>

                        <PropertyCardSheet v-else-if="card" :card="card" />
                    </div>
                </motion.div>
            </motion.div>
        </AnimatePresence>
    </Teleport>
</template>

