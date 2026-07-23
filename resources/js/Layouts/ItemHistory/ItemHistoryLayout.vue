<script setup>
import { ref } from "vue";
import NavHeader from "@/Components/NavHeader.vue";
import SideBar from "@/Components/SideBar.vue";
import PageHeader from "@/Components/PageHeader.vue";
import LoadingOverlay from "@/Components/LoadingOverlay.vue";

defineProps({
    title: { type: String, default: "" },
    isLoading: { type: Boolean, default: false },
    loadingTitle: { type: String, default: "" },
    loadingMessage: { type: String, default: "" },
});

const isSidebarOpen = ref(true);
const toggleSidebar = () => {
    isSidebarOpen.value = !isSidebarOpen.value;
};
</script>

<template>
    <LoadingOverlay
        :show="isLoading"
        :title="loadingTitle"
        :message="loadingMessage"
    />

    <div class="h-screen flex flex-col bg-gray-100">
        <NavHeader class="flex-shrink-0" @toggleSidebar="toggleSidebar" />

        <div class="flex flex-1 overflow-hidden">
            <aside
                class="transition-all duration-300 ease-in-out"
                :class="
                    isSidebarOpen
                        ? 'translate-x-0 opacity-100'
                        : '-translate-x-full opacity-0 w-0'
                "
            >
                <SideBar />
            </aside>

            <main class="flex-1 p-4 sm:p-5 md:p-6 overflow-y-auto">
                <PageHeader v-if="title" :title="title" class="ml-4" />

                <slot />
            </main>
        </div>
    </div>
</template>