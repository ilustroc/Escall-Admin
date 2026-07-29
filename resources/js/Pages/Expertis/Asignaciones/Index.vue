<script setup>
import { computed, reactive } from 'vue';
import { router } from '@inertiajs/vue3';
import AppButton from '../../../Components/AppButton.vue';
import AppInput from '../../../Components/AppInput.vue';
import AppPagination from '../../../Components/AppPagination.vue';
import AppSelect from '../../../Components/AppSelect.vue';
import AppTable from '../../../Components/AppTable.vue';
import PageHeader from '../../../Components/PageHeader.vue';
import AppLayout from '../../../Layouts/AppLayout.vue';

const props = defineProps({
    asignaciones: { type: Object, required: true },
    filtros: { type: Object, default: () => ({}) },
    opciones: { type: Object, required: true },
});

const filters = reactive({
    periodo: props.filtros.periodo ?? '',
    documento: props.filtros.documento ?? props.filtros.dni ?? '',
    codigo: props.filtros.codigo ?? '',
    titular: props.filtros.titular ?? '',
    tipo_cartera: props.filtros.tipo_cartera ?? '',
    departamento: props.filtros.departamento ?? '',
    producto: props.filtros.producto ?? '',
    per_page: props.filtros.per_page ?? 50,
});
let debounceTimer;

const columns = [
    { key: 'periodo', label: 'PERIODO' },
    { key: 'empresa', label: 'EMPRESA' },
    { key: 'documento', label: 'DOCUMENTO' },
    { key: 'titular', label: 'TITULAR' },
    { key: 'codigo', label: 'CODIGO' },
    { key: 'tipo_cartera', label: 'TIPO DE CARTERA' },
    { key: 'cosecha', label: 'COSECHA' },
    { key: 'sub_cosecha', label: 'SUB COSECHA' },
    { key: 'producto', label: 'PRODUCTO' },
    { key: 'sub_producto', label: 'SUB_PRODUCTO' },
    { key: 'historico', label: 'HISTORICO' },
    { key: 'departamento', label: 'DEPARTAMENTO' },
    { key: 'deuda_total', label: 'DEUDA TOTAL' },
    { key: 'deuda_capital', label: 'DEUDA CAPITAL' },
    { key: 'campania', label: 'CAMPAÑA' },
    { key: 'porcentaje', label: '%' },
    { key: 'fecha_nacimiento', label: 'AÑO_NACIMIENTO' },
    { key: 'edad', label: 'EDAD' },
    { key: 'entidades', label: 'ENTIDADES' },
    { key: 'negocio', label: 'NEGOCIO' },
    { key: 'sueldo', label: 'SUELDO' },
    { key: 'situacion_laboral', label: 'SITUACION_LABORAL' },
    { key: 'anio_laboral', label: 'AÑO_LABORAL' },
    { key: 'sexo', label: 'SEXO' },
    { key: 'rango_sueldo', label: 'RANGO_SUELDO' },
    { key: 'anio_castigo', label: 'AÑO_CASTIGO' },
];

const exportUrl = computed(() => {
    const params = new URLSearchParams();
    Object.entries(filters).forEach(([key, value]) => {
        if (value !== '' && value !== null) params.set(key, value);
    });

    return `/expertis/asignaciones/export?${params.toString()}`;
});

function applyFilters(replace = true) {
    router.get('/expertis/asignaciones', filters, {
        preserveState: true,
        preserveScroll: true,
        replace,
        only: ['asignaciones', 'filtros'],
    });
}

function debouncedFilter() {
    clearTimeout(debounceTimer);
    debounceTimer = setTimeout(() => applyFilters(), 450);
}

function clearFilters() {
    Object.assign(filters, {
        periodo: props.opciones.periodos?.[0] ?? '',
        documento: '',
        codigo: '',
        titular: '',
        tipo_cartera: '',
        departamento: '',
        producto: '',
        per_page: 50,
    });
    applyFilters();
}

function money(value) {
    if (value === null || value === '') return '—';

    return new Intl.NumberFormat('es-PE', {
        style: 'currency',
        currency: 'PEN',
    }).format(Number(value));
}

function date(value) {
    if (!value) return '—';
    const match = String(value).match(/^(\d{4})-(\d{2})-(\d{2})/);

    return match ? `${match[3]}/${match[2]}/${match[1]}` : value;
}
</script>

<template>
    <AppLayout
        title="Asignaciones Expertis"
        subtitle="Consulta mensual paginada de las asignaciones importadas."
        :breadcrumbs="['Operativo', 'Expertis', 'Asignaciones']"
    >
        <PageHeader
            title="Asignaciones por periodo"
            description="El periodo más reciente se selecciona automáticamente. Los filtros y la exportación se ejecutan en el servidor."
        >
            <template #actions>
                <AppButton :href="exportUrl" download>Exportar XLSX</AppButton>
            </template>
        </PageHeader>

        <section class="mb-5 rounded-2xl border border-slate-200 bg-white p-4 shadow-card">
            <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
                <AppSelect v-model="filters.periodo" label="Periodo" :options="opciones.periodos" @change="applyFilters" />
                <AppInput
                    v-model="filters.documento"
                    label="Documento"
                    placeholder="DNI, RUC o CE"
                    @input="debouncedFilter"
                />
                <AppInput v-model="filters.codigo" label="Código" @input="debouncedFilter" />
                <AppInput v-model="filters.titular" label="Titular" @input="debouncedFilter" />
                <AppSelect v-model="filters.tipo_cartera" label="Tipo de cartera" :options="opciones.tipos_cartera" @change="applyFilters" />
                <AppSelect v-model="filters.departamento" label="Departamento" :options="opciones.departamentos" @change="applyFilters" />
                <AppSelect v-model="filters.producto" label="Producto" :options="opciones.productos" @change="applyFilters" />
                <AppSelect v-model="filters.per_page" label="Filas" :options="[50, 75, 100]" @change="applyFilters" />
            </div>
            <div class="mt-4 flex justify-end border-t border-slate-100 pt-4">
                <AppButton variant="ghost" @click="clearFilters">Limpiar filtros</AppButton>
            </div>
        </section>

        <AppTable :columns="columns" :rows="asignaciones.data">
            <template #cell-documento="{ value }"><span class="font-mono">{{ value }}</span></template>
            <template #cell-codigo="{ value }"><strong class="text-[#172033]">{{ value }}</strong></template>
            <template #cell-deuda_total="{ value }">{{ money(value) }}</template>
            <template #cell-deuda_capital="{ value }">{{ money(value) }}</template>
            <template #cell-campania="{ value }">{{ money(value) }}</template>
            <template #cell-sueldo="{ value }">{{ money(value) }}</template>
            <template #cell-porcentaje="{ value }">{{ value === null ? '—' : value }}</template>
            <template #cell-fecha_nacimiento="{ value }">{{ date(value) }}</template>
        </AppTable>
        <AppPagination v-bind="asignaciones" />
    </AppLayout>
</template>
