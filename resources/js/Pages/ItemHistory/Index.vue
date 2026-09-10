<script setup>
import { ref } from "vue";
import { router } from "@inertiajs/vue3";
import { Head } from "@inertiajs/vue3";
import Layout from "@/Layouts/Layout.vue";
import PageHeader from "@/Components/PageHeader.vue";
import ItemHistoryTable from "@/Components/ItemHistoryTable.vue";
import SearchFilterBar from "@/Components/Filters/SearchFilterBar.vue";
import { useLoading } from "@/Composables/useLoading";

defineProps({
    items: Object,
    rooms: Array,
});

const { startLoading, stopLoading } = useLoading();

const search = ref("");
const room_id = ref("");
const acknowledgement_status = ref("");

function viewItem(id) {
    startLoading("Loading item history", "Fetching location records...");
    router.visit(route("item-histories.show", id), {
        onFinish: () => stopLoading(),
    });
}
</script>

<template>
    <Head title="UP | Item History" />

    <Layout>
        <PageHeader title="Item Location History" />

        <div class="flex flex-col md:flex-row md:items-center justify-between gap-3 mt-5">
            <SearchFilterBar
                :search="search"
                :room_id="room_id"
                :rooms="rooms"
                :acknowledgement_status="acknowledgement_status"
                @update:acknowledgement_status="acknowledgement_status = $event"
                @update:search="search = $event"
                :mode="'item-history'"
            />
        </div>

        <!-- Table -->
        <ItemHistoryTable :items="items" @view="viewItem" />
    </Layout>
</template>