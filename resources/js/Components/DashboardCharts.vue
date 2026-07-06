<script setup>
import { computed } from "vue";
import { usePage } from "@inertiajs/vue3";
import ItemOverview from "@/Components/ItemOverview.vue";
import PageHeader from "@/Components/PageHeader.vue";
import ItemsByClassificationChart from "@/Components/ItemsByClassificationChart.vue";
import AcquisitionsByClassificationChart from "@/Components/AcquisitionsByClassificationChart.vue";
import AccountablePersonChart from "@/Components/AccountablePersonChart.vue";
import IcsParPercentageChart from "@/Components/IcsParPercentageChart.vue";
import OrganizationUserProfileChart from "@/Components/OrganizationUserProfileChart.vue";
import { Boxes, Truck, Users } from "lucide-vue-next";

defineProps({
    showHeader: {
        type: Boolean,
        default: true,
    },
    embedToken: {
        type: String,
        default: null,
    },
});

const page = usePage();
const stats = computed(() => page.props.stats);

const classificationChartData = computed(
    () => page.props.classificationChartData,
);
const acquisitionsByClassification = computed(
    () => page.props.acquisitionsByClassification,
);
const availableYears = computed(() => page.props.availableYears);
const selectedYear = computed(() => page.props.selectedYear);
const icsParChartData = computed(() => page.props.icsParChartData || []);
const accountablePersonChartData = computed(
    () => page.props.accountablePersonChartData || [],
);
const organizationChartData = computed(
    () => page.props.organizationChartData || [],
);

const itemOverview = computed(() => [
    {
        title: "Total Items",
        icon: Boxes,
        bgColor: "bg-[#005740]",
        value: stats.value.items,
    },
    {
        title: "Total Suppliers",
        icon: Truck,
        bgColor: "bg-[#005740]",
        value: stats.value.suppliers,
    },
    {
        title: "Total Users",
        icon: Users,
        bgColor: "bg-[#005740]",
        value: stats.value.users,
    },
]);
</script>

<template>
    <div class="max-w-[1600px] mx-auto flex flex-col gap-6">
        <PageHeader v-if="showHeader" title="Dashboard" />

        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <ItemOverview :item-overview="itemOverview" />
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <div class="min-h-[240px]">
                <IcsParPercentageChart
                    class="w-full h-full"
                    :ics-par-chart-data="icsParChartData"
                />
            </div>
            <div class="min-h-[240px]">
                <AccountablePersonChart
                    class="w-full h-full"
                    :accountable-person-chart-data="accountablePersonChartData"
                />
            </div>
            <div class="min-h-[240px]">
                <ItemsByClassificationChart
                    class="w-full h-full"
                    :classification-chart-data="classificationChartData"
                />
            </div>
        </div>

        <div class="grid grid-cols-1 xl:grid-cols-2 gap-6">
            <div class="xl:col-span-2 min-h-[420px] lg:min-h-[480px]">
                <OrganizationUserProfileChart
                    class="w-full h-full"
                    :organization-chart-data="organizationChartData"
                />
            </div>
            <div class="xl:col-span-2 min-h-[420px] lg:min-h-[480px]">
                <AcquisitionsByClassificationChart
                    class="w-full h-full"
                    :acquisitions-by-classification="
                        acquisitionsByClassification
                    "
                    :available-years="availableYears"
                    :selected-year="selectedYear"
                    :embed-token="embedToken"
                />
            </div>
        </div>
    </div>
</template>
