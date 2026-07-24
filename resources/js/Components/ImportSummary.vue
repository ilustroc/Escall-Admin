<script setup>
import StatCard from './StatCard.vue';

defineProps({
    summary: { type: Object, required: true },
});
</script>

<template>
    <section>
        <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-5">
            <StatCard label="Total" :value="summary.total_filas ?? summary.total ?? 0" />
            <StatCard label="Insertadas" :value="summary.filas_insertadas ?? summary.insertadas ?? 0" tone="success" />
            <StatCard label="Actualizadas" :value="summary.filas_actualizadas ?? summary.actualizadas ?? 0" tone="info" />
            <StatCard label="Duplicadas" :value="summary.filas_duplicadas ?? summary.duplicadas ?? 0" tone="warning" />
            <StatCard label="Errores" :value="summary.filas_error ?? summary.errores ?? 0" tone="danger" />
        </div>
        <div v-if="summary.fecha_minima || summary.fecha_maxima" class="mt-3 rounded-xl bg-slate-50 px-4 py-3 text-xs text-slate-600">
            Rango detectado:
            <strong>{{ summary.fecha_minima ?? '—' }}</strong>
            a
            <strong>{{ summary.fecha_maxima ?? '—' }}</strong>
        </div>
    </section>
</template>
