<script setup>
import { ref, computed } from 'vue';
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

const searchQuery = ref('');
const activeCategory = ref('all');

const form = useForm({ name: '' });

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
    other: 'Other',
};

const moduleIcons = {
    inventory: 'fa-boxes-stacked',
    suppliers: 'fa-truck-field',
    categories: 'fa-tags',
    acknowledgements: 'fa-file-signature',
    users: 'fa-users',
    roles: 'fa-user-shield',
    archive_item: 'fa-box-archive',
    archive_supplier: 'fa-box-archive',
    'item histories': 'fa-clock-rotate-left',
    other: 'fa-layer-group',
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

const categoryKeys = computed(() => Object.keys(groupedPermissions.value));

const filteredPermissions = computed(() => {
    const q = searchQuery.value.trim().toLowerCase();
    let list = props.permissions;

    if (activeCategory.value !== 'all') {
        list = list.filter((p) => getModuleKey(p.name) === activeCategory.value);
    }
    if (q) {
        list = list.filter((p) => p.name.toLowerCase().includes(q));
    }
    return list;
});

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
    <div class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm flex items-center justify-center p-4 sm:p-6 z-50" @click="closeWithAnimation">
        <div :class="[
            'bg-white rounded-xl shadow-2xl w-full max-w-3xl lg:max-w-4xl overflow-hidden flex flex-col max-h-[85vh]',
            isClosing ? 'animate-pop-out' : 'animate-pop-in'
        ]" @click.stop>
            <Toast />

            <!-- Header (#005740 Top Background) -->
            <div class="bg-[#005740] px-6 py-4 flex items-center justify-between flex-shrink-0 text-white border-b border-[#004230]">
                <div class="flex items-center gap-3.5">
                    <div>
                        <h3 class="text-lg font-bold text-white leading-tight tracking-wide">Manage Permissions</h3>
                        <p class="text-xs text-emerald-100/80 mt-0.5 font-medium">
                            {{ permissions.length }} total permission{{ permissions.length === 1 ? '' : 's' }} configured
                        </p>
                    </div>
                </div>
                <button @click="closeWithAnimation"
                    class="text-emerald-100/70 hover:text-white hover:bg-white/10 rounded-lg h-9 w-9 flex items-center justify-center transition-all flex-shrink-0"
                    title="Close">
                    <i class="fa-solid fa-xmark text-lg"></i>
                </button>
            </div>

            <!-- Main Body Layout (Sidebar + Content Panel) -->
            <div class="flex flex-col md:flex-row flex-1 overflow-hidden">
                <!-- Left Sidebar: Category Filters -->
                <aside v-if="categoryKeys.length > 0" class="w-full md:w-64 bg-slate-50 border-b md:border-b-0 md:border-r border-slate-200 p-4 flex-shrink-0 overflow-y-auto">
                    <div class="text-[11px] font-bold text-slate-400 uppercase tracking-wider mb-2.5 px-2">
                        Categories
                    </div>
                    <nav class="space-y-1">
                        <!-- 'All' Button -->
                        <button type="button" @click="activeCategory = 'all'" :class="[
                            'w-full flex items-center justify-between px-3 py-2 rounded-lg text-xs font-semibold transition-all',
                            activeCategory === 'all'
                                ? 'bg-[#005740] text-white shadow-sm'
                                : 'text-slate-600 hover:bg-slate-200/60 hover:text-slate-900',
                        ]">
                            <div class="flex items-center gap-2.5 truncate">
                                <i class="fa-solid fa-layer-group text-xs w-4 text-center"></i>
                                <span class="truncate">All Modules</span>
                            </div>
                            <span :class="[
                                'text-[10px] px-2 py-0.5 rounded-md font-bold',
                                activeCategory === 'all' ? 'bg-white/20 text-white' : 'bg-slate-200/80 text-slate-600',
                            ]">
                                {{ permissions.length }}
                            </span>
                        </button>

                        <!-- Dynamic Category Buttons -->
                        <button v-for="key in categoryKeys" :key="key" type="button" @click="activeCategory = key" :class="[
                            'w-full flex items-center justify-between px-3 py-2 rounded-lg text-xs font-semibold transition-all',
                            activeCategory === key
                                ? 'bg-[#005740] text-white shadow-sm'
                                : 'text-slate-600 hover:bg-slate-200/60 hover:text-slate-900',
                        ]">
                            <div class="flex items-center gap-2.5 truncate">
                                <i :class="['fa-solid text-xs w-4 text-center', moduleIcons[key] ?? 'fa-layer-group']"></i>
                                <span class="truncate">{{ moduleLabels[key] ?? key }}</span>
                            </div>
                            <span :class="[
                                'text-[10px] px-2 py-0.5 rounded-md font-bold',
                                activeCategory === key ? 'bg-white/20 text-white' : 'bg-slate-200/80 text-slate-600',
                            ]">
                                {{ groupedPermissions[key].length }}
                            </span>
                        </button>
                    </nav>
                </aside>

                <!-- Right Content Panel -->
                <main class="flex-1 p-5 overflow-y-auto space-y-4 bg-white">
                    <!-- Create / Edit Form Card -->
                    <div class="bg-slate-50 border border-slate-200 rounded-xl p-4 shadow-sm">
                        <form @submit.prevent="submitPermission">
                            <div class="flex items-center justify-between mb-2">
                                <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider">
                                    {{ editingPermission ? 'Edit Permission Name' : 'Add New Permission' }}
                                </label>
                                <span v-if="editingPermission" class="text-[11px] font-semibold text-[#005740] bg-[#005740]/10 rounded-md px-2 py-0.5">
                                    Editing Mode
                                </span>
                            </div>
                            <div class="flex flex-col sm:flex-row gap-2">
                                <div class="relative flex-1">
                                    <i class="fa-solid fa-key absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400 text-xs"></i>
                                    <input v-model="form.name" type="text" placeholder="e.g. create inventory" :class="[
                                        'w-full rounded-lg pl-9 pr-3 py-2 text-sm bg-white border transition-all',
                                        form.errors.name
                                            ? 'border-red-500 focus:ring-2 focus:ring-red-500/20'
                                            : 'border-slate-300 focus:border-[#005740] focus:ring-2 focus:ring-[#005740]/20 focus:outline-none',
                                    ]" />
                                </div>
                                <div class="flex gap-2">
                                    <button type="submit"
                                        class="flex-1 sm:flex-none bg-[#005740] text-white px-5 py-2 rounded-lg text-sm font-semibold hover:bg-[#004230] active:scale-[0.98] transition-all shadow-sm flex items-center justify-center gap-1.5">
                                        <i v-if="editingPermission" class="fa-solid fa-check text-xs"></i>
                                        <i v-else class="fa-solid fa-plus text-xs"></i>
                                        {{ editingPermission ? 'Save Changes' : 'Add Permission' }}
                                    </button>
                                    <button v-if="editingPermission" type="button" @click="cancelEdit"
                                        class="border border-slate-300 px-4 py-2 rounded-lg text-sm font-medium text-slate-600 hover:bg-slate-200/50 transition-all">
                                        Cancel
                                    </button>
                                </div>
                            </div>
                            <div v-if="form.errors.name" class="text-red-500 text-xs mt-2 flex items-center gap-1 font-medium">
                                <i class="fa-solid fa-circle-exclamation"></i>
                                {{ form.errors.name }}
                            </div>
                        </form>
                    </div>

                    <!-- Search Input -->
                    <div class="relative">
                        <i class="fa-solid fa-magnifying-glass absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400 text-xs"></i>
                        <input v-model="searchQuery" type="text" placeholder="Search permissions in list..."
                            class="w-full rounded-lg pl-9 pr-3 py-2 text-sm bg-slate-50 border border-slate-200 focus:bg-white focus:border-[#005740] focus:ring-2 focus:ring-[#005740]/20 focus:outline-none transition-all" />
                    </div>

                    <!-- Permissions Grid/List -->
                    <div class="space-y-2">
                        <div v-for="permission in filteredPermissions" :key="permission.id"
                            class="group flex items-center justify-between bg-white border border-slate-200 rounded-lg px-4 py-2.5 hover:border-[#005740]/40 hover:shadow-sm transition-all">
                            <div class="flex items-center gap-3 min-w-0">
                                <div class="h-8 w-8 rounded-lg bg-emerald-50 border border-emerald-100 flex items-center justify-center flex-shrink-0">
                                    <i class="fa-solid fa-key text-[#005740] text-xs"></i>
                                </div>
                                <span class="text-sm font-semibold text-slate-800 truncate">{{ permission.name }}</span>
                            </div>
                            <div class="flex gap-1 flex-shrink-0 opacity-80 group-hover:opacity-100 transition-opacity">
                                <button @click="startEdit(permission)"
                                    class="h-8 w-8 rounded-lg flex items-center justify-center text-slate-500 hover:bg-slate-100 hover:text-[#005740] transition-colors"
                                    title="Edit">
                                    <i class="fa-solid fa-pen-to-square text-xs"></i>
                                </button>
                                <button @click="deletePermission(permission)"
                                    class="h-8 w-8 rounded-lg flex items-center justify-center text-slate-500 hover:bg-rose-50 hover:text-rose-600 transition-colors"
                                    title="Delete">
                                    <i class="fa-solid fa-trash text-xs"></i>
                                </button>
                            </div>
                        </div>

                        <!-- Empty States -->
                        <div v-if="!permissions.length" class="text-center py-10 bg-slate-50 rounded-xl border border-dashed border-slate-200">
                            <i class="fa-solid fa-shield-halved text-3xl text-slate-300 mb-2"></i>
                            <p class="text-sm font-semibold text-slate-600">No permissions found</p>
                            <p class="text-xs text-slate-400 mt-0.5">Use the input above to create your first permission.</p>
                        </div>
                        <div v-else-if="!filteredPermissions.length" class="text-center py-10 bg-slate-50 rounded-xl border border-dashed border-slate-200">
                            <i class="fa-solid fa-magnifying-glass text-3xl text-slate-300 mb-2"></i>
                            <p class="text-sm font-semibold text-slate-600" v-if="searchQuery">No results matching "{{ searchQuery }}"</p>
                            <p class="text-sm font-semibold text-slate-600" v-else>No permissions exist in this category</p>
                        </div>
                    </div>
                </main>
            </div>

            <!-- Footer -->
            <div class="flex justify-end px-6 py-3.5 border-t border-slate-200 bg-slate-50 flex-shrink-0">
                <button @click="closeWithAnimation"
                    class="border border-slate-300 bg-white px-6 py-2 rounded-lg text-sm font-semibold text-slate-700 hover:bg-slate-100 active:scale-[0.98] transition-all shadow-sm">
                    Close
                </button>
            </div>
        </div>
        <DeleteModal v-if="showDeleteModal" :item="itemToDelete" @confirm="confirmDelete"
            @close="showDeleteModal = false" />
    </div>
</template>