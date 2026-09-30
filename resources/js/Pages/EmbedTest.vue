<script setup>
import { computed } from "vue";

const props = defineProps({
    embedUrl: {
        type: String,
        required: true,
    },
});

const iframeUrl = computed(() => props.embedUrl);
</script>

<template>
    <div class="min-h-screen bg-gray-100 p-6">
        <div class="mx-auto max-w-7xl">
            <h1 class="mb-2 text-2xl font-bold">
                IMS Embed Test
            </h1>

            <p class="mb-4 text-sm text-gray-600">
                Testing whether ims.upcebu.edu.ph can be embedded inside an iframe.
            </p>

            <div class="mb-4 rounded-lg bg-white p-4 shadow">
                <p class="mb-2 text-sm font-semibold">
                    Embed URL:
                </p>

                <div class="break-all rounded bg-gray-100 p-3 text-xs">
                    {{ iframeUrl }}
                </div>
            </div>

            <div class="overflow-hidden rounded-lg bg-white shadow">
                <iframe
                    :src="iframeUrl"
                    class="h-[800px] w-full border-0"
                    title="IMS Embed Test"
                    allowfullscreen
                ></iframe>
            </div>
        </div>
    </div>
</template>