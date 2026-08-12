<script setup>
import SideBar from "@/Components/SideBar.vue";
import NavHeader from "@/Components/NavHeader.vue";
import DashboardCharts from "@/Components/DashboardCharts.vue";
import LoadingOverlay from "@/Components/LoadingOverlay.vue";
import { useLoading } from "@/Composables/useLoading";
import { useSidebar } from "@/Composables/useSidebar";
import SessionTimeoutWarning from '@/Components/SessionTimeoutWarning.vue';
import { usePage } from '@inertiajs/vue3';

const { isSidebarOpen, toggleSidebar } = useSidebar();
const { isLoading, loadingTitle, loadingMessage } = useLoading();
const page = usePage();
</script>

<template>
  <div>
    <SessionTimeoutWarning />
    <LoadingOverlay :show="isLoading" :title="loadingTitle" :message="loadingMessage" />

    <div class="h-screen flex flex-col bg-gray-50/50">
      <div class="flex flex-1 overflow-hidden">
        <aside class="h-full transition-all duration-300 ease-in-out flex-shrink-0">
          <SideBar :isOpen="isSidebarOpen" @toggleSidebar="toggleSidebar" />
        </aside>

        <div class="flex flex-col flex-1 overflow-hidden">
          <NavHeader :isSidebarOpen="isSidebarOpen" @toggleSidebar="toggleSidebar" />

          <main class="flex-1 p-4 sm:p-6 md:p-8 overflow-y-auto">
            <slot>
              <DashboardCharts />
            </slot>
          </main>
        </div>
      </div>
    </div>
  </div>
</template>