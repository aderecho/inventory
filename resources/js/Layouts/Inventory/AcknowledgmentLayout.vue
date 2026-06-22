<script setup>
import { ref, computed } from "vue";
import { usePage } from "@inertiajs/vue3";
import InventoryAcknowledgement from "@/Components/InventoryAcknowledgement.vue";
import NavHeader from "@/Components/NavHeader.vue";
import SideBar from "@/Components/SideBar.vue";
import PageHeader from "@/Components/PageHeader.vue";
import ItemFilterControls from "@/Components/Filters/ItemFilterControls.vue";
import Show from "@/Pages/Acknowledgement/Show.vue"; 

const page = usePage();
const receipts = computed(() => page.props.receipts);

const isSidebarOpen = ref(true);
const toggleSidebar = () => {
    isSidebarOpen.value = !isSidebarOpen.value;
};

let search = ref("");

const selectedReceipt = ref(null);
const showModal = ref(false);

function openModal(receipt) {
    selectedReceipt.value = receipt;

    const groups = {};
    receipt.acknowledgement_items.forEach(item => {
        const person = item.accountable_person;
        if (!groups[person.id]) {
            groups[person.id] = { person, items: [] };
        }
        groups[person.id].items.push(item);
    });

    groupedByPerson.value = Object.values(groups);
    showModal.value = true;
}

const groupedByPerson = ref([]);

function closeModal() {
    showModal.value = false;
    selectedReceipt.value = null;
}
</script>

<template>
    <div class="h-screen flex flex-col bg-gray-100">
        <!-- NAVHEADER -->
        <NavHeader class="flex-shrink-0" @toggleSidebar="toggleSidebar" />

        <!-- SIDEBAR + MAIN -->
        <div class="flex flex-1 overflow-hidden">
            <!-- SIDEBAR -->
            <aside
                class="transition-all duration-600 ease-in-out transform"
                :class="
                    isSidebarOpen
                        ? 'translate-x-0 opacity-100'
                        : '-translate-x-full opacity-0 w-0'
                "
            >
                <SideBar />
            </aside>

            <!-- MAIN CONTENT -->
            <main class="flex-1 p-4 sm:p-5 md:p-6 overflow-y-auto">
                <div>
                    <PageHeader title="Acknowledgments" class="ml-4" />
                    <div
                        class="flex flex-col mx-3 lg:flex-row gap-4 my-5 flex-wrap"
                    >
                        <div class="flex-1 w-full lg:w-[22rem] xl:w-[25rem]">
                            <div class="flex justify-end mb-3">
                                <ItemFilterControls
                                    :search="search"
                                    @update:search="search = $event"
                                    :mode="'acknowledgements'"
                                />
                            </div>
                            <InventoryAcknowledgement 
                            :receipts="receipts"
                             @view="openModal"
                            />
                            <Show
                                v-if="showModal"
                                :receipt="selectedReceipt"
                                 :groupedByPerson="groupedByPerson"
                                @close="closeModal"
                            />
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>
</template>
