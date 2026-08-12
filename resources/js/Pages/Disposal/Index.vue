<script setup>
    import DisposalLayout from '@/Layouts/Disposal/DisposalLayout.vue';
    import { Head } from '@inertiajs/vue3';

    const props = defineProps({
    rooms: Array,
})
</script>

<template>
   <Head title="UP | Disposal" />
    
        <div class="bg-[#F3F3F3]">
            <DisposalLayout/>  
        </div> 

</template>