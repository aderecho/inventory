<script setup>
import { computed, ref, watch } from "vue";
import { Head, router, useForm } from "@inertiajs/vue3";
import Layout from "@/Layouts/Layout.vue";
import PageHeader from "@/Components/PageHeader.vue";
import DeleteModal from "@/Components/Modals/DeleteModal.vue";
import draggable from "vuedraggable";
import Toast from "primevue/toast";
import { useToast } from "primevue/usetoast";
import { Check, GripVertical, Pencil, ShieldCheck, Trash2, X } from "lucide-vue-next";
import {
    actionLabel,
    applyGroupOrder,
    groupPermissions,
    loadGroupOrder,
    saveGroupOrder,
} from "@/Composables/permissionGroups";

const props = defineProps({
    roles: { type: Array, default: () => [] },
    permissions: { type: Array, default: () => [] },
});

const toast = useToast();

const STORAGE_KEY = "roles.permission-groups";

const form = useForm({
    name: "",
    permissions: [],
});

const editingRole = ref(null);
const showDeleteModal = ref(false);
const itemToDelete = ref(null);
const query = ref("");

const groups = ref(
    applyGroupOrder(
        groupPermissions(props.permissions),
        loadGroupOrder(STORAGE_KEY),
    ),
);

// The order of the module tables is a per-browser preference.
watch(
    () => groups.value.map((group) => group.key),
    (order) => saveGroupOrder(STORAGE_KEY, order),
    { deep: true },
);

// Inertia reuses this component after a save, so rebuild the tables from the
// fresh props instead of keeping the first snapshot.
watch(
    () => props.permissions,
    (permissions) => {
        groups.value = applyGroupOrder(
            groupPermissions(permissions ?? []),
            loadGroupOrder(STORAGE_KEY),
        );
    },
);

const totalSelected = computed(() => form.permissions.length);
const totalAvailable = computed(() => props.permissions.length);

function startEdit(role) {
    editingRole.value = role;
    form.name = role.name;
    form.permissions = (role.permissions ?? []).map(
        (permission) => permission.name,
    );
    form.clearErrors();
}

function cancelEdit() {
    editingRole.value = null;
    form.reset();
    form.clearErrors();
}

function submitRole() {
    const options = {
        preserveScroll: true,
        onSuccess: () => {
            toast.add({
                severity: "success",
                summary: editingRole.value ? "Role updated" : "Role created",
                detail: form.name,
                life: 3000,
            });

            if (editingRole.value) {
                cancelEdit();
            } else {
                form.reset();
            }
        },
    };

    if (editingRole.value) {
        form.put(route("roles.update", editingRole.value.id), options);
    } else {
        form.post(route("roles.store"), options);
    }
}

function deleteRole(role) {
    itemToDelete.value = role;
    showDeleteModal.value = true;
}

function confirmDelete() {
    router.delete(route("roles.destroy", itemToDelete.value.id), {
        preserveScroll: true,
        onSuccess: () => {
            toast.add({
                severity: "success",
                summary: "Role deleted",
                detail: itemToDelete.value?.name,
                life: 3000,
            });

            if (editingRole.value?.id === itemToDelete.value?.id) {
                cancelEdit();
            }

            showDeleteModal.value = false;
            itemToDelete.value = null;
        },
    });
}

function togglePermission(name) {
    const index = form.permissions.indexOf(name);

    if (index === -1) {
        form.permissions.push(name);
    } else {
        form.permissions.splice(index, 1);
    }
}

function selectedIn(list) {
    return list.filter((permission) =>
        form.permissions.includes(permission.name),
    ).length;
}

function isModuleFullySelected(list) {
    return (
        list.length > 0 &&
        list.every((permission) => form.permissions.includes(permission.name))
    );
}

function isModulePartiallySelected(list) {
    const selected = selectedIn(list);

    return selected > 0 && selected < list.length;
}

function toggleModule(list) {
    if (isModuleFullySelected(list)) {
        const names = new Set(list.map((permission) => permission.name));

        form.permissions = form.permissions.filter((name) => !names.has(name));

        return;
    }

    const missing = list
        .map((permission) => permission.name)
        .filter((name) => !form.permissions.includes(name));

    form.permissions.push(...missing);
}

// Searching narrows the rows inside each module; the modules stay draggable.
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
    <Head title="UP | Roles & Permissions" />

    <Layout>
        <Toast />

        <PageHeader title="Roles & Permissions" />

        <div class="w-full h-full space-y-5">
            <!-- Existing roles -->
            <div
                class="mt-6 overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm"
            >
                <div
                    class="flex flex-wrap items-center justify-between gap-2 border-b border-gray-100 px-4 py-3"
                >
                    <div class="flex items-center gap-2">
                        <span
                            class="flex h-8 w-8 items-center justify-center rounded-lg bg-[#005740]/10 text-[#005740]"
                        >
                            <ShieldCheck class="h-4 w-4" />
                        </span>
                        <div>
                            <p class="text-sm font-semibold text-[#1b1b1b]">
                                Existing roles
                            </p>
                            <p class="text-[11px] text-gray-500">
                                {{ roles.length }} role(s) · pick one to edit
                                its permissions
                            </p>
                        </div>
                    </div>

                    <button
                        v-if="editingRole"
                        type="button"
                        class="flex items-center gap-1.5 rounded-lg border border-gray-200 px-3 py-1.5 text-xs font-medium text-gray-600 transition-colors hover:bg-gray-50"
                        @click="cancelEdit"
                    >
                        <X class="h-3.5 w-3.5" />
                        Stop editing
                    </button>
                </div>

                <table class="w-full text-left text-sm">
                    <thead class="bg-gray-50 text-[11px] uppercase tracking-wide text-gray-500">
                        <tr>
                            <th class="px-4 py-2 font-semibold">Role</th>
                            <th class="px-4 py-2 font-semibold">Permissions</th>
                            <th class="w-32 px-4 py-2 text-right font-semibold">
                                Actions
                            </th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        <tr
                            v-for="role in roles"
                            :key="role.id"
                            class="transition-colors"
                            :class="
                                editingRole?.id === role.id
                                    ? 'bg-[#005740]/5'
                                    : 'hover:bg-gray-50'
                            "
                        >
                            <td class="px-4 py-2.5">
                                <span class="font-semibold text-[#1f2d27]">
                                    {{ role.name }}
                                </span>
                                <span
                                    v-if="editingRole?.id === role.id"
                                    class="ml-2 rounded-full bg-[#005740] px-2 py-0.5 text-[10px] font-bold uppercase text-white"
                                >
                                    Editing
                                </span>
                            </td>
                            <td class="px-4 py-2.5 text-gray-500">
                                {{ role.permissions?.length ?? 0 }} permission(s)
                            </td>
                            <td class="px-4 py-2.5">
                                <div class="flex items-center justify-end gap-1">
                                    <button
                                        type="button"
                                        class="flex h-8 w-8 items-center justify-center rounded-md text-gray-400 transition-colors hover:bg-[#005740]/10 hover:text-[#005740]"
                                        title="Edit role"
                                        @click="startEdit(role)"
                                    >
                                        <Pencil class="h-3.5 w-3.5" />
                                    </button>
                                    <button
                                        type="button"
                                        class="flex h-8 w-8 items-center justify-center rounded-md text-gray-400 transition-colors hover:bg-red-50 hover:text-red-600"
                                        title="Delete role"
                                        @click="deleteRole(role)"
                                    >
                                        <Trash2 class="h-3.5 w-3.5" />
                                    </button>
                                </div>
                            </td>
                        </tr>

                        <tr v-if="!roles.length">
                            <td colspan="3" class="px-4 py-10 text-center text-sm text-gray-400">
                                No roles yet — create one below.
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Role form + permission tables -->
            <div class="rounded-xl border border-gray-200 bg-white shadow-sm">
                <div class="border-b border-gray-100 px-4 py-3">
                    <form
                        class="flex flex-col gap-2 sm:flex-row sm:items-center"
                        @submit.prevent="submitRole"
                    >
                        <div class="min-w-0 flex-1">
                            <label
                                class="mb-1 block text-[11px] font-semibold uppercase tracking-wide text-gray-500"
                            >
                                {{ editingRole ? "Editing role" : "New role" }}
                            </label>
                            <input
                                v-model="form.name"
                                type="text"
                                placeholder="e.g. moderator"
                                class="h-10 w-full rounded-lg border bg-white px-3 text-sm text-[#3B3B3B] focus:outline-none focus:ring-1"
                                :class="
                                    form.errors.name
                                        ? 'border-red-300 focus:border-red-400 focus:ring-red-200'
                                        : 'border-gray-300 focus:border-[#005740] focus:ring-[#005740]'
                                "
                            />
                        </div>

                        <div class="flex items-center gap-2 sm:pt-5">
                            <span
                                class="whitespace-nowrap rounded-full bg-[#005740]/10 px-2.5 py-1 text-[11px] font-semibold text-[#005740]"
                            >
                                {{ totalSelected }} / {{ totalAvailable }} selected
                            </span>

                            <button
                                type="submit"
                                :disabled="form.processing"
                                class="flex h-10 items-center gap-2 rounded-lg bg-[#0E6021] px-4 text-sm font-semibold text-white transition-colors hover:bg-[#19703a] disabled:opacity-50"
                            >
                                <Check class="h-4 w-4" />
                                {{ editingRole ? "Update role" : "Create role" }}
                            </button>

                            <button
                                v-if="editingRole"
                                type="button"
                                class="h-10 rounded-lg border border-gray-200 px-3 text-sm text-gray-600 transition-colors hover:bg-gray-50"
                                @click="cancelEdit"
                            >
                                Cancel
                            </button>
                        </div>
                    </form>

                    <p v-if="form.errors.name" class="mt-1 text-xs text-red-600">
                        {{ form.errors.name }}
                    </p>
                </div>

                <div class="flex flex-wrap items-center justify-between gap-2 px-4 py-3">
                    <label
                        class="text-[11px] font-semibold uppercase tracking-wide text-gray-500"
                    >
                        Permissions by module
                    </label>

                    <input
                        v-model="query"
                        type="search"
                        placeholder="Filter permissions…"
                        class="h-9 w-full rounded-lg border border-gray-300 bg-white px-3 text-xs text-[#3B3B3B] focus:border-[#005740] focus:outline-none focus:ring-1 focus:ring-[#005740] sm:w-64"
                    />
                </div>

                <div class="px-4 pb-4">
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
                                    class="flex flex-wrap items-center gap-2 border-b border-gray-100 bg-gray-50/70 px-3 py-2"
                                >
                                    <button
                                        type="button"
                                        class="group-handle cursor-grab rounded-md p-1 text-gray-400 transition-colors hover:bg-white hover:text-[#005740] active:cursor-grabbing"
                                        title="Drag to reorder this module"
                                    >
                                        <GripVertical class="h-4 w-4" />
                                    </button>

                                    <label
                                        class="flex cursor-pointer items-center gap-2 text-sm font-semibold text-[#1f2d27]"
                                    >
                                        <input
                                            type="checkbox"
                                            class="h-4 w-4 rounded border-gray-300 text-[#005740] focus:ring-[#005740]"
                                            :checked="isModuleFullySelected(group.permissions)"
                                            :indeterminate.prop="isModulePartiallySelected(group.permissions)"
                                            @change="toggleModule(group.permissions)"
                                        />
                                        {{ group.label }}
                                    </label>

                                    <span
                                        class="ml-auto rounded-full bg-[#005740]/10 px-2 py-0.5 text-[11px] font-semibold text-[#005740]"
                                    >
                                        {{ selectedIn(group.permissions) }}/{{
                                            group.permissions.length
                                        }}
                                    </span>
                                </header>

                                <table class="w-full table-fixed text-left text-sm">
                                    <colgroup>
                                        <col style="width: 4.5rem" />
                                        <col />
                                        <col style="width: 8rem" />
                                    </colgroup>
                                    <thead
                                        class="text-[11px] uppercase tracking-wide text-gray-400"
                                    >
                                        <tr>
                                            <th class="px-3 py-1.5 font-semibold">
                                                Include
                                            </th>
                                            <th class="px-3 py-1.5 font-semibold">
                                                Permission
                                            </th>
                                            <th class="px-3 py-1.5 font-semibold">
                                                Action
                                            </th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-gray-100">
                                        <tr
                                            v-for="permission in visiblePermissions(
                                                group.permissions,
                                            )"
                                            :key="permission.id"
                                            class="cursor-pointer transition-colors hover:bg-gray-50"
                                            @click="togglePermission(permission.name)"
                                        >
                                            <td class="px-3 py-1.5">
                                                <input
                                                    type="checkbox"
                                                    class="h-4 w-4 rounded border-gray-300 text-[#005740] focus:ring-[#005740]"
                                                    :checked="
                                                        form.permissions.includes(
                                                            permission.name,
                                                        )
                                                    "
                                                    @click.stop
                                                    @change="
                                                        togglePermission(
                                                            permission.name,
                                                        )
                                                    "
                                                />
                                            </td>
                                            <td
                                                class="truncate px-3 py-1.5 text-gray-700"
                                            >
                                                {{ permission.name }}
                                            </td>
                                            <td
                                                class="px-3 py-1.5 text-xs text-gray-500"
                                            >
                                                {{ actionLabel(permission.name) }}
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

