<script setup>
defineProps({
    email: { type: String, default: '' },
    reason: { type: String, default: '' },
});
</script>

<template>
    <div class="min-h-screen bg-slate-100 px-4 py-8 flex items-center justify-center">
        <section class="w-full max-w-xl rounded-3xl border border-slate-200 bg-white p-8 text-center shadow-xl">
            <img src="/images/UPC-LOGO.png" alt="UP Cebu" class="mx-auto h-24 w-32 object-contain" />
            <div class="mt-6 inline-flex rounded-full bg-amber-100 px-4 py-2 text-sm font-bold text-amber-900">
                SAML access not enabled
            </div>
            <h1 class="mt-5 text-3xl font-bold text-slate-950">User not found in Inventory</h1>
            <p class="mt-4 leading-7 text-slate-600">
                Your OnePortal sign-in succeeded, but this email is not registered in the UP Cebu Inventory system. Contact an Inventory administrator to request access.
            </p>
            <div v-if="email" class="mt-6 rounded-2xl bg-slate-50 px-4 py-3 text-sm font-semibold text-slate-900 break-all">
                {{ email }}
            </div>
            <p v-if="reason === 'inactive'" class="mt-4 text-sm font-semibold text-red-700">
                This Inventory account exists, but it is inactive. Ask an administrator to reactivate it.
            </p>
            <a href="/login" class="mt-8 inline-flex rounded-full bg-[#005740] px-6 py-3 text-sm font-bold text-white hover:bg-[#003f30]">
                Back to Login
            </a>
        </section>
    </div>
</template>
