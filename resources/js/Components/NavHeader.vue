<script setup>
import { usePage, Link } from "@inertiajs/vue3";
import { computed, ref, onMounted, onUnmounted } from "vue";
import { User, LayoutGrid, ChevronRight } from "lucide-vue-next";
import EditProfileModal from "@/Components/Modals/EditProfileModal.vue";

defineProps({ isSidebarOpen: { type: Boolean, default: true } });
defineEmits(["toggleSidebar"]);

const page = usePage();
const profile = computed(() => page.props.auth.user?.user_profiles);

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

// Quick Links (Switch System) — same set of external systems as the
// top NavBar's app grid.
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
</script>

<template>
    <div>
        <nav
            class="bg-gradient-to-r from-[#005740] via-[#006B4F] to-[#0E6021] shadow-md border-l border-white/20"
        >
           <div class="flex h-16 items-center">
                <h3 class="text-white font-bold text-sm tracking-wide pl-6">
                    Inventory Management System (IMS)
                </h3>

                <div class="ml-auto mr-6 flex items-center gap-3">
                    <div class="relative" ref="dropdownRef">
                        <div class="flex items-center gap-2">
                            <div
                                class="h-9 w-9 rounded-full flex items-center justify-center flex-shrink-0 bg-white"
                            >
                                <span
                                    class="text-[#005740] text-sm font-semibold"
                                >
                                    {{ initials }}
                                </span>
                            </div>

                            <span class="text-xs font-medium text-white">
                                {{ page.props.auth.user?.email }}
                            </span>

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
                                <!-- Dropdown header -->
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

                                <!-- Dropdown items -->
                                <div class="bg-white py-1">
                                    <button
                                        type="button"
                                        class="w-full flex items-center gap-3 px-4 py-2.5 text-sm text-gray-700 hover:bg-[#005740]/10 hover:text-[#005740] transition-colors duration-200"
                                        @click="openProfileModal"
                                    >
                                        <User class="w-4 h-4 text-[#005740]" />
                                        My Profile
                                    </button>

                                    <button
                                        type="button"
                                        class="w-full flex items-center gap-3 px-4 py-2.5 text-sm text-gray-700 hover:bg-[#005740]/10 hover:text-[#005740] transition-colors duration-200"
                                        @click="toggleQuickLinks"
                                    >
                                        <LayoutGrid
                                            class="w-4 h-4 text-[#005740]"
                                        />
                                        <span class="flex-1 text-left">Switch System</span>
                                        <ChevronRight
                                            class="w-3.5 h-3.5 text-gray-400 transition-transform duration-200"
                                            :class="{ 'rotate-90': showQuickLinks }"
                                        />
                                    </button>

                                    <!-- Quick Links panel -->
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
                                            <div
                                                class="px-3 py-2 bg-gradient-to-r from-[#005740] via-[#006B4F] to-[#0E6021]"
                                            >
                                                <p class="text-[11px] font-semibold text-white">
                                                    Quick Links
                                                </p>
                                            </div>

                                            <div class="grid grid-cols-3 gap-1 p-2 bg-white">
                                                <a
                                                    v-for="app in apps"
                                                    :key="app.label"
                                                    :href="app.href"
                                                    target="_blank"
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

                                    <div
                                        class="border-t border-gray-100 my-1"
                                    ></div>

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

                    <slot />
                </div>
            </div>
        </nav>

        <EditProfileModal
            :show="showProfileModal"
            :user="page.props.auth.user"
            @close="closeProfileModal"
        />
    </div>
</template>