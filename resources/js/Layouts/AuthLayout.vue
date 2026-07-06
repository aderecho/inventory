<template>
    <div
        class="relative min-h-screen w-full flex items-center justify-center p-6 font-sans box-border overflow-hidden"
    >
        <img
            :src="backgroundImage"
            alt=""
            class="absolute inset-0 w-full h-full object-cover object-center -z-10"
        />

        <div
            class="absolute inset-0 -z-10 pointer-events-none"
            style="
                background-image: linear-gradient(
                    to top,
                    rgba(0, 87, 64, 0.5) 0%,
                    rgba(0, 87, 64, 0.5) 30%,
                    transparent 100%
                );
            "
        />

        <!-- Soft blurred aurora blob for ambient depth -->
        <div
            class="absolute -top-1/4 left-1/2 -translate-x-1/2 w-[900px] h-[900px] rounded-full bg-[#1E4D2B]/40 blur-[120px] pointer-events-none"
        />

        <div
            class="relative z-[1] grid grid-cols-1 md:grid-cols-2 w-full max-w-[980px] min-h-[560px] rounded-3xl overflow-hidden shadow-[0_30px_60px_-20px_rgba(0,0,0,0.45)]"
        >
            <!-- Left / brand panel - hidden on mobile and tablet -->
            <aside
                class="hidden md:flex relative flex-col gap-7 p-9 md:p-11 text-white bg-white/10 backdrop-blur-xl backdrop-saturate-150 border border-white/25 border-l-0 md:border-l shadow-[inset_0_1px_0_rgba(255,255,255,0.15)]"
            >
                <span
                    class="self-start inline-flex items-center gap-2 py-2 px-4 rounded-full text-[14px] font-medium text-white bg-white/10 border border-white/25"
                >
                    <span class="relative flex w-3 h-3 shrink-0">
                        <span
                            class="animate-ping absolute inline-flex h-full w-full rounded-full bg-[#FFAD0D] opacity-75"
                        />
                        <span
                            class="relative inline-flex w-3 h-3 rounded-full bg-[#FFAD0D]"
                        />
                    </span>
                    {{ badge }}
                </span>

                <div
                    class="flex-1 flex flex-col items-left justify-center gap-3.5 text-left"
                >
                    <h1
                        class="m-0 text-[36px] leading-[1.2] font-bold tracking-[-0.01em]"
                    >
                        {{ title }}
                    </h1>
                    <p
                        class="m-0 max-w-[34ch] text-[16px] leading-[1.55] text-white"
                    >
                        {{ description }}
                    </p>
                </div>

                <ul
                    v-if="tags.length"
                    class="list-none flex flex-wrap justify-left gap-3 mt-auto mb-0 p-0"
                >
                    <li
                        v-for="tag in tags"
                        :key="tag"
                        class="py-3 px-5 rounded-full text-sm font-semibold text-white bg-white/[0.14] border border-white/[0.18]"
                    >
                        {{ tag }}
                    </li>
                </ul>
            </aside>

            <!-- Right / form panel - full width on mobile/tablet -->
            <section
                class="bg-white flex items-center justify-center p-9 md:p-10 col-span-1 md:col-span-1"
            >
                <slot />
            </section>
        </div>
    </div>
</template>

<script setup>
defineProps({
    badge: {
        type: String,
        default: "",
    },
    title: {
        type: String,
        default: "",
    },
    description: {
        type: String,
        default: "",
    },
    tags: {
        type: Array,
        default: () => [],
    },
    backgroundImage: {
        type: String,
        default: "/images/image (7).png",
    },
});
</script>
