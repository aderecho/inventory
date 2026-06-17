<script setup>
defineProps({
    title: {
        type: String,
        required: true,
    },

    value: {
        type: [String, Number],
        required: true,
    },

    description: {
        type: String,
        default: "",
    },
});
</script>

<template>
    <div
        class="bg-white rounded-2xl border border-gray-200 shadow-sm p-6 hover:shadow-md transition-all duration-200"
    >
        <div class="flex items-start justify-between">
            <div>
                <p
                    class="text-sm font-medium uppercase tracking-wide text-gray-500"
                >
                    {{ title }}
                </p>

                <h2
                    class="text-4xl font-bold text-[#850038] mt-3"
                >
                    {{ value }}
                </h2>

                <p
                    v-if="description"
                    class="text-sm text-gray-500 mt-3"
                >
                    {{ description }}
                </p>
            </div>

            <div
                class="w-12 h-12 rounded-xl bg-[#850038]/10 flex items-center justify-center"
            >
                <div
                    class="w-6 h-6 rounded-full bg-[#850038]"
                />
            </div>
        </div>
    </div>
</template>