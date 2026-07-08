<script setup>
import { ref, computed } from "vue";
import { usePage, router, useForm } from "@inertiajs/vue3";
import SideBar from "@/Components/SideBar.vue";
import NavHeader from "@/Components/NavHeader.vue";
import PageHeader from "@/Components/PageHeader.vue";
import { useSidebar } from "@/Composables/useSidebar";
import { Plus, Trash2, Pencil, Copy, Check, RefreshCw } from "lucide-vue-next";

const { isSidebarOpen, toggleSidebar } = useSidebar();

const page = usePage();
const clients = computed(() => page.props.clients);

// New client form
const showCreateModal = ref(false);
const createForm = useForm({
    name: "",
    allowed_domains: "",
});

function submitCreate() {
    createForm
        .transform((data) => ({
            ...data,
            allowed_domains: data.allowed_domains
                ? data.allowed_domains
                      .split(",")
                      .map((d) => d.trim())
                      .filter(Boolean)
                : null,
        }))
        .post(route("api_clients.store"), {
            onSuccess: () => {
                showCreateModal.value = false;
                createForm.reset();
                showKeyModal.value = true;
            },
        });
}

// Show generated key exactly once
const showKeyModal = ref(false);
const copied = ref(false);
const plainKey = computed(() => page.props.flash?.plain_api_key);
const clientName = computed(() => page.props.flash?.client_name);

function copyKey() {
    navigator.clipboard.writeText(plainKey.value);
    copied.value = true;
    setTimeout(() => (copied.value = false), 2000);
}

// Edit
const editingClient = ref(null);
const editForm = useForm({
    name: "",
    allowed_domains: "",
    is_active: true,
});

function openEdit(client) {
    editingClient.value = client;
    editForm.name = client.name;
    editForm.allowed_domains = client.allowed_domains.join(", ");
    editForm.is_active = client.is_active;
}

function submitEdit() {
    editForm
        .transform((data) => ({
            ...data,
            allowed_domains: data.allowed_domains
                ? data.allowed_domains
                      .split(",")
                      .map((d) => d.trim())
                      .filter(Boolean)
                : null,
        }))
        .put(route("api_clients.update", editingClient.value.id), {
            onSuccess: () => (editingClient.value = null),
        });
}

function toggleActive(client) {
    router.put(
        route("api_clients.update", client.id),
        {
            name: client.name,
            allowed_domains: client.allowed_domains,
            is_active: !client.is_active,
        },
        { preserveScroll: true },
    );
}

function deleteClient(client) {
    if (confirm(`Revoke access for "${client.name}"? This cannot be undone.`)) {
        router.delete(route("api_clients.destroy", client.id), {
            preserveScroll: true,
        });
    }
}

function regenerateKey(client) {
    if (
        confirm(
            `Regenerate the API key for "${client.name}"? Their current key will stop working immediately.`,
        )
    ) {
        router.post(
            route("api_clients.regenerate", client.id),
            {},
            {
                onSuccess: () => (showKeyModal.value = true),
                preserveScroll: true,
            },
        );
    }
}
</script>

<template>
    <div class="h-screen flex flex-col bg-gray-50/50">
        <div class="flex flex-1 overflow-hidden">
            <aside
                class="h-full transition-all duration-300 ease-in-out flex-shrink-0"
            >
                <SideBar
                    :isOpen="isSidebarOpen"
                    @toggleSidebar="toggleSidebar"
                />
            </aside>

            <div class="flex flex-col flex-1 overflow-hidden">
                <NavHeader
                    :isSidebarOpen="isSidebarOpen"
                    @toggleSidebar="toggleSidebar"
                />

                <main class="flex-1 p-4 sm:p-6 md:p-8 overflow-y-auto">
                    <div class="max-w-5xl mx-auto flex flex-col gap-6">
                        <div class="flex items-center justify-between">
                            <PageHeader title="API Clients" />
                            <button
                                @click="showCreateModal = true"
                                class="flex items-center gap-2 bg-[#005740] text-white px-4 py-2 rounded-lg text-sm font-medium hover:bg-[#004d38]"
                            >
                                <Plus class="w-4 h-4" /> New Client
                            </button>
                        </div>

                        <div
                            class="bg-white rounded-xl border border-gray-100 overflow-hidden"
                        >
                            <table class="w-full text-sm">
                                <thead
                                    class="bg-gray-50 text-left text-gray-500"
                                >
                                    <tr>
                                        <th class="px-4 py-3">Name</th>
                                        <th class="px-4 py-3">
                                            Allowed Domains
                                        </th>
                                        <th class="px-4 py-3">Status</th>
                                        <th class="px-4 py-3">Created</th>
                                        <th class="px-4 py-3 text-right">
                                            Actions
                                        </th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr
                                        v-for="client in clients"
                                        :key="client.id"
                                        class="border-t border-gray-100"
                                    >
                                        <td class="px-4 py-3 font-medium">
                                            {{ client.name }}
                                        </td>
                                        <td class="px-4 py-3 text-gray-600">
                                            {{
                                                client.allowed_domains.length
                                                    ? client.allowed_domains.join(
                                                          ", ",
                                                      )
                                                    : "Any (no restriction)"
                                            }}
                                        </td>
                                        <td class="px-4 py-3">
                                            <button
                                                @click="toggleActive(client)"
                                                :class="
                                                    client.is_active
                                                        ? 'bg-green-100 text-green-700'
                                                        : 'bg-gray-100 text-gray-500'
                                                "
                                                class="px-2 py-1 rounded-full text-xs font-medium"
                                            >
                                                {{
                                                    client.is_active
                                                        ? "Active"
                                                        : "Inactive"
                                                }}
                                            </button>
                                        </td>
                                        <td class="px-4 py-3 text-gray-500">
                                            {{ client.created_at }}
                                        </td>
                                        <td class="px-4 py-3 text-right">
                                            <button
                                                @click="regenerateKey(client)"
                                                class="text-amber-600 hover:text-amber-800 mr-3"
                                                title="Regenerate API Key"
                                            >
                                                <RefreshCw class="w-4 h-4" />
                                            </button>
                                            <button
                                                @click="openEdit(client)"
                                                class="text-gray-500 hover:text-gray-900 mr-3"
                                            >
                                                <Pencil class="w-4 h-4" />
                                            </button>
                                            <button
                                                @click="deleteClient(client)"
                                                class="text-red-500 hover:text-red-700"
                                            >
                                                <Trash2 class="w-4 h-4" />
                                            </button>
                                        </td>
                                    </tr>
                                    <tr v-if="!clients.length">
                                        <td
                                            colspan="5"
                                            class="px-4 py-8 text-center text-gray-400"
                                        >
                                            No API clients yet.
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </main>
            </div>
        </div>

        <!-- Create Modal -->
        <div
            v-if="showCreateModal"
            class="fixed inset-0 bg-black/40 flex items-center justify-center z-50"
        >
            <div class="bg-white rounded-xl p-6 w-full max-w-md">
                <h3 class="text-lg font-semibold mb-4">New API Client</h3>
                <div class="flex flex-col gap-4">
                    <div>
                        <label class="text-sm font-medium text-gray-700"
                            >Partner name</label
                        >
                        <input
                            v-model="createForm.name"
                            type="text"
                            class="w-full border rounded-lg px-3 py-2 mt-1"
                            placeholder="Partner System A"
                        />
                    </div>
                    <div>
                        <label class="text-sm font-medium text-gray-700"
                            >Allowed domains (comma-separated, optional)</label
                        >
                        <input
                            v-model="createForm.allowed_domains"
                            type="text"
                            class="w-full border rounded-lg px-3 py-2 mt-1"
                            placeholder="https://partner.com"
                        />
                    </div>
                </div>
                <div class="flex justify-end gap-2 mt-6">
                    <button
                        @click="showCreateModal = false"
                        class="px-4 py-2 text-sm text-gray-600"
                    >
                        Cancel
                    </button>
                    <button
                        @click="submitCreate"
                        class="px-4 py-2 text-sm bg-[#005740] text-white rounded-lg"
                    >
                        Create
                    </button>
                </div>
            </div>
        </div>

        <!-- Show generated key once -->
        <div
            v-if="showKeyModal && plainKey"
            class="fixed inset-0 bg-black/40 flex items-center justify-center z-50"
        >
            <div class="bg-white rounded-xl p-6 w-full max-w-md">
                <h3 class="text-lg font-semibold mb-2">
                    API key for {{ clientName }}
                </h3>
                <p class="text-sm text-gray-500 mb-4">
                    Copy this now — it will not be shown again.
                </p>
                <div
                    class="flex items-center gap-2 bg-gray-50 border rounded-lg px-3 py-2"
                >
                    <code class="text-sm flex-1 truncate">{{ plainKey }}</code>
                    <button
                        @click="copyKey"
                        class="text-gray-500 hover:text-gray-900"
                    >
                        <Check v-if="copied" class="w-4 h-4 text-green-600" />
                        <Copy v-else class="w-4 h-4" />
                    </button>
                </div>
                <div class="flex justify-end mt-6">
                    <button
                        @click="showKeyModal = false"
                        class="px-4 py-2 text-sm bg-[#005740] text-white rounded-lg"
                    >
                        Done
                    </button>
                </div>
            </div>
        </div>

        <!-- Edit Modal -->
        <div
            v-if="editingClient"
            class="fixed inset-0 bg-black/40 flex items-center justify-center z-50"
        >
            <div class="bg-white rounded-xl p-6 w-full max-w-md">
                <h3 class="text-lg font-semibold mb-4">
                    Edit {{ editingClient.name }}
                </h3>
                <div class="flex flex-col gap-4">
                    <div>
                        <label class="text-sm font-medium text-gray-700"
                            >Partner name</label
                        >
                        <input
                            v-model="editForm.name"
                            type="text"
                            class="w-full border rounded-lg px-3 py-2 mt-1"
                        />
                    </div>
                    <div>
                        <label class="text-sm font-medium text-gray-700"
                            >Allowed domains (comma-separated)</label
                        >
                        <input
                            v-model="editForm.allowed_domains"
                            type="text"
                            class="w-full border rounded-lg px-3 py-2 mt-1"
                        />
                    </div>
                </div>
                <div class="flex justify-end gap-2 mt-6">
                    <!-- <button
                        @click="regenerateKey(editingClient)"
                        class="text-amber-600 hover:text-amber-800 mr-3"
                        title="Regenerate API Key"
                    >
                        <RefreshCw class="w-4 h-4" />
                    </button> -->
                    <button
                        @click="editingClient = null"
                        class="px-4 py-2 text-sm text-gray-600"
                    >
                        Cancel
                    </button>
                    <button
                        @click="submitEdit"
                        class="px-4 py-2 text-sm bg-[#005740] text-white rounded-lg"
                    >
                        Save
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>
