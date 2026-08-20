<script setup>
import { ref, watch, onMounted } from "vue";
import { useForm, usePage } from "@inertiajs/vue3";
import AuthLayout from "@/Layouts/AuthLayout.vue";
import { Mail, Lock } from "lucide-vue-next";
import { useToast } from "primevue/usetoast";

const toast = useToast();
const page = usePage();

const form = useForm({
    email: "",
    password: "",
    remember: false,
});

const showPassword = ref(false);

function showSsoError() {
    const message = page.props.errors?.sso;
    if (message) {
        toast.add({
            severity: "error",
            summary: "Sign-in failed",
            detail: message,
            life: 5000,
        });
    }
}

onMounted(() => {
    showSsoError();
});

// keep the watcher too, for subsequent Inertia visits (non-hard-reload navigations)
watch(() => page.props.errors?.sso, (message) => {
    if (message) {
        toast.add({
            severity: "error",
            summary: "Sign-in failed",
            detail: message,
            life: 5000,
        });
    }
});

function handleSubmit() {
    form.post(route("login"), {
        onFinish: () => form.reset("password"),
    });
}

function handleGoogle() {
    window.location.href = route("auth.google");
}

function handleSaml() {
    window.location.href = route("saml.login");
}
</script>

<template>
    <AuthLayout
        badge="University of the Philippines Cebu"
        title="Inventory Management System"
        description="UP Cebu's Inventory Management System."
        :tags="['Smart', 'Secure', 'System']"
    >
        <div class="flex flex-col items-center w-full max-w-[340px] font-sans">
            <div>
                <img
                    class="w-[150px] h-[100px] object-contain mb-5"
                    src="/images/UPC-LOGO.png"
                    alt="University of the Philippines seal"
                />
            </div>

            <h2
                class="m-0 mb-1 text-2xl font-bold text-[#14210f] text-center"
            >
                Welcome Back
            </h2>
            <p
                class="m-0 mb-8 text-xs font-medium tracking-wider uppercase text-gray-500 text-center"
            >
                UP Cebu Inventory Management System
            </p>

            <div
                v-if="status"
                class="w-full mb-4 text-sm font-medium text-green-700 bg-green-50 border border-green-200 rounded-lg px-4 py-2 text-center"
            >
                {{ status }}
            </div>

            <p v-if="form.errors.sso" class="w-full mb-4 text-sm text-red-700 bg-red-50 border border-red-200 rounded-lg px-4 py-2 text-center">
                {{ form.errors.sso }}
            </p>

            <button
                type="button"
                class="w-full flex items-center justify-center gap-2.5 py-3 rounded-full border border-gray-300 bg-white text-sm font-medium text-gray-700 cursor-pointer transition-colors duration-150 ease-in-out hover:bg-gray-50"
                @click="handleGoogle"
            >
                <img
                    src="https://cdn.jsdelivr.net/gh/glincker/thesvg@main/public/icons/google/default.svg"
                    alt="Google"
                    class="h-6 w-6"
                />
                Continue with Google
            </button>
<!-- 
            <button
                type="button"
                class="mt-3 w-full flex items-center justify-center gap-2.5 py-3 rounded-full border border-[#005740] bg-[#005740] text-sm font-medium text-white cursor-pointer transition-colors duration-150 ease-in-out hover:bg-[#003f30]"
                @click="handleSaml"
            >
                Continue with OnePortal
            </button> -->
        </div>
    </AuthLayout>
</template>
