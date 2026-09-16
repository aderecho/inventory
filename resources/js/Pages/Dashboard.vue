<script setup>
import { Head } from "@inertiajs/vue3";
import Layout from "@/Layouts/Layout.vue";
import DashboardCharts from "@/Components/DashboardCharts.vue";
import axios from "axios";
import { ref, onMounted, onBeforeUnmount } from "vue";

// const canvas = ref(null);

// let ctx = null;
// let drawing = false;

// const startDrawing = (event) => {
//     drawing = true;

//     const rect = canvas.value.getBoundingClientRect();

//     ctx.beginPath();
//     ctx.moveTo(
//         event.clientX - rect.left,
//         event.clientY - rect.top
//     );
// };

// const draw = (event) => {
//     if (!drawing) return;

//     const rect = canvas.value.getBoundingClientRect();

//     ctx.lineTo(
//         event.clientX - rect.left,
//         event.clientY - rect.top
//     );

//     ctx.stroke();
// };

// const stopDrawing = () => {
//     drawing = false;
//     ctx.closePath();
// };

// const clearSignature = () => {
//     ctx.clearRect(
//         0,
//         0,
//         canvas.value.width,
//         canvas.value.height
//     );
// };

// const testSignReceipt = async () => {
//     try {
//         // Convert signature canvas to PNG Base64
//         const signature = canvas.value.toDataURL("image/png");

//         const response = await axios.post(
//             route("print.receipt.sign"),
//             {
//                 ids: [1],
//                 signature: signature,
//             }
//         );

//         console.log(response.data);

//         window.open(
//             `/storage/${response.data.path}`,
//             "_blank"
//         );
//     } catch (error) {
//         console.error(
//             error.response?.data || error
//         );
//     }
// };

// onMounted(() => {
//     const element = canvas.value;

//     ctx = element.getContext("2d");

//     ctx.strokeStyle = "#000000";
//     ctx.lineWidth = 2;
//     ctx.lineCap = "round";

//     element.addEventListener(
//         "mousedown",
//         startDrawing
//     );

//     element.addEventListener(
//         "mousemove",
//         draw
//     );

//     element.addEventListener(
//         "mouseup",
//         stopDrawing
//     );

//     element.addEventListener(
//         "mouseleave",
//         stopDrawing
//     );
// });

// onBeforeUnmount(() => {
//     const element = canvas.value;

//     element.removeEventListener(
//         "mousedown",
//         startDrawing
//     );

//     element.removeEventListener(
//         "mousemove",
//         draw
//     );

//     element.removeEventListener(
//         "mouseup",
//         stopDrawing
//     );

//     element.removeEventListener(
//         "mouseleave",
//         stopDrawing
//     );
// });
</script>

<template>
    <Head title="UP | Dashboard" />

    <Layout>
        <!-- <div class="mb-6 rounded-xl border bg-white p-6 shadow">
            <h2 class="mb-4 text-lg font-semibold">
                Sign Receipt
            </h2>

            <div
                class="mb-4 overflow-hidden rounded-lg border-2 border-gray-300"
            >
                <canvas
                    ref="canvas"
                    width="600"
                    height="250"
                    class="block w-full bg-white"
                />
            </div>

            <div class="flex gap-3">
                <button
                    type="button"
                    @click="clearSignature"
                    class="rounded-lg bg-gray-500 px-4 py-2 text-white hover:bg-gray-600"
                >
                    Clear
                </button>

                <button
                    type="button"
                    @click="testSignReceipt"
                    class="rounded-lg bg-blue-600 px-4 py-2 text-white hover:bg-blue-700"
                >
                    Sign Receipt
                </button>
            </div>
        </div> -->

        <DashboardCharts />
    </Layout>
</template>