<script setup>
import { computed } from "vue";
import { Head, usePage } from "@inertiajs/vue3";
import DashboardCharts from "@/Components/DashboardCharts.vue";

const page = usePage();
const embedToken = computed(() => page.props.embedToken);
</script>

<template>
    <Head title="Dashboard" />
    <div class="bg-[#F3F3F3] min-h-screen p-4 sm:p-6">
        <DashboardCharts :show-header="false" :embed-token="embedToken" />
    </div>
</template>