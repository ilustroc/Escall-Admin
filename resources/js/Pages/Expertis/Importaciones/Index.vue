<script setup>
import { reactive } from 'vue';
import { router } from '@inertiajs/vue3';
import AppBadge from '../../../Components/AppBadge.vue';
import AppButton from '../../../Components/AppButton.vue';
import AppInput from '../../../Components/AppInput.vue';
import AppPagination from '../../../Components/AppPagination.vue';
import AppSelect from '../../../Components/AppSelect.vue';
import AppTable from '../../../Components/AppTable.vue';
import ImportSummary from '../../../Components/ImportSummary.vue';
import AppLayout from '../../../Layouts/AppLayout.vue';

const props = defineProps({
    importaciones: { type: Object, required: true },
    seleccionada: { type: Object, default: null },
    filtros: { type: Object, default: () => ({}) },
});

const filters = reactive({
    tipo: props.filtros.tipo ?? '',
    estado: props.filtros.estado ?? '',
    desde: props.filtros.desde ?? '',
    hasta: props.filtros.hasta ?? '',
});

const columns = [
    { key: 'created_at', label: 'Fecha y hora' },
    { key: 'tipo', label: 'Tipo' },
    { key: 'nombre_original', label: 'Archivo' },
    { key: 'usuario', label: 'Usuario' },
    { key: 'estado', label: 'Estado' },
    { key: 'total_filas', label: 'Total' },
    { key: 'filas_insertadas', label: 'Insertadas' },
    { key: 'filas_actualizadas', label: 'Actualizadas' },
    { key: 'filas_duplicadas', label: 'Duplicadas' },
    { key: 'filas_error', label: 'Errores' },
    { key: 'rango', label: 'Rango' },
    { key: 'duracion', label: 'Duración' },
    { key: 'acciones', label: '' },
];

function applyFilters() {
    router.get('/expertis/importaciones', filters, { preserveState: true, replace: true });
}

function clearFilters() {
    Object.assign(filters, { tipo: '', estado: '', desde: '', hasta: '' });
    applyFilters();
}

function tone(status) {
    if (status === 'completado') return 'success';
    if (status === 'completado_con_errores' || status === 'duplicado') return 'warning';
    if (status === 'fallido') return 'danger';
    return 'info';
}

function duration(row) {
    if (!row.iniciado_at || !row.finalizado_at) return '—';
    const seconds = Math.max(0, Math.round((new Date(row.finalizado_at) - new Date(row.iniciado_at)) / 1000));
    return seconds < 60 ? `${seconds}s` : `${Math.floor(seconds / 60)}m ${seconds % 60}s`;
}
</script>

<template>
    <AppLayout
        title="Historial de importaciones"
        subtitle="Trazabilidad de archivos, duplicados, errores y usuario responsable."
        :breadcrumbs="['Operativo', 'Expertis', 'Importaciones']"
    >
        <section class="mb-5 rounded-2xl border border-slate-200 bg-white p-4 shadow-card">
            <form class="grid gap-3 md:grid-cols-2 xl:grid-cols-5" @submit.prevent="applyFilters">
                <AppSelect v-model="filters.tipo" label="Tipo" :options="[{ value: 'gestiones', label: 'Gestiones' }, { value: 'pagos', label: 'Pagos' }]" />
                <AppSelect
                    v-model="filters.estado"
                    label="Estado"
                    :options="[
                        'pendiente', 'validando', 'procesando', 'completado',
                        'completado_con_errores', 'fallido', 'duplicado',
                    ]"
                />
                <AppInput v-model="filters.desde" type="date" label="Desde" />
                <AppInput v-model="filters.hasta" type="date" label="Hasta" />
                <div class="flex items-end gap-2">
                    <AppButton type="submit" class="flex-1">Filtrar</AppButton>
                    <AppButton variant="ghost" @click="clearFilters">Limpiar</AppButton>
                </div>
            </form>
        </section>

        <AppTable :columns="columns" :rows="importaciones.data">
            <template #cell-created_at="{ value }">{{ new Date(value).toLocaleString('es-PE') }}</template>
            <template #cell-tipo="{ value }"><AppBadge tone="info">{{ value }}</AppBadge></template>
            <template #cell-nombre_original="{ row }">
                <div class="max-w-[240px]">
                    <p class="truncate font-semibold text-[#172033]">{{ row.nombre_original }}</p>
                    <p class="mt-0.5 font-mono text-[10px] text-slate-400">{{ row.hash_archivo.slice(0, 12) }}…</p>
                </div>
            </template>
            <template #cell-usuario="{ row }">{{ row.usuario?.name ?? 'Sistema' }}</template>
            <template #cell-estado="{ value }"><AppBadge :tone="tone(value)">{{ value.replaceAll('_', ' ') }}</AppBadge></template>
            <template #cell-rango="{ row }"><span class="text-xs">{{ row.fecha_minima ?? '—' }}<br>{{ row.fecha_maxima ?? '—' }}</span></template>
            <template #cell-duracion="{ row }">{{ duration(row) }}</template>
            <template #cell-acciones="{ row }">
                <div class="flex items-center gap-1">
                    <AppButton :href="`/expertis/importaciones/${row.id}`" variant="ghost" size="sm">Detalle</AppButton>
                    <a
                        v-if="row.ruta_archivo"
                        :href="`/expertis/importaciones/${row.id}/archivo`"
                        class="rounded-lg px-2 py-1.5 text-xs font-semibold text-escall-600 hover:bg-escall-50"
                    >XLSX</a>
                    <a
                        v-if="row.filas_error"
                        :href="`/expertis/importaciones/${row.id}/errores`"
                        class="rounded-lg px-2 py-1.5 text-xs font-semibold text-[#F04438] hover:bg-red-50"
                    >Errores</a>
                </div>
            </template>
        </AppTable>
        <AppPagination v-bind="importaciones" />

        <section v-if="seleccionada" class="mt-6 rounded-2xl border border-slate-200 bg-white p-5 shadow-card">
            <div class="flex flex-wrap items-start justify-between gap-3">
                <div>
                    <h2 class="text-base font-bold text-[#172033]">Importación #{{ seleccionada.id }}</h2>
                    <p class="mt-1 text-xs text-slate-500">{{ seleccionada.nombre_original }}</p>
                </div>
                <AppBadge :tone="tone(seleccionada.estado)">{{ seleccionada.estado.replaceAll('_', ' ') }}</AppBadge>
            </div>
            <ImportSummary class="mt-5" :summary="seleccionada" />

            <div v-if="seleccionada.resumen?.tipificaciones_desconocidas?.length" class="mt-5 rounded-xl border border-amber-200 bg-amber-50 p-4">
                <h3 class="text-xs font-bold uppercase tracking-wide text-amber-800">Tipificaciones no reconocidas</h3>
                <div class="mt-2 flex flex-wrap gap-2">
                    <AppBadge v-for="item in seleccionada.resumen.tipificaciones_desconocidas" :key="item" tone="warning">{{ item }}</AppBadge>
                </div>
            </div>

            <div v-if="seleccionada.errores?.length" class="mt-5">
                <h3 class="mb-3 text-sm font-bold text-[#172033]">Primeros errores</h3>
                <div class="space-y-2">
                    <div v-for="item in seleccionada.errores" :key="item.id" class="rounded-lg bg-red-50 px-4 py-3 text-xs text-red-800">
                        <strong>Fila {{ item.numero_fila }}:</strong> {{ item.mensaje }}
                    </div>
                </div>
            </div>
        </section>
    </AppLayout>
</template>
