<script setup>
import { computed } from 'vue';
import { Head } from '@inertiajs/vue3';
import AppButton from '../../Components/AppButton.vue';
import AppSelect from '../../Components/AppSelect.vue';
import DateRangeFilter from '../../Components/DateRangeFilter.vue';
import ExportButton from '../../Components/ExportButton.vue';
import FilterPanel from '../../Components/FilterPanel.vue';
import MetricCard from '../../Components/MetricCard.vue';
import PageHeader from '../../Components/PageHeader.vue';
import { useFilters } from '../../Composables/useFilters';
import AppLayout from '../../Layouts/AppLayout.vue';

const props = defineProps({ filtros: { type: Object, required: true }, total: { type: Number, required: true } });
const { filters, apply, clean } = useFilters(props.filtros, '/reportes/impulse');
const exportUrl = computed(() => `/reportes/impulse/export?${new URLSearchParams(clean()).toString()}`);
</script>

<template>
    <Head title="Reporte Impulse" />
    <AppLayout title="Reporte Impulse" subtitle="Gestiones de cartera propia ESCALL." :breadcrumbs="['Analítica', 'Reportes', 'Impulse']">
        <PageHeader title="Gestiones Impulse" description="Equipo 2 excluye BCP y equipo 3 incluye únicamente BCP.">
            <template #actions><ExportButton :href="exportUrl" label="Exportar XLSX" /></template>
        </PageHeader>
        <FilterPanel>
            <DateRangeFilter v-model:from="filters.fi" v-model:to="filters.ff" />
            <AppSelect v-model="filters.equipo" label="Equipo" :options="[{ value: 2, label: 'Propia 2 - ESCALL' }, { value: 3, label: 'Propia 3 - ESCALL (BCP)' }]" placeholder="Equipo" />
            <template #actions><AppButton @click="apply()">Consultar</AppButton></template>
        </FilterPanel>
        <div class="max-w-sm"><MetricCard label="Gestiones encontradas" :value="total" hint="Filas que incluirá el XLSX" tone="info" /></div>
    </AppLayout>
</template>
