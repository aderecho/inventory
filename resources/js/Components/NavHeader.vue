<script setup>
import { usePage, Link } from "@inertiajs/vue3";
import { computed, ref, onMounted, onUnmounted } from "vue";
import { User, LayoutGrid, ChevronRight, Bell } from "lucide-vue-next";
import EditProfileModal from "@/Components/Modals/EditProfileModal.vue";
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
    }
}

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
const showItemsModal = ref(false);
const selectedNotification = ref(null);

function closeItemsModal() {
    showItemsModal.value = false;
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
        showItemsModal.value = true;
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
                                class="absolute right-0 mt-3 w-80 bg-white rounded-xl shadow-xl border border-gray-100 overflow-hidden z-50"
                            >
                                <!-- Header -->
                                <div
                                    class="flex items-center justify-between px-4 py-3 bg-gradient-to-r from-[#005740] via-[#006B4F] to-[#0E6021]"
                                >
                                    <p
                                        class="text-[13px] font-semibold text-white"
                                    >
                                        Notifications
                                    </p>
                                    <button
                                        v-if="unreadCount > 0"
                                        @click="markAllAsRead"
                                        class="text-[11px] font-medium text-white/80 hover:text-white transition-colors"
                                    >
                                        Mark all as read
                                    </button>
                                </div>

                                <!-- List -->
                                <div
                                    class="max-h-80 overflow-y-auto divide-y divide-gray-50"
                                >
                                    <button
                                        v-for="notification in notifications"
                                        :key="notification.id"
                                        @click="markAsRead(notification)"
                                        class="w-full text-left px-4 py-3 flex gap-3 hover:bg-gray-50 transition-colors"
                                    >
                                        <span
                                            class="mt-1.5 w-2 h-2 rounded-full shrink-0"
                                            :class="
                                                notification.read_at
                                                    ? 'bg-transparent'
                                                    : 'bg-[#D32F2F]'
                                            "
                                        ></span>

                                        <div class="min-w-0">
                                            <p
                                                class="text-[12.5px] leading-snug truncate"
                                                :class="
                                                    notification.read_at
                                                        ? 'text-gray-500 font-medium'
                                                        : 'text-gray-900 font-semibold'
                                                "
                                            >
                                                {{ notification.title }}
                                            </p>
                                            <p
                                                class="text-[11.5px] text-gray-400 leading-snug mt-0.5 line-clamp-2"
                                            >
                                                {{ notification.message }}
                                            </p>
                                            <p
                                                class="text-[10.5px] text-gray-300 mt-1"
                                            >
                                                {{
                                                    timeAgo(
                                                        notification.created_at,
                                                    )
                                                }}
                                            </p>
                                        </div>
                                    </button>

                                    <div
                                        v-if="!notifications.length"
                                        class="px-4 py-10 text-center"
                                    >
                                        <i
                                            class="fa-regular fa-bell-slash text-gray-300 text-xl mb-2"
                                        ></i>
                                        <p class="text-[12px] text-gray-400">
                                            You're all caught up.
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
        <Teleport to="body">
            <div
                v-if="showItemsModal"
                class="fixed inset-0 z-[60] flex items-center justify-center bg-black/40 px-4"
                @click.self="closeItemsModal"
            >
                <div
                    class="w-full max-w-md bg-white rounded-xl shadow-xl overflow-hidden"
                >
                    <div
                        class="flex items-center justify-between px-4 py-3 bg-gradient-to-r from-[#005740] via-[#006B4F] to-[#0E6021]"
                    >
                        <p class="text-[13px] font-semibold text-white">
                            {{ selectedNotification?.title ?? "Items" }}
                        </p>
                        <button
                            @click="closeItemsModal"
                            class="text-white/80 hover:text-white text-sm"
                        >
                            ✕
                        </button>
                    </div>

                    <div class="px-4 py-3">
                        <p class="text-[12px] text-gray-500 mb-3">
                            {{ selectedNotification?.message }}
                        </p>

                        <div
                            class="max-h-64 overflow-y-auto divide-y divide-gray-100 border border-gray-100 rounded-lg"
                        >
                            <div
                                v-for="item in selectedNotification?.items ??
                                []"
                                :key="item.id"
                                class="px-3 py-2"
                            >
                                <p
                                    class="text-[12.5px] font-medium text-gray-800"
                                >
                                    {{ item.item_name }}
                                </p>
                                <p class="text-[11px] text-gray-400">
                                    {{ item.property_number }}
                                </p>
                            </div>

                            <div
                                v-if="!selectedNotification?.items?.length"
                                class="px-3 py-6 text-center text-[12px] text-gray-400"
                            >
                                No item details available.
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </Teleport>
    </div>
</template>
