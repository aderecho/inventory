<script setup>
import { ref, watch, computed } from "vue";
import { useForm, router } from "@inertiajs/vue3";
import Toast from "primevue/toast";
import { useToast } from "primevue/usetoast";
import Multiselect from "@vueform/multiselect";
import "/node_modules/@vueform/multiselect/themes/default.css";

const toast = useToast();

const props = defineProps({
    mode: String,
    user: Object,
    roles: Array,
    permissions: Array,
    organizations: Array,
});

const emit = defineEmits(["submit", "close"]);

const form = useForm({
    id: null,
    email: "",
    status: 0,
    user_profiles: {
        first_name: "",
        last_name: "",
        middle_name: "",
        contact_number: "",
    },
    role: null,
    organizations: [],
    primary_organization_id: null,
});

const givePermissions = ref([]);
const revokePermissions = ref([]);

const rolePermissionNames = computed(() => {
    if (!form.role) return [];
    const selectedRole = props.roles.find((r) => r.name === form.role);
    return selectedRole?.permissions?.map((p) => p.name) ?? [];
});

const allPermissions = computed(() => props.permissions ?? []);

const moduleLabels = {
    inventory: "Inventory",
    suppliers: "Suppliers",
    categories: "Categories",
    acknowledgements: "Acknowledgements",
    users: "Users",
    roles: "Roles",
    archive_item: "Item Archive",
    archive_supplier: "Supplier Archive",
    "item histories": "Item Histories",
};

function getModuleKey(permName) {
    const parts = permName.split(" ");
    if (parts.length === 3) {
        return parts.slice(1).join(" ");
    }
    return parts[1] ?? "other";
}

const groupedPermissions = computed(() => {
    const groups = {};
    for (const perm of allPermissions.value) {
        const key = getModuleKey(perm.name);
        if (!groups[key]) groups[key] = [];
        groups[key].push(perm);
    }
    return groups;
});

function permissionState(name) {
    const fromRole = rolePermissionNames.value.includes(name);
    const isGiven = givePermissions.value.includes(name);
    const isRevoked = revokePermissions.value.includes(name);

    if (fromRole && !isRevoked) return "granted";
    if (fromRole && isRevoked) return "revoked";
    if (!fromRole && isGiven) return "given";
    return "none";
}

function isSelected(name) {
    const state = permissionState(name);
    return state === "granted" || state === "given";
}

function togglePermission(name) {
    const state = permissionState(name);
    const fromRole = rolePermissionNames.value.includes(name);

    if (fromRole) {
        if (state === "granted") {
            revokePermissions.value.push(name);
        } else {
            revokePermissions.value = revokePermissions.value.filter(
                (p) => p !== name,
            );
        }
    } else {
        if (state === "given") {
            givePermissions.value = givePermissions.value.filter(
                (p) => p !== name,
            );
        } else {
            givePermissions.value.push(name);
        }
    }
}

function isModuleFullySelected(modulePerms) {
    return modulePerms.every((p) => isSelected(p.name));
}

function isModulePartiallySelected(modulePerms) {
    const selectedCount = modulePerms.filter((p) => isSelected(p.name)).length;
    return selectedCount > 0 && selectedCount < modulePerms.length;
}

function toggleModule(modulePerms) {
    const allSelected = isModuleFullySelected(modulePerms);

    modulePerms.forEach((perm) => {
        const name = perm.name;
        const fromRole = rolePermissionNames.value.includes(name);
        const currentlySelected = isSelected(name);

        if (allSelected) {
            // Deselect everything in this module
            if (fromRole && currentlySelected) {
                if (!revokePermissions.value.includes(name)) {
                    revokePermissions.value.push(name);
                }
            } else if (!fromRole && currentlySelected) {
                givePermissions.value = givePermissions.value.filter(
                    (p) => p !== name,
                );
            }
        } else {
            // Select everything in this module
            if (fromRole && !currentlySelected) {
                revokePermissions.value = revokePermissions.value.filter(
                    (p) => p !== name,
                );
            } else if (!fromRole && !currentlySelected) {
                if (!givePermissions.value.includes(name)) {
                    givePermissions.value.push(name);
                }
            }
        }
    });
}

watch(
    () => props.user,
    (val) => {
        givePermissions.value = [];
        revokePermissions.value = [];

        if (!val) {
            form.reset();
            return;
        }

        form.id = val.id;
        form.email = val.email;
        form.status = val.status ?? 0;
        form.user_profiles.first_name = val.user_profiles?.first_name ?? "";
        form.user_profiles.last_name = val.user_profiles?.last_name ?? "";
        form.user_profiles.middle_name = val.user_profiles?.middle_name ?? "";
        form.organizations = val.user_profiles?.organizations?.map((o) => o.id) ?? [];
        form.primary_organization_id =
            val.user_profiles?.primary_organization_id ?? null;
        form.user_profiles.contact_number =
            val.user_profiles?.contact_number ?? "";
        form.role = val.roles?.length ? val.roles[0].name : null;

        givePermissions.value =
            val.direct_permissions?.map((p) =>
                typeof p === "object" ? p.name : p,
            ) ?? [];

        revokePermissions.value =
            val.forbidden_permissions?.map((p) =>
                typeof p === "object" ? p.name : p,
            ) ?? [];
    },
    { immediate: true },
);

watch(
    () => form.role,
    () => {
        revokePermissions.value = revokePermissions.value.filter((name) =>
            rolePermissionNames.value.includes(name),
        );
    },
);

const isClosing = ref(false);

function closeWithAnimation() {
    isClosing.value = true;
    setTimeout(() => {
        emit("close");
        isClosing.value = false;
    }, 200);
}

function submit() {
    const url =
        props.mode === "edit"
            ? route("user_management.update", form.id)
            : route("user_management.store");

    const method = props.mode === "edit" ? "put" : "post";

    form[method](url, {
        onSuccess: () => {
            if (props.mode === "edit") {
                router.put(
                    route("user_management.permissions", form.id),
                    {
                        give: givePermissions.value,
                        revoke: revokePermissions.value,
                    },
                    {
                        preserveScroll: true,
                        onSuccess: () => {
                            toast.add({
                                severity: "success",
                                summary: "Updated",
                                detail: "User and permissions updated successfully.",
                                life: 3000,
                            });
                            emit("submit");
                            emit("close");
                        },
                        onError: (errors) => {
                            toast.add({
                                severity: "error",
                                summary: "Permission Update Failed",
                                detail: Object.values(errors)[0],
                                life: 4000,
                            });
                        },
                    },
                );
            } else {
                toast.add({
                    severity: "success",
                    summary: "Created",
                    detail: "User added successfully.",
                    life: 3000,
                });
                form.reset();
                emit("submit");
                emit("close");
            }
        },
        onError: (errors) => {
            toast.add({
                severity: "error",
                summary: "Validation Failed",
                detail: Object.values(errors)[0],
                life: 4000,
            });
        },
    });
}

function inputClass(hasError) {
    return [
        "w-full rounded-md px-3 py-3 text-[#3B3B3B] bg-[#F8F8F8] text-sm focus:ring-1 focus:outline-none border",
        hasError
            ? "border-red-500 focus:ring-red-500 focus:border-red-500"
            : "border-gray-300 focus:ring-[#850038] focus:border-[#850038]",
    ];
}

const selectedOrganizations = computed(() =>
    (props.organizations ?? []).filter((org) =>
        form.organizations.includes(org.id),
    ),
);

function orgLabel(org) {
    return `${org.name} (${org.short_code})`;
}

const organizationOptions = computed(() =>
    (props.organizations ?? []).map((org) => ({
        value: org.id,
        label: orgLabel(org),
    })),
);

watch(
    () => [...form.organizations],
    (newVal) => {
        if (
            form.primary_organization_id &&
            !newVal.includes(form.primary_organization_id)
        ) {
            form.primary_organization_id = null;
        }
        if (newVal.length === 1) {
            form.primary_organization_id = newVal[0];
        }
    },
);
</script>

<template>
    <div
        class="fixed inset-0 bg-black/50 flex items-center justify-center p-4 z-50"
        @click="closeWithAnimation"
    >
        <div
            :class="[
                'bg-white rounded-2xl w-full max-w-5xl shadow-2xl overflow-hidden flex flex-col max-h-[90vh]',
                isClosing ? 'animate-pop-out' : 'animate-pop-in',
            ]"
            @click.stop
        >
            <Toast />

            <!-- Header -->
            <div
                class="bg-gradient-to-r from-[#003d2c] via-[#005740] to-[#00795a] px-6 py-5 flex items-center justify-between flex-shrink-0"
            >
                <div>
                    <h3 class="text-lg font-bold text-white">
                        {{ mode === "edit" ? "Edit User" : "Add User" }}
                    </h3>
                    <p class="text-xs text-white/70 mt-0.5">
                        {{
                            mode === "edit"
                                ? "Update account details and access."
                                : "Create a new account and assign a role."
                        }}
                    </p>
                </div>
                <button
                    @click="closeWithAnimation"
                    class="text-white/80 hover:text-white hover:bg-white/10 rounded-full h-9 w-9 flex items-center justify-center transition-colors"
                    title="Close"
                >
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>

            <form
                @submit.prevent="submit"
                class="flex flex-col flex-1 overflow-hidden"
            >
                <!-- Body: two columns -->
                <div class="flex flex-1 overflow-hidden">
                    <!-- LEFT: Account details -->
                    <div
                        class="w-full md:w-[60%] p-6 overflow-y-auto border-r border-gray-100 space-y-4"
                    >
                        <div>
                            <label
                                class="block text-xs font-semibold text-gray-500 uppercase tracking-wide mb-1.5"
                            >
                                Email <span class="text-red-500">*</span>
                            </label>
                            <input
                                v-model="form.email"
                                type="email"
                                placeholder="Email"
                                :class="inputClass(form.errors.email)"
                            />
                            <p
                                v-if="form.errors.email"
                                class="text-red-500 text-xs mt-1"
                            >
                                {{ form.errors.email }}
                            </p>
                        </div>

                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label
                                    class="block text-xs font-semibold text-gray-500 uppercase tracking-wide mb-1.5"
                                >
                                    Status <span class="text-red-500">*</span>
                                </label>
                                <select
                                    v-model="form.status"
                                    :class="inputClass(form.errors.status)"
                                >
                                    <option :value="1">Active</option>
                                    <option :value="0">Inactive</option>
                                </select>
                                <p
                                    v-if="form.errors.status"
                                    class="text-red-500 text-xs mt-1"
                                >
                                    {{ form.errors.status }}
                                </p>
                            </div>

                            <div>
                                <label
                                    class="block text-xs font-semibold text-gray-500 uppercase tracking-wide mb-1.5"
                                >
                                    Role <span class="text-red-500">*</span>
                                </label>
                                <select
                                    v-model="form.role"
                                    :class="inputClass(form.errors.role)"
                                >
                                    <option value="">Select</option>
                                    <option
                                        v-for="role in roles"
                                        :key="role.id"
                                        :value="role.name"
                                    >
                                        {{ role.name }}
                                    </option>
                                </select>
                                <p
                                    v-if="form.errors.role"
                                    class="text-red-500 text-xs mt-1"
                                >
                                    {{ form.errors.role }}
                                </p>
                            </div>
                        </div>

                        <div class="grid grid-cols-2 gap-3">
    <div>
        <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wide mb-1.5">
            Units <span class="text-red-500">*</span>
        </label>

        <Multiselect
            v-model="form.organizations"
            :options="organizationOptions"
            mode="tags"
            :searchable="true"
            :close-on-select="false"
            placeholder="Select units"
            :classes="{
                container: form.errors.organizations
                    ? 'multiselect border border-red-500 rounded-md bg-[#F8F8F8]'
                    : 'multiselect border border-gray-300 rounded-md bg-[#F8F8F8]',
            }"
        />

        <p v-if="form.errors.organizations" class="text-red-500 text-xs mt-1">
            {{ form.errors.organizations }}
        </p>
    </div>

    <div>
        <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wide mb-1.5">
            Primary Unit <span class="text-red-500">*</span>
        </label>
        <select
            v-model="form.primary_organization_id"
            :disabled="selectedOrganizations.length === 0"
            :class="inputClass(form.errors.primary_organization_id)"
        >
            <option :value="null" disabled>
                {{ selectedOrganizations.length === 0 ? "Select units first" : "Select primary unit" }}
            </option>
            <option v-for="org in selectedOrganizations" :key="org.id" :value="org.id">
                {{ org.name }}
            </option>
        </select>
        <p v-if="form.errors.primary_organization_id" class="text-red-500 text-xs mt-1">
            {{ form.errors.primary_organization_id }}
        </p>
    </div>
</div>

                        <template v-if="mode === 'edit'">
                            <div class="grid grid-cols-2 gap-3">
                                <div>
                                    <label
                                        class="block text-xs font-semibold text-gray-500 uppercase tracking-wide mb-1.5"
                                    >
                                        First Name
                                        <span class="text-red-500">*</span>
                                    </label>
                                    <input
                                        v-model="form.user_profiles.first_name"
                                        :class="
                                            inputClass(
                                                form.errors[
                                                    'user_profiles.first_name'
                                                ],
                                            )
                                        "
                                    />
                                    <p
                                        v-if="
                                            form.errors[
                                                'user_profiles.first_name'
                                            ]
                                        "
                                        class="text-red-500 text-xs mt-1"
                                    >
                                        {{
                                            form.errors[
                                                "user_profiles.first_name"
                                            ]
                                        }}
                                    </p>
                                </div>

                                <div>
                                    <label
                                        class="block text-xs font-semibold text-gray-500 uppercase tracking-wide mb-1.5"
                                    >
                                        Last Name
                                        <span class="text-red-500">*</span>
                                    </label>
                                    <input
                                        v-model="form.user_profiles.last_name"
                                        :class="
                                            inputClass(
                                                form.errors[
                                                    'user_profiles.last_name'
                                                ],
                                            )
                                        "
                                    />
                                    <p
                                        v-if="
                                            form.errors[
                                                'user_profiles.last_name'
                                            ]
                                        "
                                        class="text-red-500 text-xs mt-1"
                                    >
                                        {{
                                            form.errors[
                                                "user_profiles.last_name"
                                            ]
                                        }}
                                    </p>
                                </div>
                            </div>

                            <div>
                                <label
                                    class="block text-xs font-semibold text-gray-500 uppercase tracking-wide mb-1.5"
                                >
                                    Middle Name
                                </label>
                                <input
                                    v-model="form.user_profiles.middle_name"
                                    :class="
                                        inputClass(
                                            form.errors[
                                                'user_profiles.middle_name'
                                            ],
                                        )
                                    "
                                />
                                <p
                                    v-if="
                                        form.errors['user_profiles.middle_name']
                                    "
                                    class="text-red-500 text-xs mt-1"
                                >
                                    {{
                                        form.errors["user_profiles.middle_name"]
                                    }}
                                </p>
                            </div>

                            <div>
                                <label
                                    class="block text-xs font-semibold text-gray-500 uppercase tracking-wide mb-1.5"
                                >
                                    Contact Number
                                </label>
                                <input
                                    v-model="form.user_profiles.contact_number"
                                    :class="
                                        inputClass(
                                            form.errors[
                                                'user_profiles.contact_number'
                                            ],
                                        )
                                    "
                                />
                                <p
                                    v-if="
                                        form.errors[
                                            'user_profiles.contact_number'
                                        ]
                                    "
                                    class="text-red-500 text-xs mt-1"
                                >
                                    {{
                                        form.errors[
                                            "user_profiles.contact_number"
                                        ]
                                    }}
                                </p>
                            </div>
                        </template>
                    </div>

                    <!-- RIGHT: Permission overrides / name details -->
                    <div
                        class="w-full md:w-[56%] p-6 overflow-y-auto bg-gray-50/60"
                    >
                        <template v-if="mode === 'edit'">
                            <label
                                class="block text-xs font-semibold text-gray-500 uppercase tracking-wide mb-3"
                            >
                                Permission Overrides
                            </label>

                            <div
                                class="flex flex-wrap gap-3 mb-3 text-xs text-gray-500"
                            >
                                <span class="flex items-center gap-1.5">
                                    <span
                                        class="w-3 h-3 rounded-sm bg-green-100 border border-green-400 inline-block"
                                    ></span>
                                    From role
                                </span>
                                <span class="flex items-center gap-1.5">
                                    <span
                                        class="w-3 h-3 rounded-sm bg-red-100 border border-red-400 inline-block"
                                    ></span>
                                    Revoked
                                </span>
                                <span class="flex items-center gap-1.5">
                                    <span
                                        class="w-3 h-3 rounded-sm bg-blue-100 border border-blue-400 inline-block"
                                    ></span>
                                    Extra
                                </span>
                                <span class="flex items-center gap-1.5">
                                    <span
                                        class="w-3 h-3 rounded-sm bg-gray-100 border border-gray-300 inline-block"
                                    ></span>
                                    Not assigned
                                </span>
                            </div>

                            <div
                                v-if="allPermissions.length === 0"
                                class="text-sm text-gray-400 italic"
                            >
                                No permissions defined yet.
                            </div>

                            <div
                                class="space-y-2.5 max-h-[30rem] overflow-y-auto pr-1"
                            >
                                <div
                                    v-for="(
                                        modulePerms, moduleKey
                                    ) in groupedPermissions"
                                    :key="moduleKey"
                                    class="rounded-lg border border-gray-200 overflow-hidden bg-white"
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
                                                    isModuleFullySelected(
                                                        modulePerms,
                                                    )
                                                        ? 'bg-[#005740] border-[#005740]'
                                                        : isModulePartiallySelected(
                                                                modulePerms,
                                                            )
                                                          ? 'bg-[#005740]/15 border-[#005740]/50'
                                                          : 'bg-white border-gray-300',
                                                ]"
                                            >
                                                <i
                                                    v-if="
                                                        isModuleFullySelected(
                                                            modulePerms,
                                                        )
                                                    "
                                                    class="fa-solid fa-check text-white text-[10px]"
                                                ></i>
                                                <i
                                                    v-else-if="
                                                        isModulePartiallySelected(
                                                            modulePerms,
                                                        )
                                                    "
                                                    class="fa-solid fa-minus text-[#005740] text-[9px]"
                                                ></i>
                                            </span>
                                            <span
                                                class="text-sm font-semibold text-[#1f2d27]"
                                            >
                                                {{
                                                    moduleLabels[moduleKey] ??
                                                    moduleKey
                                                }}
                                            </span>
                                        </span>
                                        <span class="text-[11px] text-gray-400">
                                            {{
                                                modulePerms.filter((p) =>
                                                    isSelected(p.name),
                                                ).length
                                            }}/{{ modulePerms.length }}
                                        </span>
                                    </button>

                                    <!-- Permission toggles -->
                                    <div
                                        class="grid grid-cols-2 gap-1.5 px-3.5 py-3"
                                    >
                                        <button
                                            v-for="perm in modulePerms"
                                            :key="perm.id"
                                            type="button"
                                            @click="togglePermission(perm.name)"
                                            :class="[
                                                'flex items-center justify-between w-full text-left px-2.5 py-1.5 rounded-md text-xs border transition-colors duration-150',
                                                permissionState(perm.name) ===
                                                'granted'
                                                    ? 'bg-green-50 border-green-300 text-green-800'
                                                    : permissionState(
                                                            perm.name,
                                                        ) === 'revoked'
                                                      ? 'bg-red-50 border-red-300 text-red-700 line-through'
                                                      : permissionState(
                                                              perm.name,
                                                          ) === 'given'
                                                        ? 'bg-blue-50 border-blue-300 text-blue-800'
                                                        : 'bg-white border-gray-200 text-gray-500',
                                            ]"
                                        >
                                            <span class="truncate">{{
                                                perm.name
                                            }}</span>
                                            <span
                                                class="text-xs font-medium ml-2 shrink-0"
                                            >
                                                <template
                                                    v-if="
                                                        permissionState(
                                                            perm.name,
                                                        ) === 'granted'
                                                    "
                                                >
                                                    <i
                                                        class="fa-solid fa-check text-green-600"
                                                    ></i>
                                                </template>
                                                <template
                                                    v-else-if="
                                                        permissionState(
                                                            perm.name,
                                                        ) === 'revoked'
                                                    "
                                                >
                                                    <i
                                                        class="fa-solid fa-ban text-red-500"
                                                    ></i>
                                                </template>
                                                <template
                                                    v-else-if="
                                                        permissionState(
                                                            perm.name,
                                                        ) === 'given'
                                                    "
                                                >
                                                    <i
                                                        class="fa-solid fa-plus text-blue-500"
                                                    ></i>
                                                </template>
                                                <template v-else>
                                                    <i
                                                        class="fa-regular fa-circle text-gray-400"
                                                    ></i>
                                                </template>
                                            </span>
                                        </button>
                                    </div>
                                </div>
                            </div>

                            <p class="text-xs text-gray-400 mt-2">
                                Click a permission to toggle. Role permissions
                                can be revoked; others can be granted
                                individually.
                            </p>
                        </template>

                        <!-- For create mode: name/contact fields, with notice at the bottom -->
                        <template v-else>
                            <div class="space-y-4">
                                <div class="grid grid-cols-2 gap-3">
                                    <div>
                                        <label
                                            class="block text-xs font-semibold text-gray-500 uppercase tracking-wide mb-1.5"
                                        >
                                            First Name
                                            <span class="text-red-500">*</span>
                                        </label>
                                        <input
                                            v-model="
                                                form.user_profiles.first_name
                                            "
                                            :class="
                                                inputClass(
                                                    form.errors[
                                                        'user_profiles.first_name'
                                                    ],
                                                )
                                            "
                                        />
                                        <p
                                            v-if="
                                                form.errors[
                                                    'user_profiles.first_name'
                                                ]
                                            "
                                            class="text-red-500 text-xs mt-1"
                                        >
                                            {{
                                                form.errors[
                                                    "user_profiles.first_name"
                                                ]
                                            }}
                                        </p>
                                    </div>

                                    <div>
                                        <label
                                            class="block text-xs font-semibold text-gray-500 uppercase tracking-wide mb-1.5"
                                        >
                                            Last Name
                                            <span class="text-red-500">*</span>
                                        </label>
                                        <input
                                            v-model="
                                                form.user_profiles.last_name
                                            "
                                            :class="
                                                inputClass(
                                                    form.errors[
                                                        'user_profiles.last_name'
                                                    ],
                                                )
                                            "
                                        />
                                        <p
                                            v-if="
                                                form.errors[
                                                    'user_profiles.last_name'
                                                ]
                                            "
                                            class="text-red-500 text-xs mt-1"
                                        >
                                            {{
                                                form.errors[
                                                    "user_profiles.last_name"
                                                ]
                                            }}
                                        </p>
                                    </div>
                                </div>

                                <div>
                                    <label
                                        class="block text-xs font-semibold text-gray-500 uppercase tracking-wide mb-1.5"
                                    >
                                        Middle Name
                                    </label>
                                    <input
                                        v-model="form.user_profiles.middle_name"
                                        :class="
                                            inputClass(
                                                form.errors[
                                                    'user_profiles.middle_name'
                                                ],
                                            )
                                        "
                                    />
                                    <p
                                        v-if="
                                            form.errors[
                                                'user_profiles.middle_name'
                                            ]
                                        "
                                        class="text-red-500 text-xs mt-1"
                                    >
                                        {{
                                            form.errors[
                                                "user_profiles.middle_name"
                                            ]
                                        }}
                                    </p>
                                </div>

                                <div>
                                    <label
                                        class="block text-xs font-semibold text-gray-500 uppercase tracking-wide mb-1.5"
                                    >
                                        Contact Number
                                    </label>
                                    <input
                                        v-model="
                                            form.user_profiles.contact_number
                                        "
                                        :class="
                                            inputClass(
                                                form.errors[
                                                    'user_profiles.contact_number'
                                                ],
                                            )
                                        "
                                    />
                                    <p
                                        v-if="
                                            form.errors[
                                                'user_profiles.contact_number'
                                            ]
                                        "
                                        class="text-red-500 text-xs mt-1"
                                    >
                                        {{
                                            form.errors[
                                                "user_profiles.contact_number"
                                            ]
                                        }}
                                    </p>
                                </div>
                            </div>
                        </template>
                    </div>
                </div>

                <!-- Footer -->
                <div
                    class="flex justify-end gap-3 px-6 py-4 border-t border-gray-100 flex-shrink-0"
                >
                    <button
                        type="button"
                        @click="closeWithAnimation"
                        class="px-6 py-2.5 rounded-lg text-sm font-medium text-gray-600 hover:bg-gray-100 transition-colors"
                    >
                        Cancel
                    </button>
                    <button
                        type="submit"
                        :disabled="form.processing"
                        class="bg-gradient-to-r from-[#005740] to-[#00795a] text-white px-8 py-2.5 rounded-lg text-sm font-semibold hover:shadow-md hover:from-[#00432f] hover:to-[#006548] transition-all disabled:opacity-60"
                    >
                        {{ mode === "edit" ? "Confirm" : "Add" }}
                    </button>
                </div>
            </form>
        </div>
    </div>
</template>
