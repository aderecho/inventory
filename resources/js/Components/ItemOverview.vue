<script setup>
import { computed } from "vue";
import DashboardStatCard from "@/Components/DashboardStatCard.vue";

/**
 * The three headline cards. Contextual copy per card, and the split that sits
 * behind the number is supplied by the dashboard payload.
 */
const props = defineProps({
    itemOverview: {
        type: Array,
        required: true,
        default: () => [],
    },
});

const META = {
    "Total Items": {
        subtitle: "Active Tracked Assets",
        emptyTitle: "No Items Found",
        emptyMessage: "No records have been logged yet.",
    },
    "Total Suppliers": {
        subtitle: "Vetted Logistics Channels",
        emptyTitle: "No Suppliers Found",
        emptyMessage: "No suppliers have been registered yet.",
    },
    "Total Users": {
        subtitle: "Registered Accounts",
        emptyTitle: "No Users Found",
        emptyMessage: "No user accounts have been registered yet.",
    },
};

const metaFor = (title) =>
    META[title] ?? {
        subtitle: "System Parameter",
        emptyTitle: "No Records Found",
        emptyMessage: "No records have been logged yet.",
    };

const cards = computed(() =>
    props.itemOverview.map((card) => ({ ...card, meta: metaFor(card.title) })),
);
</script>

<template>
    <DashboardStatCard
        v-for="(card, index) in cards"
        :key="card.title"
        :item="card"
        :index="index"
    />
</template>
