import './bootstrap';
import '../css/app.css';
import { createApp, h } from 'vue';
import { createInertiaApp } from '@inertiajs/vue3';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';

const inertiaRoot = document.querySelector('#app[data-page]');

if (inertiaRoot) {
    createInertiaApp({
        title: (title) => (title ? `${title} | ESCALL Perú` : 'ESCALL Perú'),
        resolve: (name) => resolvePageComponent(
            `./Pages/${name}.vue`,
            import.meta.glob('./Pages/**/*.vue'),
        ),
        setup({ el, App, props, plugin }) {
            return createApp({ render: () => h(App, props) })
                .use(plugin)
                .mount(el);
        },
        progress: {
            color: '#155EEF',
            showSpinner: false,
        },
    });
} else {
    document.addEventListener('DOMContentLoaded', () => {
        const button = document.getElementById('mobile-menu-btn');
        const sidebar = document.getElementById('sidebar');
        const overlay = document.getElementById('mobile-overlay');

        if (!button || !sidebar || !overlay) {
            return;
        }

        const toggleMenu = () => {
            const closed = sidebar.classList.contains('-translate-x-full');
            sidebar.classList.toggle('-translate-x-full', !closed);
            overlay.classList.toggle('opacity-0', !closed);
            overlay.classList.toggle('pointer-events-none', !closed);
        };

        button.addEventListener('click', toggleMenu);
        overlay.addEventListener('click', toggleMenu);
    });
}
