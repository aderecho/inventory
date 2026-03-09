<script setup>
import Logo from "@/Components/Logo.vue";
import { Link } from "@inertiajs/vue3";
</script>

<template>
  <div class="flex flex-col md:flex-row min-h-screen">
    <div
      class="hidden md:flex md:w-1/2 bg-[#E3E3E3] items-center justify-center"
    >
      <div class="flex flex-col items-center justify-center h-full p-12">
        <Logo />
        <h1 class="mt-6 text-3xl font-bold text-center">
          Welcome to UP Inventory
        </h1>
        <p class="mt-4 font-medium text-center">
          Manage your inventory efficiently and effectively with our
          user-friendly platform.
        </p>
      </div>
    </div>
    <div
      class="w-full md:w-1/2 bg-[#850038] h-screen md:rounded-tl-2xl md:rounded-bl-2xl flex items-center justify-center p-6 md:p-12"
    >
      <div
        class="w-full sm:w-[90%] md:w-[85%] lg:w-[80%] xl:w-[75%] max-w-2xl bg-white shadow-md rounded-lg"
      >
        <slot />
      </div>
    </div>
  </div>
</template>
