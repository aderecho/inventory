<script setup>
import { ref, computed, onMounted, onUnmounted, onBeforeUnmount } from "vue";
import { Link, router } from "@inertiajs/vue3";
import { LayoutGrid, Headset } from "lucide-vue-next";

const props = defineProps({
    user: {
        type: Object,
        default: () => ({}),
    },
    notifications: {
        type: Array,
        // Expected shape per item:
        // { id, title, message, read_at, created_at, url? }
        // NOTE: sample data below is placeholder for design purposes only —
        // replace with real data once a notifications source exists on the backend.
        default: () => [
            {
                id: 1,
                title: "New item assigned",
                message:
                    "A Dell Latitude 5420 Laptop has been assigned to you.",
                read_at: null,
                created_at: new Date(Date.now() - 1000 * 60 * 12).toISOString(),
            },
            {
                id: 2,
                title: "Acknowledgement receipt ready",
                message:
                    "Your PAR for Property No. 2026-00142 is ready to view.",
                read_at: null,
                created_at: new Date(
                    Date.now() - 1000 * 60 * 60 * 3,
                ).toISOString(),
            },
            {
                id: 3,
                title: "Profile updated",
                message: "Your contact information was updated successfully.",
                read_at: new Date().toISOString(),
                created_at: new Date(
                    Date.now() - 1000 * 60 * 60 * 26,
                ).toISOString(),
            },
        ],
    },
});

const isNotifOpen = ref(false);
const notifRef = ref(null);

const unreadCount = computed(
    () => props.notifications.filter((n) => !n.read_at).length,
);

const toggleNotif = () => {
    isNotifOpen.value = !isNotifOpen.value;
};

const closeNotif = (e) => {
    if (notifRef.value && !notifRef.value.contains(e.target)) {
        isNotifOpen.value = false;
    }
};

onMounted(() => document.addEventListener("click", closeNotif));
onBeforeUnmount(() => document.removeEventListener("click", closeNotif));

const timeAgo = (dateStr) => {
    if (!dateStr) return "";
    const diff = (Date.now() - new Date(dateStr).getTime()) / 1000;
    if (diff < 60) return "Just now";
    if (diff < 3600) return `${Math.floor(diff / 60)}m ago`;
    if (diff < 86400) return `${Math.floor(diff / 3600)}h ago`;
    return `${Math.floor(diff / 86400)}d ago`;
};

const markAsRead = (notification) => {
    if (notification.read_at) return;

    // Optimistic UI update; wire this to a real endpoint when available,
    // e.g. router.patch(route('notifications.read', notification.id))
    notification.read_at = new Date().toISOString();
};

const markAllAsRead = () => {
    props.notifications.forEach((n) => {
        if (!n.read_at) n.read_at = new Date().toISOString();
    });
};

const isGridOpen = ref(false);
const gridRef = ref(null);

const toggleGrid = () => {
    isGridOpen.value = !isGridOpen.value;
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

const handleClickOutside = (event) => {
    if (gridRef.value && !gridRef.value.contains(event.target)) {
        isGridOpen.value = false;
    }
    if (notifRef.value && !notifRef.value.contains(event.target)) {
        isNotifOpen.value = false;
    }
};

onMounted(() => {
    document.addEventListener("click", handleClickOutside);
});

onUnmounted(() => {
    document.removeEventListener("click", handleClickOutside);
});
</script>

<template>
    <nav class="bg-white border-b border-gray-200 relative font-['Poppins']">
        <div class="max-w-7xl mx-auto px-8">
            <div class="flex items-center justify-between py-2">
                <!-- Left: Brand -->
                <div class="flex items-center gap-3">
                    <div class="flex items-center justify-center flex-shrink-0">
                        <img
                            src="/images/UPC-LOGO.png"
                            alt="Logo"
                            class="w-[130px] h-[70px] object-contain"
                        />
                    </div>

                    <div>
                        <h1
                            class="text-[15px] font-bold text-[#005740] leading-tight"
                            style="
                                font-family:
                                    Palatino, &quot;Palatino Linotype&quot;,
                                    &quot;Book Antiqua&quot;, Georgia, serif;
                            "
                        >
                            Inventory Management System
                        </h1>
                        <p class="text-[11px] text-gray-400 leading-tight">
                            User Portal
                        </p>
                    </div>
                </div>

                <!-- Right: Nav Links + User + Logout -->
                <div class="flex items-center gap-5">
                    <!-- Notifications -->
                    <div class="flex items-center gap-1">
                        <!-- Grid / Apps -->
                        <div class="relative" ref="gridRef">
                            <div class="relative group">
                                <button
                                    @click="toggleGrid"
                                    class="w-10 h-10 flex items-center justify-center rounded-full text-gray-500 hover:bg-gray-50 hover:text-[#005740] transition-colors"
                                >
                                    <LayoutGrid class="w-4 h-4" />
                                </button>

                                <!-- Tooltip -->
                                <span
                                    class="pointer-events-none absolute left-1/2 -translate-x-1/2 top-full mt-2 whitespace-nowrap px-2.5 py-1 rounded-md bg-gray-900 text-white text-[11px] font-medium opacity-0 scale-95 group-hover:opacity-100 group-hover:scale-100 transition-all duration-150 z-50"
                                >
                                    Switch System
                                    <span
                                        class="absolute bottom-full left-1/2 -translate-x-1/2 -mb-[1px] w-2 h-2 bg-gray-900 rotate-45"
                                    ></span>
                                </span>
                            </div>

                            <!-- Dropdown -->
                            <Transition
                                enter-active-class="transition ease-out duration-150"
                                enter-from-class="opacity-0 -translate-y-1"
                                enter-to-class="opacity-100 translate-y-0"
                                leave-active-class="transition ease-in duration-100"
                                leave-from-class="opacity-100"
                                leave-to-class="opacity-0"
                            >
                                <div
                                    v-if="isGridOpen"
                                    class="absolute right-0 mt-3 w-72 bg-white rounded-xl shadow-xl border border-gray-100 overflow-hidden z-50"
                                >
                                    <!-- Header -->
                                    <div
                                        class="px-4 py-3 bg-gradient-to-r from-[#005740] via-[#006B4F] to-[#0E6021]"
                                    >
                                        <p
                                            class="text-[13px] font-semibold text-white"
                                        >
                                            Quick Links
                                        </p>
                                    </div>

                                    <!-- Grid -->
                                    <div class="grid grid-cols-3 gap-1 p-3">
                                        <a
                                            v-for="app in apps"
                                            :key="app.label"
                                            :href="app.href"
                                            target="_blank"
                                            class="group flex flex-col items-center gap-2 px-2 py-3 rounded-lg hover:bg-gray-50 transition-colors"
                                        >
                                            <span
                                                class="w-11 h-11 flex items-center justify-center rounded-full bg-gradient-to-br from-[#005740]/10 via-[#006B4F]/10 to-[#0E6021]/10 group-hover:from-[#005740]/15 group-hover:via-[#006B4F]/15 group-hover:to-[#0E6021]/15 transition-colors"
                                            >
                                                <img
                                                    :src="app.icon"
                                                    :alt="app.label"
                                                    class="w-6 h-6 object-contain"
                                                />
                                            </span>
                                            <span
                                                class="text-[11px] font-medium text-gray-600 group-hover:text-[#005740] text-center leading-tight transition-colors"
                                            >
                                                {{ app.label }}
                                            </span>
                                        </a>
                                    </div>
                                </div>
                            </Transition>
                        </div>

                        <!-- Support -->
                        <div class="relative group">
                            <a href="https://support.upcebu.edu.ph/open.php?topicId=62" target="_blank"
                                class="w-10 h-10 flex items-center justify-center rounded-full text-black hover:bg-gray-50 hover:text-[#005740] transition-colors"
                            >
                                <Headset class="w-4 h-4" />
                            </a>

                            <!-- Tooltip -->
                            <span
                                class="pointer-events-none absolute left-1/2 -translate-x-1/2 top-full mt-2 whitespace-nowrap px-2.5 py-1 rounded-md bg-gray-900 text-white text-[11px] font-medium opacity-0 scale-95 group-hover:opacity-100 group-hover:scale-100 transition-all duration-150 z-50"
                            >
                                Ticket Support
                                <span
                                    class="absolute bottom-full left-1/2 -translate-x-1/2 -mb-[1px] w-2 h-2 bg-gray-900 rotate-45"
                                ></span>
                            </span>
                        </div>

                        <!-- Notifications -->
                        <div class="relative" ref="notifRef">
                            <div class="relative group">
                                <button
                                    @click="toggleNotif"
                                    class="relative w-10 h-10 flex items-center justify-center rounded-full text-black hover:bg-gray-50 hover:text-[#005740] transition-colors"
                                >
                                    <i class="fa-solid fa-bell text-[16px]"></i>
                                    <span
                                        v-if="unreadCount > 0"
                                        class="absolute top-1.5 right-1.5 min-w-[16px] h-[16px] px-[3px] flex items-center justify-center rounded-full bg-[#850038] text-white text-[9px] font-bold leading-none"
                                    >
                                        {{
                                            unreadCount > 9 ? "9+" : unreadCount
                                        }}
                                    </span>
                                </button>

                                <!-- Tooltip -->
                                <span
                                    class="pointer-events-none absolute left-1/2 -translate-x-1/2 top-full mt-2 whitespace-nowrap px-2.5 py-1 rounded-md bg-gray-900 text-white text-[11px] font-medium opacity-0 scale-95 group-hover:opacity-100 group-hover:scale-100 transition-all duration-150 z-50"
                                >
                                    Notification
                                    <span
                                        class="absolute bottom-full left-1/2 -translate-x-1/2 -mb-[1px] w-2 h-2 bg-gray-900 rotate-45"
                                    ></span>
                                </span>
                            </div>

                            <!-- Dropdown -->
                            <Transition
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
                                                        : 'bg-[#850038]'
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
                                            <p
                                                class="text-[12px] text-gray-400"
                                            >
                                                You're all caught up.
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            </Transition>
                        </div>
                    </div>

                    <!-- Divider -->
                    <div class="w-px h-5 bg-gray-200"></div>

                    <!-- User Info -->
                    <div class="text-right">
                        <p
                            class="text-[13px] font-semibold text-gray-800 leading-tight"
                        >
                            {{ user?.email ?? "N/A" }}
                        </p>
                        <p
                            class="text-[11px] text-gray-400 leading-tight mt-0.5"
                        >
                            {{ user?.user_profiles?.contact_number ?? "N/A" }}
                        </p>
                    </div>

                    <!-- Logout -->
                    <Link
                        :href="route('logout')"
                        method="post"
                        as="button"
                        class="px-4 py-2 bg-gradient-to-r from-[#005740] via-[#006B4F] to-[#0E6021] text-white text-[13px] font-medium rounded-lg hover:bg-[#6a002d] transition-colors duration-150"
                    >
                        Logout
                    </Link>
                </div>
            </div>
        </div>

        <!-- Bottom Accent Line -->
        <div
            class="absolute bottom-0 left-0 right-0 h-[3px] bg-[#005740]"
        ></div>
    </nav>
</template>
