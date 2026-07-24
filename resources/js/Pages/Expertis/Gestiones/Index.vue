<script setup>
import { computed, reactive, ref } from 'vue';
import { router } from '@inertiajs/vue3';
import AppBadge from '../../../Components/AppBadge.vue';
import AppButton from '../../../Components/AppButton.vue';
import AppInput from '../../../Components/AppInput.vue';
import AppPagination from '../../../Components/AppPagination.vue';
import AppSelect from '../../../Components/AppSelect.vue';
import AppTable from '../../../Components/AppTable.vue';
import AppLayout from '../../../Layouts/AppLayout.vue';

const props = defineProps({
    gestiones: { type: Object, required: true },
    filtros: { type: Object, default: () => ({}) },
    opciones: { type: Object, required: true },
});

const filters = reactive({
    fecha_inicio: props.filtros.fecha_inicio ?? '',
    fecha_fin: props.filtros.fecha_fin ?? '',
    codigo: props.filtros.codigo ?? '',
    dni: props.filtros.dni ?? '',
    cartera: props.filtros.cartera ?? '',
    asesor: props.filtros.asesor ?? '',
    equipo: props.filtros.equipo ?? '',
    nivel_1: props.filtros.nivel_1 ?? '',
    nivel_2: props.filtros.nivel_2 ?? '',
    peso: props.filtros.peso ?? '',
    estado: props.filtros.estado ?? '',
    unico: props.filtros.unico ?? '',
    campania: props.filtros.campania ?? '',
    medio_gestion: props.filtros.medio_gestion ?? '',
    sort: props.filtros.sort ?? 'fecha_hora',
    direction: props.filtros.direction ?? 'desc',
    per_page: props.filtros.per_page ?? 25,
});
const expanded = ref(new Set());
let debounceTimer;

const columns = [
    { key: 'codigo', label: 'Código' },
    { key: 'dni', label: 'DNI' },
    { key: 'cartera', label: 'Cartera' },
    { key: 'asesor', label: 'Asesor' },
    { key: 'equipo', label: 'Equipo' },
    { key: 'telefono', label: 'Teléfono' },
    { key: 'fecha_llamada', label: 'Fecha llamada' },
    { key: 'hora', label: 'Hora' },
    { key: 'nivel_1', label: 'Nivel 1' },
    { key: 'nivel_2', label: 'Nivel 2' },
    { key: 'peso', label: 'Peso' },
    { key: 'fecha_compromiso', label: 'Compromiso' },
    { key: 'monto', label: 'Monto' },
    { key: 'estado', label: 'Estado' },
    { key: 'fecha_pagada', label: 'Fecha pagada' },
    { key: 'monto_pagado', label: 'Monto pagado' },
    { key: 'ps_proyectado', label: 'PS-Proyectado' },
    { key: 'unico', label: 'Único' },
    { key: 'acciones', label: '' },
];

const exportBase = computed(() => {
    const params = new URLSearchParams();
    Object.entries(filters).forEach(([key, value]) => {
        if (value !== '' && value !== null) params.set(key, value);
    });
    return `/expertis/gestiones/export?${params}`;
});

function applyFilters(replace = true) {
    router.get('/expertis/gestiones', filters, {
        preserveState: true,
        preserveScroll: true,
        replace,
        only: ['gestiones', 'filtros'],
    });
}

function debouncedFilter() {
    clearTimeout(debounceTimer);
    debounceTimer = setTimeout(() => applyFilters(), 450);
}

function clearFilters() {
    Object.assign(filters, {
        fecha_inicio: '', fecha_fin: '', codigo: '', dni: '', cartera: '', asesor: '',
        equipo: '', nivel_1: '', nivel_2: '', peso: '', estado: '', unico: '',
        campania: '', medio_gestion: '', sort: 'fecha_hora', direction: 'desc', per_page: 25,
    });
    applyFilters();
}

function toggleRow(id) {
    const copy = new Set(expanded.value);
    copy.has(id) ? copy.delete(id) : copy.add(id);
    expanded.value = copy;
}

const money = (value) => new Intl.NumberFormat('es-PE', { style: 'currency', currency: 'PEN' }).format(Number(value || 0));
</script>

<template>
    <AppLayout
        title="Gestiones Expertis"
        subtitle="Consulta paginada con peso, único, pagos válidos y proyectado."
        :breadcrumbs="['Analítica', 'Tablas', 'Gestiones Expertis']"
    >
        <section class="mb-5 rounded-2xl border border-slate-200 bg-white p-4 shadow-card">
            <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4 2xl:grid-cols-7">
                <AppInput v-model="filters.fecha_inicio" type="date" label="Fecha inicio" @change="applyFilters" />
                <AppInput v-model="filters.fecha_fin" type="date" label="Fecha fin" @change="applyFilters" />
                <AppInput v-model="filters.codigo" label="Código" placeholder="Buscar código" @input="debouncedFilter" />
                <AppInput v-model="filters.dni" label="DNI" placeholder="Buscar DNI" @input="debouncedFilter" />
                <AppSelect v-model="filters.cartera" label="Cartera" :options="opciones.carteras" @change="applyFilters" />
                <AppSelect v-model="filters.asesor" label="Asesor" :options="opciones.asesores" @change="applyFilters" />
                <AppSelect v-model="filters.equipo" label="Equipo" :options="opciones.equipos" @change="applyFilters" />
                <AppSelect v-model="filters.nivel_1" label="Nivel 1" :options="opciones.niveles_1" @change="applyFilters" />
                <AppSelect v-model="filters.nivel_2" label="Nivel 2" :options="opciones.niveles_2" @change="applyFilters" />
                <AppInput v-model="filters.peso" type="number" label="Peso" @input="debouncedFilter" />
                <AppSelect v-model="filters.estado" label="Estado" :options="['PAGO', 'NO PAGO']" @change="applyFilters" />
                <AppSelect v-model="filters.unico" label="Único" :options="[{ value: '1', label: 'Sí' }, { value: '0', label: 'No' }]" @change="applyFilters" />
                <AppSelect v-model="filters.campania" label="Campaña" :options="opciones.campanias" @change="applyFilters" />
                <AppSelect v-model="filters.medio_gestion" label="Medio" :options="opciones.medios" @change="applyFilters" />
                <AppSelect
                    v-model="filters.sort"
                    label="Ordenar por"
                    :options="[
                        { value: 'fecha_hora', label: 'Fecha y hora' },
                        { value: 'codigo', label: 'Código' },
                        { value: 'cartera', label: 'Cartera' },
                        { value: 'asesor', label: 'Asesor' },
                        { value: 'nivel_2', label: 'Nivel 2' },
                        { value: 'peso', label: 'Peso' },
                        { value: 'monto_pagado', label: 'Monto pagado' },
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
            </div>
            <div class="mt-4 flex flex-wrap items-end justify-between gap-3 border-t border-slate-100 pt-4">
                <div class="flex items-end gap-2">
                    <AppSelect v-model="filters.per_page" label="Filas" :options="[25, 50, 100, 250]" @change="applyFilters" />
                    <AppButton variant="ghost" @click="clearFilters">Limpiar filtros</AppButton>
                </div>
                <div class="flex gap-2">
                    <a :href="`${exportBase}&formato=csv`" class="rounded-lg border border-slate-300 bg-white px-3 py-2 text-xs font-bold text-slate-700 hover:bg-slate-50">CSV</a>
                    <a :href="`${exportBase}&formato=xlsx`" class="rounded-lg bg-escall-600 px-3 py-2 text-xs font-bold text-white hover:bg-escall-700">Exportar XLSX</a>
                </div>
            </div>
        </section>

        <AppTable :columns="columns" :rows="gestiones.data">
            <template #cell-codigo="{ value }"><strong class="text-[#172033]">{{ value }}</strong></template>
            <template #cell-nivel_2="{ value }"><AppBadge tone="info">{{ value }}</AppBadge></template>
            <template #cell-peso="{ value }">{{ value ?? '' }}</template>
            <template #cell-monto="{ value }">{{ money(value) }}</template>
            <template #cell-estado="{ value }"><AppBadge :tone="value === 'PAGO' ? 'success' : 'neutral'">{{ value }}</AppBadge></template>
            <template #cell-monto_pagado="{ value }"><strong :class="Number(value) > 0 ? 'text-[#12B76A]' : 'text-slate-400'">{{ money(value) }}</strong></template>
            <template #cell-ps_proyectado="{ value }">{{ money(value) }}</template>
            <template #cell-unico="{ value }"><AppBadge :tone="Number(value) === 1 ? 'success' : 'neutral'">{{ Number(value) === 1 ? '1' : '0' }}</AppBadge></template>
            <template #cell-acciones="{ row }">
                <button class="rounded-lg px-2 py-1 text-xs font-bold text-escall-600 hover:bg-escall-50" @click="toggleRow(row.id)">
                    {{ expanded.has(row.id) ? 'Cerrar' : 'Detalle' }}
                </button>
            </template>
            <template #after-row="{ row, colspan }">
                <tr v-if="expanded.has(row.id)" class="bg-slate-50">
                    <td :colspan="colspan" class="px-5 py-4">
                        <dl class="grid gap-4 text-xs sm:grid-cols-2 lg:grid-cols-5">
                            <div><dt class="font-bold text-slate-500">Nombre cliente</dt><dd class="mt-1 text-[#172033]">{{ row.nombre_cliente ?? '—' }}</dd></div>
                            <div><dt class="font-bold text-slate-500">Campaña</dt><dd class="mt-1 text-[#172033]">{{ row.campania ?? '—' }}</dd></div>
                            <div class="lg:col-span-2"><dt class="font-bold text-slate-500">Observación</dt><dd class="mt-1 whitespace-normal leading-5 text-[#172033]">{{ row.observacion ?? '—' }}</dd></div>
                            <div><dt class="font-bold text-slate-500">Medio / Importación</dt><dd class="mt-1 text-[#172033]">{{ row.medio_gestion ?? '—' }} · #{{ row.importacion_expertis_id ?? '—' }}</dd></div>
                        </dl>
                    </td>
                </tr>
            </template>
        </AppTable>
        <AppPagination v-bind="gestiones" />
    </AppLayout>
</template>
