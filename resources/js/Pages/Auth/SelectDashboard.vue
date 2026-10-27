<script setup>
import { Head, useForm } from "@inertiajs/vue3";
import AuthLayout from "@/Layouts/AuthLayout.vue";
import DashboardChooser from "@/Components/Auth/DashboardChooser.vue";

defineProps({
    options: {
        type: Array,
        default: () => [],
    },
    staffView: {
        type: Boolean,
        default: false,
    },
});

const form = useForm({
    route: null,
});

function choose(routeName) {
    if (form.processing) return;

    form.route = routeName;
    form.post(route("auth.choose-dashboard"), { preserveScroll: true });
}
</script>

<template>
    <Head title="UP | Select Dashboard" />

    <AuthLayout
        badge="University of the Philippines Cebu"
        title="Where would you like to continue?"
        description="Your account can open more than one workspace. Pick the dashboard you need right now."
        :tags="['Inventory', 'Assets', 'Accountability']"
    >
        <DashboardChooser
            :options="options"
            :staff-view="staffView"
            :processing="form.processing"
            :chosen="form.route"
            :error="form.errors.route"
            @choose="choose"
        />
    </AuthLayout>
</template>

