<script setup>
import { Head, useForm } from "@inertiajs/vue3";

const props = defineProps({
    options: {
        type: Array,
        default: () => [],
    },
});

const form = useForm({
    route: null,
});

const routeLabels = {
    "dashboard.index": {
        label: "Admin Dashboard",
        description: "Manage inventory, users, roles, and system settings.",
        icon: "fa-solid fa-gauge-high",
    },
    "user.dashboard": {
        label: "User Dashboard",
        description: "View your assigned items and account details.",
        icon: "fa-solid fa-user",
    },
};

function labelFor(routeName) {
    return (
        routeLabels[routeName] ?? {
            label: routeName,
            description: "",
            icon: "fa-solid fa-arrow-right",
        }
    );
}

function choose(routeName) {
    form.route = routeName;
    form.post(route("auth.choose-dashboard"));
}
</script>

<template>
    <Head title="UP | Select Dashboard" />

    <div
        class="min-h-screen flex items-center justify-center bg-gray-50 px-4"
    >
        <div class="w-full max-w-2xl">
            <div class="text-center mb-8">
                <h1 class="text-2xl font-bold text-[#1f2d27]">
                    Choose where you'd like to go
                </h1>
                <p class="text-sm text-gray-500 mt-1">
                    Your account has access to more than one dashboard.
                    Select one to continue.
                </p>
            </div>

            <div
                :class="[
                    'grid gap-4',
                    options.length > 1 ? 'sm:grid-cols-2' : 'sm:grid-cols-1',
                ]"
            >
                <button
                    v-for="option in options"
                    :key="option"
                    type="button"
                    :disabled="form.processing"
                    @click="choose(option)"
                    class="text-left bg-white rounded-2xl border border-gray-200 p-6 hover:border-[#005740] hover:shadow-md transition-all disabled:opacity-60 disabled:cursor-not-allowed"
                >
                    <div
                        class="h-11 w-11 rounded-xl bg-gradient-to-r from-[#005740] to-[#00795a] flex items-center justify-center mb-4"
                    >
                        <i
                            :class="[labelFor(option).icon, 'text-white text-lg']"
                        ></i>
                    </div>

                    <h3 class="font-semibold text-[#1f2d27] text-base">
                        {{ labelFor(option).label }}
                    </h3>

                    <p
                        v-if="labelFor(option).description"
                        class="text-xs text-gray-500 mt-1.5"
                    >
                        {{ labelFor(option).description }}
                    </p>
                </button>
            </div>

            <p
                v-if="form.errors.route"
                class="text-red-500 text-xs text-center mt-4"
            >
                {{ form.errors.route }}
            </p>
        </div>
    </div>
</template>