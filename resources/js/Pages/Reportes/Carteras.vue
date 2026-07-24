<script setup>
import { computed } from 'vue';
import { Head } from '@inertiajs/vue3';
import AppButton from '../../Components/AppButton.vue';
import AppInput from '../../Components/AppInput.vue';
import ExportButton from '../../Components/ExportButton.vue';
import FilterPanel from '../../Components/FilterPanel.vue';
import MetricCard from '../../Components/MetricCard.vue';
import PageHeader from '../../Components/PageHeader.vue';
import { useFilters } from '../../Composables/useFilters';
import AppLayout from '../../Layouts/AppLayout.vue';

const props = defineProps({
    filtros: { type: Object, required: true },
    resumen: { type: Object, required: true },
});
const { filters, apply, clean } = useFilters(props.filtros, '/reportes/carteras');
const query = computed(() => new URLSearchParams(clean()).toString());
const links = computed(() => ({
    data: `/reportes/carteras/export-data-xlsx?${query.value}`,
    asignacion: `/reportes/carteras/export-tec?${query.value}`,
    dataTec: `/reportes/data-tec-center?${query.value}`,
    gestionesTec: `/reportes/tec-center-data?${query.value}`,
}));
</script>

<template>
    <Head title="Reportes de carteras" />
    <AppLayout title="Reportes de carteras" subtitle="Entregables operativos por mes, etiqueta y lote." :breadcrumbs="['Analítica', 'Reportes', 'Carteras']">
        <PageHeader title="Carteras y TEC Center" description="Conserva los formatos y nombres de archivo requeridos por cada destino." />
        <FilterPanel>
            <AppInput v-model="filters.mes" type="month" label="Mes" />
            <AppInput v-model="filters.tag" label="Etiqueta del reporte" />
            <AppInput v-model="filters.lote" label="Lote de asignación" />
            <template #actions><AppButton @click="apply()">Actualizar indicadores</AppButton></template>
        </FilterPanel>
        <div class="mb-6 grid gap-4 sm:grid-cols-3">
            <MetricCard label="Clientes en DATA" :value="resumen.clientes" />
            <MetricCard label="Gestiones del mes" :value="resumen.gestiones_mes" tone="info" />
            <MetricCard label="Carteras activas" :value="resumen.carteras.length" />
        </div>
        <section class="grid gap-4 md:grid-cols-2">
            <article class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm"><h2 class="font-bold">Reporte general de DATA</h2><p class="mb-4 mt-1 text-sm text-slate-500">Carteras para el mes y etiqueta seleccionados.</p><ExportButton :href="links.data" label="Descargar XLSX" /></article>
            <article class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm"><h2 class="font-bold">Asignación TEC Center</h2><p class="mb-4 mt-1 text-sm text-slate-500">Formato de agencias externas para el lote indicado.</p><ExportButton :href="links.asignacion" label="Descargar asignación" /></article>
            <article class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm"><h2 class="font-bold">DATA TEC Center</h2><p class="mb-4 mt-1 text-sm text-slate-500">Detalle mensual de la cartera TEC Center.</p><ExportButton :href="links.dataTec" label="Descargar DATA" /></article>
            <article class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm"><h2 class="font-bold">Gestiones TEC Center</h2><p class="mb-4 mt-1 text-sm text-slate-500">Archivo de gestiones con el formato operativo existente.</p><ExportButton :href="links.gestionesTec" label="Descargar gestiones" /></article>
        </section>
    </AppLayout>
</template>
