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
        <label class="text-xs text-[#3B3B3B] font-bold mb-1 sm:mb-0">
            {{ label }}
        </label>
        <select
            :value="modelValue"
            @change="$emit('update:modelValue', $event.target.value)"
            :class="widthClass"
            class="h-8 sm:h-9 w-full text-xs rounded-md text-gray-600 border focus:ring-[#850038] focus:outline-none focus:border-[#850038]"
        >
            <option v-if="includeSelectAll" value="">Select All</option>
            <option v-for="(option, i) in options" :key="i" :value="option.value">
                {{ option.label }}
            </option>
        </select>
    </div>
</template>