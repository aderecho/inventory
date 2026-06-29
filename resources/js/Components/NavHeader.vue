<script setup>
import { Link } from "@inertiajs/vue3";

const emit = defineEmits(["toggleSidebar"]);
</script>

<template>
    <div>
        <nav>
            <div class="flex bg-[#005740] h-auto border-t-4 border-[#0E6021] items-center gap-3">
                <div class="ml-6">
                    <button @click="$emit('toggleSidebar')">
                        <svg xmlns="http://www.w3.org/2000/svg"
                            class="h-[40px] w-[37px] text-white hover:text-gray-300 text-semibold" fill="none"
                            viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16" />
                        </svg>
                    </button>
                </div>
                <div>
                    <Link><img class="h-24 w-24" src="/images/uplogo-1.png" alt="Logo" /></Link>
                </div>
            </div>
        </nav>
    </div>
</template>
