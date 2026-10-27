<script setup>
import { computed, ref } from "vue";
import { Head, usePage } from "@inertiajs/vue3";
import Layout from "@/Layouts/Layout.vue";
import PageHeader from "@/Components/PageHeader.vue";
import InventoryTable from "@/Components/InventoryTable.vue";
import SearchFilterBar from "@/Components/Filters/SearchFilterBar.vue";
import PropertyCardModal from "@/Components/Modals/PropertyCardModal.vue";
import { IdCard } from "lucide-vue-next";

const page = usePage();

const items = computed(() => page.props.items || { data: [] });
const rooms = computed(() => page.props.rooms || []);
const assetConditions = computed(() => page.props.assetConditions || []);

const search = ref("");
const room_id = ref("");
const asset_condition_id = ref("");

const conditionFilter = computed(() => [
    {
        label: "Condition",
        options: [
            { label: "All conditions", value: "" },
            ...assetConditions.value.map((condition) => ({
                label: condition.condition_name,
                value: condition.id,
            })),
        ],
    },
]);

const money = (value) =>
    value != null
        ? "₱" +
          Number(value).toLocaleString("en-PH", {
              minimumFractionDigits: 2,
              maximumFractionDigits: 2,
          })
        : "N/A";

const columns = [
    { label: "Item Name", key: "item_name" },
    { label: "Property Number", key: "property_number" },
    {
        label: "Serial Number",
        key: "serial_number",
        format: (value) => value || "N/A",
    },
    {
        label: "Accountable Person",
        key: "latest_acknowledgement_item",
        format: (value) => {
            const person = value?.accountable_person;
            const name = [person?.first_name, person?.last_name]
                .filter(Boolean)
                .join(" ");

            return name || '<span class="text-gray-400 italic">Unassigned</span>';
        },
    },
    { label: "Room Name", key: "room_name" },
    {
        label: "Condition",
        key: "latest_inspection",
        format: (value) =>
            value?.asset_condition?.condition_name ??
            '<span class="text-gray-400 italic">Not inspected</span>',
    },
    {
        label: "Unit Cost",
        key: "unit_cost",
        format: (value) => money(value),
    },
];

const showCard = ref(false);
const cardItemId = ref(null);

function openCard(item) {
    cardItemId.value = item.id;
    showCard.value = true;
}
</script>

<template>
    <Head title="UP | Property Cards" />

    <Layout>
        <PageHeader title="Property Cards" />

        <div class="w-full h-full">
            <div class="mt-6 rounded-xl border border-gray-200 bg-white p-3 shadow-sm">
                <div class="flex flex-col gap-3 xl:flex-row xl:items-end xl:justify-between">
                    <div class="flex items-center gap-2">
                        <span
                            class="flex h-9 w-9 items-center justify-center rounded-lg bg-[#005740]/10 text-[#005740]"
                        >
                            <IdCard class="h-4 w-4" />
                        </span>

                        <div>
                            <p class="text-xs font-semibold text-[#1b1b1b]">
                                {{ items.total ?? 0 }} article(s)
                            </p>
                            <p class="text-[11px] text-gray-500">
                                One card per article — open a row to see every
                                recorded movement.
                            </p>
                        </div>
                    </div>

                    <div class="w-full xl:max-w-3xl">
                        <SearchFilterBar
                            :search="search"
                            :room_id="room_id"
                            :rooms="rooms"
                            :asset_condition_id="asset_condition_id"
                            :filterCondition="conditionFilter"
                            @update:search="search = $event"
                            @update:room_id="room_id = $event"
                            @update:asset_condition_id="asset_condition_id = $event"
                            :mode="'property-cards'"
                        />
                    </div>
                </div>
            </div>

            <InventoryTable
                :columns="columns"
                :rows="items"
                :rooms="rooms"
                :module="'property-cards'"
                row-action="view"
                row-action-tooltip="Click to open the property card"
                @view="openCard"
            />

            <PropertyCardModal
                :show="showCard"
                :item-id="cardItemId"
                @close="showCard = false"
            />
        </div>
    </Layout>
</template>

