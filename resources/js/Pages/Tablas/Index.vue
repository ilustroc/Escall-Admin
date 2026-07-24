<script setup>
import { computed } from 'vue';
import { Head } from '@inertiajs/vue3';
import { ArrowDownTrayIcon } from '@heroicons/vue/24/outline';
import AppButton from '../../Components/AppButton.vue';
import AppInput from '../../Components/AppInput.vue';
import AppPagination from '../../Components/AppPagination.vue';
import AppSelect from '../../Components/AppSelect.vue';
import DataTable from '../../Components/DataTable.vue';
import FilterPanel from '../../Components/FilterPanel.vue';
import MetricCard from '../../Components/MetricCard.vue';
import PageHeader from '../../Components/PageHeader.vue';
import SearchInput from '../../Components/SearchInput.vue';
import Tabs from '../../Components/Tabs.vue';
import { useDebouncedSearch } from '../../Composables/useDebouncedSearch';
import { useFilters } from '../../Composables/useFilters';
import AppLayout from '../../Layouts/AppLayout.vue';

const props = defineProps({
    tab: { type: String, required: true },
    filtros: { type: Object, required: true },
    resultado: { type: Object, required: true },
});
const { filters, apply, clean } = useFilters(props.filtros, '/tablas');
const tabs = [
    { label: 'Gestiones', value: 'gestiones', href: '/tablas?tab=gestiones' },
    { label: 'Pagos', value: 'pagos', href: '/tablas?tab=pagos' },
    { label: 'Cartera / Data', value: 'data', href: '/tablas?tab=data' },
];
const columns = computed(() => ({
    gestiones: [
        { key: 'fecha_gestion', label: 'Fecha' }, { key: 'dni', label: 'DNI' },
        { key: 'telefono', label: 'Teléfono' }, { key: 'status', label: 'Contacto' },
        { key: 'tipificacion', label: 'Tipificación' }, { key: 'nombre', label: 'Asesor' },
        { key: 'observacion', label: 'Observación' },
    ],
    pagos: [
        { key: 'fecha', label: 'Fecha' }, { key: 'codigo', label: 'Código' },
        { key: 'dni', label: 'DNI' }, { key: 'nombre', label: 'Cliente' },
        { key: 'cartera', label: 'Cartera' }, { key: 'monto', label: 'Monto' },
        { key: 'asesor', label: 'Asesor' }, { key: 'operacion', label: 'Operación' },
    ],
    data: [
        { key: 'codigo', label: 'Código' }, { key: 'dni', label: 'DNI' },
        { key: 'titular', label: 'Titular' }, { key: 'cartera', label: 'Cartera' },
        { key: 'entidad', label: 'Entidad' }, { key: 'cosecha', label: 'Cosecha' },
        { key: 'producto', label: 'Producto' }, { key: 'deuda_capital', label: 'Capital' },
    ],
}[props.tab]));
const exportUrl = computed(() => {
    const params = { ...clean(), tab: props.tab };
    delete params.search;
    return `/listas/export?${new URLSearchParams(params).toString()}`;
});
const money = (value) => new Intl.NumberFormat('es-PE', { style: 'currency', currency: 'PEN' }).format(Number(value || 0));
const shortDay = (date) => date?.slice(8, 10);

useDebouncedSearch(() => filters.search, () => apply(), 400);
</script>

<template>
    <Head title="Tablas" />
    <AppLayout title="Tablas" subtitle="Indicadores y detalle paginado en servidor." :breadcrumbs="['Analítica', 'Tablas']">
        <PageHeader title="Análisis operativo" description="Consulta matrices agregadas y el detalle sin cargar tablas completas en el navegador.">
            <template #actions><AppButton :href="exportUrl" variant="secondary"><ArrowDownTrayIcon class="h-4 w-4" />Exportar detalle</AppButton></template>
        </PageHeader>
        <Tabs :items="tabs" :active="tab" />
        <FilterPanel>
            <SearchInput v-model="filters.search" placeholder="Código, DNI, cliente o asesor…" />
            <AppInput v-if="tab !== 'data'" v-model="filters.mes" type="month" label="Mes" />
            <AppInput v-if="tab === 'gestiones'" v-model="filters.week" type="week" label="Semana" />
            <AppSelect v-if="tab !== 'gestiones'" v-model="filters.cartera" label="Cartera" :options="resultado.carteras || ['KP INVEST', 'TEC CENTER', 'IMPULSE']" />
            <AppSelect v-model="filters.per_page" label="Filas" :options="[25, 50, 100, 250]" />
            <AppSelect v-model="filters.direction" label="Orden" :options="[{ value: 'desc', label: 'Descendente' }, { value: 'asc', label: 'Ascendente' }]" placeholder="Orden" />
            <template #actions><AppButton @click="apply()">Actualizar</AppButton></template>
        </FilterPanel>

        <template v-if="tab === 'gestiones'">
            <div class="mb-5 grid gap-4 sm:grid-cols-2">
                <MetricCard label="Gestiones del mes" :value="resultado.total_general" tone="info" />
                <MetricCard label="Asesores con actividad" :value="resultado.matriz.length" />
            </div>
            <section class="mb-6 overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                <header class="border-b border-slate-100 px-5 py-4"><h2 class="font-bold">Gestiones por asesor y día</h2></header>
                <div class="scrollbar-thin overflow-x-auto">
                    <table class="min-w-max text-sm">
                        <thead class="sticky top-0 bg-slate-50"><tr><th class="sticky left-0 bg-slate-50 px-4 py-3 text-left">Asesor</th><th v-for="day in resultado.dias" :key="day" class="px-3 py-3 text-center">{{ shortDay(day) }}</th><th class="px-4 py-3">Total</th></tr></thead>
                        <tbody class="divide-y divide-slate-100"><tr v-for="row in resultado.matriz" :key="row.agente"><td class="sticky left-0 bg-white px-4 py-3 font-semibold">{{ row.agente }}</td><td v-for="day in resultado.dias" :key="day" class="px-3 py-3 text-center">{{ row.dias[day] || 0 }}</td><td class="px-4 py-3 text-center font-bold">{{ row.total }}</td></tr></tbody>
                    </table>
                </div>
            </section>
            <section class="mb-6 grid gap-5 xl:grid-cols-3">
                <article v-for="wallet in resultado.semana.carteras" :key="wallet.nombre" class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                    <header class="border-b border-slate-100 px-5 py-4"><h3 class="font-bold">{{ wallet.etiqueta }}</h3><p class="text-xs text-slate-500">Semana {{ resultado.semana.valor }}</p></header>
                    <div class="scrollbar-thin max-h-80 overflow-auto">
                        <table class="min-w-full text-xs"><thead class="sticky top-0 bg-slate-50"><tr><th class="px-3 py-2 text-left">Rango</th><th v-for="label in resultado.semana.etiquetas" :key="label" class="px-2 py-2">{{ label.split(' ')[0] }}</th><th class="px-2 py-2">Total</th></tr></thead><tbody><tr v-for="row in wallet.rangos" :key="row.etiqueta" class="border-t border-slate-100"><td class="whitespace-nowrap px-3 py-2">{{ row.etiqueta }}</td><td v-for="day in resultado.semana.dias" :key="day" class="px-2 py-2 text-center">{{ row.dias[day] }}</td><td class="px-2 py-2 text-center font-bold">{{ row.total }}</td></tr></tbody></table>
                    </div>
                </article>
            </section>
        </template>

        <template v-else-if="tab === 'pagos'">
            <div class="mb-5 grid gap-4 sm:grid-cols-2"><MetricCard label="Recaudo del mes" :value="money(resultado.totales.grand_monto)" tone="success" /><MetricCard label="Operaciones" :value="resultado.totales.grand_ops" /></div>
            <section class="mb-6 overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                <div class="scrollbar-thin overflow-x-auto"><table class="min-w-full text-sm"><thead class="bg-slate-50"><tr><th class="px-4 py-3 text-left">Asesor</th><th v-for="wallet in resultado.carteras" :key="wallet" class="px-4 py-3 text-right">{{ wallet }}</th><th class="px-4 py-3 text-right">Total</th></tr></thead><tbody class="divide-y divide-slate-100"><tr v-for="row in resultado.matriz" :key="row.asesor"><td class="px-4 py-3 font-semibold">{{ row.asesor }}</td><td v-for="wallet in resultado.carteras" :key="wallet" class="px-4 py-3 text-right"><span class="font-semibold">{{ money(row.carteras[wallet]?.monto) }}</span><small class="block text-slate-400">{{ row.carteras[wallet]?.ops || 0 }} ops.</small></td><td class="px-4 py-3 text-right font-bold">{{ money(row.total_monto) }}</td></tr></tbody></table></div>
            </section>
        </template>

        <section>
            <h2 class="mb-3 text-lg font-bold">Detalle</h2>
            <DataTable :columns="columns" :rows="resultado.detalle.data" :row-key="tab === 'data' ? 'codigo' : 'id'">
                <template #cell-monto="{ value }">{{ money(value) }}</template>
                <template #cell-deuda_capital="{ value }">{{ money(value) }}</template>
            </DataTable>
            <AppPagination v-bind="resultado.detalle" />
        </section>
    </AppLayout>
</template>
