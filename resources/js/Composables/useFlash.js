import { computed } from 'vue';
import { usePage } from '@inertiajs/vue3';

export function useFlash() {
    const page = usePage();
    return computed(() => {
        const flash = page.props.flash ?? {};
        for (const tone of ['error', 'warning', 'info', 'success']) {
            if (flash[tone]) return { tone, message: flash[tone] };
        }
        return null;
    });
}
