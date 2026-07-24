import { computed } from 'vue';

export function usePagination(paginator) {
    const rows = computed(() => paginator.value?.data ?? []);
    const meta = computed(() => ({
        links: paginator.value?.links ?? [],
        from: paginator.value?.from ?? 0,
        to: paginator.value?.to ?? 0,
        total: paginator.value?.total ?? 0,
    }));
    return { rows, meta };
}
