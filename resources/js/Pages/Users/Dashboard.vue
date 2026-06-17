<script setup>
import UserLayout from "@/Layouts/Users/UserLayout.vue";

defineProps({
    user: Object,
    items: Object,
    stats: Object,
});
</script>

<template>
    <UserLayout
        :user="user"
        :items="items"
        :stats="stats"
    />
</template>