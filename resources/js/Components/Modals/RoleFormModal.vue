<script setup>
import { ref, watch } from 'vue';
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

function getRolePermissions(role) {
    return role.permissions?.length
        ? role.permissions.map(p => p.name).join(', ')
        : 'No permissions assigned';
}
</script>

<template>
    <div class="fixed inset-0 bg-black/40 flex items-center justify-center p-4 z-50">
        <div :class="[
            'bg-white rounded-lg w-full max-w-lg p-6 overflow-y-auto max-h-[90vh]',
            isClosing ? 'animate-pop-out' : 'animate-pop-in'
        ]">
            <Toast />

            <h3 class="text-2xl font-bold text-[#850038] mb-6">Manage Roles</h3>

            <!-- Add / Edit Form -->
            <form @submit.prevent="submitRole" class="mb-6">
                <label class="block text-sm font-bold mb-1">
                    {{ editingRole ? 'Edit Role Name' : 'New Role Name' }}
                </label>
                <div class="flex gap-2 mb-4">
                    <input v-model="form.name" type="text" placeholder="e.g. moderator" :class="[
                        'flex-1 rounded-md px-3 py-2 text-sm bg-[#F8F8F8] focus:ring-1 focus:outline-none',
                        form.errors.name
                            ? 'border-red-500 focus:ring-red-500'
                            : 'border-gray-300 focus:ring-[#850038]',
                    ]" />
                    <button type="submit"
                        class="bg-[#850038] text-white px-4 py-2 rounded-md text-sm font-semibold hover:bg-[#6e002f]">
                        {{ editingRole ? 'Update' : 'Add' }}
                    </button>
                    <button v-if="editingRole" type="button" @click="cancelEdit"
                        class="border px-4 py-2 rounded-md text-sm hover:bg-gray-100">
                        Cancel
                    </button>
                </div>
                <div v-if="form.errors.name" class="text-red-500 text-sm -mt-3 mb-2">
                    {{ form.errors.name }}
                </div>

                <!-- Permissions checkboxes -->
                <label class="block text-sm font-bold mb-2">Assign Permissions</label>
                <div class="grid grid-cols-2 gap-2 max-h-40 overflow-y-auto border rounded-md p-3 bg-[#F8F8F8]">
                    <label v-for="perm in permissions" :key="perm.id"
                        class="flex items-center gap-2 text-sm cursor-pointer">
                        <input type="checkbox" :value="perm.name" :checked="form.permissions.includes(perm.name)"
                            @change="togglePermission(perm.name)" class="accent-[#850038]" />
                        {{ perm.name }}
                    </label>
                </div>
            </form>

            <!-- Roles List -->
            <ul class="space-y-2">
                <li v-for="role in roles" :key="role.id"
                    class="flex items-center justify-between bg-[#F8F8F8] border rounded-md px-4 py-3">
                    <div>
                        <p class="text-sm font-semibold text-[#3B3B3B]">{{ role.name }}</p>
                        <p class="text-xs text-gray-400 mt-0.5">
                            {{ getRolePermissions(role) }}
                        </p>
                    </div>
                    <div class="flex gap-3">
                        <button @click="startEdit(role)" class="text-[#54B3AB] hover:text-[#38a69d]" title="Edit">
                            <i class="fa-solid fa-pen-to-square"></i>
                        </button>
                        <button @click="deleteRole(role)" class="text-[#D71D1D] hover:text-[#c50e0e]" title="Delete">
                            <i class="fa-solid fa-trash"></i>
                        </button>
                    </div>
                </li>
                <li v-if="!roles.length" class="text-sm text-gray-400 text-center py-4">
                    No roles found.
                </li>
            </ul>

            <!-- Close -->
            <div class="flex justify-end mt-6">
                <button @click="closeWithAnimation" class="border px-6 py-2 rounded-full text-sm hover:bg-gray-100">
                    Close
                </button>
            </div>
        </div>
        <DeleteModal v-if="showDeleteModal" :item="itemToDelete" @confirm="confirmDelete"
            @close="showDeleteModal = false" />
    </div>
</template>