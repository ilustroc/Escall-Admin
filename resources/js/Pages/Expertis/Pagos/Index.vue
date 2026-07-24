<script setup>
import { computed, reactive } from 'vue';
import { router } from '@inertiajs/vue3';
import AppBadge from '../../../Components/AppBadge.vue';
import AppButton from '../../../Components/AppButton.vue';
import AppInput from '../../../Components/AppInput.vue';
import AppPagination from '../../../Components/AppPagination.vue';
import AppSelect from '../../../Components/AppSelect.vue';
import AppTable from '../../../Components/AppTable.vue';
import PageHeader from '../../../Components/PageHeader.vue';
import StatCard from '../../../Components/StatCard.vue';
import AppLayout from '../../../Layouts/AppLayout.vue';

const props = defineProps({
    pagos: { type: Object, required: true },
    resumen: { type: Object, required: true },
    filtros: { type: Object, default: () => ({}) },
    opciones: { type: Object, required: true },
});

const filters = reactive({
    fecha_inicio: props.filtros.fecha_inicio ?? '',
    fecha_fin: props.filtros.fecha_fin ?? '',
    cuenta: props.filtros.cuenta ?? '',
    dni: props.filtros.dni ?? '',
    ejecutivo: props.filtros.ejecutivo ?? '',
    tipo_acuerdo: props.filtros.tipo_acuerdo ?? '',
    recaudo: props.filtros.recaudo ?? '',
    monto_min: props.filtros.monto_min ?? '',
    monto_max: props.filtros.monto_max ?? '',
    sort: props.filtros.sort ?? 'fecha',
    direction: props.filtros.direction ?? 'desc',
    per_page: props.filtros.per_page ?? 25,
});
let debounceTimer;

const columns = [
    { key: 'fecha', label: 'Fecha' },
    { key: 'cuenta', label: 'Cuenta' },
    { key: 'dni', label: 'DNI' },
    { key: 'monto', label: 'Monto' },
    { key: 'ejecutivo', label: 'Ejecutivo' },
    { key: 'tipo_acuerdo', label: 'Tipo de acuerdo' },
    { key: 'recaudo', label: 'Recaudo' },
    { key: 'cartera_relacionada', label: 'Cartera' },
    { key: 'tiene_gestion_previa', label: 'Gestión previa' },
    { key: 'archivo_origen', label: 'Archivo origen' },
    { key: 'created_at', label: 'Fecha de carga' },
];

const money = (value) => new Intl.NumberFormat('es-PE', { style: 'currency', currency: 'PEN' }).format(Number(value || 0));
const exportBase = computed(() => {
    const params = new URLSearchParams();
    Object.entries(filters).forEach(([key, value]) => {
        if (value !== '' && value !== null) params.set(key, value);
    });
    return `/expertis/pagos/export?${params}`;
});

function applyFilters() {
    router.get('/expertis/pagos', filters, {
        preserveState: true,
        preserveScroll: true,
        replace: true,
        only: ['pagos', 'resumen', 'filtros'],
    });
}

function debouncedFilter() {
    clearTimeout(debounceTimer);
    debounceTimer = setTimeout(applyFilters, 450);
}

function clearFilters() {
    Object.assign(filters, {
        fecha_inicio: '', fecha_fin: '', cuenta: '', dni: '', ejecutivo: '',
        tipo_acuerdo: '', recaudo: '', monto_min: '', monto_max: '',
        sort: 'fecha', direction: 'desc', per_page: 25,
    });
    applyFilters();
}
</script>

<template>
    <AppLayout
        title="Pagos Expertis"
        subtitle="Pagos deduplicados y relación con la gestión válida más reciente."
        :breadcrumbs="['Analítica', 'Tablas', 'Pagos Expertis']"
    >
        <PageHeader
            title="Pagos registrados"
            description="Consulta pagos importados y registros individuales con la misma regla de deduplicación."
        >
            <template #actions>
                <AppButton href="/expertis/importaciones/pagos?modo=manual">
                    Registrar pago manual
                </AppButton>
                <AppButton href="/expertis/importaciones/pagos" variant="secondary">
                    Importar XLSX
                </AppButton>
            </template>
        </PageHeader>

        <div class="mb-5 grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
            <StatCard label="Monto total" :value="money(resumen.monto_total)" tone="success" />
            <StatCard label="Cantidad de pagos" :value="resumen.cantidad_pagos" tone="info" />
            <StatCard label="Clientes únicos" :value="resumen.clientes_unicos" />
            <StatCard label="Pago promedio" :value="money(resumen.pago_promedio)" tone="warning" />
        </div>

        <section class="mb-5 rounded-2xl border border-slate-200 bg-white p-4 shadow-card">
            <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4 xl:grid-cols-6">
                <AppInput v-model="filters.fecha_inicio" type="date" label="Fecha inicio" @change="applyFilters" />
                <AppInput v-model="filters.fecha_fin" type="date" label="Fecha fin" @change="applyFilters" />
                <AppInput v-model="filters.cuenta" label="Cuenta" placeholder="Buscar cuenta" @input="debouncedFilter" />
                <AppInput v-model="filters.dni" label="DNI" placeholder="Buscar DNI" @input="debouncedFilter" />
                <AppSelect v-model="filters.ejecutivo" label="Ejecutivo" :options="opciones.ejecutivos" @change="applyFilters" />
                <AppSelect v-model="filters.tipo_acuerdo" label="Tipo de acuerdo" :options="opciones.tipos_acuerdo" @change="applyFilters" />
                <AppSelect v-model="filters.recaudo" label="Recaudo" :options="opciones.recaudos" @change="applyFilters" />
                <AppInput v-model="filters.monto_min" type="number" step="0.01" label="Monto mínimo" @input="debouncedFilter" />
                <AppInput v-model="filters.monto_max" type="number" step="0.01" label="Monto máximo" @input="debouncedFilter" />
                <AppSelect
                    v-model="filters.sort"
                    label="Ordenar por"
                    :options="[
                        { value: 'fecha', label: 'Fecha' },
                        { value: 'cuenta', label: 'Cuenta' },
                        { value: 'monto', label: 'Monto' },
                        { value: 'ejecutivo', label: 'Ejecutivo' },
                        { value: 'created_at', label: 'Fecha de carga' },
                    ]"
                    @change="applyFilters"
                />
                <AppSelect
                    v-model="filters.direction"
                    label="Dirección"
                    :options="[
                        { value: 'desc', label: 'Descendente' },
                        { value: 'asc', label: 'Ascendente' },
                    ]"
                    @change="applyFilters"
                />
                <AppSelect v-model="filters.per_page" label="Filas" :options="[25, 50, 100, 250]" @change="applyFilters" />
            </div>
            <div class="mt-4 flex flex-wrap justify-between gap-3 border-t border-slate-100 pt-4">
                <AppButton variant="ghost" @click="clearFilters">Limpiar filtros</AppButton>
                <div class="flex gap-2">
                    <a :href="`${exportBase}&formato=csv`" class="rounded-lg border border-slate-300 bg-white px-3 py-2 text-xs font-bold text-slate-700 hover:bg-slate-50">CSV</a>
                    <a :href="`${exportBase}&formato=xlsx`" class="rounded-lg bg-escall-600 px-3 py-2 text-xs font-bold text-white hover:bg-escall-700">Exportar XLSX</a>
                </div>
            </div>
        </section>

        <AppTable :columns="columns" :rows="pagos.data">
            <template #cell-cuenta="{ value }"><strong class="text-[#172033]">{{ value }}</strong></template>
            <template #cell-monto="{ value }"><strong class="text-[#12B76A]">{{ money(value) }}</strong></template>
            <template #cell-recaudo="{ value }"><AppBadge tone="info">{{ value ?? '—' }}</AppBadge></template>
            <template #cell-tiene_gestion_previa="{ value }"><AppBadge :tone="Number(value) ? 'success' : 'warning'">{{ Number(value) ? 'Sí' : 'No' }}</AppBadge></template>
            <template #cell-archivo_origen="{ value }"><span class="block max-w-[220px] truncate text-xs">{{ value ?? '—' }}</span></template>
            <template #cell-created_at="{ value }">{{ value ? new Date(value).toLocaleString('es-PE') : '—' }}</template>
        </AppTable>
        <AppPagination v-bind="pagos" />
    </AppLayout>
</template>
