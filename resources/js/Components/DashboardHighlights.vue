<script setup>
import { computed } from "vue";
import { Link } from "@inertiajs/vue3";
import {
    ChevronRight,
    Clock3,
    HandHelping,
    PackageX,
    TriangleAlert,
    Trash2,
} from "lucide-vue-next";

const props = defineProps({
    highlights: { type: Object, default: () => ({}) },
    // The embed version stays read-only: no links inside an iframe.
    linkable: { type: Boolean, default: true },
});

const count = (value) => Number(value ?? 0).toLocaleString("en-PH");

const TONES = {
    brand: {
        ring: "border-[#005740]/20 bg-[#005740]/[0.05]",
        chip: "bg-[#005740] text-white",
        value: "text-[#005740]",
        dot: "bg-[#005740]",
    },
    warning: {
        ring: "border-amber-200 bg-amber-50/70",
        chip: "bg-amber-500 text-white",
        value: "text-amber-700",
        dot: "bg-amber-500",
    },
    quiet: {
        ring: "border-gray-200 bg-white",
        chip: "bg-gray-300 text-white",
        value: "text-gray-700",
        dot: "bg-gray-300",
    },
};

const cards = computed(() => {
    const h = props.highlights ?? {};

    const unassigned = Number(h.unassigned_items ?? 0);
    const disposed = Number(h.disposal_items ?? 0);
    const forDisposal = Number(h.for_disposal_items ?? 0);
    const pending = Number(h.pending_approvals ?? 0);
    const borrowed = Number(h.borrowed_items ?? 0);

    return [
        {
            key: "unassigned_items",
            label: "No Accountable Person",
            value: count(unassigned),
            hint: "Items that still need an assignment",
            icon: PackageX,
            tone: unassigned > 0 ? "warning" : "quiet",
            href: "/inventory/items?acknowledgement_status=without_acknowledgement",
        },
        {
            key: "pending_approvals",
            label: "Pending Approvals",
            value: count(pending),
            hint: "Acknowledgement lines waiting for approval",
            icon: Clock3,
            tone: pending > 0 ? "warning" : "quiet",
            href: "/item-approvals?acknowledgement_status=pending",
        },
        // {
        //     key: "borrowed_items",
        //     label: "Out on Loan",
        //     value: count(borrowed),
        //     hint: "Loans that are still open",
        //     icon: HandHelping,
        //     tone: borrowed > 0 ? "brand" : "quiet",
        //     href: "/borrowings?acknowledgement_status=borrowed",
        // },
        {
            key: "for_disposal_items",
            label: "Marked for Disposal",
            value: count(forDisposal),
            hint: "Inspected as for disposal, not disposed yet",
            icon: TriangleAlert,
            tone: forDisposal > 0 ? "warning" : "quiet",
            href: null,
        },
        {
            key: "disposal_items",
            label: "Disposed Assets",
            value: count(disposed),
            hint: "Disposed and out of the active inventory",
            icon: Trash2,
            tone: disposed > 0 ? "brand" : "quiet",
            href: "/disposal",
        },
    ];
});
</script>

<template>
    <div
        class="grid auto-rows-fr grid-cols-1 gap-3 sm:grid-cols-2 sm:gap-4 lg:grid-cols-3"
    >
        <component
            :is="card.href && linkable ? Link : 'div'"
            v-for="card in cards"
            :key="card.key"
            v-bind="card.href && linkable ? { href: card.href } : {}"
            class="group flex h-full items-start gap-3 rounded-2xl border p-4 transition-all focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[#005740]/40"
            :class="[
                TONES[card.tone].ring,
                card.href && linkable
                    ? 'hover:-translate-y-0.5 hover:shadow-md'
                    : '',
            ]"
        >
            <span
                class="flex h-9 w-9 flex-shrink-0 items-center justify-center rounded-xl"
                :class="TONES[card.tone].chip"
            >
                <component :is="card.icon" class="h-4 w-4" />
            </span>

            <span class="flex min-w-0 flex-1 flex-col">
                <span class="flex items-center gap-1.5">
                    <span
                        class="h-1.5 w-1.5 flex-shrink-0 rounded-full"
                        :class="TONES[card.tone].dot"
                    ></span>
                    <span
                        class="text-[11px] font-semibold uppercase leading-tight tracking-wide text-gray-500"
                    >
                        {{ card.label }}
                    </span>
                </span>

                <span
                    class="mt-1.5 block text-2xl font-bold leading-none tracking-tight tabular-nums"
                    :class="TONES[card.tone].value"
                >
                    {{ card.value }}
                </span>

                <span class="mt-1.5 block text-xs leading-snug text-gray-500">
                    {{ card.hint }}
                </span>
            </span>

            <ChevronRight
                v-if="card.href && linkable"
                class="mt-1 h-4 w-4 flex-shrink-0 text-gray-300 transition-transform group-hover:translate-x-0.5 group-hover:text-[#005740]"
            />
        </component>
    </div>
</template>
