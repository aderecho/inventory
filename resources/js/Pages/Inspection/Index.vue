<script setup>
    import InspectionLayout from '@/Layouts/Inspection/InspectionLayout.vue';
    import { Head } from '@inertiajs/vue3';

    const props = defineProps({
    rooms: Array,
})
</script>

<template>
   <Head title="UP | Inspection" />
    
        <div class="bg-[#F3F3F3]">
            <InspectionLayout/>  
        </div> 

</template>