<script setup>
import ItemHistoryShowLayout from "@/Layouts/ItemHistory/ItemHistoryShowLayout.vue";

defineProps({
    item: Object,
});
</script>

<template>
    <ItemHistoryShowLayout :item="item" />
</template>