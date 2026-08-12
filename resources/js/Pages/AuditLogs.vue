<script setup>
import AuditLogsLayout from "@/Layouts/AuditLogsLayout.vue";
import { Head } from "@inertiajs/vue3";

defineProps({
    activities: Object,
});
</script>

<template>
    <Head title="UP | Audit Logs" />

    <div class="bg-[#F3F3F3]">
        <AuditLogsLayout :activities="activities" />
    </div>
</template>