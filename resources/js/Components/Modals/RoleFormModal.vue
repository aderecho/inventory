<script setup>
import { ref, computed } from 'vue';
import { useForm, router } from '@inertiajs/vue3';
import Toast from 'primevue/toast';
import { useToast } from 'primevue/usetoast';
import DeleteModal from '@/Components/Modals/DeleteModal.vue';

const props = defineProps({
    roles: { type: Array, default: () => [] },
    permissions: { type: Array, default: () => [] },
});

const emit = defineEmits(['close']);
const toast = useToast();

const isClosing = ref(false);
const editingRole = ref(null);

const showDeleteModal = ref(false);
const itemToDelete = ref(null);

const form = useForm({
    name: '',
    permissions: [],
});

const moduleLabels = {
    inventory: 'Inventory',
    suppliers: 'Suppliers',
    categories: 'Categories',
    acknowledgements: 'Acknowledgements',
    users: 'Users',
    roles: 'Roles',
    archive_item: 'Item Archive',
    archive_supplier: 'Supplier Archive',
    'item histories': 'Item Histories',
};

function getModuleKey(permName) {
    const parts = permName.split(' ');
    if (parts.length === 3) {
        return parts.slice(1).join(' ');
    }
    return parts[1] ?? 'other';
}

const groupedPermissions = computed(() => {
    const groups = {};
    for (const perm of props.permissions) {
        const key = getModuleKey(perm.name);
        if (!groups[key]) groups[key] = [];
        groups[key].push(perm);
    }
    return groups;
});

const totalSelected = computed(() => form.permissions.length);
const totalAvailable = computed(() => props.permissions.length);

function closeWithAnimation() {
    isClosing.value = true;
    setTimeout(() => {
        emit('close');
        isClosing.value = false;
    }, 200);
}

function startEdit(role) {
    editingRole.value = role;
    form.name = role.name;
    form.permissions = role.permissions?.map(p => p.name) ?? [];
}

function cancelEdit() {
    editingRole.value = null;
    form.reset();
}

function submitRole() {
    if (editingRole.value) {
        form.put(route('roles.update', editingRole.value.id), {
            onSuccess: () => {
                toast.add({ severity: 'success', summary: 'Updated', detail: 'Role updated.', life: 3000 });
                cancelEdit();
            },
            onError: () => {
                toast.add({ severity: 'error', summary: 'Error', detail: 'Failed to update role.', life: 3000 });
            },
        });
    } else {
        form.post(route('roles.store'), {
            onSuccess: () => {
                toast.add({ severity: 'success', summary: 'Created', detail: 'Role created.', life: 3000 });
                form.reset();
            },
            onError: () => {
                toast.add({ severity: 'error', summary: 'Error', detail: 'Failed to create role.', life: 3000 });
            },
        });
    }
}

function deleteRole(role) {
    itemToDelete.value = role;
    showDeleteModal.value = true;
}

function confirmDelete() {
    router.delete(route('roles.destroy', itemToDelete.value.id), {
        preserveScroll: true,
        onSuccess: () => {
            toast.add({ severity: 'success', summary: 'Deleted', detail: 'Role deleted.', life: 3000 });
            if (editingRole.value?.id === itemToDelete.value.id) cancelEdit();
            showDeleteModal.value = false;
            itemToDelete.value = null;
        },
    });
}

function togglePermission(permName) {
    const idx = form.permissions.indexOf(permName);
    if (idx === -1) {
        form.permissions.push(permName);
    } else {
        form.permissions.splice(idx, 1);
    }
}

function isModuleFullySelected(modulePerms) {
    return modulePerms.every(p => form.permissions.includes(p.name));
}

function isModulePartiallySelected(modulePerms) {
    const selectedCount = modulePerms.filter(p => form.permissions.includes(p.name)).length;
    return selectedCount > 0 && selectedCount < modulePerms.length;
}

function toggleModule(modulePerms) {
    const allSelected = isModuleFullySelected(modulePerms);
    if (allSelected) {
        form.permissions = form.permissions.filter(
            name => !modulePerms.some(p => p.name === name)
        );
    } else {
        const namesToAdd = modulePerms
            .map(p => p.name)
            .filter(name => !form.permissions.includes(name));
        form.permissions.push(...namesToAdd);
    }
}
</script>

<template>
    <div class="fixed inset-0 bg-black/50 flex items-center justify-center p-4 z-50" @click="closeWithAnimation">
        <div :class="[
            'bg-white rounded-2xl w-full max-w-5xl shadow-2xl overflow-hidden flex flex-col max-h-[92vh]',
            isClosing ? 'animate-pop-out' : 'animate-pop-in'
        ]" @click.stop>
            <Toast />

            <!-- Header -->
            <div class="bg-gradient-to-r from-[#003d2c] via-[#005740] to-[#00795a] px-6 py-5 flex items-center justify-between flex-shrink-0">
                <div>
                    <h3 class="text-lg font-bold text-white">Roles &amp; Permissions</h3>
                    <p class="text-xs text-white/70 mt-0.5">Create roles and control what each one can access.</p>
                </div>
                <button
                    @click="closeWithAnimation"
                    class="text-white/80 hover:text-white hover:bg-white/10 rounded-full h-9 w-9 flex items-center justify-center transition-colors"
                    title="Close"
                >
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>

            <!-- Body: two columns -->
            <div class="flex flex-1 overflow-hidden">

                <!-- LEFT: Form + Permissions -->
                <div class="w-full md:w-[62%] p-6 overflow-y-auto border-r border-gray-100">

                    <form @submit.prevent="submitRole">
                        <!-- Role name -->
                        <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wide mb-1.5">
                            {{ editingRole ? "Edit Role" : "New Role" }}
                        </label>
                        <div class="flex gap-2 mb-1.5">
                            <input
                                v-model="form.name"
                                type="text"
                                placeholder="e.g. moderator"
                                :class="[
                                    'flex-1 rounded-lg px-3.5 py-2.5 text-sm bg-gray-50 border focus:ring-2 focus:outline-none transition-shadow',
                                    form.errors.name
                                        ? 'border-red-300 focus:ring-red-200'
                                        : 'border-gray-200 focus:ring-[#005740]/30 focus:border-[#005740]',
                                ]"
                            />
                            <button
                                type="submit"
                                class="bg-gradient-to-r from-[#005740] to-[#00795a] text-white px-5 py-2.5 rounded-lg text-sm font-semibold hover:shadow-md hover:from-[#00432f] hover:to-[#006548] transition-all whitespace-nowrap"
                            >
                                {{ editingRole ? "Update Role" : "Create Role" }}
                            </button>
                            <button
                                v-if="editingRole"
                                type="button"
                                @click="cancelEdit"
                                class="border border-gray-200 text-gray-500 px-4 py-2.5 rounded-lg text-sm hover:bg-gray-50 transition-colors"
                            >
                                Cancel
                            </button>
                        </div>
                        <p v-if="form.errors.name" class="text-red-500 text-xs mb-3">
                            {{ form.errors.name }}
                        </p>

                        <!-- Permissions -->
                        <div class="flex items-center justify-between mt-6 mb-2">
                            <label class="text-xs font-semibold text-gray-500 uppercase tracking-wide">
                                Permissions
                            </label>
                            <span class="text-xs font-medium text-[#005740] bg-[#005740]/10 px-2.5 py-1 rounded-full">
                                {{ totalSelected }} / {{ totalAvailable }} selected
                            </span>
                        </div>

                        <div class="space-y-2.5">
                            <div
                                v-for="(modulePerms, moduleKey) in groupedPermissions"
                                :key="moduleKey"
                                class="rounded-lg border border-gray-200 overflow-hidden"
                            >
                                <!-- Module header -->
                                <button
                                    type="button"
                                    @click="toggleModule(modulePerms)"
                                    class="w-full flex items-center justify-between px-3.5 py-2.5 bg-gray-50 hover:bg-gray-100 transition-colors"
                                >
                                    <span class="flex items-center gap-2.5">
                                        <span
                                            :class="[
                                                'h-4 w-4 rounded flex items-center justify-center border transition-colors',
                                                isModuleFullySelected(modulePerms)
                                                    ? 'bg-[#005740] border-[#005740]'
                                                    : isModulePartiallySelected(modulePerms)
                                                    ? 'bg-[#005740]/15 border-[#005740]/50'
                                                    : 'bg-white border-gray-300',
                                            ]"
                                        >
                                            <i
                                                v-if="isModuleFullySelected(modulePerms)"
                                                class="fa-solid fa-check text-white text-[10px]"
                                            ></i>
                                            <i
                                                v-else-if="isModulePartiallySelected(modulePerms)"
                                                class="fa-solid fa-minus text-[#005740] text-[9px]"
                                            ></i>
                                        </span>
                                        <span class="text-sm font-semibold text-[#1f2d27]">
                                            {{ moduleLabels[moduleKey] ?? moduleKey }}
                                        </span>
                                    </span>
                                    <span class="text-[11px] text-gray-400">
                                        {{ modulePerms.filter(p => form.permissions.includes(p.name)).length }}/{{ modulePerms.length }}
                                    </span>
                                </button>

                                <!-- Permission toggles -->
                                <div class="grid grid-cols-2 gap-1.5 px-3.5 py-3 bg-white">
                                    <button
                                        v-for="perm in modulePerms"
                                        :key="perm.id"
                                        type="button"
                                        @click="togglePermission(perm.name)"
                                        :class="[
                                            'flex items-center gap-2 text-left px-2.5 py-1.5 rounded-md text-xs border transition-colors duration-150',
                                            form.permissions.includes(perm.name)
                                                ? 'bg-[#005740]/10 border-[#005740]/40 text-[#005740] font-medium'
                                                : 'bg-white border-gray-200 text-gray-500 hover:border-gray-300',
                                        ]"
                                    >
                                        <span
                                            :class="[
                                                'h-3.5 w-3.5 rounded flex items-center justify-center border flex-shrink-0 transition-colors',
                                                form.permissions.includes(perm.name)
                                                    ? 'bg-[#005740] border-[#005740]'
                                                    : 'bg-white border-gray-300',
                                            ]"
                                        >
                                            <i
                                                v-if="form.permissions.includes(perm.name)"
                                                class="fa-solid fa-check text-white text-[9px]"
                                            ></i>
                                        </span>
                                        <span class="truncate">{{ perm.name }}</span>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>

                <!-- RIGHT: Roles list -->
                <div class="w-full md:w-[38%] p-6 overflow-y-auto bg-gray-50/60">
                    <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wide mb-3">
                        Existing Roles
                    </label>

                    <ul class="space-y-2">
                        <li
                            v-for="role in roles"
                            :key="role.id"
                            :class="[
                                'flex items-center justify-between rounded-lg px-3.5 py-2.5 border transition-colors',
                                editingRole?.id === role.id
                                    ? 'bg-[#005740]/5 border-[#005740]/30'
                                    : 'bg-white border-gray-200 hover:border-gray-300'
                            ]"
                        >
                            <span class="text-sm font-medium text-[#1f2d27] truncate">
                                {{ role.name }}
                            </span>
                            <div class="flex items-center gap-1 flex-shrink-0">
                                <button
                                    @click="startEdit(role)"
                                    class="h-8 w-8 flex items-center justify-center rounded-md text-gray-400 hover:text-[#005740] hover:bg-[#005740]/10 transition-colors"
                                    title="Edit"
                                >
                                    <i class="fa-solid fa-pen-to-square text-xs"></i>
                                </button>
                                <button
                                    @click="deleteRole(role)"
                                    class="h-8 w-8 flex items-center justify-center rounded-md text-gray-400 hover:text-red-600 hover:bg-red-50 transition-colors"
                                    title="Delete"
                                >
                                    <i class="fa-solid fa-trash text-xs"></i>
                                </button>
                            </div>
                        </li>
                        <li
                            v-if="!roles.length"
                            class="text-sm text-gray-400 text-center py-10 border border-dashed border-gray-200 rounded-lg"
                        >
                            <i class="fa-solid fa-shield-halved text-2xl text-gray-300 block mb-2"></i>
                            No roles yet.
                        </li>
                    </ul>
                </div>
            </div>

            <!-- Footer -->
            <div class="flex justify-end px-6 py-4 border-t border-gray-100 flex-shrink-0">
                <button
                    @click="closeWithAnimation"
                    class="px-6 py-2.5 rounded-lg text-sm font-medium text-gray-600 hover:bg-gray-100 transition-colors"
                >
                    Close
                </button>
            </div>
        </div>

        <DeleteModal
            v-if="showDeleteModal"
            :item="itemToDelete"
            @confirm="confirmDelete"
            @close="showDeleteModal = false"
        />
    </div>
</template>