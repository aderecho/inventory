<script setup>
import { computed, ref } from "vue";
import { router } from "@inertiajs/vue3";
import { motion } from "motion-v";
import {
    Bell,
    Check,
    ClipboardCheck,
    HandCoins,
    UserPlus,
    X,
} from "lucide-vue-next";

const props = defineProps({
    notification: { type: Object, default: null },
    // The administration navbar shows the same details without answering items.
    allowActions: { type: Boolean, default: true },
});

const emit = defineEmits(["close"]);

const processingId = ref(null);
const bulkProcessing = ref(false);

const TYPES = {
    items_pending_approval: {
        icon: ClipboardCheck,
        tile: "bg-[#005740]/10 text-[#005740]",
        label: "Approval",
    },
    borrowing_created: {
        icon: HandCoins,
        tile: "bg-[#005740]/10 text-[#005740]",
        label: "Borrowing",
    },
    user_created: {
        icon: UserPlus,
        tile: "bg-[#005740]/10 text-[#005740]",
        label: "Account",
    },
};

const meta = computed(
    () =>
        TYPES[props.notification?.notification_type] ?? {
            icon: Bell,
            tile: "bg-gray-100 text-gray-500",
            label: "Notice",
        },
);

const reference = computed(() => {
    const notification = props.notification ?? {};

    return (
        notification.reference_code ||
        [notification.document_type, notification.category]
            .filter(Boolean)
            .join(" · ")
    );
});

const items = computed(() => props.notification?.items ?? []);

const isPending = (item) =>
    item.approval_status !== "approved" && item.approval_status !== "rejected";

// Only acknowledgement notices ask for an answer. A borrowing notice lists
// the same shape of items, but there is nothing to approve there.
const isApprovalNotice = computed(
    () => props.notification?.notification_type === "items_pending_approval",
);

const canAnswer = (item) =>
    props.allowActions && isApprovalNotice.value && isPending(item);

const pendingItems = computed(() =>
    props.allowActions && isApprovalNotice.value
        ? items.value.filter(isPending)
        : [],
);

function setStatus(item, status) {
    if (processingId.value) return;

    processingId.value = item.id;

    router.patch(
        route("inventory_items.approval", item.id),
        { approval_status: status },
        {
            preserveScroll: true,
            preserveState: true,
            onSuccess: () => {
                item.approval_status = status;
            },
            onFinish: () => {
                processingId.value = null;
            },
        },
    );
}

function openDashboard() {
    emit("close");
    router.visit(route("user.dashboard"));
}

function openTarget() {
    const url = props.notification?.url;

    emit("close");

    if (url) {
        router.visit(url);
    }
}

/**
 * Answers every still-pending item on this notice, one request at a time so
 * each row keeps its own server response.
 */
async function bulkUpdate(status) {
    if (bulkProcessing.value || processingId.value) return;

    const pending = pendingItems.value;

    if (!pending.length) return;

    bulkProcessing.value = true;

    try {
        for (const item of pending) {
            await new Promise((resolve) => {
                router.patch(
                    route("inventory_items.approval", item.id),
                    { approval_status: status },
                    {
                        preserveScroll: true,
                        preserveState: true,
                        onSuccess: () => {
                            item.approval_status = status;
                        },
                        onFinish: resolve,
                    },
                );
            });
        }
    } finally {
        bulkProcessing.value = false;
    }
}
</script>

<template>
    <Teleport to="body">
        <motion.div
            v-if="notification"
            class="fixed inset-0 z-[9998] flex items-center justify-center bg-black/45 p-3 backdrop-blur-sm sm:p-6"
            :initial="{ opacity: 0 }"
            :animate="{ opacity: 1 }"
            @click.self="emit('close')"
        >
            <motion.div
                class="flex max-h-[92vh] w-full max-w-xl flex-col overflow-hidden rounded-2xl bg-white shadow-2xl"
                :initial="{ opacity: 0, y: 18, scale: 0.97 }"
                :animate="{ opacity: 1, y: 0, scale: 1 }"
                :transition="{ type: 'spring', stiffness: 320, damping: 28 }"
            >
                <!-- HEADER -->
                <div
                    class="relative flex items-start gap-3 bg-gradient-to-r from-[#003d2c] via-[#005740] to-[#0E6021] px-5 py-4 sm:px-6"
                >
                    <span
                        class="pointer-events-none absolute inset-0 opacity-20"
                        style="
                            background: radial-gradient(
                                circle at 92% 8%,
                                #fff 0,
                                transparent 42%
                            );
                        "
                    ></span>

                    <span
                        class="relative flex h-10 w-10 flex-shrink-0 items-center justify-center rounded-xl border border-white/20 bg-white/15 text-white"
                    >
                        <component :is="meta.icon" class="h-5 w-5" />
                    </span>

                    <div class="relative min-w-0 flex-1">
                        <p class="text-[10px] font-bold uppercase tracking-[0.14em] text-white/70">
                            {{ meta.label }}
                            <template v-if="reference">· {{ reference }}</template>
                        </p>
                        <h3 class="truncate text-base font-bold text-white">
                            {{ notification.title }}
                        </h3>
                    </div>

                    <button
                        type="button"
                        class="relative flex h-9 w-9 items-center justify-center rounded-lg text-white/80 transition-colors hover:bg-white/15 hover:text-white"
                        @click="emit('close')"
                    >
                        <X class="h-4 w-4" />
                    </button>
                </div>

                <!-- BODY -->
                <div class="flex-1 overflow-y-auto px-5 py-4">
                    <p class="text-sm leading-relaxed text-gray-600">
                        {{ notification.message }}
                    </p>

                    <div
                        v-if="items.length"
                        class="mt-4 overflow-hidden rounded-xl border border-gray-200"
                    >
                        <div
                            class="flex items-center justify-between border-b border-gray-100 bg-[#005740]/[0.04] px-3 py-2"
                        >
                            <p class="text-[11px] font-semibold uppercase tracking-wide text-[#005740]">
                                Items
                            </p>

                            <div
                                v-if="pendingItems.length"
                                class="flex items-center gap-1.5"
                            >
                                <span class="text-[10px] font-medium text-gray-400">
                                    {{ pendingItems.length }} pending
                                </span>

                                <button
                                    type="button"
                                    class="flex items-center gap-1 rounded-md bg-[#005740] px-2 py-1 text-[10px] font-semibold text-white transition-colors hover:bg-[#00432f] disabled:cursor-not-allowed disabled:opacity-50"
                                    :disabled="bulkProcessing || Boolean(processingId)"
                                    @click="bulkUpdate('approved')"
                                >
                                    <Check class="h-3 w-3" />
                                    {{ bulkProcessing ? "Working…" : "Approve all" }}
                                </button>

                                <button
                                    type="button"
                                    class="rounded-md border border-red-200 px-2 py-1 text-[10px] font-semibold text-red-600 transition-colors hover:bg-red-50 disabled:cursor-not-allowed disabled:opacity-50"
                                    :disabled="bulkProcessing || Boolean(processingId)"
                                    @click="bulkUpdate('rejected')"
                                >
                                    Reject all
                                </button>
                            </div>

                            <span v-else class="text-[11px] text-gray-400">
                                {{ items.length }}
                            </span>
                        </div>

                        <ul class="divide-y divide-gray-100">
                            <li
                                v-for="item in items"
                                :key="item.id"
                                class="flex flex-wrap items-center gap-2 px-3 py-2.5"
                            >
                                <div class="min-w-0 flex-1">
                                    <p class="truncate text-sm font-medium text-[#1f2d27]">
                                        {{ item.item_name || "Item" }}
                                    </p>
                                    <p class="text-[11px] text-gray-400">
                                        {{ item.property_number }}
                                    </p>
                                </div>

                                <template v-if="canAnswer(item)">
                                    <button
                                        type="button"
                                        class="flex items-center gap-1 rounded-lg bg-[#005740] px-2.5 py-1.5 text-[11px] font-semibold text-white transition-colors hover:bg-[#00432f] disabled:opacity-50"
                                        :disabled="processingId === item.id || bulkProcessing"
                                        @click="setStatus(item, 'approved')"
                                    >
                                        <Check class="h-3 w-3" />
                                        Approve
                                    </button>
                                    <button
                                        type="button"
                                        class="rounded-lg border border-red-200 px-2.5 py-1.5 text-[11px] font-semibold text-red-600 transition-colors hover:bg-red-50 disabled:opacity-50"
                                        :disabled="processingId === item.id || bulkProcessing"
                                        @click="setStatus(item, 'rejected')"
                                    >
                                        Reject
                                    </button>
                                </template>

                                <span
                                    v-else-if="item.approval_status"
                                    class="text-[11px] font-semibold"
                                    :class="
                                        item.approval_status === 'approved'
                                            ? 'text-[#2E7D32]'
                                            : 'text-red-600'
                                    "
                                >
                                    {{
                                        item.approval_status === "approved"
                                            ? "Approved"
                                            : "Rejected"
                                    }}
                                </span>
                            </li>
                        </ul>
                    </div>
                </div>

                <!-- FOOTER -->
                <div class="flex items-center justify-between gap-2 border-t border-gray-100 px-5 py-3">
                    <p class="text-[11px] text-gray-400">
                        {{
                            notification.created_at
                                ? new Date(notification.created_at).toLocaleString("en-PH", {
                                      month: "short",
                                      day: "numeric",
                                      hour: "numeric",
                                      minute: "2-digit",
                                  })
                                : ""
                        }}
                    </p>

                    <div class="flex items-center gap-2">
                        <button
                            type="button"
                            class="rounded-lg px-3 py-2 text-xs font-medium text-gray-600 transition-colors hover:bg-gray-100"
                            @click="emit('close')"
                        >
                            Close
                        </button>
                        <button
                            v-if="allowActions"
                            type="button"
                            class="rounded-lg bg-[#005740] px-3.5 py-2 text-xs font-semibold text-white transition-colors hover:bg-[#00432f]"
                            @click="openDashboard"
                        >
                            Open dashboard
                        </button>

                        <button
                            v-else-if="notification.url"
                            type="button"
                            class="rounded-lg bg-[#005740] px-3.5 py-2 text-xs font-semibold text-white transition-colors hover:bg-[#00432f]"
                            @click="openTarget"
                        >
                            Open
                        </button>
                    </div>
                </div>
            </motion.div>
        </motion.div>
    </Teleport>
</template>

