<script setup>
import { Link } from "@inertiajs/vue3";
import { ref, computed, watchEffect } from "vue";
import {
    PanelLeftClose,
    Headset,
    Menu,
    CircleChevronRight,
} from "lucide-vue-next";
import { useAuth } from "@/Composables/useAuth";

const { can } = useAuth();

const menuItems = [
    {
        name: "Dashboard",
        description: "Overview & quick analytics",
        icon: "fas fa-chart-line",
        route: "dashboard.index",
    },
    {
        name: "Inventory",
        description: "Asset records & assignments",
        icon: "fa-solid fa-boxes-stacked",
        children: [
            {
                name: "Item List",
                route: "inventory.items",
                permission: "view inventory",
            },
            {
                name: "Item Evidence",
                route: "acknowledgements.index",
                permission: "view acknowledgements",
            },
            {
                name: "Location History",
                route: "item-histories.index",
                permission: "view inventory",
            },
        ],
    },
    {
        name: "Asset Inspection",
        description: "Condition & maintenance logs",
        icon: "fa-solid fa-clipboard-list",
        children: [
            {
                name: "Inspection Records",
                route: "inspection.index",
                permission: "view inspections",
            },
        ],
    },
    {
        name: "Core Records",
        description: "Suppliers & item categories",
        icon: "fa-solid fa-layer-group",
        children: [
            {
                name: "Suppliers",
                icon: "fa-solid fa-handshake",
                route: "suppliers.index",
                permission: "view suppliers",
            },
            {
                name: "Categories",
                route: "categories.index",
                permission: "view categories",
            },
        ],
    },
    {
        name: "Disposal",
        description: "Disposed Items",
        icon: "fa-solid fa-box-archive",
        children: [
            {
                name: "Disposal Table",
                icon: "fa-solid fa-recycle",
                route: "disposal.index",
                permission: "view disposal",
            },
        ],
    },
    {
        name: "Archives",
        description: "Archived master files",
        icon: "fa-solid fa-folder",
        children: [
            {
                name: "Item Archive",
                icon: "fa-solid fa-recycle",
                route: "items.archive.index",
                permission: "view archive_item",
            },
            {
                name: "Supplier Archive",
                icon: "fa-solid fa-recycle",
                route: "suppliers.archive.index",
                permission: "view archive_supplier",
            },
            {
                name: "Categories Archive",
                icon: "fa-solid fa-recycle",
                route: "categories.archive.index",
                permission: "view archive_supplier",
            },
        ],
    },
    {
        name: "Administration",
        description: "System control & access",
        icon: "fa-solid fa-user-shield",
        children: [
            {
                name: "User Management",
                icon: "fa-solid fa-users-gear",
                route: "user_management.index",
                permission: "view users",
            },
            {
                name: "API Clients",
                icon: "fa-solid fa-key",
                route: "api_clients.index",
                permission: "view api_clients",
            },
            {
                name: "SAML Configuration",
                icon: "fa-solid fa-shield-halved",
                route: "saml_configurations.index",
                permission: "view saml_configurations",
            },
            {
                name: "Audit Logs",
                icon: "fa-solid fa-shield-halved",
                route: "audit_logs.index",
                permission: "view audit_logs",
            },
        ],
    },
];

const openDropdown = ref(null);

const toggleDropdown = (name) => {
    openDropdown.value = openDropdown.value === name ? null : name;
};

const visibleMenuItems = computed(() =>
    menuItems
        .map((item) => {
            if (item.children) {
                const visibleChildren = item.children.filter(
                    (child) => !child.permission || can(child.permission),
                );
                return visibleChildren.length
                    ? { ...item, children: visibleChildren }
                    : null;
            }
            return !item.permission || can(item.permission) ? item : null;
        })
        .filter(Boolean),
);

watchEffect(() => {
    menuItems.forEach((item) => {
        if (item.children) {
            const isChildActive = item.children.some((child) =>
                route().current(child.route),
            );
            if (isChildActive) {
                openDropdown.value = item.name;
            }
        }
    });
});

defineProps({ isOpen: { type: Boolean, default: true } });
</script>

<template>
    <div
        class="text-lg font-semibold bg-[#005740] h-full shadow-lg flex flex-col transition-all duration-300 ease-in-out overflow-hidden"
        :class="isOpen ? 'w-[15rem] sm:w-[16rem]' : 'w-16'"
    >
        <!-- Sidebar Header -->
        <div
            class="flex items-center justify-center h-20 border-b border-white/20 shrink-0 relative px-2"
        >
            <Link
                v-if="isOpen"
                href="/"
                class="flex items-center justify-center gap-3 w-full pr-8"
            >
                <div class="flex-shrink-0">
                    <img
                        class="h-32 w-32 object-contain"
                        src="/images/UPC-LOGO.png"
                        alt="Logo"
                    />
                </div>
            </Link>

            <button
                @click="$emit('toggleSidebar')"
                class="text-white hover:text-white/70 focus:outline-none flex items-center justify-center"
                :class="
                    isOpen
                        ? 'absolute right-3 top-1/2 -translate-y-1/2'
                        : 'mx-auto'
                "
            >
                <component
                    :is="isOpen ? Menu : CircleChevronRight"
                    class="h-6 w-6"
                />
            </button>
        </div>

        <!-- Menu -->
        <ul class="flex-1 py-3 overflow-y-auto space-y-1">
            <li v-for="item in visibleMenuItems" :key="item.name">
                <!-- If item has children -->
                <div
                    v-if="item.children"
                    @click="
                        isOpen
                            ? toggleDropdown(item.name)
                            : $emit('toggleSidebar')
                    "
                    class="flex items-center py-3 rounded-md cursor-pointer transition-all duration-300 text-white"
                    :class="[
                        isOpen
                            ? 'justify-between px-4 mx-2 sm:mx-3 gap-3'
                            : 'justify-center px-0 mx-1 w-full',
                        item.children.some((child) =>
                            route().current(child.route),
                        )
                            ? 'bg-white/20 font-semibold'
                            : 'hover:bg-white/20',
                    ]"
                >
                    <div class="flex items-center gap-3 min-w-0 justify-center">
                        <i :class="item.icon" class="shrink-0 text-lg"></i>
                        <div
                            v-if="isOpen"
                            class="flex flex-col leading-tight overflow-hidden"
                        >
                            <span class="truncate text-base">{{
                                item.name
                            }}</span>
                            <span
                                v-if="item.description"
                                class="text-[11px] font-normal text-white/70 truncate"
                            >
                                {{ item.description }}
                            </span>
                        </div>
                    </div>
                    <i
                        v-if="isOpen"
                        class="fa-solid transform transition-transform duration-300 shrink-0 text-sm"
                        :class="
                            openDropdown === item.name
                                ? 'fa-chevron-up'
                                : 'fa-chevron-down'
                        "
                    ></i>
                </div>

                <!-- Link if no children -->
                <Link
                    v-else
                    :href="route(item.route)"
                    :method="item.method || 'get'"
                    as="button"
                    class="w-full block"
                >
                    <div
                        class="flex items-center py-3 rounded-md transition-all duration-300 cursor-pointer text-white hover:bg-white/20"
                        :class="[
                            isOpen
                                ? 'px-4 mx-2 sm:mx-3 gap-3'
                                : 'justify-center px-0 mx-1 w-full',
                            route().current(item.route)
                                ? 'bg-white/20 font-semibold'
                                : '',
                        ]"
                    >
                        <i :class="item.icon" class="shrink-0 text-lg"></i>
                        <div
                            v-if="isOpen"
                            class="flex flex-col leading-tight overflow-hidden text-left"
                        >
                            <span class="truncate text-base">{{
                                item.name
                            }}</span>
                            <span
                                v-if="item.description"
                                class="text-[11px] font-normal text-white/70 truncate"
                            >
                                {{ item.description }}
                            </span>
                        </div>
                    </div>
                </Link>

                <!-- Dropdown children — hide when collapsed -->
                <div
                    v-if="item.children && isOpen"
                    class="overflow-hidden transition-all duration-500 ease-in-out"
                    :class="
                        openDropdown === item.name
                            ? 'max-h-60 opacity-100 mt-1 mb-2'
                            : 'max-h-0 opacity-0'
                    "
                >
                    <ul class="ml-10 space-y-1 text-[15px]">
                        <li v-for="child in item.children" :key="child.name">
                            <Link
                                :href="route(child.route)"
                                class="block py-2 px-2 mx-3 rounded-md transition-all duration-300 text-white/80 hover:bg-white/20 hover:text-white"
                                :class="{
                                    'font-semibold text-white bg-white/20':
                                        route().current(child.route),
                                }"
                            >
                                {{ child.name }}
                            </Link>
                        </li>
                    </ul>
                </div>
            </li>
        </ul>

        <!-- Footer -->
        <a
            href="https://support.upcebu.edu.ph/open.php?topicId=62"
            target="_blank"
            class="mt-auto py-3 border-t border-white/20 flex flex-col items-center justify-center gap-1 shrink-0"
        >
            <span
                class="flex items-center justify-center gap-2 text-center text-xs text-white"
            >
                <Headset :size="18" />
                <p class="underline underline-offset-4" v-if="isOpen">
                    ITC Support
                </p>
            </span>
        </a>
    </div>
</template>
