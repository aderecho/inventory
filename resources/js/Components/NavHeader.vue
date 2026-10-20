<script setup>
import { usePage, Link, router } from "@inertiajs/vue3";
import { computed, ref, onMounted, onUnmounted } from "vue";
import {
    ArrowLeftRight,
    ClipboardCheck,
    HandCoins,
    UserPlus,
    User,
    LayoutGrid,
    ChevronRight,
    Bell,
} from "lucide-vue-next";
import EditProfileModal from "@/Components/Modals/EditProfileModal.vue";
import UserNotificationModal from "@/Components/UserComponents/UserNotificationModal.vue";
import axios from "axios";

defineProps({ isSidebarOpen: { type: Boolean, default: true } });
defineEmits(["toggleSidebar"]);

const page = usePage();
const profile = computed(() => page.props.auth.user?.user_profiles);
const authUser = computed(() => page.props.auth.user);

const initials = computed(() => {
    const first = profile.value?.first_name?.[0] ?? "";
    const last = profile.value?.last_name?.[0] ?? "";
    return (first + last).toUpperCase();
});

const dropdownOpen = ref(false);
const dropdownRef = ref(null);

const toggleDropdown = () => (dropdownOpen.value = !dropdownOpen.value);

// Only accounts that can actually reach more than one dashboard see this.
const canSwitchWorkspace = computed(
    () => (page.props.workspaceOptions?.length ?? 0) > 1,
);

function switchWorkspace() {
    dropdownOpen.value = false;
    showQuickLinks.value = false;
    router.post(route("auth.switch-dashboard"));
}

const handleClickOutside = (e) => {
    if (dropdownRef.value && !dropdownRef.value.contains(e.target)) {
        dropdownOpen.value = false;
        showQuickLinks.value = false;
    }
    if (notifRef.value && !notifRef.value.contains(e.target)) {
        isNotifOpen.value = false;
    }
};

onMounted(() => document.addEventListener("mousedown", handleClickOutside));

onUnmounted(() =>
    document.removeEventListener("mousedown", handleClickOutside),
);

const showProfileModal = ref(false);

function openProfileModal() {
    dropdownOpen.value = false;
    showProfileModal.value = true;
}

function closeProfileModal() {
    showProfileModal.value = false;
}

// Quick Links
const showQuickLinks = ref(false);

const toggleQuickLinks = () => {
    showQuickLinks.value = !showQuickLinks.value;
};

const apps = ref([
    {
        label: "AMIS",
        icon: "/images/uplogo-1.png",
        href: "http://amis.upcebu.edu.ph",
    },
    {
        label: "BULSA",
        icon: "/images/uplogo-1.png",
        href: "http://bulsa.up.edu.ph",
    },
    {
        label: "PUSO",
        icon: "/images/uplogo-1.png",
        href: "http://puso.up.edu.ph",
    },
    {
        label: "CORE",
        icon: "/images/uplogo-2.png",
        href: "https://core.upcebu.edu.ph/login",
    },
    {
        label: "KAT-ON",
        icon: "/images/uplogo-2.png",
        href: "http://lms.upcebu.edu.ph",
    },
    {
        label: "VMS",
        icon: "/images/uplogo-2.png",
        href: "https://vms.upcebu.edu.ph/login",
    },
]);

// ---------------- Notifications ----------------
const notifications = ref([]);
const isNotifOpen = ref(false);
const notifRef = ref(null);
const notifTab = ref("all");
const notifLoading = ref(true);

const unreadCount = computed(
    () => notifications.value.filter((n) => !n.read_at).length,
);

function mapNotification(n) {
    const data = n.data ?? n;

    return {
        id: n.id ?? data.id,
        title: data.title ?? n.title ?? "Notification",
        message: data.message ?? n.message ?? "",
        notification_type: data.notification_type ?? n.notification_type ?? null,
        status: data.status ?? n.status ?? null,
        approved_by: data.approved_by ?? n.approved_by ?? null,
        category: data.category ?? n.category ?? null,
        document_type: data.document_type ?? n.document_type ?? null,
        reference_code: data.reference_code ?? n.reference_code ?? null,
        items: data.items ?? n.items ?? [],
        read_at: n.read_at ?? null,
        created_at: n.created_at ?? data.created_at ?? new Date().toISOString(),
        url: data.url ?? n.url ?? null,
    };
}

function isSameLiveNotification(existing, incoming) {
    if (existing.notification_type !== incoming.notification_type) {
        return false;
    }

    if (incoming.notification_type === "approval_status_changed") {
        return (
            !existing.read_at &&
            existing.approved_by === incoming.approved_by &&
            existing.status === incoming.status
        );
    }

    if (incoming.notification_type === "items_pending_approval") {
        return !existing.read_at && existing.category === incoming.category;
    }

    return false;
}

async function fetchNotifications() {
    try {
        const { data } = await axios.get(route("notifications.index"));
        notifications.value = data.notifications.map(mapNotification);
    } catch (e) {
        console.error("Failed to load notifications", e);
    } finally {
        notifLoading.value = false;
    }
}

const NOTIFICATION_TYPES = {
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

function notificationMeta(notification) {
    return (
        NOTIFICATION_TYPES[notification.notification_type] ?? {
            icon: Bell,
            tile: "bg-gray-100 text-gray-500",
            label: "Notice",
        }
    );
}

function notificationReference(notification) {
    return (
        notification.reference_code ||
        [notification.document_type, notification.category]
            .filter(Boolean)
            .join(" · ") ||
        ""
    );
}

const visibleNotifications = computed(() =>
    notifTab.value === "unread"
        ? notifications.value.filter((notification) => !notification.read_at)
        : notifications.value,
);

const toggleNotif = () => {
    isNotifOpen.value = !isNotifOpen.value;
    dropdownOpen.value = false;
};

const timeAgo = (dateStr) => {
    if (!dateStr) return "";
    const diff = (Date.now() - new Date(dateStr).getTime()) / 1000;
    if (diff < 60) return "Just now";
    if (diff < 3600) return `${Math.floor(diff / 60)}m ago`;
    if (diff < 86400) return `${Math.floor(diff / 3600)}h ago`;
    return `${Math.floor(diff / 86400)}d ago`;
};

// ---------------- Items modal ----------------
const selectedNotification = ref(null);

function closeItemsModal() {
    selectedNotification.value = null;
}

async function markAsRead(notification) {
    if (!notification.read_at) {
        notification.read_at = new Date().toISOString();

        try {
            await axios.patch(route("notifications.read", notification.id));
        } catch (e) {
            notification.read_at = null;
            console.error("Failed to mark notification as read", e);
            return;
        }
    }

    isNotifOpen.value = false;

    if (notification.items?.length) {
        selectedNotification.value = notification;
    } else if (notification.url) {
        router.visit(notification.url);
    }
}

async function markAllAsRead() {
    const previous = notifications.value.map((n) => n.read_at);
    notifications.value.forEach((n) => {
        if (!n.read_at) n.read_at = new Date().toISOString();
    });

    try {
        await axios.patch(route("notifications.read-all"));
    } catch (e) {
        notifications.value.forEach((n, i) => (n.read_at = previous[i]));
        console.error("Failed to mark all as read", e);
    }
}

onMounted(() => {
    fetchNotifications();

    if (authUser.value?.id && window.Echo) {
        window.Echo.private(
            `App.Models.User.${authUser.value.id}`,
        ).notification((notification) => {
            const entry = mapNotification({
                id: notification.id,
                data: notification.data ?? notification,
                read_at: null,
                created_at:
                    notification.created_at ?? new Date().toISOString(),
            });

            const idx = notifications.value.findIndex((existing) =>
                isSameLiveNotification(existing, entry),
            );

            if (idx !== -1) {
                notifications.value.splice(idx, 1, entry);
            } else {
                notifications.value.unshift(entry);
            }

            if (
                selectedNotification.value &&
                isSameLiveNotification(selectedNotification.value, entry)
            ) {
                selectedNotification.value = entry;
            }
        });
    }
});

onUnmounted(() => {
    if (authUser.value?.id && window.Echo) {
        window.Echo.leave(`App.Models.User.${authUser.value.id}`);
    }
});
</script>

<template>
    <div>
        <nav
            class="relative bg-gradient-to-r from-[#005740] via-[#006B4F] to-[#0E6021] shadow-md"
        >
            <div class="relative flex h-[80px] items-center">
                <!-- Sidebar Toggle / X -->
                <div class="absolute left-4 top-1/2 -translate-y-1/2 z-10">
                    <slot />
                </div>

                <!-- IMS Title - Centered -->
                <h3
                    class="absolute left-1/2 -translate-x-1/2 text-white font-bold text-lg tracking-wide whitespace-nowrap"
                >
                    (IMS) Inventory Management System
                </h3>

                <!-- User Section -->
                <div class="ml-auto mr-6 flex items-center gap-4">
                    <!-- Notification Bell -->
                    <div class="relative" ref="notifRef">
                        <button
                            @click="toggleNotif"
                            class="relative w-9 h-9 flex items-center justify-center rounded-full text-white hover:bg-white/10 transition-colors"
                        >
                            <Bell class="w-4.5 h-4.5" />
                            <span
                                v-if="unreadCount > 0"
                                class="absolute top-1 right-1 min-w-[16px] h-[16px] px-[3px] flex items-center justify-center rounded-full bg-[#D32F2F] text-white text-[9px] font-bold leading-none"
                            >
                                {{ unreadCount > 9 ? "9+" : unreadCount }}
                            </span>
                        </button>

                        <!-- Notification Dropdown -->
                        <transition
                            enter-active-class="transition ease-out duration-150"
                            enter-from-class="opacity-0 -translate-y-1"
                            enter-to-class="opacity-100 translate-y-0"
                            leave-active-class="transition ease-in duration-100"
                            leave-from-class="opacity-100"
                            leave-to-class="opacity-0"
                        >
                            <div
                                v-if="isNotifOpen"
                                class="absolute right-0 z-50 mt-3 w-[22rem] overflow-hidden rounded-2xl border border-gray-100 bg-white shadow-2xl sm:w-96"
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
                                            <i class="fa-solid fa-check-double text-[10px]"></i>
                                            Mark all read
                                        </button>
                                    </div>

                                    <div class="mt-3 flex gap-1 rounded-lg bg-white/10 p-1">
                                        <button
                                            v-for="option in [
                                                { key: 'all', label: 'All' },
                                                { key: 'unread', label: 'Unread' },
                                            ]"
                                            :key="option.key"
                                            type="button"
                                            class="flex-1 rounded-md px-3 py-1.5 text-[11px] font-semibold transition-colors"
                                            :class="
                                                notifTab === option.key
                                                    ? 'bg-white text-[#005740] shadow-sm'
                                                    : 'text-white/75 hover:bg-white/10 hover:text-white'
                                            "
                                            @click="notifTab = option.key"
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
                                <div class="max-h-80 overflow-y-auto">
                                    <div v-if="notifLoading" class="space-y-3 px-4 py-4">
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

                                    <ul v-else-if="visibleNotifications.length" class="divide-y divide-gray-100">
                                        <li v-for="notification in visibleNotifications" :key="notification.id">
                                            <button
                                                type="button"
                                                class="flex w-full gap-3 px-4 py-3 text-left transition-colors hover:bg-[#005740]/[0.04]"
                                                @click="markAsRead(notification)"
                                            >
                                                <span
                                                    class="mt-0.5 flex h-9 w-9 flex-shrink-0 items-center justify-center rounded-lg"
                                                    :class="notificationMeta(notification).tile"
                                                >
                                                    <component
                                                        :is="notificationMeta(notification).icon"
                                                        class="h-4 w-4"
                                                    />
                                                </span>

                                                <span class="min-w-0 flex-1">
                                                    <span class="flex items-center gap-2">
                                                        <span
                                                            class="rounded-full bg-gray-100 px-1.5 py-0.5 text-[9px] font-bold uppercase tracking-wide text-gray-500"
                                                        >
                                                            {{ notificationMeta(notification).label }}
                                                        </span>

                                                        <span
                                                            v-if="notificationReference(notification)"
                                                            class="truncate text-[10px] font-semibold text-[#005740]"
                                                        >
                                                            {{ notificationReference(notification) }}
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

                                                    <span class="mt-0.5 block text-[11.5px] leading-snug text-gray-500 line-clamp-2">
                                                        {{ notification.message }}
                                                    </span>

                                                    <span class="mt-1 flex items-center gap-1 text-[10px] text-gray-400">
                                                        <i class="fa-regular fa-clock text-[9px]"></i>
                                                        {{ timeAgo(notification.created_at) }}
                                                    </span>
                                                </span>
                                            </button>
                                        </li>
                                    </ul>

                                    <div v-else class="px-4 py-10 text-center">
                                        <span
                                            class="mx-auto flex h-11 w-11 items-center justify-center rounded-full bg-gray-100 text-gray-400"
                                        >
                                            <Bell class="h-5 w-5" />
                                        </span>
                                        <p class="mt-2 text-xs text-gray-500">
                                            {{
                                                notifTab === "unread"
                                                    ? "No unread notifications."
                                                    : "You're all caught up."
                                            }}
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </transition>
                    </div>

                    <div class="relative" ref="dropdownRef">
                        <div class="flex items-center gap-2">
                            <!-- User Initials -->
                            <div
                                class="h-9 w-9 rounded-full flex items-center justify-center flex-shrink-0 bg-white"
                            >
                                <span
                                    class="text-[#005740] text-sm font-semibold"
                                >
                                    {{ initials }}
                                </span>
                            </div>

                            <!-- Email -->
                            <span class="text-xs font-medium text-white">
                                {{ page.props.auth.user?.email }}
                            </span>

                            <!-- Settings -->
                            <button
                                @click="toggleDropdown"
                                class="focus:outline-none"
                            >
                                <i
                                    class="fa-solid fa-gear text-white text-md transition-transform duration-500 hover:rotate-[360deg]"
                                ></i>
                            </button>
                        </div>

                        <!-- Dropdown -->
                        <transition
                            enter-active-class="transition ease-out duration-200"
                            enter-from-class="opacity-0 scale-95"
                            enter-to-class="opacity-100 scale-100"
                            leave-active-class="transition ease-in duration-150"
                            leave-from-class="opacity-100 scale-100"
                            leave-to-class="opacity-0 scale-95"
                        >
                            <div
                                v-if="dropdownOpen"
                                class="absolute right-0 mt-2 w-56 rounded-xl shadow-lg overflow-hidden z-50 border border-gray-100"
                            >
                                <!-- Dropdown Header -->
                                <div
                                    class="px-4 py-3 text-white bg-gradient-to-r from-[#005740] via-[#006B4F] to-[#0E6021]"
                                >
                                    <div
                                        class="flex items-center justify-between gap-2"
                                    >
                                        <div
                                            class="flex flex-col leading-tight flex-1 min-w-0"
                                        >
                                            <span class="text-sm">
                                                {{ profile?.first_name }}
                                                {{ profile?.last_name }}
                                            </span>

                                            <span
                                                class="text-xs text-white/70 truncate"
                                            >
                                                {{
                                                    page.props.auth.user?.email
                                                }}
                                            </span>
                                        </div>
                                    </div>
                                </div>

                                <!-- Dropdown Items -->
                                <div class="bg-white py-1">
                                    <!-- My Profile -->
                                    <button
                                        type="button"
                                        class="w-full flex items-center gap-3 px-4 py-2.5 text-sm text-gray-700 hover:bg-[#005740]/10 hover:text-[#005740] transition-colors duration-200"
                                        @click="openProfileModal"
                                    >
                                        <User class="w-4 h-4 text-[#005740]" />

                                        My Profile
                                    </button>

                                    <!-- Switch Workspace -->
                                    <button
                                        v-if="canSwitchWorkspace"
                                        type="button"
                                        class="w-full flex items-center gap-3 px-4 py-2.5 text-sm text-gray-700 hover:bg-[#005740]/10 hover:text-[#005740] transition-colors duration-200"
                                        @click="switchWorkspace"
                                    >
                                        <ArrowLeftRight
                                            class="w-4 h-4 text-[#005740]"
                                        />

                                        Switch workspace
                                    </button>

                                    <!-- Switch System -->
                                    <button
                                        type="button"
                                        class="w-full flex items-center gap-3 px-4 py-2.5 text-sm text-gray-700 hover:bg-[#005740]/10 hover:text-[#005740] transition-colors duration-200"
                                        @click="toggleQuickLinks"
                                    >
                                        <LayoutGrid
                                            class="w-4 h-4 text-[#005740]"
                                        />

                                        <span class="flex-1 text-left">
                                            Switch System
                                        </span>

                                        <ChevronRight
                                            class="w-3.5 h-3.5 text-gray-400 transition-transform duration-200"
                                            :class="{
                                                'rotate-90': showQuickLinks,
                                            }"
                                        />
                                    </button>

                                    <!-- Quick Links Panel -->
                                    <transition
                                        enter-active-class="transition ease-out duration-150"
                                        enter-from-class="opacity-0 -translate-y-1"
                                        enter-to-class="opacity-100 translate-y-0"
                                        leave-active-class="transition ease-in duration-100"
                                        leave-from-class="opacity-100"
                                        leave-to-class="opacity-0"
                                    >
                                        <div
                                            v-if="showQuickLinks"
                                            class="mx-2 mb-2 rounded-lg border border-gray-100 overflow-hidden"
                                        >
                                            <!-- Quick Links Header -->
                                            <div
                                                class="px-3 py-2 bg-gradient-to-r from-[#005740] via-[#006B4F] to-[#0E6021]"
                                            >
                                                <p
                                                    class="text-[11px] font-semibold text-white"
                                                >
                                                    Quick Links
                                                </p>
                                            </div>

                                            <!-- Apps -->
                                            <div
                                                class="grid grid-cols-3 gap-1 p-2 bg-white"
                                            >
                                                <a
                                                    v-for="app in apps"
                                                    :key="app.label"
                                                    :href="app.href"
                                                    target="_blank"
                                                    rel="noopener noreferrer"
                                                    class="group flex flex-col items-center gap-1.5 px-1 py-2 rounded-lg hover:bg-gray-50 transition-colors"
                                                >
                                                    <span
                                                        class="w-9 h-9 flex items-center justify-center rounded-full bg-gradient-to-br from-[#005740]/10 via-[#006B4F]/10 to-[#0E6021]/10 group-hover:from-[#005740]/15 group-hover:via-[#006B4F]/15 group-hover:to-[#0E6021]/15 transition-colors"
                                                    >
                                                        <img
                                                            :src="app.icon"
                                                            :alt="app.label"
                                                            class="w-5 h-5 object-contain"
                                                        />
                                                    </span>

                                                    <span
                                                        class="text-[10px] font-medium text-gray-600 group-hover:text-[#005740] text-center leading-tight transition-colors"
                                                    >
                                                        {{ app.label }}
                                                    </span>
                                                </a>
                                            </div>
                                        </div>
                                    </transition>

                                    <!-- Divider -->
                                    <div
                                        class="border-t border-gray-100 my-1"
                                    ></div>

                                    <!-- Logout -->
                                    <Link
                                        :href="route('logout')"
                                        method="post"
                                        as="button"
                                        class="w-full flex items-center gap-3 px-4 py-2.5 text-sm text-red-600 hover:bg-red-50 transition-colors duration-200"
                                        @click="dropdownOpen = false"
                                    >
                                        <i
                                            class="fa-solid fa-share-from-square text-red"
                                        ></i>

                                        Logout
                                    </Link>
                                </div>
                            </div>
                        </transition>
                    </div>
                </div>
            </div>
        </nav>

        <!-- Edit Profile Modal -->
        <EditProfileModal
            :show="showProfileModal"
            :user="page.props.auth.user"
            @close="closeProfileModal"
        />
        <UserNotificationModal
            :notification="selectedNotification"
            :allow-actions="false"
            @close="closeItemsModal"
        />
    </div>
</template>
