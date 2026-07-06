<script setup>
import { ref, watch } from "vue";
import { useForm } from "@inertiajs/vue3";
import Toast from "primevue/toast";
import { useToast } from "primevue/usetoast";

const toast = useToast();

const props = defineProps({
  show: Boolean,
  user: Object,
});

const emit = defineEmits(["close"]);

const form = useForm({
  email: "",
  current_password: "",
  password: "",
  password_confirmation: "",
  user_profiles: {
    first_name: "",
    last_name: "",
    middle_name: "",
    contact_number: "",
  },
});

watch(
  () => props.show,
  (isOpen) => {
    if (!isOpen || !props.user) return;

    form.clearErrors();
    form.email = props.user.email ?? "";
    form.current_password = "";
    form.password = "";
    form.password_confirmation = "";
    form.user_profiles.first_name = props.user.user_profiles?.first_name ?? "";
    form.user_profiles.last_name = props.user.user_profiles?.last_name ?? "";
    form.user_profiles.middle_name = props.user.user_profiles?.middle_name ?? "";
    form.user_profiles.contact_number = props.user.user_profiles?.contact_number ?? "";
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
  // Adjust the route name below to match your app's profile update route.
  form.put(route("profile.update"), {
    preserveScroll: true,
    onSuccess: () => {
      toast.add({
        severity: "success",
        summary: "Updated",
        detail: "Your profile has been updated successfully.",
        life: 3000,
      });
      form.current_password = "";
      form.password = "";
      form.password_confirmation = "";
      closeWithAnimation();
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
</script>

<template>
  <div
    v-if="show"
    class="fixed inset-0 bg-black/50 flex items-center justify-center p-4 z-50"
    @click="closeWithAnimation"
  >
    <div
      :class="[
        'bg-white rounded-2xl w-full max-w-3xl shadow-2xl overflow-hidden flex flex-col max-h-[85vh]',
        isClosing ? 'animate-pop-out' : 'animate-pop-in',
      ]"
      @click.stop
    >
      <Toast />

      <!-- Header -->
      <div class="bg-gradient-to-r from-[#003d2c] via-[#005740] to-[#00795a] px-6 py-4 flex items-center justify-between flex-shrink-0">
        <div>
          <h3 class="text-lg font-bold text-white">My Profile</h3>
          <p class="text-xs text-white/70 mt-0.5">Update your account details.</p>
        </div>
        <button
          @click="closeWithAnimation"
          class="text-white/80 hover:text-white hover:bg-white/10 rounded-full h-9 w-9 flex items-center justify-center transition-colors"
          title="Close"
        >
          <i class="fa-solid fa-xmark"></i>
        </button>
      </div>

      <form @submit.prevent="submit" class="flex flex-col flex-1 overflow-hidden">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-x-8 gap-y-4 p-6 overflow-y-auto">

          <!-- LEFT: Account details -->
          <div class="space-y-4">
            <div class="grid grid-cols-2 gap-3">
              <div>
                <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wide mb-1.5">
                  First Name <span class="text-red-500">*</span>
                </label>
                <input v-model="form.user_profiles.first_name" :class="inputClass(form.errors['user_profiles.first_name'])" />
                <p v-if="form.errors['user_profiles.first_name']" class="text-red-500 text-xs mt-1">
                  {{ form.errors["user_profiles.first_name"] }}
                </p>
              </div>

              <div>
                <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wide mb-1.5">
                  Last Name <span class="text-red-500">*</span>
                </label>
                <input v-model="form.user_profiles.last_name" :class="inputClass(form.errors['user_profiles.last_name'])" />
                <p v-if="form.errors['user_profiles.last_name']" class="text-red-500 text-xs mt-1">
                  {{ form.errors["user_profiles.last_name"] }}
                </p>
              </div>
            </div>

            <div class="grid grid-cols-2 gap-3">
              <div>
                <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wide mb-1.5">
                  Middle Name
                </label>
                <input v-model="form.user_profiles.middle_name" :class="inputClass(form.errors['user_profiles.middle_name'])" />
                <p v-if="form.errors['user_profiles.middle_name']" class="text-red-500 text-xs mt-1">
                  {{ form.errors["user_profiles.middle_name"] }}
                </p>
              </div>

              <div>
                <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wide mb-1.5">
                  Contact Number
                </label>
                <input v-model="form.user_profiles.contact_number" :class="inputClass(form.errors['user_profiles.contact_number'])" />
                <p v-if="form.errors['user_profiles.contact_number']" class="text-red-500 text-xs mt-1">
                  {{ form.errors["user_profiles.contact_number"] }}
                </p>
              </div>
            </div>

            <div>
              <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wide mb-1.5">
                Email <span class="text-red-500">*</span>
              </label>
              <input v-model="form.email" type="email" placeholder="Email" :class="inputClass(form.errors.email)" />
              <p v-if="form.errors.email" class="text-red-500 text-xs mt-1">{{ form.errors.email }}</p>
            </div>
          </div>

          <!-- RIGHT: Password change -->
          <div class="space-y-3 md:border-l md:border-gray-100 md:pl-8">
            <p class="text-xs font-semibold text-gray-500 uppercase tracking-wide">
              Change Password <span class="text-gray-400 font-normal normal-case">(optional)</span>
            </p>

            <div>
              <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wide mb-1.5">
                Current Password
              </label>
              <input v-model="form.current_password" type="password" placeholder="Current Password" :class="inputClass(form.errors.current_password)" />
              <p v-if="form.errors.current_password" class="text-red-500 text-xs mt-1">{{ form.errors.current_password }}</p>
            </div>

            <div>
              <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wide mb-1.5">
                New Password
              </label>
              <input v-model="form.password" type="password" placeholder="New Password" :class="inputClass(form.errors.password)" />
              <p v-if="form.errors.password" class="text-red-500 text-xs mt-1">{{ form.errors.password }}</p>
            </div>

            <div>
              <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wide mb-1.5">
                Confirm Password
              </label>
              <input v-model="form.password_confirmation" type="password" placeholder="Confirm Password" :class="inputClass(false)" />
            </div>
          </div>
        </div>

        <!-- Footer -->
        <div class="flex justify-end gap-3 px-6 py-4 border-t border-gray-100 flex-shrink-0">
          <button type="button" @click="closeWithAnimation" class="px-6 py-2.5 rounded-lg text-sm font-medium text-gray-600 hover:bg-gray-100 transition-colors">
            Cancel
          </button>
          <button
            type="submit"
            :disabled="form.processing"
            class="bg-gradient-to-r from-[#005740] to-[#00795a] text-white px-8 py-2.5 rounded-lg text-sm font-semibold hover:shadow-md hover:from-[#00432f] hover:to-[#006548] transition-all disabled:opacity-60"
          >
            Save Changes
          </button>
        </div>
      </form>
    </div>
  </div>
</template>