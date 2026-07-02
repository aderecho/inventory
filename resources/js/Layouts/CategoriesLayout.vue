<script setup>
import { ref, computed } from "vue";
import { usePage, router } from "@inertiajs/vue3";
import SideBar from "@/Components/SideBar.vue";
import PageHeader from "@/Components/PageHeader.vue";
import InventoryTable from "@/Components/InventoryTable.vue";
import ItemFilterControls from "@/Components/Filters/ItemFilterControls.vue";
import PrimaryButton from "@/Components/Buttons/PrimaryButton.vue";
import CategoriesFormModal from "@/Components/Modals/CategoriesFormModal.vue";
import ArchiveModal from "@/Components/Modals/ArchiveModal.vue";
import SuccessModal from "@/Components/Modals/SuccessModal.vue";
import SuccessDeleteModal from "@/Components/Modals/SuccessDeleteModal.vue";
import LoadingOverlay from "@/Components/LoadingOverlay.vue";
import NavHeader from "@/Components/NavHeader.vue";
import { useLoading } from "@/Composables/useLoading";
import { usePermissions } from "@/Composables/usePermissions";
import { useSidebar } from "@/Composables/useSidebar";
const { isSidebarOpen, toggleSidebar } = useSidebar();

const { isLoading, loadingTitle, loadingMessage, startLoading, stopLoading } = useLoading();

const {
    categoryActions,
    canCreateCategory,
} = usePermissions()

const columns = [
    { label: "Classification Name", key: "classification_name" },
    { label: "Classification Code", key: "classification_code" },
    {
        label: "Status",
        key: "status",
        format: (status) => {
            let label = "Unknown",
                cls = "text-gray-500",
                icon = "";
            if (status === 0) {
                label = "Inactive";
                cls =
                    "text-[#D32F2F] font-bold bg-[#F8D4D4] py-2 px-4 rounded-md";
            } else if (status === 1) {
                label = "Active";
                cls =
                    "text-[#2E7D32] font-bold bg-[#D4F8D4] py-2 px-4 rounded-md";
            }
            return `<span class="${cls}">${icon} ${label}</span>`;
        },
    },
    { label: "Action", key: "action" },
];

const categoriesFields = [
    {
        label: "Classification Name",
        model: "classification_name",
        placeholder: "Classification Name",
        type: "text",
    },
    {
        label: "Classification Code",
        model: "classification_code",
        placeholder: "Classification Code",
        type: "text",
    },
];

const page = usePage();
const categories = computed(() => page.props.categories || []);

let search = ref("");

let formMode = ref("create"); // create | edit
let showFormModal = ref(false);
let showArchiveModal = ref(false);

let currentCategories = ref({});

const showSuccessModal = ref(false);
const showDeleteSuccessModal = ref(false);
const successMessage = ref("");

function openAdd() {
    formMode.value = "create";
    currentCategories.value = {};
    showFormModal.value = true;
}

function handleEdit(category) {
    formMode.value = "edit";
    currentCategories.value = category;
    showFormModal.value = true;
}

function handleDelete(category) {
    currentCategories.value = category;
    showArchiveModal.value = true;
}

function handleSubmit(form) {
    if (formMode.value === "create") {
        form.post(route("categories.store"), {
            preserveScroll: true,
            onSuccess: () => {
                stopLoading();
                showFormModal.value = false;
                form.reset();

                successMessage.value = "Category added successfully!";
                showSuccessModal.value = true;
            },
        });
    } else {
        form.put(route("categories.update", form.id), {
            preserveScroll: true,
            onSuccess: () => {
                stopLoading();
                showFormModal.value = false;
                form.reset();

                successMessage.value = "Category updated successfully!";
                showSuccessModal.value = true;
            },
        });
    }
}

function confirmArchive() {
    router.delete(route("categories.destroy", currentCategories.value.id), {
        preserveScroll: true,
        onSuccess: () => {
            stopLoading();
            showArchiveModal.value = false;
            showDeleteSuccessModal.value = true;
            currentCategories.value = {};
        },
    });
}
</script>

<template>
    <LoadingOverlay
        :show="isLoading"
        :title="loadingTitle"
        :message="loadingMessage"
    />
    <div class="h-screen flex flex-col bg-gray-100">

        <div class="flex flex-1 overflow-hidden">
            <aside class="h-full transition-all duration-300 ease-in-out flex-shrink-0">
        <SideBar :isOpen="isSidebarOpen" @toggleSidebar="toggleSidebar" />
    </aside>

     <div class="flex flex-col flex-1 overflow-hidden">

        <NavHeader
                    :isSidebarOpen="isSidebarOpen"
                    @toggleSidebar="toggleSidebar"
                >
                </NavHeader>

            <main class="flex-1 sm:p-5 md:p-6 overflow-y-auto m-2">
                <PageHeader title="Categories" />

                <div
                    class="mt-10 flex flex-col md:flex-row gap-4 justify-between"
                >
                    <PrimaryButton @click="openAdd "v-if="canCreateCategory">
                        <i class="fa-solid fa-user-group"></i>
                        <span>Add Category</span>
                    </PrimaryButton>

                    <ItemFilterControls
                        :search="search"
                        @update:search="search = $event"
                        :mode="'categories'"
                    />
                </div>

                <CategoriesFormModal
                    v-if="showFormModal"
                    :mode="formMode"
                    :categories="currentCategories"
                    :categoriesFields="categoriesFields"
                    @submit="handleSubmit"
                    @close="showFormModal = false"
                />

                <ArchiveModal
                    v-if="showArchiveModal"
                    :item="currentCategories"
                    @confirm="confirmArchive"
                    @close="showArchiveModal = false"
                />

                <SuccessModal
                    v-if="showSuccessModal"
                    title="Success"
                    :message="successMessage"
                    actionButtonLabel="OK"
                    @close="showSuccessModal = false"
                />

                <SuccessDeleteModal
                    v-if="showDeleteSuccessModal"
                    title="Archive Success"
                    message="Category archived successfully!"
                    buttonText="Confirm"
                    @close="showDeleteSuccessModal = false"
                />

                <InventoryTable
                    :columns="columns"
                    :module="'categories'"
                    :rows="categories"
                    :actions="categoryActions"
                    @edit="handleEdit"
                    @delete="handleDelete"
                />
            </main>
            </div>
        </div>
    </div>
</template>
