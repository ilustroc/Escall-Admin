import { reactive } from 'vue';
import { router } from '@inertiajs/vue3';

export function useFilters(initial = {}, route = '') {
    const filters = reactive({ ...initial });

    function clean(values = filters) {
        return Object.fromEntries(
            Object.entries(values).filter(([, value]) => value !== '' && value !== null && value !== undefined),
        );
    }

    function apply(options = {}) {
        router.get(route, clean(), {
            preserveState: true,
            preserveScroll: true,
            replace: true,
            ...options,
        });
    }

    function reset(defaults = {}) {
        Object.keys(filters).forEach((key) => delete filters[key]);
        Object.assign(filters, defaults);
        apply();
    }

    return { filters, clean, apply, reset };
}
