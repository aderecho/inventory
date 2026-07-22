<script setup>
import { Link } from "@inertiajs/vue3";
import { ref, watchEffect } from "vue";
import {
    PanelLeftClose,
    Headset,
    CircleX,
    CircleChevronRight,
} from "lucide-vue-next";

const menuItems = [
    {
        name: "Dashboard",
        icon: "fas fa-chart-line",
        route: "dashboard.index",
    },
    {
        name: "Inventory",
        icon: "fa-solid fa-boxes-packing",
        children: [
            {
                name: "Items",
                route: "inventory.items",
            },
            {
                name: "Acknowledgements",
                route: "acknowledgements.index",
            },
            {
                name: "Item Location",
                route: "item-histories.index",
            },
        ],
    },
    {
        name: "Core Records",
        icon: "fa-solid fa-layer-group",
        children: [
            {
                name: "Suppliers",
                icon: "fa-solid fa-handshake",
                route: "suppliers.index",
            },
            { name: "Categories", route: "categories.index" },
        ],
    },
    {
        name: "Archives",
        icon: "fa-solid fa-box-archive",
        children: [
            {
                name: "Item Archive",
                icon: "fa-solid fa-recycle",
                route: "items.archive.index",
            },
            {
                name: "Supplier Archive",
                icon: "fa-solid fa-recycle",
                route: "suppliers.archive.index",
            },
        ],
    },
    {
        name: "Administration",
        icon: "fa-solid fa-user-shield",
        children: [
            {
                name: "User Management",
                icon: "fa-solid fa-users-gear",
                route: "user_management.index",
            },
            {
                name: "API Clients",
                icon: "fa-solid fa-key",
                route: "api_clients.index",
            },
            {
                name: "SAML Configuration",
                icon: "fa-solid fa-shield-halved",
                route: "saml_configurations.index",
            },
        ],
    },
];

const openDropdown = ref(null);

const toggleDropdown = (name) => {
    openDropdown.value = openDropdown.value === name ? null : name;
};

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
        :class="isOpen ? 'w-[15rem] sm:w-[16rem]' : 'w-12'"
    >
        <!-- Sidebar Header -->
        <div
            class="flex items-center justify-center h-24 border-b border-white/20 shrink-0 overflow-hidden relative"
            :class="isOpen ? 'px-4 gap-3' : 'justify-center px-0'"
        >
            <Link
                v-if="isOpen"
                href="/"
                class="flex items-center justify-center gap-3 w-full"
            >
                <div
                    class="h-16 w-16 rounded-full overflow-hidden flex-shrink-0"
                >
                    <img
                        class="h-full w-full object-cover scale-125"
                        src="/images/uplogo-2.png"
                        alt="Logo"
                    />
                </div>
                <div
                    class="h-16 w-16 rounded-full overflow-hidden flex-shrink-0"
                >
                    <img
                        class="h-full w-full object-cover scale-125"
                        src="/images/uplogo-1.png"
                        alt="Logo"
                    />
                </div>
            </Link>

            <button
                @click="$emit('toggleSidebar')"
                class="text-white hover:text-white/70 focus:outline-none"
                :class="isOpen ? 'absolute top-2 right-2' : 'mx-auto'"
            >
                <component
                    :is="isOpen ? CircleX : CircleChevronRight"
                    class="h-6 w-6"
                />
            </button>
        </div>

        <!-- Menu -->
        <ul class="flex-1 py-3">
            <li v-for="item in menuItems" :key="item.name" class="rounded-md">
                <!-- If item has children -->
                <div
                    v-if="item.children"
                    @click="
                        isOpen
                            ? toggleDropdown(item.name)
                            : $emit('toggleSidebar')
                    "
                    class="flex items-center gap-3 py-4 px-4 mx-2 rounded-md cursor-pointer transition-all duration-300 text-white"
                    :class="[
                        isOpen
                            ? 'justify-between sm:mx-3'
                            : 'justify-center mx-1',
                        item.children.some((child) =>
                            route().current(child.route),
                        )
                            ? 'bg-white/20 font-semibold'
                            : 'hover:bg-white/20',
                    ]"
                >
                    <div class="flex items-center gap-3">
                        <i :class="item.icon" class="shrink-0"></i>
                        <span v-if="isOpen">{{ item.name }}</span>
                    </div>
                    <i
                        v-if="isOpen"
                        class="fa-solid transform transition-transform duration-300"
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
                        class="flex items-center gap-3 py-4 px-4 mx-2 rounded-md transition-all duration-300 cursor-pointer text-white hover:bg-white/20"
                        :class="[
                            isOpen ? 'sm:mx-3' : 'justify-center mx-1',
                            route().current(item.route)
                                ? 'bg-white/20 font-semibold'
                                : '',
                        ]"
                    >
                        <i :class="item.icon" class="shrink-0"></i>
                        <span v-if="isOpen">{{ item.name }}</span>
                    </div>
                </Link>

                <!-- Dropdown children — hide when collapsed -->
                <div
                    v-if="item.children && isOpen"
                    class="overflow-hidden transition-all duration-500 ease-in-out"
                    :class="
                        openDropdown === item.name
                            ? 'max-h-60 opacity-100 mt-2'
                            : 'max-h-0 opacity-0'
                    "
                >
                    <ul class="ml-10 mt-1 space-y-1 text-[15px]">
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
            class="mt-auto py-3 border-t border-white/20 flex flex-col items-center gap-1"
        >
            <span
                class="flex items-center gap-1 text-center text-xs text-white"
            >
                <Headset :size="16" />
                <p class="underline underline-offset-4">ITC Support</p>
            </span>
        </a>
    </div>
</template>
