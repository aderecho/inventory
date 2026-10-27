<script setup>
import { CalendarClock } from "lucide-vue-next";

/**
 * Borrow lines shaped by UserService::presentBorrowedLine. Open loans show
 * their due date; returned ones show when they came back.
 */
defineProps({
    items: { type: Array, default: () => [] },
    returned: { type: Boolean, default: false },
    emptyText: { type: String, default: "Nothing to show." },
});
</script>

<template>
    <div v-if="items.length" class="overflow-x-auto">
        <table class="w-full table-auto border-collapse text-left text-sm">
            <thead class="bg-gray-50 text-[11px] uppercase tracking-wide text-gray-500">
                <tr>
                    <th class="px-4 py-2.5 font-semibold">Item</th>
                    <th class="px-4 py-2.5 font-semibold">Reference</th>
                    <th class="px-4 py-2.5 font-semibold">Borrowed</th>
                    <th class="px-4 py-2.5 font-semibold">{{ returned ? "Returned" : "Due" }}</th>
                    <th class="px-4 py-2.5 font-semibold">Status</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                <tr
                    v-for="line in items"
                    :key="line.id"
                    class="transition-colors hover:bg-gray-50"
                >
                    <td class="px-4 py-2.5">
                        <p class="font-medium text-[#1f2d27]">
                            {{ line.item_name }}
                        </p>
                        <p class="text-[11px] text-gray-400">
                            {{ line.property_number }}
                            <template v-if="line.serial_number">
                                · SN {{ line.serial_number }}
                            </template>
                        </p>
                    </td>
                    <td class="px-4 py-2.5 text-xs font-semibold text-[#005740]">
                        {{ line.reference_code }}
                    </td>
                    <td class="px-4 py-2.5 text-xs text-gray-600">
                        {{ line.borrowed_at }}
                    </td>
                    <td class="px-4 py-2.5 text-xs text-gray-600">
                        <template v-if="returned">{{ line.returned_at }}</template>
                        <span v-else class="inline-flex items-center gap-1.5">
                            <CalendarClock class="h-3.5 w-3.5 text-gray-400" />
                            {{ line.due_at || "No due date" }}
                        </span>
                    </td>
                    <td class="px-4 py-2.5">
                        <span
                            class="text-[11px] font-semibold"
                            :class="
                                returned
                                    ? 'text-[#005740]'
                                    : line.is_overdue
                                      ? 'text-[#D71D1D]'
                                      : 'text-amber-700'
                            "
                        >
                            {{ returned ? "Returned" : line.is_overdue ? "Overdue" : "Borrowed" }}
                        </span>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>

    <p v-else class="px-4 py-10 text-center text-sm text-gray-400">
        {{ emptyText }}
    </p>
</template>
