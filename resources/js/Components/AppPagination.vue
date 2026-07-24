<script setup>
import { Link } from '@inertiajs/vue3';

defineProps({
    links: { type: Array, default: () => [] },
    from: { type: Number, default: 0 },
    to: { type: Number, default: 0 },
    total: { type: Number, default: 0 },
});
</script>

<template>
    <div v-if="links.length" class="mt-4 flex flex-col items-center justify-between gap-3 sm:flex-row">
        <p class="text-xs text-slate-500">Mostrando {{ from || 0 }}–{{ to || 0 }} de {{ total || 0 }}</p>
        <nav class="flex flex-wrap items-center gap-1" aria-label="Paginación">
            <template v-for="(link, index) in links" :key="index">
                <Link
                    v-if="link.url"
                    :href="link.url"
                    preserve-scroll
                    class="min-w-9 rounded-lg border px-2.5 py-2 text-center text-xs font-semibold transition"
                    :class="link.active
                        ? 'border-escall-600 bg-escall-600 text-white'
                        : 'border-slate-200 bg-white text-slate-600 hover:border-escall-300 hover:text-escall-600'"
                    v-html="link.label"
                />
                <span
                    v-else
                    class="min-w-9 rounded-lg border border-slate-100 bg-slate-50 px-2.5 py-2 text-center text-xs text-slate-300"
                    v-html="link.label"
                />
            </template>
        </nav>
    </div>
</template>
