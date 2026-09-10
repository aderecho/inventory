<script setup>
import { Head } from "@inertiajs/vue3";
import Layout from "@/Layouts/Layout.vue";
import DashboardCharts from "@/Components/DashboardCharts.vue";
</script>

<template>
    <Head title="UP | Dashboard" />

    <Layout>
        <DashboardCharts />
    </Layout>
</template>