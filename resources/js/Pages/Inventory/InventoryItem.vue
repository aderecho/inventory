<script setup>
    import InventoryItemLayout from '@/Layouts/Inventory/InventoryItemLayout.vue';
    import { Head } from '@inertiajs/vue3';

    const props = defineProps({
    rooms: Array,
})
</script>

<template>
   <Head title="UP | Inventory/Items" />
    
        <div class="bg-[#F3F3F3]">
            <InventoryItemLayout/>  
        </div> 

</template>