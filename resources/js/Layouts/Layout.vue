<script setup>
import { useSidebar } from "@/Composables/useSidebar";
import { useLoading } from "@/Composables/useLoading";
import SideBar from "@/Components/SideBar.vue";
import NavHeader from "@/Components/NavHeader.vue";
import SessionTimeoutWarning from "@/Components/SessionTimeoutWarning.vue";
import LoadingOverlay from "@/Components/LoadingOverlay.vue";
import Toast from "primevue/toast";

const { isSidebarOpen, toggleSidebar } = useSidebar();
const { isLoading, loadingTitle, loadingMessage } = useLoading();
</script>

<template>
    <SessionTimeoutWarning />
    <Toast />
    <LoadingOverlay
        :show="isLoading"
        :title="loadingTitle"
        :message="loadingMessage"
    />

    <div class="h-screen flex flex-col bg-gray-100">
        <div class="flex flex-1 overflow-hidden">
            <aside
                class="h-full transition-all duration-300 ease-in-out flex-shrink-0"
            >
                <SideBar :isOpen="isSidebarOpen" @toggleSidebar="toggleSidebar" />
            </aside>

            <div class="flex flex-col flex-1 overflow-hidden">
                <NavHeader
                    :isSidebarOpen="isSidebarOpen"
                    @toggleSidebar="toggleSidebar"
                />

                <main class="flex-1 sm:p-5 md:p-6 md:mx-0 overflow-y-auto">
                    <div class="m-2">
                        <slot />
                    </div>
                </main>
            </div>
        </div>
    </div>
</template>