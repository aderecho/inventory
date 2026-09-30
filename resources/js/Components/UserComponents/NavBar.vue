<script setup>
import { ref, computed, onMounted, onUnmounted } from "vue";
import { Link, router } from "@inertiajs/vue3";
import { LayoutGrid, Headset, X } from "lucide-vue-next";
import axios from "axios";

const props = defineProps({
    user: {
        type: Object,
        default: () => ({}),
    },
});

const notifications = ref([]);
const isNotifOpen = ref(false);
const notifRef = ref(null);

const selectedNotification = ref(null);
const isApprovalModalOpen = ref(false);

function resolvedItemStatus(item) {
    const status = item?.local_status ?? item?.approval_status ?? null;

    return status === "approved" || status === "rejected" ? status : null;
}

function mapNotification(n) {
    const data = n.data ?? {};
    const items = (data.items ?? []).map((item) => ({
        ...item,
        local_status: resolvedItemStatus(item),
    }));

    return {
        id: n.id,
        title: data.title ?? "Notification",
        message: data.message ?? "",
        read_at: n.read_at ?? null,
        created_at: n.created_at ?? new Date().toISOString(),
        url: data.url ?? null,
        notification_type: data.notification_type ?? null,
        status: data.status ?? null,
        approver_id: data.approver_id ?? null,
        approver_name: data.approver_name ?? null,
        category: data.category ?? null,
        item_count: data.item_count ?? items.length,
        items,
    };
}

async function fetchNotifications() {
    try {
        const { data } = await axios.get(route("notifications.index"));

        notifications.value = (data.notifications ?? []).map(mapNotification);
    } catch (e) {
        console.error("Failed to load notifications", e);
    }
}

const unreadCount = computed(() => {
    return notifications.value.filter((n) => !n.read_at).length;
});

const toggleNotif = () => {
    isNotifOpen.value = !isNotifOpen.value;
};

const timeAgo = (dateStr) => {
    if (!dateStr) return "";

    const date = new Date(dateStr);

    if (Number.isNaN(date.getTime())) {
        return "";
    }

    const diff = Math.max(0, (Date.now() - date.getTime()) / 1000);

    if (diff < 60) return "Just now";
    if (diff < 3600) {
        return `${Math.floor(diff / 60)}m ago`;
    }

    if (diff < 86400) {
        return `${Math.floor(diff / 3600)}h ago`;
    }

    return `${Math.floor(diff / 86400)}d ago`;
};

const propertyPreview = (notification) => {
    if (!notification.items?.length) {
        return "";
    }

    const properties = notification.items
        .map((item) => item.property_number)
        .filter(Boolean);

    if (!properties.length) {
        return "";
    }

    if (properties.length <= 2) {
        return properties.join(", ");
    }

    return `${properties.slice(0, 2).join(", ")} +${
        properties.length - 2
    } more`;
};

async function markAsRead(notification) {
    if (!notification.read_at) {
        const previousReadAt = notification.read_at;

        notification.read_at = new Date().toISOString();

        try {
            await axios.patch(route("notifications.read", notification.id));
        } catch (e) {
            notification.read_at = previousReadAt;
            console.error("Failed to mark notification as read", e);
            return;
        }
    }

    if (notification.items?.length) {
        await fetchNotifications();

        selectedNotification.value =
            notifications.value.find((entry) => entry.id === notification.id) ??
            notification;
        isApprovalModalOpen.value = true;
        isNotifOpen.value = false;
        return;
    }

    if (notification.url) {
        router.visit(notification.url);
    }
}

async function markAllAsRead() {
    const previous = notifications.value.map(
        (notification) => notification.read_at,
    );

    notifications.value.forEach((notification) => {
        if (!notification.read_at) {
            notification.read_at = new Date().toISOString();
        }
    });

    try {
        await axios.patch(route("notifications.read-all"));
    } catch (e) {
        notifications.value.forEach((notification, index) => {
            notification.read_at = previous[index];
        });

        console.error("Failed to mark all notifications as read", e);
    }
}

function closeApprovalModal() {
    isApprovalModalOpen.value = false;
    selectedNotification.value = null;
}

const processingItemId = ref(null);
const bulkProcessing = ref(false);

function setItemLocalStatus(item, status) {
    item.local_status = status;
    item.approval_status = status;

    const applyStatus = (notification) => {
        if (!notification?.items) {
            return notification;
        }

        return {
            ...notification,
            items: notification.items.map((entry) =>
                entry.id === item.id
                    ? { ...entry, local_status: status, approval_status: status }
                    : entry,
            ),
        };
    };

    notifications.value = notifications.value.map((notification) =>
        notification.id === selectedNotification.value?.id
            ? applyStatus(notification)
            : notification,
    );

    if (selectedNotification.value) {
        selectedNotification.value = applyStatus(selectedNotification.value);
    }
}

function patchItemApproval(item, status) {
    return new Promise((resolve) => {
        processingItemId.value = item.id;

        router.patch(
            route("inventory_items.approval", item.id),
            { approval_status: status },
            {
                preserveScroll: true,
                preserveState: true,
                onSuccess: () => {
                    setItemLocalStatus(item, status);
                },
                onFinish: () => {
                    processingItemId.value = null;
                    resolve();
                },
            },
        );
    });
}

function updateItemApproval(item, status) {
    if (processingItemId.value || bulkProcessing.value) return;

    patchItemApproval(item, status);
}

const hasPendingItems = computed(() =>
    (selectedNotification.value?.items ?? []).some((i) => !resolvedItemStatus(i)),
);

async function bulkUpdateApproval(status) {
    const pendingItems = (selectedNotification.value?.items ?? []).filter(
        (i) => !resolvedItemStatus(i),
    );

    if (!pendingItems.length || bulkProcessing.value) return;

    bulkProcessing.value = true;

    try {
        for (const item of pendingItems) {
            await patchItemApproval(item, status);
        }
    } finally {
        processingItemId.value = null;
        bulkProcessing.value = false;
    }
}

function approveAllItems() {
    bulkUpdateApproval("approved");
}

function rejectAllItems() {
    bulkUpdateApproval("rejected");
}

function approveItem(item) {
    updateItemApproval(item, "approved");
}

function rejectItem(item) {
    updateItemApproval(item, "rejected");
}

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
        href: "http://puso.upcebu.edu.ph",
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

let echoChannel = null;

onMounted(() => {
    document.addEventListener("click", handleClickOutside);

    fetchNotifications();

    if (props.user?.id && window.Echo) {
        const channelName = `App.Models.User.${props.user.id}`;

        echoChannel = window.Echo.private(channelName);

        echoChannel.notification((notification) => {
            notifications.value.unshift(
                mapNotification({
                    id: notification.id,
                    data: notification,
                    read_at: null,
                    created_at:
                        notification.created_at ?? new Date().toISOString(),
                }),
            );
        });
    }
});

onUnmounted(() => {
    document.removeEventListener("click", handleClickOutside);

    if (props.user?.id && window.Echo) {
        window.Echo.leave(`App.Models.User.${props.user.id}`);
    }
});
</script>

<template>
    <nav class="bg-white border-b border-gray-200 relative font-['Poppins']">
        <div class="max-w-7xl mx-auto px-8">
            <div class="flex items-center justify-between py-2">
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

                <div class="flex items-center gap-5">
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

                                <span
                                    class="pointer-events-none absolute left-1/2 -translate-x-1/2 top-full mt-2 whitespace-nowrap px-2.5 py-1 rounded-md bg-gray-900 text-white text-[11px] font-medium opacity-0 scale-95 group-hover:opacity-100 group-hover:scale-100 transition-all duration-150 z-50"
                                >
                                    Switch System

                                    <span
                                        class="absolute bottom-full left-1/2 -translate-x-1/2 -mb-[1px] w-2 h-2 bg-gray-900 rotate-45"
                                    ></span>
                                </span>
                            </div>

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
                                    <div
                                        class="px-4 py-3 bg-gradient-to-r from-[#005740] via-[#006B4F] to-[#0E6021]"
                                    >
                                        <p
                                            class="text-[13px] font-semibold text-white"
                                        >
                                            Quick Links
                                        </p>
                                    </div>

                                    <div class="grid grid-cols-3 gap-1 p-3">
                                        <a
                                            v-for="app in apps"
                                            :key="app.label"
                                            :href="app.href"
                                            target="_blank"
                                            rel="noopener noreferrer"
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
                            <a
                                href="https://support.upcebu.edu.ph/open.php?topicId=62"
                                target="_blank"
                                rel="noopener noreferrer"
                                class="w-10 h-10 flex items-center justify-center rounded-full text-black hover:bg-gray-50 hover:text-[#005740] transition-colors"
                            >
                                <Headset class="w-4 h-4" />
                            </a>

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

                                <span
                                    class="pointer-events-none absolute left-1/2 -translate-x-1/2 top-full mt-2 whitespace-nowrap px-2.5 py-1 rounded-md bg-gray-900 text-white text-[11px] font-medium opacity-0 scale-95 group-hover:opacity-100 group-hover:scale-100 transition-all duration-150 z-50"
                                >
                                    Notification

                                    <span
                                        class="absolute bottom-full left-1/2 -translate-x-1/2 -mb-[1px] w-2 h-2 bg-gray-900 rotate-45"
                                    ></span>
                                </span>
                            </div>

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
                                    class="absolute right-0 mt-3 w-96 bg-white rounded-xl shadow-xl border border-gray-100 overflow-hidden z-50"
                                >
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

                                            <div class="min-w-0 flex-1">
                                                <p
                                                    class="text-[12.5px] leading-snug"
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
                                                    v-if="
                                                        notification.items
                                                            ?.length
                                                    "
                                                    class="text-[10.5px] text-gray-400 mt-1 truncate"
                                                >
                                                    {{
                                                        propertyPreview(
                                                            notification,
                                                        )
                                                    }}
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
                            {{ user?.user_profiles?.contact_number ?? "" }}
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

    <!-- Approval Items Modal -->
    <Transition
        enter-active-class="transition ease-out duration-150"
        enter-from-class="opacity-0 scale-95"
        enter-to-class="opacity-100 scale-100"
        leave-active-class="transition ease-in duration-100"
        leave-from-class="opacity-100 scale-100"
        leave-to-class="opacity-0 scale-95"
    >
        <div
            v-if="isApprovalModalOpen"
            class="fixed inset-0 z-[100] flex items-center justify-center px-4"
        >
            <div
                class="absolute inset-0 bg-black/30"
                @click="closeApprovalModal"
            ></div>

            <div
                class="relative w-full max-w-2xl bg-white rounded-2xl shadow-2xl overflow-hidden"
            >
                <div
                    class="flex items-center justify-between px-5 py-4 border-b border-gray-100"
                >
                    <div>
                        <h2 class="text-[15px] font-semibold text-gray-900">
                            {{
                                selectedNotification?.notification_type ===
                                "items_pending_approval"
                                    ? "Pending Acknowledgement"
                                    : "Inventory Approval"
                            }}
                        </h2>

                        <p class="text-[11px] text-gray-400 mt-0.5">
                            <template
                                v-if="
                                    selectedNotification?.notification_type ===
                                    'items_pending_approval'
                                "
                            >
                                You have
                                {{
                                    selectedNotification?.item_count ??
                                    selectedNotification?.items?.length ??
                                    0
                                }}
                                item(s) pending acknowledgement under PAR
                                {{ selectedNotification?.category }}.
                            </template>
                            <template v-else>
                                {{
                                    selectedNotification?.approver_name ??
                                    "User"
                                }}
                                {{ selectedNotification?.status ?? "approved" }}
                                {{
                                    selectedNotification?.item_count ??
                                    selectedNotification?.items?.length ??
                                    0
                                }}
                                inventory item(s).
                            </template>
                        </p>
                        <div
                            v-if="
                                selectedNotification?.notification_type ===
                                    'items_pending_approval' &&
                                selectedNotification?.items?.length &&
                                hasPendingItems
                            "
                            class="flex items-center gap-2 mt-3"
                        >
                            <button
                                @click="approveAllItems"
                                :disabled="bulkProcessing"
                                class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-[11px] font-semibold bg-green-50 text-green-700 hover:bg-green-600 hover:text-white transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
                            >
                                Approve All
                            </button>

                            <button
                                @click="rejectAllItems"
                                :disabled="bulkProcessing"
                                class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-[11px] font-semibold bg-red-50 text-red-700 hover:bg-red-600 hover:text-white transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
                            >
                                Reject All
                            </button>
                        </div>
                    </div>

                    <button
                        @click="closeApprovalModal"
                        class="w-8 h-8 flex items-center justify-center rounded-full text-gray-400 hover:bg-gray-100 hover:text-gray-700 transition-colors"
                    >
                        <X class="w-4 h-4" />
                    </button>
                </div>

                <div class="max-h-[60vh] overflow-y-auto">
                    <table class="w-full text-left">
                        <thead
                            class="sticky top-0 bg-gray-50 border-b border-gray-100"
                        >
                            <tr>
                                <th
                                    class="px-5 py-3 text-[10px] font-semibold text-gray-500 uppercase tracking-wide"
                                >
                                    Property Number
                                </th>

                                <th
                                    class="px-5 py-3 text-[10px] font-semibold text-gray-500 uppercase tracking-wide"
                                >
                                    Item
                                </th>

                                <th
                                    v-if="
                                        selectedNotification?.notification_type !==
                                        'items_pending_approval'
                                    "
                                    class="px-5 py-3 text-[10px] font-semibold text-gray-500 uppercase tracking-wide text-right"
                                >
                                    Status
                                </th>
                                <th
                                    v-if="
                                        selectedNotification?.notification_type ===
                                        'items_pending_approval'
                                    "
                                    class="px-5 py-3 text-[10px] font-semibold text-gray-500 uppercase tracking-wide text-right"
                                >
                                    Actions
                                </th>
                            </tr>
                        </thead>

                        <tbody class="divide-y divide-gray-50">
                            <tr
                                v-for="item in selectedNotification?.items ??
                                []"
                                :key="item.id"
                                class="hover:bg-gray-50"
                            >
                                <td
                                    class="px-5 py-3 text-[11px] font-medium text-gray-700"
                                >
                                    {{ item.property_number ?? "N/A" }}
                                </td>

                                <td class="px-5 py-3 text-[11px] text-gray-500">
                                    {{ item.item_name ?? "N/A" }}
                                </td>

                                <td
                                    v-if="
                                        selectedNotification?.notification_type !==
                                        'items_pending_approval'
                                    "
                                    class="px-5 py-3 text-right"
                                >
                                    <span
                                        class="inline-flex px-2 py-1 rounded-full text-[9px] font-semibold"
                                        :class="
                                            selectedNotification?.status ===
                                            'approved'
                                                ? 'bg-green-50 text-green-700'
                                                : 'bg-red-50 text-red-700'
                                        "
                                    >
                                        {{ selectedNotification?.status }}
                                    </span>
                                </td>
                                <td
                                    v-if="
                                        selectedNotification?.notification_type ===
                                        'items_pending_approval'
                                    "
                                    class="px-5 py-3 text-right"
                                >
                                    <span
                                        v-if="item.local_status"
                                        class="inline-flex px-2 py-1 rounded-full text-[9px] font-semibold"
                                        :class="
                                            item.local_status === 'approved'
                                                ? 'bg-green-50 text-green-700'
                                                : 'bg-red-50 text-red-700'
                                        "
                                    >
                                        {{ item.local_status }}
                                    </span>

                                    <div
                                        v-else
                                        class="flex items-center justify-end gap-2"
                                    >
                                        <button
                                            @click="approveItem(item)"
                                            :disabled="
                                                processingItemId === item.id ||
                                                bulkProcessing
                                            "
                                            class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-[10px] font-semibold bg-green-50 text-green-700 hover:bg-green-600 hover:text-white transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
                                        >
                                            Approve
                                        </button>

                                        <button
                                            @click="rejectItem(item)"
                                            :disabled="
                                                processingItemId === item.id ||
                                                bulkProcessing
                                            "
                                            class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-[10px] font-semibold bg-red-50 text-red-700 hover:bg-red-600 hover:text-white transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
                                        >
                                            Reject
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>

                    <div
                        v-if="!selectedNotification?.items?.length"
                        class="px-5 py-10 text-center"
                    >
                        <p class="text-[12px] text-gray-400">
                            No item details available.
                        </p>
                    </div>
                </div>

                <div
                    class="flex justify-end px-5 py-3 border-t border-gray-100 bg-gray-50"
                >
                    <button
                        @click="closeApprovalModal"
                        class="px-4 py-2 rounded-lg bg-[#005740] text-white text-[11px] font-medium hover:bg-[#004532] transition-colors"
                    >
                        Close
                    </button>
                </div>
            </div>
        </div>
    </Transition>
</template>
