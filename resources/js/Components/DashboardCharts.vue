<script setup>
import { computed } from "vue";
import { usePage } from "@inertiajs/vue3";
import ItemOverview from "@/Components/ItemOverview.vue";
import DashboardHighlights from "@/Components/DashboardHighlights.vue";
import PageHeader from "@/Components/PageHeader.vue";
import ItemsByClassificationChart from "@/Components/ItemsByClassificationChart.vue";
import AcquisitionsByClassificationChart from "@/Components/AcquisitionsByClassificationChart.vue";
import AccountablePersonChart from "@/Components/AccountablePersonChart.vue";
import IcsParPercentageChart from "@/Components/IcsParPercentageChart.vue";
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
const highlights = computed(() => page.props.highlights || {});

const itemOverview = computed(() => {
    const totalItems = Number(stats.value?.items ?? 0);
    const unassigned = Number(highlights.value?.unassigned_items ?? 0);
    const assigned = Math.max(0, totalItems - unassigned);

    const suppliers = Number(stats.value?.suppliers ?? 0);
    const archivedSuppliers = Number(stats.value?.suppliers_archived ?? 0);

    const users = Number(stats.value?.users ?? 0);
    const spmoUsers = Number(stats.value?.users_spmo ?? 0);
    const otherUsers = Math.max(0, users - spmoUsers);

    const percent = (part, whole) =>
        whole > 0 ? Math.round((part / whole) * 100) : 0;

    // Links only go where the signed-in user is actually allowed in.
    const permissions = page.props.auth?.permissions ?? [];
    const can = (name) => permissions.includes(name);

    const canViewItems = can("view inventory");
    const canViewSuppliers = can("view suppliers");
    const canViewSupplierArchive = can("view archive_supplier");
    const canViewUsers = can("view users");

    return [
        {
            title: "Total Items",
            icon: Boxes,
            bgColor: "bg-[#005740]",
            value: totalItems,
            href: canViewItems ? "/inventory/items" : null,
            support: {
                badge: `${percent(assigned, totalItems)}% assigned`,
                segments: [
                    {
                        label: "Assigned",
                        value: assigned,
                        tone: "brand",
                        href: canViewItems
                            ? "/inventory/items?acknowledgement_status=with_acknowledgement"
                            : null,
                    },
                    {
                        label: "Unassigned",
                        value: unassigned,
                        tone: "amber",
                        href: canViewItems
                            ? "/inventory/items?acknowledgement_status=without_acknowledgement"
                            : null,
                    },
                ],
            },
        },
        {
            title: "Total Suppliers",
            icon: Truck,
            bgColor: "bg-[#005740]",
            value: suppliers,
            href: canViewSuppliers ? "/suppliers" : null,
            support: {
                badge: `${percent(suppliers, suppliers + archivedSuppliers)}% active`,
                segments: [
                    {
                        label: "Active",
                        value: suppliers,
                        tone: "brand",
                        href: canViewSuppliers ? "/suppliers?status=1" : null,
                    },
                    {
                        label: "Archived",
                        value: archivedSuppliers,
                        tone: "gray",
                        href: canViewSupplierArchive ? "/suppliers/archive" : null,
                    },
                ],
            },
        },
        {
            title: "Total Users",
            icon: Users,
            bgColor: "bg-[#005740]",
            value: users,
            href: canViewUsers ? "/user-management" : null,
            support: {
                badge: `${percent(spmoUsers, users)}% SPMO`,
                segments: [
                    {
                        label: "SPMO users",
                        value: spmoUsers,
                        tone: "brand",
                        href: canViewUsers
                            ? "/user-management?search=" +
                              encodeURIComponent(
                                  "UPC SUPPLY AND PROPERTY MANAGEMENT OFFICE",
                              )
                            : null,
                    },
                    {
                        label: "Users",
                        value: otherUsers,
                        tone: "gray",
                        href: canViewUsers ? "/user-management" : null,
                    },
                ],
            },
        },
    ];
});
</script>

<template>
    <div class="max-w-[1600px] mx-auto flex flex-col gap-6 sm:gap-8">
        <PageHeader v-if="showHeader" title="Dashboard" />

        <!-- Headline counts first, so the page opens with where the inventory
             stands before the things that need a decision. -->
        <section class="flex flex-col gap-3 sm:gap-4">
            <h2
                class="flex items-center gap-3 text-[11px] font-semibold uppercase tracking-[0.14em] text-gray-400"
            >
                <span>Inventory at a glance</span>
                <span class="h-px flex-1 bg-gray-200"></span>
            </h2>

            <!-- Summary cards: full width on phones so the labels stay on one
                 line, three per row from 640px. -->
            <div class="grid grid-cols-1 gap-3 sm:grid-cols-3 sm:gap-4">
                <ItemOverview :item-overview="itemOverview" />
            </div>
        </section>

        <section class="flex flex-col gap-3 sm:gap-4">
            <h2
                class="flex items-center gap-3 text-[11px] font-semibold uppercase tracking-[0.14em] text-gray-400"
            >
                <span>Needs attention</span>
                <span class="h-px flex-1 bg-gray-200"></span>
            </h2>

            <!-- Numbers worth acting on, built from the modules that record
                 them. Each card opens the filtered list behind it. -->
            <DashboardHighlights
                :highlights="highlights"
                :linkable="showHeader"
            />
        </section>

        <section class="flex flex-col gap-4 sm:gap-6">
            <h2
                class="flex items-center gap-3 text-[11px] font-semibold uppercase tracking-[0.14em] text-gray-400"
            >
                <span>Breakdowns</span>
                <span class="h-px flex-1 bg-gray-200"></span>
            </h2>

            <!-- Two compact breakdowns side by side, stacked on phones. -->
            <div class="grid grid-cols-1 gap-4 sm:gap-6 lg:grid-cols-2">
                <div class="min-h-[340px]">
                    <IcsParPercentageChart
                        class="w-full h-full"
                        :ics-par-chart-data="icsParChartData"
                    />
                </div>
                <div class="min-h-[340px]">
                    <AccountablePersonChart
                        class="w-full h-full"
                        :accountable-person-chart-data="accountablePersonChartData"
                    />
                </div>
            </div>

            <!-- Full width on purpose: the classification bars carry long
                 labels, and the card grows so every classification stays
                 visible. -->
            <ItemsByClassificationChart
                class="w-full"
                :classification-chart-data="classificationChartData"
            />

            <div class="min-h-[420px] sm:min-h-[460px]">
                <AcquisitionsByClassificationChart
                    class="w-full h-full"
                    :acquisitions-by-classification="acquisitionsByClassification"
                    :available-years="availableYears"
                    :selected-year="selectedYear"
                    :embed-token="embedToken"
                />
            </div>
        </section>
    </div>
</template>
