<script setup>
import {
    computed,
    onBeforeUnmount,
    onMounted,
    reactive,
    ref,
    watch,
} from 'vue';
import { router } from '@inertiajs/vue3';
import axios from 'axios';
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
    tipos: { type: Array, required: true },
});

const filters = reactive({
    tipo: props.filtros.tipo ?? '',
    estado: props.filtros.estado ?? '',
    desde: props.filtros.desde ?? '',
    hasta: props.filtros.hasta ?? '',
});
const selected = ref(props.seleccionada);
const polling = ref(false);
const retrying = ref(false);
const actionError = ref('');
let timer = null;

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
    { key: 'periodo', label: 'Periodo' },
    { key: 'rango', label: 'Rango' },
    { key: 'duracion', label: 'Duración' },
    { key: 'acciones', label: '' },
];

const stateOptions = [
    { value: 'pendiente', label: 'Pendiente' },
    { value: 'validando', label: 'Validando archivo' },
    { value: 'listo_para_importar', label: 'Listo para importar' },
    { value: 'en_cola', label: 'En cola' },
    { value: 'procesando', label: 'Procesando' },
    { value: 'completado', label: 'Completado' },
    {
        value: 'completado_con_errores',
        label: 'Completado con errores',
    },
    { value: 'fallido', label: 'Fallido' },
    { value: 'duplicado', label: 'Duplicado' },
];

const activeStates = ['validando', 'en_cola', 'procesando'];

const previewColumns = computed(() => (
    selected.value?.resumen?.columnas_detectadas ?? []
).map((key) => ({
    key,
    label: String(key)
        .replaceAll('_', ' ')
        .replace(/\b\w/g, (letter) => letter.toUpperCase()),
})));

function applyFilters() {
    router.get('/expertis/importaciones', filters, {
        preserveState: true,
        replace: true,
    });
}

function clearFilters() {
    Object.assign(filters, {
        tipo: '',
        estado: '',
        desde: '',
        hasta: '',
    });
    applyFilters();
}

function tone(status) {
    if (
        status === 'completado'
        || status === 'listo_para_importar'
    ) {
        return 'success';
    }
    if (
        status === 'completado_con_errores'
        || status === 'duplicado'
    ) {
        return 'warning';
    }
    if (status === 'fallido') return 'danger';

    return 'info';
}

function statusLabel(status) {
    return stateOptions.find((item) => item.value === status)?.label
        ?? String(status ?? 'Sin estado').replaceAll('_', ' ');
}

function typeLabel(value) {
    return props.tipos.find((type) => type.value === value)?.label ?? value;
}

function duration(row) {
    if (!row.iniciado_at || !row.finalizado_at) return '—';
    const seconds = Math.max(
        0,
        Math.round(
            (new Date(row.finalizado_at) - new Date(row.iniciado_at))
            / 1000,
        ),
    );

    return seconds < 60
        ? `${seconds}s`
        : `${Math.floor(seconds / 60)}m ${seconds % 60}s`;
}

function applyStatus(data) {
    if (!selected.value || selected.value.id !== data.id) {
        return;
    }

    selected.value = {
        ...selected.value,
        estado: data.estado,
        fase: data.fase,
        progreso_actual: data.progreso.actual,
        progreso_total: data.progreso.total,
        total_filas: data.totales.filas,
        filas_insertadas: data.totales.insertadas,
        filas_actualizadas: data.totales.actualizadas,
        filas_duplicadas: data.totales.duplicadas,
        filas_error: data.totales.errores,
        queued_at: data.encolado_at,
        heartbeat_at: data.heartbeat_at,
        iniciado_at: data.iniciado_at,
        finalizado_at: data.finalizado_at,
        mensaje_error: data.mensaje_error,
        intentos: data.intentos,
        resumen: {
            ...(selected.value.resumen ?? {}),
            periodo: data.periodo,
            empresa: data.empresa,
            filas_validas: data.totales.validas,
            total_chunks: data.totales.chunks,
            columnas_detectadas: data.columnas_detectadas,
            preview: data.preview,
        },
    };

    if (!data.activo) {
        stopPolling();
    }
}

async function refreshStatus() {
    if (
        !selected.value
        || selected.value.tipo !== 'asignaciones'
        || !activeStates.includes(selected.value.estado)
    ) {
        stopPolling();

        return;
    }

    polling.value = true;
    try {
        const { data } = await axios.get(
            `/expertis/importaciones/${selected.value.id}/estado`,
        );
        applyStatus(data);
        actionError.value = '';
    } catch {
        actionError.value = 'No se pudo actualizar el estado. Se intentará nuevamente.';
    } finally {
        polling.value = false;
    }
}

function startPolling() {
    stopPolling();

    if (
        selected.value?.tipo !== 'asignaciones'
        || !activeStates.includes(selected.value?.estado)
    ) {
        return;
    }

    refreshStatus();
    timer = window.setInterval(refreshStatus, 3000);
}

function stopPolling() {
    if (timer) {
        window.clearInterval(timer);
        timer = null;
    }
}

function confirmAssignment() {
    if (selected.value?.estado !== 'listo_para_importar') {
        return;
    }

    router.post(
        '/expertis/importaciones/asignaciones',
        { importacion_id: selected.value.id },
        { preserveScroll: true },
    );
}

async function retryAssignment() {
    if (selected.value?.estado !== 'fallido') {
        return;
    }

    retrying.value = true;
    actionError.value = '';
    try {
        const { data } = await axios.post(
            `/expertis/importaciones/${selected.value.id}/reintentar`,
        );
        applyStatus(data);
        startPolling();
    } catch (exception) {
        actionError.value = exception.response?.data?.message
            ?? 'No se pudo reintentar la importación.';
    } finally {
        retrying.value = false;
    }
}

watch(
    () => props.seleccionada,
    (value) => {
        selected.value = value;
        actionError.value = '';
        startPolling();
    },
);

onMounted(startPolling);
onBeforeUnmount(stopPolling);
</script>

<template>
    <AppLayout
        title="Historial de importaciones"
        subtitle="Trazabilidad de archivos, progreso, duplicados, errores y usuario responsable."
        :breadcrumbs="['Operativo', 'Expertis', 'Importaciones']"
    >
        <section class="mb-5 rounded-2xl border border-slate-200 bg-white p-4 shadow-card">
            <form class="grid gap-3 md:grid-cols-2 xl:grid-cols-5" @submit.prevent="applyFilters">
                <AppSelect v-model="filters.tipo" label="Tipo" :options="tipos" />
                <AppSelect v-model="filters.estado" label="Estado" :options="stateOptions" />
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
            <template #cell-tipo="{ value }"><AppBadge tone="info">{{ typeLabel(value) }}</AppBadge></template>
            <template #cell-nombre_original="{ row }">
                <div class="max-w-[240px]">
                    <p class="truncate font-semibold text-[#172033]">{{ row.nombre_original }}</p>
                    <p class="mt-0.5 font-mono text-[10px] text-slate-400">{{ row.hash_archivo.slice(0, 12) }}…</p>
                </div>
            </template>
            <template #cell-usuario="{ row }">{{ row.usuario?.name ?? 'Sistema' }}</template>
            <template #cell-estado="{ value }"><AppBadge :tone="tone(value)">{{ statusLabel(value) }}</AppBadge></template>
            <template #cell-periodo="{ row }">{{ row.resumen?.periodo ?? '—' }}</template>
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

        <section v-if="selected" class="mt-6 rounded-2xl border border-slate-200 bg-white p-5 shadow-card">
            <div class="flex flex-wrap items-start justify-between gap-3">
                <div>
                    <h2 class="text-base font-bold text-[#172033]">Importación #{{ selected.id }}</h2>
                    <p class="mt-1 text-xs text-slate-500">{{ selected.nombre_original }}</p>
                </div>
                <div class="flex items-center gap-2">
                    <span v-if="polling" class="text-xs text-slate-400">Actualizando…</span>
                    <AppBadge :tone="tone(selected.estado)">{{ statusLabel(selected.estado) }}</AppBadge>
                </div>
            </div>

            <p
                v-if="activeStates.includes(selected.estado)"
                class="mt-4 rounded-xl border border-blue-100 bg-blue-50 px-4 py-3 text-xs leading-5 text-blue-800"
            >
                El proceso continúa en segundo plano. Puedes salir de esta página y volver más tarde.
            </p>
            <p
                v-if="selected.mensaje_error"
                class="mt-4 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-xs leading-5 text-red-800"
            >
                {{ selected.mensaje_error }}
            </p>
            <p
                v-if="actionError"
                class="mt-4 rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-xs leading-5 text-amber-800"
            >
                {{ actionError }}
            </p>

            <ImportSummary class="mt-5" :summary="selected" />

            <div
                v-if="selected.tipo === 'asignaciones'"
                class="mt-5 flex flex-wrap gap-2"
            >
                <AppButton
                    v-if="selected.estado === 'listo_para_importar'"
                    variant="success"
                    @click="confirmAssignment"
                >
                    Confirmar importación
                </AppButton>
                <AppButton
                    v-if="selected.estado === 'fallido'"
                    :loading="retrying"
                    @click="retryAssignment"
                >
                    Reintentar
                </AppButton>
            </div>

            <div
                v-if="selected.resumen?.preview?.length"
                class="mt-5"
            >
                <h3 class="mb-3 text-sm font-bold text-[#172033]">
                    Vista previa de filas válidas
                </h3>
                <AppTable
                    :columns="previewColumns"
                    :rows="selected.resumen.preview"
                    row-key="codigo_normalizado"
                />
            </div>

            <div v-if="selected.resumen?.tipificaciones_desconocidas?.length" class="mt-5 rounded-xl border border-amber-200 bg-amber-50 p-4">
                <h3 class="text-xs font-bold uppercase tracking-wide text-amber-800">Tipificaciones no reconocidas</h3>
                <div class="mt-2 flex flex-wrap gap-2">
                    <AppBadge v-for="item in selected.resumen.tipificaciones_desconocidas" :key="item" tone="warning">{{ item }}</AppBadge>
                </div>
            </div>

            <div v-if="selected.errores?.length" class="mt-5">
                <h3 class="mb-3 text-sm font-bold text-[#172033]">Primeros errores</h3>
                <div class="space-y-2">
                    <div v-for="item in selected.errores" :key="item.id" class="rounded-lg bg-red-50 px-4 py-3 text-xs text-red-800">
                        <strong>Fila {{ item.numero_fila }}:</strong> {{ item.mensaje }}
                    </div>
                </div>
            </div>
        </section>
    </AppLayout>
</template>
