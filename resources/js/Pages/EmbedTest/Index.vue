<script setup>
const accessToken = 'jhISOfH5YtOw5cudg5UW5F2ZFat3g3gu4WIyrWRXfdyMllRSRyTVjCCkOech8btt';

const embedUrl = `http://localhost:8000/embed/dashboard?access_token=${accessToken}`;
</script>

<template>
    <div class="p-6">
        <h1 class="text-2xl font-bold mb-4">
            IFrame Dashboard Test
        </h1>

        <iframe
            :src="embedUrl"
            class="w-full border rounded-lg"
            style="height: 800px;"
            frameborder="0"
        ></iframe>
    </div>
</template>