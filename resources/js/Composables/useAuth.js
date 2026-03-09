import { usePage } from '@inertiajs/vue3'
import { computed } from 'vue'

export function useAuth() {
    const page = usePage()

    const role = computed(() => page.props.auth?.role ?? null)
    const permissions = computed(() => page.props.auth?.permissions ?? [])

    const isAdmin = computed(() => role.value === 'admin')
    const isStaff = computed(() => role.value === 'staff')
    const can = (permission) => permissions.value.includes(permission)

    return { role, isAdmin, isStaff, can }
}