<script setup>
import { computed, onBeforeUnmount, onMounted, ref } from "vue";
import axios from "axios";
import { motion } from "motion-v";
import {
    Bell,
    CheckCheck,
    ClipboardCheck,
    Clock3,
    HandCoins,
    RefreshCw,
    UserPlus,
} from "lucide-vue-next";
import UserNotificationModal from "@/Components/UserComponents/UserNotificationModal.vue";

/**
 * The portal's notification bell. Approvals and loans arrive here; the portal
 * only has the dashboard, so a click marks the notice read and lands there
 * instead of following an admin URL the account cannot open.
 */
const notifications = ref([]);
const loading = ref(true);
const failed = ref(false);
const open = ref(false);
const tab = ref("all");
const containerRef = ref(null);
const selected = ref(null);

let pollTimer = null;

const unreadCount = computed(
    () => notifications.value.filter((notification) => !notification.read_at).length,
);

const visibleNotifications = computed(() =>
    tab.value === "unread"
        ? notifications.value.filter((notification) => !notification.read_at)
        : notifications.value,
);

const TYPES = {
    items_pending_approval: {
        icon: ClipboardCheck,
        tile: "bg-[#005740]/10 text-[#005740]",
        label: "Approval",
    },
    borrowing_created: {
        icon: HandCoins,
        tile: "bg-[#1d4ed8]/10 text-[#1d4ed8]",
        label: "Borrowing",
    },
    user_created: {
        icon: UserPlus,
        tile: "bg-[#850038]/10 text-[#850038]",
        label: "Account",
    },
};

function metaFor(notification) {
    return (
        TYPES[notification.notification_type] ?? {
            icon: Bell,
            tile: "bg-gray-100 text-gray-500",
            label: "Notice",
        }
    );
}

function timeAgo(value) {
    if (!value) return "";

    const then = new Date(value).getTime();

    if (Number.isNaN(then)) return "";

    const seconds = Math.max(0, (Date.now() - then) / 1000);

    if (seconds < 60) return "Just now";
    if (seconds < 3600) return Math.floor(seconds / 60) + "m ago";
    if (seconds < 86400) return Math.floor(seconds / 3600) + "h ago";
    if (seconds < 604800) return Math.floor(seconds / 86400) + "d ago";

    return new Date(value).toLocaleDateString("en-PH", {
        month: "short",
        day: "numeric",
    });
}

function referenceOf(notification) {
    return (
        notification.reference_code ||
        [notification.document_type, notification.category].filter(Boolean).join(" · ") ||
        ""
    );
}

async function fetchNotifications({ quiet = false } = {}) {
    if (!quiet) loading.value = true;

    try {
        const { data } = await axios.get(route("notifications.index"));

        // Laravel nests the payload under "data"; flatten it for the list.
        notifications.value = (data.notifications ?? []).map((notification) => ({
            ...(notification.data ?? {}),
            id: notification.id,
            read_at: notification.read_at,
            created_at: notification.created_at,
        }));
        failed.value = false;
    } catch (error) {
        if (!quiet) failed.value = true;
    } finally {
        loading.value = false;
    }
}

function toggle() {
    open.value = !open.value;

    if (open.value) {
        fetchNotifications({ quiet: true });
    }
}

function close() {
    open.value = false;
}

async function markAsRead(notification) {
    if (!notification.read_at) {
        notification.read_at = new Date().toISOString();

        try {
            await axios.patch(route("notifications.read", notification.id));
        } catch (error) {
            // The next fetch restores the server state.
        }
    }

    close();
    selected.value = notification;
}

async function markAllAsRead() {
    const previous = notifications.value;

    notifications.value = previous.map((notification) => ({
        ...notification,
        read_at: notification.read_at ?? new Date().toISOString(),
    }));

    try {
        await axios.patch(route("notifications.read-all"));
    } catch (error) {
        notifications.value = previous;
    }
}

function handlePointerDown(event) {
    if (containerRef.value && !containerRef.value.contains(event.target)) {
        close();
    }
}

function handleKeydown(event) {
    if (event.key === "Escape") {
        close();
    }
}

onMounted(() => {
    fetchNotifications();

    document.addEventListener("mousedown", handlePointerDown);
    document.addEventListener("keydown", handleKeydown);

    pollTimer = setInterval(() => {
        if (document.visibilityState === "visible") {
            fetchNotifications({ quiet: true });
        }
    }, 60000);
});

onBeforeUnmount(() => {
    clearInterval(pollTimer);
    document.removeEventListener("mousedown", handlePointerDown);
    document.removeEventListener("keydown", handleKeydown);
});
</script>

<template>
    <div ref="containerRef" class="relative">
        <button
            type="button"
            class="relative flex h-10 w-10 items-center justify-center rounded-full text-gray-600 transition-colors hover:bg-gray-100 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[#005740]/40"
            :aria-expanded="open"
            aria-haspopup="true"
            title="Notifications"
            @click="toggle"
        >
            <Bell class="h-[18px] w-[18px]" />

            <motion.span
                v-if="unreadCount"
                :key="unreadCount"
                class="absolute right-1 top-1 flex h-4 min-w-[16px] items-center justify-center rounded-full bg-[#850038] px-1 text-[9px] font-bold leading-none text-white"
                :initial="{ scale: 0.5 }"
                :animate="{ scale: 1 }"
                :transition="{ type: 'spring', stiffness: 520, damping: 22 }"
            >
                {{ unreadCount > 9 ? "9+" : unreadCount }}
            </motion.span>
        </button>

        <motion.div
            v-if="open"
                class="absolute right-0 z-50 mt-2 w-[22rem] overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-2xl sm:w-96"
                :initial="{ opacity: 0, y: -8, scale: 0.97 }"
                :animate="{ opacity: 1, y: 0, scale: 1 }"
                :transition="{ type: 'spring', stiffness: 380, damping: 30 }"
            >
                <!-- HEADER -->
                <div
                    class="bg-gradient-to-r from-[#003d2c] via-[#005740] to-[#0E6021] px-4 pb-3 pt-3.5"
                >
                    <div class="flex items-start justify-between gap-2">
                        <div>
                            <p class="text-sm font-bold text-white">
                                Notifications
                            </p>
                            <p class="text-[11px] text-white/70">
                                {{
                                    unreadCount
                                        ? unreadCount + " unread"
                                        : "You're all caught up"
                                }}
                            </p>
                        </div>

                        <button
                            v-if="unreadCount"
                            type="button"
                            class="flex items-center gap-1 rounded-lg px-2 py-1 text-[11px] font-semibold text-white/85 transition-colors hover:bg-white/15 hover:text-white"
                            @click="markAllAsRead"
                        >
                            <CheckCheck class="h-3.5 w-3.5" />
                            Mark all read
                        </button>
                    </div>

                    <!-- TABS -->
                    <div class="mt-3 flex gap-1 rounded-lg bg-white/10 p-1">
                        <button
                            v-for="option in [
                                { key: 'all', label: 'All' },
                                { key: 'unread', label: 'Unread' },
                            ]"
                            :key="option.key"
                            type="button"
                            class="relative flex-1 rounded-md px-3 py-1.5 text-[11px] font-semibold transition-colors"
                            :class="
                                tab === option.key
                                    ? 'bg-white text-[#005740] shadow-sm'
                                    : 'text-white/75 hover:bg-white/10 hover:text-white'
                            "
                            @click="tab = option.key"
                        >
                            {{ option.label }}
                            <span
                                v-if="option.key === 'unread' && unreadCount"
                                class="ml-1 rounded-full bg-[#850038] px-1.5 py-0.5 text-[9px] font-bold text-white"
                            >
                                {{ unreadCount }}
                            </span>
                        </button>
                    </div>
                </div>

                <!-- LIST -->
                <div class="max-h-[22rem] overflow-y-auto">
                    <div
                        v-if="loading"
                        class="space-y-3 px-4 py-4"
                    >
                        <div
                            v-for="n in 3"
                            :key="n"
                            class="flex animate-pulse gap-3"
                        >
                            <div class="h-9 w-9 flex-shrink-0 rounded-lg bg-gray-100"></div>
                            <div class="flex-1 space-y-2 py-1">
                                <div class="h-2.5 w-1/2 rounded bg-gray-100"></div>
                                <div class="h-2 w-3/4 rounded bg-gray-100"></div>
                            </div>
                        </div>
                    </div>

                    <div v-else-if="failed" class="px-4 py-8 text-center">
                        <p class="text-xs text-gray-500">
                            Could not load notifications.
                        </p>
                        <button
                            type="button"
                            class="mt-2 inline-flex items-center gap-1.5 rounded-lg px-2.5 py-1.5 text-[11px] font-semibold text-[#005740] hover:bg-[#005740]/10"
                            @click="fetchNotifications()"
                        >
                            <RefreshCw class="h-3.5 w-3.5" />
                            Try again
                        </button>
                    </div>

                    <ul v-else-if="visibleNotifications.length" class="divide-y divide-gray-100">
                        <motion.li
                            v-for="(notification, index) in visibleNotifications"
                            :key="notification.id"
                            :initial="{ opacity: 0, x: -6 }"
                            :animate="{ opacity: 1, x: 0 }"
                            :transition="{ delay: Math.min(index, 6) * 0.03 }"
                        >
                            <button
                                type="button"
                                class="flex w-full gap-3 px-4 py-3 text-left transition-colors hover:bg-[#005740]/[0.04]"
                                @click="markAsRead(notification)"
                            >
                                <span
                                    class="mt-0.5 flex h-9 w-9 flex-shrink-0 items-center justify-center rounded-lg"
                                    :class="metaFor(notification).tile"
                                >
                                    <component :is="metaFor(notification).icon" class="h-4 w-4" />
                                </span>

                                <span class="min-w-0 flex-1">
                                    <span class="flex items-center gap-2">
                                        <span
                                            class="rounded-full bg-gray-100 px-1.5 py-0.5 text-[9px] font-bold uppercase tracking-wide text-gray-500"
                                        >
                                            {{ metaFor(notification).label }}
                                        </span>

                                        <span
                                            v-if="referenceOf(notification)"
                                            class="truncate text-[10px] font-semibold text-[#005740]"
                                        >
                                            {{ referenceOf(notification) }}
                                        </span>

                                        <span
                                            v-if="!notification.read_at"
                                            class="ml-auto h-2 w-2 flex-shrink-0 rounded-full bg-[#850038]"
                                        ></span>
                                    </span>

                                    <span
                                        class="mt-1 block text-[12.5px] font-semibold leading-snug"
                                        :class="
                                            notification.read_at
                                                ? 'text-gray-600'
                                                : 'text-[#1b1b1b]'
                                        "
                                    >
                                        {{ notification.title }}
                                    </span>

                                    <span
                                        class="mt-0.5 block text-[11.5px] leading-snug text-gray-500"
                                    >
                                        {{ notification.message }}
                                    </span>

                                    <span
                                        v-if="notification.items?.length"
                                        class="mt-1.5 flex flex-wrap gap-1"
                                    >
                                        <span
                                            v-for="item in notification.items.slice(0, 2)"
                                            :key="item.id"
                                            class="rounded-md border border-gray-200 bg-gray-50 px-1.5 py-0.5 text-[10px] text-gray-500"
                                        >
                                            {{ item.item_name || item.property_number }}
                                        </span>

                                        <span
                                            v-if="notification.items.length > 2"
                                            class="rounded-md bg-gray-100 px-1.5 py-0.5 text-[10px] font-semibold text-gray-500"
                                        >
                                            +{{ notification.items.length - 2 }}
                                        </span>
                                    </span>

                                    <span
                                        class="mt-1 flex items-center gap-1 text-[10px] text-gray-400"
                                    >
                                        <Clock3 class="h-3 w-3" />
                                        {{ timeAgo(notification.created_at) }}
                                    </span>
                                </span>
                            </button>
                        </motion.li>
                    </ul>

                    <div v-else class="px-4 py-10 text-center">
                        <span
                            class="mx-auto flex h-11 w-11 items-center justify-center rounded-full bg-gray-100 text-gray-400"
                        >
                            <Bell class="h-5 w-5" />
                        </span>
                        <p class="mt-2 text-xs text-gray-500">
                            {{
                                tab === "unread"
                                    ? "No unread notifications."
                                    : "You're all caught up."
                            }}
                        </p>
                    </div>
                </div>

                <!-- FOOTER -->
                <div
                    class="flex items-center justify-between border-t border-gray-100 bg-gray-50/60 px-4 py-2.5"
                >
                    <span class="text-[10px] text-gray-400">
                        Updates every minute
                    </span>

                    <button
                        type="button"
                        class="flex items-center gap-1.5 text-[11px] font-semibold text-[#005740] transition-colors hover:underline"
                        @click="fetchNotifications()"
                    >
                        <RefreshCw class="h-3.5 w-3.5" />
                        Refresh
                    </button>
                </div>
        </motion.div>
        <UserNotificationModal
            :notification="selected"
            @close="selected = null"
        />
    </div>
</template>

