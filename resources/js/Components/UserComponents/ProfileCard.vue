<script setup>
defineProps({
    user: {
        type: Object,
        required: true,
    },

    fields: {
        type: Array,
        default: () => [],
    },
});
</script>

<template>
    <div
        class="bg-white rounded-2xl border border-gray-200 shadow-sm overflow-hidden"
    >
        <!-- Cover -->
        <div class="h-24 bg-[#850038]" />

        <!-- Profile -->
        <div class="px-6 pb-6">
            <!-- Avatar -->
            <div class="flex justify-center -mt-12">
                <div
                    class="w-24 h-24 rounded-full bg-white border-4 border-white shadow-md flex items-center justify-center"
                >
                    <span
                        class="text-3xl font-bold text-[#850038]"
                    >
                        {{
                            `${user?.user_profiles?.first_name?.[0] ?? ''}${
                                user?.user_profiles?.last_name?.[0] ?? ''
                            }`
                        }}
                    </span>
                </div>
            </div>

            <!-- Name -->
            <div class="text-center mt-4">
                <h2
                    class="text-xl font-bold text-gray-900"
                >
                    {{ user?.user_profiles?.full_name }}
                </h2>

                <span
                    class="inline-flex mt-2 px-3 py-1 rounded-full bg-[#850038]/10 text-[#850038] text-xs font-semibold"
                >
                    {{ user?.role ?? 'User' }}
                </span>
            </div>

            <!-- Divider -->
            <div class="border-t border-gray-200 my-6" />

            <!-- Details -->
            <div class="space-y-5">
                <div
                    v-for="field in fields"
                    :key="field.label"
                >
                    <p
                        class="text-xs uppercase tracking-wider text-gray-500"
                    >
                        {{ field.label }}
                    </p>

                    <p
                        class="mt-1 text-sm font-medium text-gray-900 break-words"
                    >
                        {{ field.value }}
                    </p>
                </div>
            </div>
        </div>
    </div>
</template>