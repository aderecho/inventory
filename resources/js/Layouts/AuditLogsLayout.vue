<script setup>
import SideBar from "@/Components/SideBar.vue";
import NavHeader from "@/Components/NavHeader.vue";
import PageHeader from "@/Components/PageHeader.vue";
import LoadingOverlay from "@/Components/LoadingOverlay.vue";

import { useSidebar } from "@/Composables/useSidebar";
import { useLoading } from "@/Composables/useLoading";

defineProps({
    activities: Object,
});

const { isSidebarOpen, toggleSidebar } = useSidebar();

const { isLoading, loadingTitle, loadingMessage } = useLoading();

const formatDate = (date) => {
    return new Date(date).toLocaleString();
};

const getUserName = (activity) => {
    return (
        activity.causer?.user_profiles?.full_name ||
        activity.causer?.userProfiles?.full_name ||
        activity.causer?.email ||
        "System"
    );
};

const getModelName = (subjectType) => {
    if (!subjectType) return "-";

    return subjectType.split("\\").pop();
};
</script>

<template>
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
                <SideBar
                    :isOpen="isSidebarOpen"
                    @toggleSidebar="toggleSidebar"
                />
            </aside>

            <div class="flex flex-col flex-1 overflow-hidden">
                <NavHeader
                    :isSidebarOpen="isSidebarOpen"
                    @toggleSidebar="toggleSidebar"
                />

                <main class="flex-1 overflow-y-auto sm:p-5 md:p-6 m-2">
                    <PageHeader title="Audit Logs" />

                    <div class="mt-6 bg-white rounded-lg shadow overflow-hidden">
                        <div class="overflow-x-auto">
                            <table
                                class="w-full table-auto border-collapse text-left bg-white text-xs sm:text-sm"
                            >
                                <thead class="bg-[#005740]">
                                    <tr class="text-white border">
                                        <th
                                            class="p-2 sm:p-3 md:p-4 text-left first:rounded-tl last:rounded-tr"
                                        >
                                            User
                                        </th>

                                        <th
                                            class="p-2 sm:p-3 md:p-4 text-left first:rounded-tl last:rounded-tr"
                                        >
                                            Event
                                        </th>

                                        <th
                                            class="p-2 sm:p-3 md:p-4 text-left first:rounded-tl last:rounded-tr"
                                        >
                                            Activity
                                        </th>

                                        <th
                                            class="p-2 sm:p-3 md:p-4 text-left first:rounded-tl last:rounded-tr"
                                        >
                                            Date
                                        </th>
                                    </tr>
                                </thead>

                                <tbody class="text-gray-700">
                                    <tr
                                        v-for="activity in activities.data"
                                        :key="activity.id"
                                        class="border border-rose-300 even:bg-gray-200"
                                    >
                                        <td class="p-2 sm:p-3 md:p-4">
                                            {{ getUserName(activity) }}
                                        </td>

                                        <td class="p-2 sm:p-3 md:p-4 capitalize">
                                            {{ activity.event }}
                                        </td>

                                        <td class="p-2 sm:p-3 md:p-4">
                                            {{ activity.description }}
                                        </td>

                                        <td class="p-2 sm:p-3 md:p-4">
                                            {{ formatDate(activity.created_at) }}
                                        </td>
                                    </tr>

                                    <tr v-if="activities.data.length === 0">
                                        <td
                                            colspan="4"
                                            class="text-center py-8 text-gray-500"
                                        >
                                            No audit logs found.
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        <!-- Pagination -->
                        <div class="mt-2 flex justify-between items-center mx-2 pb-3">
                            <div>
                                <p class="text-base font-bold text-[#3B3B3B]">
                                    Results:
                                    <span class="text-[#850038]"
                                        >{{ activities.from ?? 0 }}-{{ activities.to ?? 0 }} of {{ activities.total }}</span
                                    >
                                </p>
                            </div>
                            <div class="flex justify-center">
                                <div class="flex items-center gap-1 sm:gap-2 py-2">
                                    <span
                                        v-for="link in activities.links"
                                        :key="link.label"
                                    >
                                        <span
                                            v-if="link.url"
                                            @click="$inertia.visit(link.url)"
                                            class="flex items-center justify-center min-w-[32px] h-8 px-2 text-base rounded-full transition-all duration-200 cursor-pointer"
                                            :class="{
                                                'text-[#000000] hover:bg-[#e7e7e7]': !link.active,
                                                'bg-[#850038] text-white font-semibold shadow-sm':
                                                    link.active,
                                            }"
                                        >
                                            <i
                                                v-if="link.label.includes('Previous')"
                                                class="fa-solid fa-chevron-left"
                                            ></i>
                                            <i
                                                v-else-if="link.label.includes('Next')"
                                                class="fa-solid fa-chevron-right"
                                            ></i>
                                            <span v-else>{{ link.label }}</span>
                                        </span>
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                </main>
            </div>
        </div>
    </div>
</template>