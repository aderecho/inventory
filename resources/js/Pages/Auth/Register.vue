    <script setup>
import AuthLayout from "@/Layouts/AuthLayout.vue";
import InputError from "@/Components/InputError.vue";
import InputLabel from "@/Components/InputLabel.vue";
import TestButton from "@/Components/Buttons/TestButton.vue";
import TextInput from "@/Components/TextInput.vue";
import SuccessModal from "@/Components/Modals/SuccessModal.vue";
import { Head, Link, useForm, router } from "@inertiajs/vue3";
import { ref } from "vue";

const showSuccessModal = ref(false);

const form = useForm({
  first_name: "",
  last_name: "",
  middle_name: "",
  contact_number: "",
  email: "",
  password: "",
  password_confirmation: "",

  role_id: 1,
});

const submit = () => {
  form.post(route("store"), {
    onSuccess: () => {
      showSuccessModal.value = true;
      form.reset("password", "password_confirmation");
    },
  });
};
</script>

    <template>
  <AuthLayout>
    <Head title="Register" />

    <form @submit.prevent="submit">
      <div class="px-8 py-6">
        <div class="text-center mb-8">
          <h1 class="font-bold text-2xl text-[#332B2B]">Create Account</h1>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-2 md:gap-4">
          <div>
            <InputLabel for="first-name" value="First Name" />
            <TextInput
              id="first-name"
              type="text"
              class="mt-1 block w-full text-sm"
              v-model="form.first_name"
              placeholder="First Name"
              required
              autofocus
              autocomplete="given-name"
            />
            <InputError class="mt-2" :message="form.errors.first_name" />
          </div>

          <div>
            <InputLabel for="last-name" value="Last Name" />
            <TextInput
              id="last-name"
              type="text"
              class="mt-1 block w-full text-sm"
              v-model="form.last_name"
              placeholder="Last Name"
              required
              autocomplete="family-name"
            />
            <InputError class="mt-2" :message="form.errors.last_name" />
          </div>

          <div>
            <InputLabel for="middle-name" value="Middle Name" />
            <TextInput
              id="middle-name"
              type="text"
              class="mt-1 block w-full text-sm"
              v-model="form.middle_name"
              placeholder="Middle Name"
              autocomplete="additional-name"
            />
            <InputError class="mt-2" :message="form.errors.middle_name" />
          </div>

          <div>
            <InputLabel for="contact-number" value="Contact Number" />
            <TextInput
              id="contact-number"
              type="tel"
              class="mt-1 block w-full text-sm"
              v-model="form.contact_number"
              placeholder="Contact Number"
              required
              autocomplete="tel"
            />
            <InputError class="mt-2" :message="form.errors.contact_number" />
          </div>
        </div>

        <div class="mt-4">
          <InputLabel for="email" value="Email" />

          <TextInput
            id="email"
            type="email"
            class="mt-1 block w-full text-sm"
            v-model="form.email"
            placeholder="Email"
            required
            autocomplete="username"
          />

          <InputError class="mt-2" :message="form.errors.email" />
        </div>

        <div class="mt-4">
          <InputLabel for="password" value="Password" />

          <TextInput
            id="password"
            type="password"
            class="mt-1 block w-full text-sm"
            v-model="form.password"
            placeholder="Password"
            required
            autocomplete="new-password"
          />

          <InputError class="mt-2" :message="form.errors.password" />
        </div>

        <div class="mt-4 mb-8">
          <InputLabel for="password_confirmation" value="Confirm Password" />

          <TextInput
            id="password_confirmation"
            type="password"
            class="mt-1 block w-full text-sm"
            v-model="form.password_confirmation"
            placeholder="Confirm Password"
            required
            autocomplete="new-password"
          />

          <InputError
            class="mt-2"
            :message="form.errors.password_confirmation"
          />
        </div>

        <div class="mt-4 flex flex-col gap-4 items-center">
          <TestButton
            class="w-full justify-center text-sm font-bold py-4 bg-[#850038] text-white text-bold hover:bg-[#6e002f] focus:ring-[#850038]"
            :class="{ 'opacity-25': form.processing }"
            :disabled="form.processing"
          >
            Register
          </TestButton>
          <p class="mt-4 items-center text-[#A6A6A6] justify-center flex gap-2">
            Already have an account?
            <Link
              href="/login"
              class="text-[#850038] text-sm no-underline hover:underline focus:outline-none"
            >
              Login
            </Link>
          </p>
        </div>
      </div>
    </form>
  </AuthLayout>

  <SuccessModal
    v-if="showSuccessModal"
    title="Account Created"
    message="Your account has been successfully created. You can now log in."
    buttonConfirm="Go to Login"
    icon='<i class="fa-solid fa-circle-check text-6xl text-green-500"></i>'
    @close="() => router.visit(route('login'))"
  />
 
</template>
