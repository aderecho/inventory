<script setup>
import { computed, reactive, ref } from "vue";
import { Head, router } from "@inertiajs/vue3";
import Layout from "@/Layouts/Layout.vue";
import PageHeader from "@/Components/PageHeader.vue";
import {
    ChevronDown,
    ChevronUp,
    Filter,
    Search,
    X,
} from "lucide-vue-next";

const props = defineProps({
    activities: {
        type: Object,
        default: () => ({ data: [], links: [] }),
    },
    filters: { type: Object, default: () => ({}) },
    logNames: { type: Array, default: () => [] },
    events: { type: Array, default: () => [] },
    users: { type: Array, default: () => [] },
});

const filters = reactive({
    search: props.filters.search ?? "",
    log_name: props.filters.log_name ?? "",
    event: props.filters.event ?? "",
    user_id: props.filters.user_id ?? "",
    date_from: props.filters.date_from ?? "",
    date_to: props.filters.date_to ?? "",
});

const expandedId = ref(null);

const hasFilters = computed(() =>
    Object.values(filters).some((value) => value !== "" && value != null),
);

const moduleLabels = {
    acknowledgement: "Assignments",
    api_client: "API Clients",
    asset_condition: "Asset Conditions",
    authentication: "Authentication",
    borrowing: "Borrowings",
    borrowing_item: "Borrowed Items",
    default: "General",
    inspection: "Inspections",
    inventory: "Inventory",
    item_classification: "Categories",
    location: "Locations",
    permission: "Permissions",
    receipt: "Receipts",
    role: "Roles",
    saml_configuration: "SAML Configuration",
    supplier: "Suppliers",
    user_permission: "User Permissions",
    user_profile: "User Profiles",
    users: "Users",
};

function applyFilters() {
    const payload = Object.fromEntries(
        Object.entries(filters).filter(([, value]) => value !== "" && value != null),
    );

    router.get(route("audit_logs.index"), payload, {
        preserveScroll: true,
        preserveState: true,
        replace: true,
    });
}

function clearFilters() {
    Object.keys(filters).forEach((key) => (filters[key] = ""));
    applyFilters();
}

function toggleDetails(id) {
    expandedId.value = expandedId.value === id ? null : id;
}

function formatDate(date) {
    return new Date(date).toLocaleString(undefined, {
        dateStyle: "medium",
        timeStyle: "short",
    });
}

function formatValue(value) {
    if (value === null || value === undefined || value === "") return "—";
    if (typeof value === "boolean") return value ? "Yes" : "No";
    if (typeof value === "object") return JSON.stringify(value);

    return String(value);
}

function getUserName(activity) {
    return (
        activity.causer?.user_profiles?.full_name ||
        activity.causer?.userProfiles?.full_name ||
        activity.causer?.email ||
        "System"
    );
}

function getModelName(subjectType) {
    if (!subjectType) return "—";

    return subjectType.split("\\").pop();
}

function getRecordLabel(activity) {
    if (!activity.subject_type) return "—";

    const model = getModelName(activity.subject_type);

    return activity.subject_id ? `${model} #${activity.subject_id}` : model;
}

function humanize(value) {
    return String(value ?? "")
        .replace(/[_-]+/g, " ")
        .replace(/\b\w/g, (letter) => letter.toUpperCase());
}

function moduleLabel(name) {
    return moduleLabels[name] ?? humanize(name || "System");
}

function eventTone(event) {
    return {
        "bg-emerald-50 text-emerald-700": ["created", "restored"].includes(event),
        "bg-amber-50 text-amber-700": event === "updated",
        "bg-red-50 text-red-700": ["deleted", "archived", "force deleted"].includes(event),
        "bg-blue-50 text-blue-700": ["login", "logout"].includes(event),
        "bg-violet-50 text-violet-700": event === "permissions_updated",
        "bg-gray-100 text-gray-600": !["created", "restored", "updated", "deleted", "archived", "force deleted", "login", "logout", "permissions_updated"].includes(event),
    };
}

function changeRows(activity) {
    const properties = activity.properties ?? {};
    const attributes = properties.attributes ?? properties;
    const old = properties.old ?? {};

    return Object.keys(attributes)
        .filter((key) => !["attributes", "old", "created_at", "updated_at", "deleted_at"].includes(key))
        .map((key) => ({
            field: humanize(key),
            old: formatValue(old[key]),
            new: formatValue(attributes[key]),
        }));
}

function hasChanges(activity) {
    return changeRows(activity).length > 0;
}

function pageLabel(label) {
    return label
        .replace("&laquo;", "")
        .replace("&raquo;", "")
        .trim();
}

function visitPage(url) {
    if (!url) return;

    router.visit(url, { preserveScroll: true, preserveState: true });
}
</script>

<template>
    <Head title="UP | Audit Logs" />

    <Layout>
        <PageHeader title="Audit Logs" />

        <div class="mt-6 space-y-4">
            <section class="rounded-xl border border-gray-200 bg-white p-2.5 shadow-sm">
                <div class="flex flex-wrap items-center gap-2">
                    <div class="relative min-w-[13rem] flex-1">
                        <Search class="pointer-events-none absolute left-3.5 top-1/2 h-4 w-4 -translate-y-1/2 text-gray-400" />
                        <input
                            v-model="filters.search"
                            type="text"
                            placeholder="Search audit logs..."
                            class="h-9 w-full rounded-lg border border-gray-300 bg-white py-1.5 pl-10 pr-3 text-xs text-gray-700 focus:border-[#005740] focus:outline-none focus:ring-1 focus:ring-[#005740]"
                            @keyup.enter="applyFilters"
                        />
                    </div>

                    <div class="relative">
                        <select v-model="filters.log_name" aria-label="Module" class="audit-filter-select h-9 w-40 appearance-none rounded-lg border border-gray-300 bg-white pl-2 pr-8 text-xs text-gray-700 focus:border-[#005740] focus:outline-none">
                            <option value="">All modules</option>
                            <option v-for="name in logNames" :key="name" :value="name">
                                {{ moduleLabel(name) }}
                            </option>
                        </select>
                        <ChevronDown class="pointer-events-none absolute right-2 top-1/2 h-3.5 w-3.5 -translate-y-1/2 text-gray-400" />
                    </div>

                    <div class="relative">
                        <select v-model="filters.event" aria-label="Event" class="audit-filter-select h-9 w-36 appearance-none rounded-lg border border-gray-300 bg-white pl-2 pr-8 text-xs text-gray-700 focus:border-[#005740] focus:outline-none">
                            <option value="">All events</option>
                            <option v-for="event in events" :key="event" :value="event">
                                {{ humanize(event) }}
                            </option>
                        </select>
                        <ChevronDown class="pointer-events-none absolute right-2 top-1/2 h-3.5 w-3.5 -translate-y-1/2 text-gray-400" />
                    </div>

                    <div class="relative">
                        <select v-model="filters.user_id" aria-label="User" class="audit-filter-select h-9 w-44 appearance-none rounded-lg border border-gray-300 bg-white pl-2 pr-8 text-xs text-gray-700 focus:border-[#005740] focus:outline-none">
                            <option value="">All users</option>
                            <option v-for="user in users" :key="user.id" :value="user.id">
                                {{ user.name }}
                            </option>
                        </select>
                        <ChevronDown class="pointer-events-none absolute right-2 top-1/2 h-3.5 w-3.5 -translate-y-1/2 text-gray-400" />
                    </div>

                    <input v-model="filters.date_from" type="date" aria-label="From date" title="From date" class="h-9 w-[8.5rem] rounded-lg border border-gray-300 bg-white px-2 text-xs text-gray-700 focus:border-[#005740] focus:outline-none" />
                    <input v-model="filters.date_to" type="date" aria-label="To date" title="To date" class="h-9 w-[8.5rem] rounded-lg border border-gray-300 bg-white px-2 text-xs text-gray-700 focus:border-[#005740] focus:outline-none" />

                    <button type="button" class="inline-flex h-9 items-center justify-center gap-1.5 rounded-lg bg-[#0E6021] px-3 text-xs font-semibold text-white transition-colors hover:bg-[#19703a]" @click="applyFilters">
                        <Filter class="h-3.5 w-3.5" />
                        Apply
                    </button>
                    <button v-if="hasFilters" type="button" class="inline-flex h-9 items-center justify-center gap-1.5 rounded-lg border border-gray-300 px-3 text-xs font-medium text-gray-600 transition-colors hover:bg-gray-50" @click="clearFilters">
                        <X class="h-3.5 w-3.5" />
                        Clear
                    </button>
                </div>
            </section>

            <section class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
                <div class="flex flex-col gap-1 border-b border-gray-100 px-5 py-4 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <h2 class="text-base font-bold text-[#1b1b1b]">Activity trail</h2>
                        <p class="text-xs text-gray-500">
                            Showing {{ activities.from ?? 0 }}–{{ activities.to ?? 0 }} of {{ activities.total ?? 0 }} entries
                        </p>
                    </div>
                    <span class="rounded-full bg-[#005740]/10 px-3 py-1 text-xs font-semibold text-[#005740]">
                        {{ activities.total ?? 0 }} total
                    </span>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full min-w-[960px] text-left text-sm">
                        <thead class="bg-[#005740] text-[11px] uppercase tracking-wider text-white/90">
                            <tr>
                                <th class="px-4 py-3 font-semibold">Date</th>
                                <th class="px-4 py-3 font-semibold">User</th>
                                <th class="px-4 py-3 font-semibold">Module</th>
                                <th class="px-4 py-3 font-semibold">Event</th>
                                <th class="px-4 py-3 font-semibold">Activity</th>
                                <th class="px-4 py-3 font-semibold">Record</th>
                                <th class="w-12 px-4 py-3"></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 text-gray-700">
                            <template v-for="activity in activities.data" :key="activity.id">
                                <tr class="transition-colors hover:bg-[#005740]/[0.025]">
                                    <td class="whitespace-nowrap px-4 py-3 text-xs text-gray-500">
                                        {{ formatDate(activity.created_at) }}
                                    </td>
                                    <td class="px-4 py-3 text-xs font-medium text-gray-700">
                                        {{ getUserName(activity) }}
                                    </td>
                                    <td class="px-4 py-3">
                                        <span class="rounded-md bg-gray-100 px-2 py-1 text-[11px] font-semibold text-gray-600">
                                            {{ moduleLabel(activity.log_name) }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3">
                                        <span class="rounded-md px-2 py-1 text-[11px] font-semibold" :class="eventTone(activity.event)">
                                            {{ humanize(activity.event) }}
                                        </span>
                                    </td>
                                    <td class="max-w-xl px-4 py-3 text-sm text-gray-700">
                                        {{ activity.description }}
                                    </td>
                                    <td class="px-4 py-3 text-xs text-gray-500">
                                        {{ getRecordLabel(activity) }}
                                    </td>
                                    <td class="px-4 py-3 text-right">
                                        <button v-if="hasChanges(activity)" type="button" class="rounded-md p-1.5 text-gray-400 transition-colors hover:bg-gray-100 hover:text-[#005740]" :title="expandedId === activity.id ? 'Hide changes' : 'Show changes'" @click="toggleDetails(activity.id)">
                                            <ChevronUp v-if="expandedId === activity.id" class="h-4 w-4" />
                                            <ChevronDown v-else class="h-4 w-4" />
                                        </button>
                                    </td>
                                </tr>

                                <tr v-if="expandedId === activity.id" class="bg-gray-50">
                                    <td colspan="7" class="px-4 py-4">
                                        <p class="mb-3 text-[11px] font-semibold uppercase tracking-wide text-gray-500">
                                            Changed fields
                                        </p>
                                        <div class="grid gap-2 md:grid-cols-2 xl:grid-cols-3">
                                            <div v-for="change in changeRows(activity)" :key="change.field" class="rounded-lg border border-gray-200 bg-white px-3 py-2">
                                                <p class="text-[11px] font-semibold text-gray-500">{{ change.field }}</p>
                                                <p class="mt-1 break-words text-xs text-gray-700">
                                                    <span class="text-red-500 line-through">{{ change.old }}</span>
                                                    <span class="mx-1.5 text-gray-400">→</span>
                                                    <span class="font-medium text-emerald-700">{{ change.new }}</span>
                                                </p>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                            </template>

                            <tr v-if="!activities.data?.length">
                                <td colspan="7" class="px-4 py-12 text-center text-sm text-gray-500">
                                    No audit logs match the selected filters.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div class="flex flex-col gap-3 border-t border-gray-100 px-5 py-4 sm:flex-row sm:items-center sm:justify-between">
                    <p class="text-xs text-gray-500">
                        Showing {{ activities.from ?? 0 }}–{{ activities.to ?? 0 }} of {{ activities.total ?? 0 }} results
                    </p>
                    <div class="flex flex-wrap items-center gap-1">
                        <button
                            v-for="link in activities.links"
                            :key="link.label"
                            type="button"
                            :disabled="!link.url"
                            class="min-w-8 rounded-md px-2.5 py-1.5 text-xs font-medium transition-colors"
                            :class="link.active ? 'bg-[#005740] text-white' : 'text-gray-600 hover:bg-gray-100 disabled:cursor-not-allowed disabled:text-gray-300 disabled:hover:bg-transparent'"
                            @click="visitPage(link.url)"
                        >
                            {{ pageLabel(link.label) }}
                        </button>
                    </div>
                </div>
            </section>
        </div>
    </Layout>
</template>

<style scoped>
.audit-filter-select {
    appearance: none;
    -webkit-appearance: none;
    -moz-appearance: none;
    background-image: none;
}
</style>
