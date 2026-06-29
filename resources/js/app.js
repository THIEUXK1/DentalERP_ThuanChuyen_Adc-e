import '../css/app.css';
import './bootstrap';

import { createInertiaApp } from '@inertiajs/vue3';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import { createApp, h, KeepAlive } from 'vue';
import { ZiggyVue } from '../../vendor/tightenco/ziggy';

createInertiaApp({
    // Centralise tab title formatting here
    title: (title) => title ? `${title} · Dental ERP` : 'Dental ERP',

    resolve: async (name) => {
        const page = await resolvePageComponent(
            `./Pages/${name}.vue`,
            import.meta.glob('./Pages/**/*.vue'),
        );
        // Give every page component a unique __name so KeepAlive caches each one separately
        // (without this, all Index.vue files share the same cache slot)
        page.default.__name = name.replaceAll('/', '_');
        return page;
    },

    setup({ el, App, props, plugin }) {
        return createApp({
            render: () =>
                h(App, props, {
                    // Wrap the active page in KeepAlive — switching tabs reactivates the cached
                    // component instead of destroying and remounting it
                    default: ({ Component }) =>
                        h(KeepAlive, { max: 12 }, () => h(Component)),
                }),
        })
            .use(plugin)
            .use(ZiggyVue)
            .mount(el);
    },

    progress: { color: '#4B5563' },
});
