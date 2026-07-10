<script setup>
import { ref } from "vue";
import { useForm } from "@inertiajs/vue3";
import AuthLayout from "@/Layouts/AuthLayout.vue";
import { Mail, Lock } from "lucide-vue-next";

const form = useForm({
    email: "",
    password: "",
    remember: false,
});

const showPassword = ref(false);

function handleSubmit() {
    form.post(route("login"), {
        onFinish: () => form.reset("password"),
    });
}

function handleGoogle() {
    window.location.href = route("auth.google");
}
</script>

<template>
    <AuthLayout
        badge="University of the Philippines Cebu"
        title="Inventory Management System."
        description="Manage UP Cebu's Inventory Management System Efficiently."
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

            <form
                class="w-full flex flex-col gap-[18px]"
                @submit.prevent="handleSubmit"
            >
                <div class="flex flex-col gap-2">
                    <label
                        class="text-[13px] font-medium text-gray-700"
                        for="email"
                    >
                        Email
                    </label>
                    <div class="relative flex items-center">
                        <Mail
                            class="absolute left-3.5 w-[18px] h-[18px] text-gray-400 pointer-events-none"
                            :stroke-width="1.8"
                        />
                        <input
                            id="email"
                            v-model="form.email"
                            type="email"
                            autocomplete="username"
                            placeholder="Email"
                            class="w-full py-3 pr-3.5 pl-[42px] rounded-lg border border-gray-400 outline-none box-border transition-colors duration-150 focus:outline-none focus:ring-0 focus:border-[#0E6021]"
                            :class="{ 'border-red-400': form.errors.email }"
                            @input="form.clearErrors('email')"
                        />
                    </div>
                    <p v-if="form.errors.email" class="text-xs text-red-600">
                        {{ form.errors.email }}
                    </p>
                </div>

                <div class="flex flex-col gap-2">
                    <label
                        class="text-[13px] font-medium text-gray-700"
                        for="password"
                    >
                        Password
                    </label>
                    <div class="relative flex items-center">
                        <Lock
                            class="absolute left-3.5 w-[18px] h-[18px] text-gray-400 pointer-events-none"
                            :stroke-width="1.8"
                        />
                        <input
                            id="password"
                            v-model="form.password"
                            :type="showPassword ? 'text' : 'password'"
                            autocomplete="current-password"
                            placeholder="Enter your password"
                            class="w-full py-3 pr-3.5 pl-[42px] rounded-lg border border-gray-400 outline-none box-border transition-colors duration-150 focus:outline-none focus:ring-0 focus:border-[#0E6021]"
                            :class="{ 'border-red-400': form.errors.password }"
                            @input="form.clearErrors('password')"
                        />
                        <button
                            type="button"
                            class="absolute right-3 flex items-center justify-center border-0 bg-transparent p-1 cursor-pointer text-gray-400"
                            :aria-label="
                                showPassword ? 'Hide password' : 'Show password'
                            "
                            @click="showPassword = !showPassword"
                        >
                            <svg
                                v-if="showPassword"
                                class="w-[18px] h-[18px]"
                                viewBox="0 0 24 24"
                                fill="none"
                                stroke="currentColor"
                                stroke-width="1.8"
                            >
                                <path d="M3 3l18 18" />
                                <path d="M10.6 10.6a2 2 0 0 0 2.8 2.8" />
                                <path
                                    d="M9.5 5.1A10.7 10.7 0 0 1 12 5c5 0 9 4 10 7-0.5 1.4-1.5 3-3 4.3M6.2 6.2C3.6 7.9 1.8 10.4 1 12c1 3 5 7 11 7 1.3 0 2.5-0.2 3.6-0.6"
                                />
                            </svg>
                            <svg
                                v-else
                                class="w-[18px] h-[18px]"
                                viewBox="0 0 24 24"
                                fill="none"
                                stroke="currentColor"
                                stroke-width="1.8"
                            >
                                <path
                                    d="M1 12s4-7 11-7 11 7 11 7-4 7-11 7-11-7-11-7z"
                                />
                                <circle cx="12" cy="12" r="3" />
                            </svg>
                        </button>
                    </div>
                    <p v-if="form.errors.password" class="text-xs text-red-600">
                        {{ form.errors.password }}
                    </p>
                </div>

                <!-- <label
                    class="flex items-center gap-2 text-[13px] text-gray-600 select-none"
                >
                    <input
                        v-model="form.remember"
                        type="checkbox"
                        class="rounded border-gray-400 text-[#1e4d2b] focus:ring-[#0E6021]"
                    />
                    Remember me
                </label> -->

                <button
                    type="submit"
                    class="mt-1.5 py-[13px] border-0 rounded-full bg-[#005740] text-white text-sm font-medium tracking-wide cursor-pointer transition-colors duration-150 ease-in-out hover:not-disabled:bg-[#005740] disabled:opacity-70 disabled:cursor-not-allowed"
                    :disabled="form.processing"
                >
                    {{ form.processing ? "Signing in…" : "Sign in" }}
                </button>
            </form>

            <div
                class="flex items-center justify-center w-full my-5 text-gray-400 text-xs font-semibold uppercase"
            >
                <div class="flex-1 h-px bg-gray-200"></div>
                <span class="px-3">or</span>
                <div class="flex-1 h-px bg-gray-200"></div>
            </div>

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
        </div>
    </AuthLayout>
</template>
