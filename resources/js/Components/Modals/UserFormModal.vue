<script setup>
import { ref, watch } from "vue";
import { useForm } from "@inertiajs/vue3";
import Toast from "primevue/toast";
import { useToast } from "primevue/usetoast";
import MultiSelect from "primevue/multiselect";

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
  permissions: [],
});

watch(
  () => props.user,
  (val) => {
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

    const userPermissionNames = val.permissions?.map(p => p.name) ?? [];
    const userRole = props.roles.find(r => r.name === form.role);
    const rolePermissionNames = userRole?.permissions?.map(p => p.name) ?? [];
    const allPermissionNames = [...new Set([...userPermissionNames, ...rolePermissionNames])];

    form.permissions = props.permissions.filter(p =>
      allPermissionNames.includes(p.name)
    );
  },
  { immediate: true }
);

watch(
  () => form.role,
  (newRole) => {
    if (!newRole) {
      form.permissions = [];
      return;
    }

    const selectedRole = props.roles.find(r => r.name === newRole);
    const rolePermissionNames = selectedRole?.permissions?.map(p => p.name) ?? [];

    form.permissions = props.permissions.filter(p =>
      rolePermissionNames.includes(p.name)
    );
  }
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
  const url = props.mode === 'edit'
    ? route('user_management.update', form.id)
    : route('user_management.store');

  const method = props.mode === 'edit' ? 'put' : 'post';

  form
    .transform((data) => ({
      ...data,
      permissions: data.permissions.map((p) =>
        typeof p === 'object' ? p.name : p
      ),
    }))
  [method](url, {
    onSuccess: () => {
      toast.add({
        severity: 'success',
        summary: props.mode === 'edit' ? 'Updated' : 'Created',
        detail: props.mode === 'edit'
          ? 'User updated successfully.'
          : 'User added successfully.',
        life: 3000,
      });
      if (props.mode !== 'edit') form.reset();
      emit('submit');
      emit('close');
    },
    onError: (errors) => {
      const firstError = Object.values(errors)[0];

      toast.add({
        severity: 'error',
        summary: 'Validation Failed',
        detail: firstError,
        life: 4000,
      });
    },
  });
}
</script>

<template>
  <div class="fixed inset-0 bg-black/40 flex items-center justify-center p-4">

    <div :class="[
      'bg-white rounded-lg w-full max-w-lg p-6 overflow-y-auto max-h-[90vh]',
      isClosing ? 'animate-pop-out' : 'animate-pop-in'
    ]">

      <h3 class="text-2xl font-bold text-[#850038] mb-6">
        {{ mode === 'edit' ? 'Edit User' : 'Add User' }}
      </h3>

      <Toast />

      <form @submit.prevent="submit">

        <div class="space-y-4">

          <div>
            <label class="block text-sm font-bold mb-1">
              Email
            </label>
            <input v-model="form.email" type="email" placeholder="Email" :class="[
              'w-full rounded-md px-3 py-3 text-[#3B3B3B] bg-[#F8F8F8] text-sm focus:ring-1 focus:outline-none',
              form.errors.email
                ? 'border-red-500 focus:ring-red-500 focus:border-red-500'
                : 'border-gray-300 focus:ring-[#850038] focus:border-[#850038]',
            ]" />
            <div v-if="form.errors.email" class="text-red-500 text-sm">
              {{ form.errors.email }}
            </div>
          </div>

          <!-- Password -->
          <div>
            <label class="block text-sm font-bold mb-1">
              Password <span v-if="mode === 'edit'" class="text-gray-400 font-normal">(leave blank to keep
                current)</span>
            </label>
            <input v-model="form.password" type="password" placeholder="Password" :class="[
              'w-full rounded-md px-3 py-3 text-[#3B3B3B] bg-[#F8F8F8] text-sm focus:ring-1 focus:outline-none',
              form.errors.password
                ? 'border-red-500 focus:ring-red-500 focus:border-red-500'
                : 'border-gray-300 focus:ring-[#850038] focus:border-[#850038]',
            ]" />
            <div v-if="form.errors.password" class="text-red-500 text-sm">
              {{ form.errors.password }}
            </div>
          </div>

          <!-- Status -->
          <div>
            <label class="block text-sm font-bold mb-1">Status</label>
            <select v-model="form.status" :class="[
              'w-full rounded-md px-3 py-3 text-[#3B3B3B] bg-[#F8F8F8] text-sm focus:ring-1 focus:outline-none',
              form.errors.status
                ? 'border-red-500 focus:ring-red-500 focus:border-red-500'
                : 'border-gray-300 focus:ring-[#850038] focus:border-[#850038]',
            ]">
              <option :value="1">Active</option>
              <option :value="0">Inactive</option>
            </select>
            <div v-if="form.errors.status" class="text-red-500 text-sm">
              {{ form.errors.status }}
            </div>
          </div>

          <div>
            <label class="block text-sm font-bold mb-1">
              First Name
            </label>
            <input v-model="form.user_profiles.first_name" :class="[
              'w-full rounded-md px-3 py-3 text-[#3B3B3B] bg-[#F8F8F8] text-sm focus:ring-1 focus:outline-none',
              form.errors['user_profiles.first_name']
                ? 'border-red-500 focus:ring-red-500 focus:border-red-500'
                : 'border-gray-300 focus:ring-[#850038] focus:border-[#850038]',
            ]" />
            <div v-if="form.errors['user_profiles.first_name']" class="text-red-500 text-sm">
              {{ form.errors['user_profiles.first_name'] }}
            </div>
          </div>

          <div>
            <label class="block text-sm font-bold mb-1">
              Last Name
            </label>
            <input v-model="form.user_profiles.last_name" :class="[
              'w-full rounded-md px-3 py-3 text-[#3B3B3B] bg-[#F8F8F8] text-sm focus:ring-1 focus:outline-none',
              form.errors['user_profiles.last_name']
                ? 'border-red-500 focus:ring-red-500 focus:border-red-500'
                : 'border-gray-300 focus:ring-[#850038] focus:border-[#850038]',
            ]" />
            <div v-if="form.errors['user_profiles.last_name']" class="text-red-500 text-sm">
              {{ form.errors['user_profiles.last_name'] }}
            </div>
          </div>

          <div>
            <label class="block text-sm font-bold mb-1">
              Middle Name
            </label>
            <input v-model="form.user_profiles.middle_name" :class="[
              'w-full rounded-md px-3 py-3 text-[#3B3B3B] bg-[#F8F8F8] text-sm focus:ring-1 focus:outline-none',
              form.errors['user_profiles.middle_name']
                ? 'border-red-500 focus:ring-red-500 focus:border-red-500'
                : 'border-gray-300 focus:ring-[#850038] focus:border-[#850038]',
            ]" />
            <div v-if="form.errors['user_profiles.middle_name']" class="text-red-500 text-sm">
              {{ form.errors['user_profiles.middle_name'] }}
            </div>
          </div>

          <div>
            <label class="block text-sm font-bold mb-1">
              Contact Number
            </label>
            <input v-model="form.user_profiles.contact_number" :class="[
              'w-full rounded-md px-3 py-3 text-[#3B3B3B] bg-[#F8F8F8] text-sm focus:ring-1 focus:outline-none',
              form.errors['user_profiles.contact_number']
                ? 'border-red-500 focus:ring-red-500 focus:border-red-500'
                : 'border-gray-300 focus:ring-[#850038] focus:border-[#850038]',
            ]" />
            <div v-if="form.errors['user_profiles.contact_number']" class="text-red-500 text-sm">
              {{ form.errors['user_profiles.contact_number'] }}
            </div>
          </div>

          <div>
            <label class="block text-sm font-bold mb-1">
              Role
            </label>
            <select v-model="form.role" :class="[
              'w-full rounded-md px-3 py-3 text-[#3B3B3B] bg-[#F8F8F8] text-sm focus:ring-1 focus:outline-none',
              form.errors.role
                ? 'border-red-500 focus:ring-red-500 focus:border-red-500'
                : 'border-gray-300 focus:ring-[#850038] focus:border-[#850038]',
            ]">
              <option value="">Select Role</option>
              <option v-for="role in roles" :key="role.id" :value="role.name">
                {{ role.name }}
              </option>
            </select>
            <div v-if="form.errors.role" class="text-red-500 text-sm">
              {{ form.errors.role }}
            </div>
          </div>

          <div>
            <label class="block text-sm font-bold mb-1">
              Permissions
            </label>
            <MultiSelect v-model="form.permissions" :options="permissions" optionLabel="name" display="chip" filter
              placeholder="Select Permissions" />
            <div v-if="form.errors.permissions" class="text-red-500 text-sm">
              {{ form.errors.permissions }}
            </div>
          </div>

        </div>

        <div class="flex justify-end gap-3 mt-6">
          <button type="button" @click="closeWithAnimation" class="border px-6 py-3 rounded-full">
            Cancel
          </button>
          <button type="submit" class="bg-green-700 text-white px-8 py-3 rounded-full">
            {{ mode === 'edit' ? 'Confirm' : 'Add' }}
          </button>
        </div>

      </form>

    </div>

  </div>
</template>