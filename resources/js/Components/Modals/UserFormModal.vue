<script setup>
import { ref, watch, computed } from "vue";
import { useForm, router } from "@inertiajs/vue3";
import Toast from "primevue/toast";
import { useToast } from "primevue/usetoast";

const toast = useToast();

const props = defineProps({
  mode: String,
  user: Object,
  roles: Array,
  permissions: Array,
});

const emit = defineEmits(["submit", "close"]);

const form = useForm({
  id: null,
  email: "",
  password: "",
  status: 0,
  user_profiles: {
    first_name: "",
    last_name: "",
    middle_name: "",
    contact_number: "",
  },
  role: null,
});

// ─── Permission override state ─────────────────────────────────────────────
// Names of permissions the user has been explicitly given (beyond role)
const givePermissions = ref([]);
// Names of permissions the user has been explicitly forbidden (despite role)
const revokePermissions = ref([]);

// Derived: which permissions does the currently selected role grant?
const rolePermissionNames = computed(() => {
  if (!form.role) return [];
  const selectedRole = props.roles.find((r) => r.name === form.role);
  return selectedRole?.permissions?.map((p) => p.name) ?? [];
});

// All permission names for iteration
const allPermissions = computed(() => props.permissions ?? []);

// For a given permission, what is its effective state?
// 'granted'  – role gives it, not revoked
// 'revoked'  – role gives it, but user has it forbidden
// 'given'    – role does NOT give it, but user has it explicitly given
// 'none'     – role does not give it, not explicitly given
function permissionState(name) {
  const fromRole = rolePermissionNames.value.includes(name);
  const isGiven = givePermissions.value.includes(name);
  const isRevoked = revokePermissions.value.includes(name);

  if (fromRole && !isRevoked) return "granted";
  if (fromRole && isRevoked) return "revoked";
  if (!fromRole && isGiven) return "given";
  return "none";
}

function togglePermission(name) {
  const state = permissionState(name);
  const fromRole = rolePermissionNames.value.includes(name);

  if (fromRole) {
    // Role-based: toggle between granted ↔ revoked
    if (state === "granted") {
      revokePermissions.value.push(name);
    } else {
      revokePermissions.value = revokePermissions.value.filter((p) => p !== name);
    }
  } else {
    // Non-role: toggle between given ↔ none
    if (state === "given") {
      givePermissions.value = givePermissions.value.filter((p) => p !== name);
    } else {
      givePermissions.value.push(name);
    }
  }
}

// ─── Populate form when editing ────────────────────────────────────────────
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
    form.password = "";
    form.status = val.status ?? 0;
    form.user_profiles.first_name = val.user_profiles?.first_name ?? "";
    form.user_profiles.last_name = val.user_profiles?.last_name ?? "";
    form.user_profiles.middle_name = val.user_profiles?.middle_name ?? "";
    form.user_profiles.contact_number = val.user_profiles?.contact_number ?? "";
    form.role = val.roles?.length ? val.roles[0].name : null;

    // direct_permissions = explicitly given (not via role)
    givePermissions.value = val.direct_permissions?.map((p) =>
      typeof p === "object" ? p.name : p
    ) ?? [];

    // forbidden_permissions = revoked despite role
    revokePermissions.value = val.forbidden_permissions?.map((p) =>
      typeof p === "object" ? p.name : p
    ) ?? [];
  },
  { immediate: true }
);

// When role changes, clear revokes that no longer make sense
watch(
  () => form.role,
  () => {
    // Drop revokes for permissions not in the new role (nothing to revoke)
    revokePermissions.value = revokePermissions.value.filter((name) =>
      rolePermissionNames.value.includes(name)
    );
  }
);

// ─── Helpers ───────────────────────────────────────────────────────────────
const isClosing = ref(false);

function closeWithAnimation() {
  isClosing.value = true;
  setTimeout(() => {
    emit("close");
    isClosing.value = false;
  }, 200);
}

// ─── Submit ────────────────────────────────────────────────────────────────
function submit() {
  const url =
    props.mode === "edit"
      ? route("user_management.update", form.id)
      : route("user_management.store");

  const method = props.mode === "edit" ? "put" : "post";

  // Step 1: save core user data (role included)
  form[method](url, {
    onSuccess: () => {
      // Step 2: if editing, also sync per-user permission overrides
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
          }
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

// ─── Input class helper ────────────────────────────────────────────────────
function inputClass(hasError) {
  return [
    "w-full rounded-md px-3 py-3 text-[#3B3B3B] bg-[#F8F8F8] text-sm focus:ring-1 focus:outline-none border",
    hasError
      ? "border-red-500 focus:ring-red-500 focus:border-red-500"
      : "border-gray-300 focus:ring-[#850038] focus:border-[#850038]",
  ];
}
</script>

<template>
  <div class="fixed inset-0 bg-black/40 flex items-center justify-center p-4 z-50">
    <div
      :class="[
        'bg-white rounded-lg w-full max-w-lg p-6 overflow-y-auto max-h-[90vh]',
        isClosing ? 'animate-pop-out' : 'animate-pop-in',
      ]"
    >
      <h3 class="text-2xl font-bold text-[#850038] mb-6">
        {{ mode === "edit" ? "Edit User" : "Add User" }}
      </h3>

      <Toast />

      <form @submit.prevent="submit">
        <div class="space-y-4">

          <!-- Email -->
          <div>
            <label class="block text-sm font-bold mb-1">Email <span class="text-red-500">*</span></label>
            <input v-model="form.email" type="email" placeholder="Email" :class="inputClass(form.errors.email)" />
            <p v-if="form.errors.email" class="text-red-500 text-xs mt-1">{{ form.errors.email }}</p>
          </div>

          <!-- Password -->
         <div>
          <label class="block text-sm font-bold mb-1">
              Password 
              <span v-if="mode !== 'edit'" class="text-red-500">*</span>
              <span v-if="mode === 'edit'" class="text-gray-400 font-normal">(leave blank to keep current)</span>
          </label>
          <input v-model="form.password" type="password" placeholder="Password" :class="inputClass(form.errors.password)" />
          <p v-if="form.errors.password" class="text-red-500 text-xs mt-1">{{ form.errors.password }}</p>
      </div>

          <!-- Status -->
          <div>
            <label class="block text-sm font-bold mb-1">Status <span class="text-red-500">*</span></label>
            <select v-model="form.status" :class="inputClass(form.errors.status)">
              <option :value="1">Active</option>
              <option :value="0">Inactive</option>
            </select>
            <p v-if="form.errors.status" class="text-red-500 text-xs mt-1">{{ form.errors.status }}</p>
          </div>

          <!-- First Name -->
          <div>
            <label class="block text-sm font-bold mb-1">First Name <span class="text-red-500">*</span></label>
            <input v-model="form.user_profiles.first_name" :class="inputClass(form.errors['user_profiles.first_name'])" />
            <p v-if="form.errors['user_profiles.first_name']" class="text-red-500 text-xs mt-1">
              {{ form.errors["user_profiles.first_name"] }}
            </p>
          </div>

          <!-- Last Name -->
          <div>
            <label class="block text-sm font-bold mb-1">Last Name <span class="text-red-500">*</span></label>
            <input v-model="form.user_profiles.last_name" :class="inputClass(form.errors['user_profiles.last_name'])" />
            <p v-if="form.errors['user_profiles.last_name']" class="text-red-500 text-xs mt-1">
              {{ form.errors["user_profiles.last_name"] }}
            </p>
          </div>

          <!-- Middle Name -->
          <div>
            <label class="block text-sm font-bold mb-1">Middle Name</label>
            <input v-model="form.user_profiles.middle_name" :class="inputClass(form.errors['user_profiles.middle_name'])" />
            <p v-if="form.errors['user_profiles.middle_name']" class="text-red-500 text-xs mt-1">
              {{ form.errors["user_profiles.middle_name"] }}
            </p>
          </div>

          <!-- Contact Number -->
          <div>
            <label class="block text-sm font-bold mb-1">Contact Number</label>
            <input v-model="form.user_profiles.contact_number" :class="inputClass(form.errors['user_profiles.contact_number'])" />
            <p v-if="form.errors['user_profiles.contact_number']" class="text-red-500 text-xs mt-1">
              {{ form.errors["user_profiles.contact_number"] }}
            </p>
          </div>

          <!-- Role -->
          <div>
            <label class="block text-sm font-bold mb-1">Role <span class="text-red-500">*</span></label>
            <select v-model="form.role" :class="inputClass(form.errors.role)">
              <option value="">Select Role</option>
              <option v-for="role in roles" :key="role.id" :value="role.name">
                {{ role.name }}
              </option>
            </select>
            <p v-if="form.errors.role" class="text-red-500 text-xs mt-1">{{ form.errors.role }}</p>
          </div>

          <!-- Permission Overrides (edit mode only) -->
          <div v-if="mode === 'edit'">
            <label class="block text-sm font-bold mb-2">Permission Overrides</label>

            <!-- Legend -->
            <div class="flex flex-wrap gap-3 mb-3 text-xs text-gray-500">
              <span class="flex items-center gap-1">
                <span class="w-3 h-3 rounded-sm bg-green-100 border border-green-400 inline-block"></span>
                From role
              </span>
              <span class="flex items-center gap-1">
                <span class="w-3 h-3 rounded-sm bg-red-100 border border-red-400 inline-block"></span>
                Revoked (role blocked)
              </span>
              <span class="flex items-center gap-1">
                <span class="w-3 h-3 rounded-sm bg-blue-100 border border-blue-400 inline-block"></span>
                Extra (given directly)
              </span>
              <span class="flex items-center gap-1">
                <span class="w-3 h-3 rounded-sm bg-gray-100 border border-gray-300 inline-block"></span>
                Not assigned
              </span>
            </div>

            <div v-if="allPermissions.length === 0" class="text-sm text-gray-400 italic">
              No permissions defined yet.
            </div>

            <div class="grid grid-cols-1 gap-1 max-h-52 overflow-y-auto pr-1">
              <button
                v-for="perm in allPermissions"
                :key="perm.id"
                type="button"
                @click="togglePermission(perm.name)"
                :class="[
                  'flex items-center justify-between w-full text-left px-3 py-2 rounded-md text-sm border transition-colors duration-150',
                  permissionState(perm.name) === 'granted'
                    ? 'bg-green-50 border-green-300 text-green-800'
                    : permissionState(perm.name) === 'revoked'
                    ? 'bg-red-50 border-red-300 text-red-700 line-through'
                    : permissionState(perm.name) === 'given'
                    ? 'bg-blue-50 border-blue-300 text-blue-800'
                    : 'bg-gray-50 border-gray-200 text-gray-500',
                ]"
              >
                <span>{{ perm.name }}</span>
                <span class="text-xs font-medium ml-2 shrink-0">
                  <template v-if="permissionState(perm.name) === 'granted'">
                    <i class="fa-solid fa-check text-green-600"></i> Role
                  </template>
                  <template v-else-if="permissionState(perm.name) === 'revoked'">
                    <i class="fa-solid fa-ban text-red-500"></i> Revoked
                  </template>
                  <template v-else-if="permissionState(perm.name) === 'given'">
                    <i class="fa-solid fa-plus text-blue-500"></i> Extra
                  </template>
                  <template v-else>
                    <i class="fa-regular fa-circle text-gray-400"></i>
                  </template>
                </span>
              </button>
            </div>

            <p class="text-xs text-gray-400 mt-2">
              Click a permission to toggle. Role permissions can be revoked; others can be granted individually.
            </p>
          </div>

          <!-- For create mode: simple permission notice -->
          <div v-else class="rounded-md bg-yellow-50 border border-yellow-200 px-3 py-2 text-xs text-yellow-700">
            <i class="fa-solid fa-circle-info mr-1"></i>
            Per-user permission overrides are available after the user is created.
          </div>

        </div>

        <!-- Actions -->
        <div class="flex justify-end gap-3 mt-6">
          <button type="button" @click="closeWithAnimation" class="border px-6 py-3 rounded-full text-sm">
            Cancel
          </button>
          <button
            type="submit"
            :disabled="form.processing"
            class="bg-green-700 text-white px-8 py-3 rounded-full text-sm disabled:opacity-60"
          >
            {{ mode === "edit" ? "Confirm" : "Add" }}
          </button>
        </div>
      </form>
    </div>
  </div>
</template>