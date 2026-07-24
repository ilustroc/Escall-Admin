<script setup>
import { QueueListIcon } from '@heroicons/vue/24/outline';
defineProps({
    title: { type: String, default: 'Sin información' },
    description: { type: String, default: 'No hay registros para los filtros seleccionados.' },
});
</script>

<template>
    <div class="rounded-2xl border border-dashed border-slate-300 bg-white px-6 py-12 text-center">
        <span class="mx-auto flex h-12 w-12 items-center justify-center rounded-2xl bg-slate-100 text-slate-500">
            <QueueListIcon class="h-6 w-6" />
        </span>
        <h3 class="mt-4 text-sm font-bold text-[#172033]">{{ title }}</h3>
        <p class="mx-auto mt-1 max-w-md text-xs leading-5 text-slate-500">{{ description }}</p>
        <div v-if="$slots.default" class="mt-5"><slot /></div>
    </div>
</template>
