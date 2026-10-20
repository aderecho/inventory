<script setup>
import { computed, ref, watch } from "vue";
import { Head, router, useForm } from "@inertiajs/vue3";
import Layout from "@/Layouts/Layout.vue";
import PageHeader from "@/Components/PageHeader.vue";
import DeleteModal from "@/Components/Modals/DeleteModal.vue";
import draggable from "vuedraggable";
import Toast from "primevue/toast";
import { useToast } from "primevue/usetoast";
import { Check, GripVertical, KeyRound, Pencil, Trash2, X } from "lucide-vue-next";
import {
    actionLabel,
    applyGroupOrder,
    groupPermissions,
    loadGroupOrder,
    saveGroupOrder,
} from "@/Composables/permissionGroups";

const props = defineProps({
    permissions: { type: Array, default: () => [] },
});

const toast = useToast();

const STORAGE_KEY = "permissions.permission-groups";

const form = useForm({ name: "" });
const editingPermission = ref(null);
const showDeleteModal = ref(false);
const itemToDelete = ref(null);
const query = ref("");

const groups = ref(
    applyGroupOrder(
        groupPermissions(props.permissions),
        loadGroupOrder(STORAGE_KEY),
    ),
);

watch(
    () => groups.value.map((group) => group.key),
    (order) => saveGroupOrder(STORAGE_KEY, order),
    { deep: true },
);

// Inertia reuses this component after create/delete, so the groups have to be
// rebuilt from the new props instead of staying on the first snapshot.
watch(
    () => props.permissions,
    (permissions) => {
        groups.value = applyGroupOrder(
            groupPermissions(permissions ?? []),
            loadGroupOrder(STORAGE_KEY),
        );
    },
);

function startEdit(permission) {
    editingPermission.value = permission;
    form.name = permission.name;
    form.clearErrors();
}

function cancelEdit() {
    editingPermission.value = null;
    form.reset();
    form.clearErrors();
}

function submitPermission() {
    const options = {
        preserveScroll: true,
        onSuccess: () => {
            toast.add({
                severity: "success",
                summary: editingPermission.value
                    ? "Permission updated"
                    : "Permission created",
                detail: form.name,
                life: 3000,
            });

            if (editingPermission.value) {
                cancelEdit();
            } else {
                form.reset();
            }
        },
    };

    if (editingPermission.value) {
        form.put(
            route("permissions.update", editingPermission.value.id),
            options,
        );
    } else {
        form.post(route("permissions.store"), options);
    }
}

function deletePermission(permission) {
    itemToDelete.value = permission;
    showDeleteModal.value = true;
}

function confirmDelete() {
    router.delete(route("permissions.destroy", itemToDelete.value.id), {
        preserveScroll: true,
        onSuccess: () => {
            toast.add({
                severity: "success",
                summary: "Permission deleted",
                detail: itemToDelete.value?.name,
                life: 3000,
            });

            if (editingPermission.value?.id === itemToDelete.value?.id) {
                cancelEdit();
            }

            showDeleteModal.value = false;
            itemToDelete.value = null;
        },
    });
}

function visiblePermissions(list) {
    const term = query.value.trim().toLowerCase();

    if (!term) {
        return list;
    }

    return list.filter((permission) =>
        permission.name.toLowerCase().includes(term),
    );
}

const visibleGroups = computed(() =>
    groups.value.filter((group) => visiblePermissions(group.permissions).length),
);
</script>

<template>
    <Head title="UP | Permissions" />

    <Layout>
        <Toast />

        <PageHeader title="Permissions" />

        <div class="w-full h-full space-y-5">
            <div
                class="mt-6 rounded-xl border border-gray-200 bg-white shadow-sm"
            >
                <div
                    class="flex flex-wrap items-center justify-between gap-3 border-b border-gray-100 px-4 py-3"
                >
                    <div class="flex items-center gap-2">
                        <span
                            class="flex h-8 w-8 items-center justify-center rounded-lg bg-[#005740]/10 text-[#005740]"
                        >
                            <KeyRound class="h-4 w-4" />
                        </span>
                        <div>
                            <p class="text-sm font-semibold text-[#1b1b1b]">
                                Permission catalogue
                            </p>
                            <p class="text-[11px] text-gray-500">
                                {{ permissions.length }} permission(s) · grouped
                                by module
                            </p>
                        </div>
                    </div>

                    <input
                        v-model="query"
                        type="search"
                        placeholder="Filter permissions…"
                        class="h-9 w-full rounded-lg border border-gray-300 bg-white px-3 text-xs text-[#3B3B3B] focus:border-[#005740] focus:outline-none focus:ring-1 focus:ring-[#005740] sm:w-64"
                    />
                </div>

                <form
                    class="flex flex-col gap-2 border-b border-gray-100 px-4 py-3 sm:flex-row sm:items-center"
                    @submit.prevent="submitPermission"
                >
                    <div class="min-w-0 flex-1">
                        <label
                            class="mb-1 block text-[11px] font-semibold uppercase tracking-wide text-gray-500"
                        >
                            {{
                                editingPermission
                                    ? "Editing permission"
                                    : "New permission"
                            }}
                        </label>
                        <input
                            v-model="form.name"
                            type="text"
                            placeholder="e.g. view property cards"
                            class="h-10 w-full rounded-lg border bg-white px-3 text-sm text-[#3B3B3B] focus:outline-none focus:ring-1"
                            :class="
                                form.errors.name
                                    ? 'border-red-300 focus:border-red-400 focus:ring-red-200'
                                    : 'border-gray-300 focus:border-[#005740] focus:ring-[#005740]'
                            "
                        />
                    </div>

                    <div class="flex items-center gap-2 sm:pt-5">
                        <button
                            type="submit"
                            :disabled="form.processing"
                            class="flex h-10 items-center gap-2 rounded-lg bg-[#0E6021] px-4 text-sm font-semibold text-white transition-colors hover:bg-[#19703a] disabled:opacity-50"
                        >
                            <Check class="h-4 w-4" />
                            {{
                                editingPermission
                                    ? "Update permission"
                                    : "Create permission"
                            }}
                        </button>

                        <button
                            v-if="editingPermission"
                            type="button"
                            class="flex h-10 items-center gap-1.5 rounded-lg border border-gray-200 px-3 text-sm text-gray-600 transition-colors hover:bg-gray-50"
                            @click="cancelEdit"
                        >
                            <X class="h-4 w-4" />
                            Cancel
                        </button>
                    </div>
                </form>

                <p v-if="form.errors.name" class="px-4 pt-2 text-xs text-red-600">
                    {{ form.errors.name }}
                </p>

                <div class="px-4 py-4">
                    <draggable
                        v-model="groups"
                        item-key="key"
                        handle=".group-handle"
                        tag="div"
                        class="space-y-3"
                    >
                        <template #item="{ element: group }">
                            <section
                                v-show="visiblePermissions(group.permissions).length"
                                class="overflow-hidden rounded-xl border border-gray-200"
                            >
                                <header
                                    class="flex items-center gap-2 border-b border-gray-100 bg-gray-50/70 px-3 py-2"
                                >
                                    <button
                                        type="button"
                                        class="group-handle cursor-grab rounded-md p-1 text-gray-400 transition-colors hover:bg-white hover:text-[#005740] active:cursor-grabbing"
                                        title="Drag to reorder this module"
                                    >
                                        <GripVertical class="h-4 w-4" />
                                    </button>

                                    <p class="text-sm font-semibold text-[#1f2d27]">
                                        {{ group.label }}
                                    </p>

                                    <span
                                        class="ml-auto rounded-full bg-[#005740]/10 px-2 py-0.5 text-[11px] font-semibold text-[#005740]"
                                    >
                                        {{ visiblePermissions(group.permissions).length }}
                                    </span>
                                </header>

                                <table class="w-full table-fixed text-left text-sm">
                                    <colgroup>
                                        <col />
                                        <col style="width: 8rem" />
                                        <col style="width: 7rem" />
                                    </colgroup>
                                    <thead
                                        class="text-[11px] uppercase tracking-wide text-gray-400"
                                    >
                                        <tr>
                                            <th class="px-3 py-1.5 font-semibold">
                                                Permission
                                            </th>
                                            <th class="px-3 py-1.5 font-semibold">
                                                Action
                                            </th>
                                            <th class="px-3 py-1.5 text-right font-semibold">
                                                Actions
                                            </th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-gray-100">
                                        <tr
                                            v-for="permission in visiblePermissions(
                                                group.permissions,
                                            )"
                                            :key="permission.id"
                                            class="transition-colors hover:bg-gray-50"
                                            :class="
                                                editingPermission?.id ===
                                                permission.id
                                                    ? 'bg-[#005740]/5'
                                                    : ''
                                            "
                                        >
                                            <td class="truncate px-3 py-2 text-gray-700">
                                                {{ permission.name }}
                                            </td>
                                            <td class="px-3 py-2 text-xs text-gray-500">
                                                {{ actionLabel(permission.name) }}
                                            </td>
                                            <td class="px-3 py-2">
                                                <div
                                                    class="flex items-center justify-end gap-1"
                                                >
                                                    <button
                                                        type="button"
                                                        class="flex h-8 w-8 items-center justify-center rounded-md text-gray-400 transition-colors hover:bg-[#005740]/10 hover:text-[#005740]"
                                                        title="Edit permission"
                                                        @click="startEdit(permission)"
                                                    >
                                                        <Pencil class="h-3.5 w-3.5" />
                                                    </button>
                                                    <button
                                                        type="button"
                                                        class="flex h-8 w-8 items-center justify-center rounded-md text-gray-400 transition-colors hover:bg-red-50 hover:text-red-600"
                                                        title="Delete permission"
                                                        @click="deletePermission(permission)"
                                                    >
                                                        <Trash2 class="h-3.5 w-3.5" />
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </section>
                        </template>
                    </draggable>

                    <p
                        v-if="!visibleGroups.length"
                        class="rounded-lg border border-dashed border-gray-200 px-4 py-8 text-center text-sm text-gray-400"
                    >
                        No permission matches “{{ query }}”.
                    </p>
                </div>
            </div>
        </div>

        <DeleteModal
            v-if="showDeleteModal"
            :item="itemToDelete"
            @confirm="confirmDelete"
            @close="showDeleteModal = false"
        />
    </Layout>
</template>

