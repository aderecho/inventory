<script setup>
import { Link } from "@inertiajs/vue3";

const props = defineProps({
    user: {
        type: Object,
        default: () => ({}),
    },
});
</script>

<template>
    <nav class="bg-white border-b border-gray-200 relative font-['Poppins']">
        <div class="max-w-7xl mx-auto px-8">
            <div class="flex items-center justify-between py-2">

                <!-- Left: Brand -->
                <div class="flex items-center gap-3">
                    <div class="flex items-center justify-center flex-shrink-0">
                        <img
                            src="/images/uplogo-1.png"
                            alt="Logo"
                            class="w-[70px] h-[70px] object-contain"
                        />
                    </div>

                    <div>
                        <h1
                            class="text-[15px] font-bold text-[#850038] leading-tight"
                            style="font-family: Palatino, 'Palatino Linotype', 'Book Antiqua', Georgia, serif;"
                        >
                            Inventory Management System
                        </h1>
                        <p class="text-[11px] text-gray-400 leading-tight">
                            User Portal
                        </p>
                    </div>
                </div>

                <!-- Right: Nav Links + User + Logout -->
                <div class="flex items-center gap-6">

                    <!-- Divider -->
                    <div class="w-px h-5 bg-gray-200"></div>

                    <!-- User Info -->
                    <div class="text-right">
                        <p class="text-[13px] font-semibold text-gray-800 leading-tight">
                            {{ user?.email ?? "N/A" }}
                        </p>
                        <p class="text-[11px] text-gray-400 leading-tight mt-0.5">
                            {{ user?.user_profiles?.contact_number ?? "N/A" }}
                        </p>
                    </div>

                    <!-- Logout -->
                    <Link
                        :href="route('logout')"
                        method="post"
                        as="button"
                        class="px-4 py-2 bg-[#850038] text-white text-[13px] font-medium rounded-lg hover:bg-[#6a002d] transition-colors duration-150"
                    >
                        Logout
                    </Link>
                </div>

            </div>
        </div>

        <!-- Bottom Accent Line -->
        <div class="absolute bottom-0 left-0 right-0 h-[3px] bg-[#850038]"></div>
    </nav>
</template>