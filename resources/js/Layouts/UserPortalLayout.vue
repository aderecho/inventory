<script setup>
import { computed, onBeforeUnmount, onMounted, ref } from "vue";
import { Link, router, usePage } from "@inertiajs/vue3";
import { ArrowLeftRight, Headset, LayoutGrid } from "lucide-vue-next";
import UserNotificationBell from "@/Components/UserComponents/UserNotificationBell.vue";

/**
 * The shell for accounts that only have the personal dashboard: the portal
 * header and no administration sidebar.
 */
const props = defineProps({
    homeRoute: { type: String, default: "user.dashboard" },
    portalLabel: { type: String, default: "User Portal" },
});

const page = usePage();

const user = computed(() => page.props.auth?.user ?? {});
const profile = computed(() => user.value?.user_profiles ?? {});
const displayName = computed(
    () => profile.value.full_name || user.value.email || "Account",
);

const canSwitchWorkspace = computed(
    () => (page.props.workspaceOptions?.length ?? 0) > 1,
);
const switchingWorkspace = ref(false);

function switchWorkspace() {
    if (switchingWorkspace.value) return;

    switchingWorkspace.value = true;

    router.post(
        route("auth.switch-dashboard"),
        {},
        { onFinish: () => (switchingWorkspace.value = false) },
    );
}

// Switch System: the UP Cebu applications this portal points at.
const apps = [
    { label: "AMIS", icon: "/images/uplogo-1.png", href: "http://amis.upcebu.edu.ph" },
    { label: "BULSA", icon: "/images/uplogo-1.png", href: "http://bulsa.up.edu.ph" },
    { label: "PUSO", icon: "/images/uplogo-1.png", href: "http://puso.up.edu.ph" },
    { label: "CORE", icon: "/images/uplogo-2.png", href: "https://core.upcebu.edu.ph/login" },
    { label: "KAT-ON", icon: "/images/uplogo-2.png", href: "http://lms.upcebu.edu.ph" },
    { label: "VMS", icon: "/images/uplogo-2.png", href: "https://vms.upcebu.edu.ph/login" },
];

const appsOpen = ref(false);
const appsRef = ref(null);

function toggleApps() {
    appsOpen.value = !appsOpen.value;
}

function closeApps(event) {
    if (appsRef.value && !appsRef.value.contains(event.target)) {
        appsOpen.value = false;
    }
}

onMounted(() => document.addEventListener("mousedown", closeApps));
onBeforeUnmount(() => document.removeEventListener("mousedown", closeApps));

const year = new Date().getFullYear();
</script>

<template>
    <div class="flex min-h-screen flex-col bg-gray-100">
        <header class="sticky top-0 z-30 border-b border-gray-200 bg-white/95 backdrop-blur">
            <div
                class="mx-auto flex max-w-7xl items-center justify-between gap-3 px-4 py-2.5 sm:px-6"
            >
                <Link :href="route(props.homeRoute)" class="flex min-w-0 items-center gap-3">
                    <img
                        src="/images/UPC-LOGO.png"
                        alt="University of the Philippines Cebu"
                        class="h-12 w-auto object-contain"
                    />

                    <span class="min-w-0">
                        <span
                            class="block truncate text-sm font-bold leading-tight text-[#005740]"
                        >
                            Inventory Management System
                        </span>
                        <span class="block text-[11px] text-gray-400">
                            {{ portalLabel }}
                        </span>
                    </span>
                </Link>

                <div class="flex items-center gap-1.5 sm:gap-2">
                    <!-- SWITCH SYSTEM -->
                    <div ref="appsRef" class="relative">
                        <button
                            type="button"
                            class="flex h-10 w-10 items-center justify-center rounded-full text-gray-600 transition-colors hover:bg-gray-100 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[#005740]/40"
                            title="Switch System"
                            :aria-expanded="appsOpen"
                            @click="toggleApps"
                        >
                            <LayoutGrid class="h-[18px] w-[18px]" />
                        </button>

                        <div
                            v-if="appsOpen"
                            class="absolute right-0 z-50 mt-2 w-72 overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-2xl"
                        >
                            <div class="bg-[#005740] px-4 py-2.5">
                                <p class="text-[11px] font-semibold uppercase tracking-wide text-white">
                                    Switch System
                                </p>
                            </div>

                            <div class="grid grid-cols-3 gap-1 p-2">
                                <a
                                    v-for="app in apps"
                                    :key="app.label"
                                    :href="app.href"
                                    target="_blank"
                                    rel="noopener noreferrer"
                                    class="group flex flex-col items-center gap-1.5 rounded-xl px-1 py-2.5 transition-colors hover:bg-[#005740]/5"
                                    @click="appsOpen = false"
                                >
                                    <span
                                        class="flex h-9 w-9 items-center justify-center rounded-full bg-[#005740]/10 transition-transform duration-200 group-hover:scale-105"
                                    >
                                        <img
                                            :src="app.icon"
                                            :alt="app.label"
                                            class="h-5 w-5 object-contain"
                                        />
                                    </span>
                                    <span
                                        class="text-center text-[10px] font-medium leading-tight text-gray-600 group-hover:text-[#005740]"
                                    >
                                        {{ app.label }}
                                    </span>
                                </a>
                            </div>
                        </div>
                    </div>

                    <!-- CUSTOMER SUPPORT -->
                    <a
                        href="https://support.upcebu.edu.ph/open.php?topicId=62"
                        target="_blank"
                        rel="noopener noreferrer"
                        class="group relative flex h-10 w-10 items-center justify-center rounded-full text-gray-600 transition-colors hover:bg-gray-100 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[#005740]/40"
                        aria-label="Customer Support"
                    >
                        <Headset class="h-[18px] w-[18px]" />

                        <span
                            class="pointer-events-none absolute left-1/2 top-full z-50 mt-2 -translate-x-1/2 whitespace-nowrap rounded-md bg-gray-900 px-2.5 py-1 text-[11px] font-medium text-white opacity-0 transition-opacity duration-75 group-hover:opacity-100 group-focus-visible:opacity-100"
                        >
                            Customer Support
                            <span
                                class="absolute bottom-full left-1/2 -mb-[1px] h-2 w-2 -translate-x-1/2 rotate-45 bg-gray-900"
                            ></span>
                        </span>
                    </a>

                    <UserNotificationBell />

                    <div class="hidden text-right sm:block">
                        <p class="max-w-[190px] truncate text-xs font-semibold text-gray-800">
                            {{ displayName }}
                        </p>
                        <p class="max-w-[190px] truncate text-[11px] text-gray-400">
                            {{ user.email }}
                        </p>
                    </div>

                    <button
                        v-if="canSwitchWorkspace"
                        type="button"
                        class="flex h-10 items-center gap-2 rounded-lg border border-[#005740]/30 px-3 text-xs font-medium text-[#005740] transition-colors hover:border-[#005740]/60 hover:bg-[#005740]/5 disabled:cursor-not-allowed disabled:opacity-50"
                        title="Switch workspace"
                        :disabled="switchingWorkspace"
                        @click="switchWorkspace"
                    >
                        <ArrowLeftRight class="h-4 w-4" />
                        <span class="hidden lg:inline">Switch workspace</span>
                    </button>

                    <Link
                        :href="route('logout')"
                        method="post"
                        as="button"
                        class="rounded-lg bg-[#005740] px-3 py-2 text-xs font-semibold text-white transition-colors hover:bg-[#00432f]"
                    >
                        Logout
                    </Link>
                </div>
            </div>
        </header>

        <main class="flex-1">
            <div class="mx-auto w-full max-w-7xl px-4 py-5 sm:px-6">
                <slot />
            </div>
        </main>

        <footer class="border-t border-gray-200 bg-white py-3">
            <p class="text-center text-[11px] text-gray-400">
                Inventory Management System · {{ year }} University of the
                Philippines Cebu
            </p>
        </footer>
    </div>
</template>

