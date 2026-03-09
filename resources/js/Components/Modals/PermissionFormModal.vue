<script setup>
import { ref } from 'vue';
import { useForm, router } from '@inertiajs/vue3';
import Toast from 'primevue/toast';
import { useToast } from 'primevue/usetoast';
import DeleteModal from '@/Components/Modals/DeleteModal.vue';

const props = defineProps({
    permissions: { type: Array, default: () => [] },
});

const emit = defineEmits(['close']);
const toast = useToast();

const isClosing = ref(false);
const editingPermission = ref(null);

const showDeleteModal = ref(false);
const itemToDelete = ref(null);

const form = useForm({ name: '' });

function closeWithAnimation() {
    isClosing.value = true;
    setTimeout(() => {
        emit('close');
        isClosing.value = false;
    }, 200);
}

function startEdit(permission) {
    editingPermission.value = permission;
    form.name = permission.name;
}

function cancelEdit() {
    editingPermission.value = null;
    form.reset();
}

function submitPermission() {
    if (editingPermission.value) {
        form.put(route('permissions.update', editingPermission.value.id), {
            onSuccess: () => {
                toast.add({ severity: 'success', summary: 'Updated', detail: 'Permission updated.', life: 3000 });
                cancelEdit();
            },
            onError: () => {
                toast.add({ severity: 'error', summary: 'Error', detail: 'Failed to update permission.', life: 3000 });
            },
        });
    } else {
        form.post(route('permissions.store'), {
            onSuccess: () => {
                toast.add({ severity: 'success', summary: 'Created', detail: 'Permission created.', life: 3000 });
                form.reset();
            },
            onError: () => {
                toast.add({ severity: 'error', summary: 'Error', detail: 'Failed to create permission.', life: 3000 });
            },
        });
    }
}

function deletePermission(permission) {
    itemToDelete.value = permission;
    showDeleteModal.value = true;
}

function confirmDelete() {
    router.delete(route('permissions.destroy', itemToDelete.value.id), {
        preserveScroll: true,
        onSuccess: () => {
            toast.add({ severity: 'success', summary: 'Deleted', detail: 'Permission deleted.', life: 3000 });
            if (editingPermission.value?.id === itemToDelete.value.id) cancelEdit();
            showDeleteModal.value = false;
            itemToDelete.value = null;
        },
    });
}
</script>

<template>
    <div class="fixed inset-0 bg-black/40 flex items-center justify-center p-4 z-50">
        <div :class="[
            'bg-white rounded-lg w-full max-w-lg p-6 overflow-y-auto max-h-[90vh]',
            isClosing ? 'animate-pop-out' : 'animate-pop-in'
        ]">
            <Toast />

            <h3 class="text-2xl font-bold text-[#850038] mb-6">Manage Permissions</h3>

            <!-- Add / Edit Form -->
            <form @submit.prevent="submitPermission" class="mb-6">
                <label class="block text-sm font-bold mb-1">
                    {{ editingPermission ? 'Edit Permission Name' : 'New Permission Name' }}
                </label>
                <div class="flex gap-2">
                    <input v-model="form.name" type="text" placeholder="e.g. delete inventory" :class="[
                        'flex-1 rounded-md px-3 py-2 text-sm bg-[#F8F8F8] focus:ring-1 focus:outline-none',
                        form.errors.name
                            ? 'border-red-500 focus:ring-red-500'
                            : 'border-gray-300 focus:ring-[#850038]',
                    ]" />
                    <button type="submit"
                        class="bg-[#850038] text-white px-4 py-2 rounded-md text-sm font-semibold hover:bg-[#6e002f]">
                        {{ editingPermission ? 'Update' : 'Add' }}
                    </button>
                    <button v-if="editingPermission" type="button" @click="cancelEdit"
                        class="border px-4 py-2 rounded-md text-sm hover:bg-gray-100">
                        Cancel
                    </button>
                </div>
                <div v-if="form.errors.name" class="text-red-500 text-sm mt-1">
                    {{ form.errors.name }}
                </div>
            </form>

            <!-- Permissions List -->
            <ul class="space-y-2">
                <li v-for="permission in permissions" :key="permission.id"
                    class="flex items-center justify-between bg-[#F8F8F8] border rounded-md px-4 py-3">
                    <p class="text-sm font-semibold text-[#3B3B3B]">{{ permission.name }}</p>
                    <div class="flex gap-3">
                        <button @click="startEdit(permission)" class="text-[#54B3AB] hover:text-[#38a69d]" title="Edit">
                            <i class="fa-solid fa-pen-to-square"></i>
                        </button>
                        <button @click="deletePermission(permission)" class="text-[#D71D1D] hover:text-[#c50e0e]"
                            title="Delete">
                            <i class="fa-solid fa-trash"></i>
                        </button>
                    </div>
                </li>
                <li v-if="!permissions.length" class="text-sm text-gray-400 text-center py-4">
                    No permissions found.
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