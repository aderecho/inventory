<script setup>
import InspectionShowLayout from "@/Layouts/Inspection/InspectionShowLayout.vue";

defineProps({
    item: Object,
});
</script>

<template>
    <InspectionShowLayout :item="item" />
</template>