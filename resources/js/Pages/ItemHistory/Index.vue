<script setup>
import ItemHistoryLayout from "@/Layouts/ItemHistory/ItemHistoryLayout.vue";

defineProps({
    items: Object,
    rooms: Array,
});
</script>

<template>
    <ItemHistoryLayout :items="items" :rooms="rooms" />
</template>