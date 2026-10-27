<script setup>
defineProps({
    modelValue: { type: String, default: "" },
    label: { type: String, required: true },
    options: { type: Array, default: () => [] },
    includeSelectAll: { type: Boolean, default: false },
    widthClass: { type: String, default: "sm:w-36" },
});

defineEmits(["update:modelValue"]);
</script>

<template>
    <div class="flex flex-col w-full sm:w-auto">
        <label class="mb-1 text-[11px] font-semibold uppercase tracking-wide text-gray-500">
            {{ label }}
        </label>
        <select
            :value="modelValue"
            @change="$emit('update:modelValue', $event.target.value)"
            :class="widthClass"
            class="h-10 w-full rounded-lg border border-gray-300 bg-white text-xs text-gray-600 transition-colors focus:border-[#005740] focus:outline-none focus:ring-1 focus:ring-[#005740]"
        >
            <option v-if="includeSelectAll" value="">Select All</option>
            <option v-for="(option, i) in options" :key="i" :value="option.value">
                {{ option.label }}
            </option>
        </select>
    </div>
</template>
