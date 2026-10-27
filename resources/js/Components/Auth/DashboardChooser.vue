<script setup>
import { onBeforeUnmount, onMounted, ref } from "vue";
import { motion } from "motion-v";
import {
    ArrowRight,
    ClipboardList,
    GraduationCap,
    LayoutDashboard,
    LoaderCircle,
    ShieldCheck,
    UserRound,
} from "lucide-vue-next";

/**
 * The dashboard picker shown once an account can reach more than one workspace.
 * Cards lift, spot-light under the pointer and open on click, Enter or a number
 * key; the chosen card keeps spinning until Inertia leaves the page.
 */
const props = defineProps({
    options: { type: Array, default: () => [] },
    processing: { type: Boolean, default: false },
    chosen: { type: String, default: "" },
    error: { type: String, default: "" },
    // Staff share the main dashboard; label its card for them.
    staffView: { type: Boolean, default: false },
});

const emit = defineEmits(["choose"]);

const cardsRef = ref(null);

const WORKSPACES = {
    "dashboard.index": {
        eyebrow: "Management",
        title: "Admin Dashboard",
        description:
            "Inventory, accounts, approvals, disposal and the full system view.",
        icon: ShieldCheck,
        tile: "bg-[#850038] text-white",
        accent: "bg-[#850038]",
        hoverBorder: "hover:border-[#850038]/35",
        glow: "rgba(133, 0, 56, 0.10)",
    },
    "user.dashboard": {
        eyebrow: "Personal",
        title: "User Dashboard",
        description:
            "The items assigned to you, their details and your daily workspace.",
        icon: UserRound,
        tile: "bg-[#005740] text-white",
        accent: "bg-[#005740]",
        hoverBorder: "hover:border-[#005740]/35",
        glow: "rgba(0, 87, 64, 0.10)",
    },
    "student.dashboard": {
        eyebrow: "Student",
        title: "Student Dashboard",
        description:
            "The items you have borrowed, their due dates and what you have returned.",
        icon: GraduationCap,
        tile: "bg-[#0E6021] text-white",
        accent: "bg-[#0E6021]",
        hoverBorder: "hover:border-[#0E6021]/35",
        glow: "rgba(14, 96, 33, 0.10)",
    },
};

const FALLBACK = {
    eyebrow: "Workspace",
    title: "Dashboard",
    description: "Continue to this workspace.",
    icon: LayoutDashboard,
    tile: "bg-gray-800 text-white",
    accent: "bg-gray-800",
    hoverBorder: "hover:border-gray-400",
    glow: "rgba(31, 41, 55, 0.10)",
};

const STAFF_WORKSPACE = {
    eyebrow: "Operations",
    title: "Staff Dashboard",
    description:
        "Inventory records, inspections, borrowings and approvals for staff.",
    icon: ClipboardList,
    tile: "bg-[#005740] text-white",
    accent: "bg-[#005740]",
    hoverBorder: "hover:border-[#005740]/35",
    glow: "rgba(0, 87, 64, 0.10)",
};

function workspaceFor(routeName) {
    if (routeName === "dashboard.index" && props.staffView) {
        return STAFF_WORKSPACE;
    }

    return WORKSPACES[routeName] ?? { ...FALLBACK, title: routeName };
}

function pick(routeName) {
    if (props.processing) return;

    emit("choose", routeName);
}

// Follows the pointer so the spotlight sits under the cursor.
function trackPointer(event) {
    const card = event.currentTarget;
    const bounds = card.getBoundingClientRect();

    card.style.setProperty("--spot-x", event.clientX - bounds.left + "px");
    card.style.setProperty("--spot-y", event.clientY - bounds.top + "px");
}

function cardButtons() {
    return Array.from(
        cardsRef.value?.querySelectorAll("button[aria-label]") ?? [],
    );
}

// Keys work without tabbing into the list first: the page has nothing else to
// focus when it opens, so they are handled at the document level.
function handleKeydown(event) {
    if (event.defaultPrevented || event.altKey || event.ctrlKey || event.metaKey) {
        return;
    }

    if (["INPUT", "TEXTAREA", "SELECT"].includes(event.target?.tagName)) {
        return;
    }

    const number = Number(event.key) - 1;

    if (Number.isInteger(number) && number >= 0 && number < props.options.length) {
        event.preventDefault();
        pick(props.options[number]);

        return;
    }

    if (
        !["ArrowDown", "ArrowRight", "ArrowUp", "ArrowLeft"].includes(event.key)
    ) {
        return;
    }

    const cards = cardButtons();

    if (!cards.length) return;

    event.preventDefault();

    const current = cards.indexOf(document.activeElement);
    const forward = event.key === "ArrowDown" || event.key === "ArrowRight";
    const next =
        current === -1 ? 0 : current + (forward ? 1 : -1);

    cards[(next + cards.length) % cards.length]?.focus();
}

onMounted(() => window.addEventListener("keydown", handleKeydown));
onBeforeUnmount(() => window.removeEventListener("keydown", handleKeydown));
</script>

<template>
    <div class="flex w-full flex-col items-center font-sans">
        <img
            class="mb-4 h-[92px] w-[138px] object-contain"
            src="/images/UPC-LOGO.png"
            alt="University of the Philippines Cebu seal"
        />

        <h2 class="m-0 text-2xl font-bold text-[#14210f]">Welcome</h2>
        <p
            class="m-0 mt-1 mb-6 text-center text-[11px] font-semibold uppercase tracking-[0.14em] text-gray-500"
        >
            Choose a dashboard to continue
        </p>

        <p
            v-if="error"
            role="alert"
            class="mb-4 w-full rounded-lg border border-red-200 bg-red-50 px-4 py-2 text-center text-sm font-medium text-red-700"
        >
            {{ error }}
        </p>

        <div ref="cardsRef" class="flex w-full flex-col gap-3">
            <motion.button
                v-for="(option, index) in options"
                :key="option"
                type="button"
                :disabled="processing"
                :aria-label="'Open ' + workspaceFor(option).title"
                @click="pick(option)"
                @mousemove="trackPointer"
                class="group relative w-full overflow-hidden rounded-2xl border bg-white p-4 text-left shadow-sm outline-none transition-colors focus-visible:ring-2 focus-visible:ring-[#005740] focus-visible:ring-offset-2 disabled:cursor-not-allowed"
                :class="[
                    workspaceFor(option).hoverBorder,
                    processing && chosen !== option
                        ? 'border-gray-200 opacity-40'
                        : 'border-gray-200',
                    chosen === option
                        ? 'border-[#005740] ring-1 ring-[#005740]/30'
                        : '',
                ]"
                :initial="{ opacity: 0, y: 16 }"
                :animate="{ opacity: 1, y: 0 }"
                :transition="{
                    delay: 0.05 + index * 0.07,
                    type: 'spring',
                    stiffness: 260,
                    damping: 26,
                }"
                :whileHover="processing ? {} : { y: -3 }"
                :whilePress="processing ? {} : { scale: 0.99 }"
            >
                <span
                    class="pointer-events-none absolute inset-0 opacity-0 transition-opacity duration-200 group-hover:opacity-100"
                    :style="{
                        background:
                            'radial-gradient(200px circle at var(--spot-x, 50%) var(--spot-y, 50%), ' +
                            workspaceFor(option).glow +
                            ', transparent 70%)',
                    }"
                />

                <span
                    class="absolute inset-x-0 top-0 h-[3px] origin-left scale-x-0 transition-transform duration-300 group-hover:scale-x-100"
                    :class="workspaceFor(option).accent"
                />

                <span class="relative flex items-center gap-3">
                    <span
                        class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl transition-transform duration-300 group-hover:-rotate-6 group-hover:scale-105"
                        :class="workspaceFor(option).tile"
                    >
                        <component
                            :is="workspaceFor(option).icon"
                            class="h-5 w-5"
                        />
                    </span>

                    <span class="min-w-0 flex-1">
                        <span
                            class="block text-[10px] font-bold uppercase tracking-[0.12em] text-gray-400"
                        >
                            {{ workspaceFor(option).eyebrow }}
                        </span>
                        <span
                            class="mt-0.5 block text-sm font-semibold text-[#1f2d27]"
                        >
                            {{ workspaceFor(option).title }}
                        </span>
                        <span
                            class="mt-0.5 block text-xs leading-snug text-gray-500"
                        >
                            {{ workspaceFor(option).description }}
                        </span>
                    </span>

                    <span
                        class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full border border-gray-200 text-gray-500 transition-all duration-300 group-hover:border-transparent group-hover:bg-[#005740] group-hover:text-white"
                    >
                        <LoaderCircle
                            v-if="processing && chosen === option"
                            class="h-4 w-4 animate-spin"
                        />
                        <ArrowRight
                            v-else
                            class="h-4 w-4 transition-transform duration-300 group-hover:translate-x-0.5"
                        />
                    </span>
                </span>
            </motion.button>
        </div>

        <p class="mt-5 text-center text-[11px] leading-relaxed text-gray-400">
            Move with <kbd class="font-sans font-semibold text-gray-500">↑</kbd>
            <kbd class="font-sans font-semibold text-gray-500">↓</kbd>, open with
            <kbd class="font-sans font-semibold text-gray-500">Enter</kbd
            ><template v-if="options.length > 1">
                , or press <kbd class="font-sans font-semibold text-gray-500">1</kbd>–<kbd
                    class="font-sans font-semibold text-gray-500"
                    >{{ options.length }}</kbd
                ></template
            >. You can change this later under Settings → Switch workspace.
        </p>
    </div>
</template>
