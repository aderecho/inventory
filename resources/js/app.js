import '../css/app.css'
import './bootstrap'

import { createInertiaApp } from '@inertiajs/vue3'
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers'
import { createApp, h } from 'vue'
import { ZiggyVue } from '../../vendor/tightenco/ziggy'
import SessionTimeoutWarning from './Components/SessionTimeoutWarning.vue'

import PrimeVue from 'primevue/config'
import ToastService from 'primevue/toastservice'
import Aura from '@primevue/themes/aura'
import 'primeicons/primeicons.css'

// Search and filter values are no longer persisted. Remove any keys written by
// the previous implementation so stale filters stop coming back on page load.
try {
    Object.keys(window.localStorage)
        .filter((key) => key.startsWith('filters-'))
        .forEach((key) => window.localStorage.removeItem(key))
} catch (error) {
    // Ignore: localStorage can be unavailable (private browsing, blocked storage).
}

createInertiaApp({
    resolve: (name) =>
        resolvePageComponent(
            `./Pages/${name}.vue`,
            import.meta.glob('./Pages/**/*.vue'),
        ),

    setup({ el, App, props, plugin }) {
        // The session warning is mounted next to the page instead of inside a
        // layout, so every module (and any future one) gets the same timers from
        // one shared watcher rather than one per layout.
        return createApp({
            render: () => [h(App, props), h(SessionTimeoutWarning)],
        })
            .use(plugin)
            .use(ZiggyVue)
            .use(ToastService)
            .use(PrimeVue, {
                theme: {
                    preset: Aura,
                    options: {
                        darkModeSelector: false // ✅ Force Light Mode Only
                    }
                },
                ripple: true
            })
            .mount(el)
    },

    progress: {
        color: '#4B5563',
    },
})
