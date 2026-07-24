<script setup>
import { computed } from 'vue';
import { Head } from '@inertiajs/vue3';
import AppButton from '../../Components/AppButton.vue';
import DateRangeFilter from '../../Components/DateRangeFilter.vue';
import ExportButton from '../../Components/ExportButton.vue';
import FilterPanel from '../../Components/FilterPanel.vue';
import MetricCard from '../../Components/MetricCard.vue';
import PageHeader from '../../Components/PageHeader.vue';
import { useFilters } from '../../Composables/useFilters';
import AppLayout from '../../Layouts/AppLayout.vue';

const props = defineProps({ filtros: { type: Object, required: true }, total: { type: Number, required: true } });
const { filters, apply, clean } = useFilters(props.filtros, '/reportes/kp-invest');
const exportUrl = computed(() => `/reportes/kp-invest/export?${new URLSearchParams(clean()).toString()}`);
</script>

<template>
    <Head title="Reporte KP Invest" />
    <AppLayout title="Reporte KP Invest" subtitle="Gestiones mapeadas al catálogo KP Invest." :breadcrumbs="['Analítica', 'Reportes', 'KP Invest']">
        <PageHeader title="Gestiones KP Invest" description="La pantalla y el exportador reutilizan exactamente la misma consulta.">
            <template #actions><ExportButton :href="exportUrl" label="Exportar XLSX" /></template>
        </PageHeader>
        <FilterPanel>
            <DateRangeFilter v-model:from="filters.fi" v-model:to="filters.ff" />
            <template #actions><AppButton @click="apply()">Consultar</AppButton></template>
        </FilterPanel>
        <div class="max-w-sm"><MetricCard label="Gestiones encontradas" :value="total" hint="Filas que incluirá el XLSX" tone="info" /></div>
    </AppLayout>
</template>
