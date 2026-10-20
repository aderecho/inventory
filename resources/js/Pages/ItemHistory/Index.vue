<script setup>
import { computed, ref } from "vue";
import { usePage } from "@inertiajs/vue3";
import { Head } from "@inertiajs/vue3";
import Layout from "@/Layouts/Layout.vue";
import InventoryTable from "@/Components/InventoryTable.vue";
import PageHeader from "@/Components/PageHeader.vue";
import SearchFilterBar from "@/Components/Filters/SearchFilterBar.vue";
import ItemHistoryModal from "@/Components/Modals/ItemHistoryModal.vue";

defineProps({
    rooms: Array,
});

const page = usePage();

const items = computed(() => page.props.items || { data: [] });

const search = ref("");
const room_id = ref("");
const acknowledgement_status = ref("");

const acknowledgementFilter = [
    {
        label: "Accountable Person",
        options: [
            { label: "All Items", value: "" },
            { label: "Assigned", value: "with_acknowledgement" },
            { label: "Unassigned", value: "without_acknowledgement" },
        ],
    },
];

// Where each item sits, who holds it, and how many moves it has recorded. The
// moves themselves open in a modal.
const columns = [
    { label: "Item Name", key: "item_name" },
    { label: "Property Number", key: "property_number" },
    {
        label: "Current Location",
        key: "room_name",
        format: (value) =>
            value && value !== "N/A"
                ? `<span class="font-medium text-gray-800">${value}</span>`
                : '<span class="text-gray-400 italic">No location set</span>',
    },
    {
        label: "Accountable Person",
        key: "latest_acknowledgement_item",
        format: (value) => {
            const person = value?.accountable_person;
            const fullName =
                `${person?.first_name ?? ""} ${person?.last_name ?? ""}`.trim();

            if (!fullName) {
                return '<span class="text-[#D32F2F] font-bold">Unassigned</span>';
            }

            return fullName;
        },
    },
    {
        label: "Location Records",
        key: "history_locations_count",
        format: (value) => {
            const count = Number(value ?? 0);

            return `${count} ${count === 1 ? "record" : "records"}`;
        },
    },
    { label: "Action", key: "action" },
];

const showHistoryModal = ref(false);
const selectedItem = ref(null);

function handleView(item) {
    selectedItem.value = item;
    showHistoryModal.value = true;
}

function closeHistoryModal() {
    showHistoryModal.value = false;
    selectedItem.value = null;
}
</script>

<template>
    <Head title="UP | Item Location History" />

    <Layout>
        <PageHeader title="Item Location History" />

        <div class="w-full h-full">
            <div
                class="mt-6 rounded-xl border border-gray-200 bg-white p-3 shadow-sm"
            >
                <div
                    class="flex flex-col gap-3 xl:flex-row xl:items-end xl:justify-between"
                >
                    <span
                        class="rounded-full border border-[#005740]/20 bg-[#005740]/[0.06] px-3 py-1.5 text-xs font-semibold text-[#005740]"
                    >
                        {{ items.total ?? 0 }} item(s)
                    </span>

                    <div class="w-full xl:max-w-2xl">
                        <SearchFilterBar
                            :search="search"
                            :room_id="room_id"
                            :rooms="rooms"
                            :acknowledgement_status="acknowledgement_status"
                            :acknowledgementFilter="acknowledgementFilter"
                            @update:search="search = $event"
                            @update:room_id="room_id = $event"
                            @update:acknowledgement_status="
                                acknowledgement_status = $event
                            "
                            :mode="'item-history'"
                        />
                    </div>
                </div>
            </div>

            <InventoryTable
                :columns="columns"
                :rows="items"
                :rooms="rooms"
                :module="'item-history'"
                :actions="['view']"
                :row-action="'view'"
                @view="handleView"
            />
        </div>

        <ItemHistoryModal
            v-if="showHistoryModal && selectedItem"
            :item="selectedItem"
            @close="closeHistoryModal"
        />
    </Layout>
</template>
