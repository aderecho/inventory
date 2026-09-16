<script setup>
import { ref, computed, watch } from "vue";
import { usePage } from "@inertiajs/vue3";
import { Head } from "@inertiajs/vue3";
import Layout from "@/Layouts/Layout.vue";
import InventoryAcknowledgement from "@/Components/InventoryAcknowledgement.vue";
import PageHeader from "@/Components/PageHeader.vue";
import SearchFilterBar from "@/Components/Filters/SearchFilterBar.vue";
import Show from "@/Pages/Acknowledgement/Show.vue";

const page = usePage();
const receipts = computed(() => page.props.receipts);

const search = ref("");

const selectedReceipt = ref(null);
const showModal = ref(false);
const groupedByPerson = ref([]);

function buildGrouped(receipt) {
    const groups = {};
    receipt.acknowledgement_items.forEach((item) => {
        const person = item.accountable_person;
        if (!groups[person.id]) {
            groups[person.id] = { person, items: [] };
        }
        groups[person.id].items.push({
            ...item,
            files: item.files ?? [], // ← ensure files array exists
            inventory_item: item.inventory_items, // ← match Show.vue expected key
        });
    });
    return Object.values(groups);
}

function openModal(receipt) {
    selectedReceipt.value = receipt;
    groupedByPerson.value = buildGrouped(receipt);
    showModal.value = true;
}

watch(receipts, (newReceipts) => {
    if (!selectedReceipt.value) return;

    const updated = newReceipts.data.find(
        (r) => r.id === selectedReceipt.value.id,
    );
    if (updated) {
        selectedReceipt.value = updated;
        groupedByPerson.value = buildGrouped(updated);
    }
});

function closeModal() {
    showModal.value = false;
    selectedReceipt.value = null;
}
</script>

<template>
    <Head title="UP | Inventory/Acknowledgements" />

    <Layout>
        <PageHeader title="Acknowledgements" class="ml-4" />
        <div class="flex flex-col mx-3 lg:flex-row gap-4 my-5 flex-wrap">
            <div class="flex-1 w-full lg:w-[22rem] xl:w-[25rem]">
                <div class="flex justify-end mb-3">
                    <SearchFilterBar
                        :search="search"
                        @update:search="search = $event"
                        :mode="'acknowledgements'"
                    />
                </div>
                <InventoryAcknowledgement :receipts="receipts" @view="openModal" />
                <Show
                    v-if="showModal"
                    :receipt="selectedReceipt"
                    :groupedByPerson="groupedByPerson"
                    @close="closeModal"
                />
            </div>
        </div>
    </Layout>
</template>