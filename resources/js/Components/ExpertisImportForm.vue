<script setup>
import { computed, ref } from 'vue';
import { router } from '@inertiajs/vue3';
import AppBadge from './AppBadge.vue';
import AppButton from './AppButton.vue';
import AppTable from './AppTable.vue';
import ConfirmDialog from './ConfirmDialog.vue';
import FileDropzone from './FileDropzone.vue';
import ImportSummary from './ImportSummary.vue';
import LoadingOverlay from './LoadingOverlay.vue';

const props = defineProps({
    type: { type: String, required: true },
    previewUrl: { type: String, required: true },
    storeUrl: { type: String, required: true },
    requiredColumns: { type: Array, required: true },
    config: { type: Object, required: true },
    lastImport: { type: Object, default: null },
});

const file = ref(null);
const preview = ref(null);
const error = ref('');
const validating = ref(false);
const processing = ref(false);
const confirmOpen = ref(false);

const title = computed(() => props.type === 'gestiones' ? 'gestiones' : 'pagos');
const previewColumns = computed(() => (preview.value?.columnas_detectadas ?? []).map((key) => ({
    key,
    label: key.replaceAll('_', ' '),
})));
const canImport = computed(() => preview.value?.token && !preview.value?.columnas_faltantes?.length);

function selectFile(selected) {
    file.value = selected;
    preview.value = null;
    error.value = '';

    if (!selected) return;
    if (!selected.name.toLowerCase().endsWith('.xlsx')) {
        error.value = 'Selecciona un archivo con extensión .xlsx.';
    } else if (selected.size > props.config.max_mb * 1024 * 1024) {
        error.value = `El archivo supera el límite de ${props.config.max_mb} MB.`;
    }
}

async function validateFile() {
    if (!file.value || error.value) return;
    validating.value = true;
    preview.value = null;

    const body = new FormData();
    body.append('archivo', file.value);

    try {
        const { data } = await window.axios.post(props.previewUrl, body, {
            headers: { 'Content-Type': 'multipart/form-data' },
        });
        preview.value = data;
        if (data.columnas_faltantes?.length) {
            error.value = `Faltan columnas obligatorias: ${data.columnas_faltantes.join(', ')}.`;
        }
    } catch (exception) {
        const validation = exception.response?.data?.errors?.archivo?.[0];
        error.value = validation || exception.response?.data?.message || 'No se pudo validar el XLSX.';
    } finally {
        validating.value = false;
    }
}

function importFile() {
    confirmOpen.value = false;
    if (!canImport.value) return;

    router.post(props.storeUrl, { preview_token: preview.value.token }, {
        preserveScroll: true,
        onStart: () => { processing.value = true; },
        onFinish: () => { processing.value = false; },
    });
}

function statusTone(status) {
    if (status === 'completado') return 'success';
    if (status === 'completado_con_errores' || status === 'duplicado') return 'warning';
    if (status === 'fallido') return 'danger';
    return 'info';
}
</script>

<template>
    <div class="grid gap-6 xl:grid-cols-[minmax(0,1fr)_360px]">
        <section class="space-y-5">
            <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-card md:p-6">
                <div class="mb-5 flex flex-wrap items-start justify-between gap-3">
                    <div>
                        <h2 class="text-base font-bold text-[#172033]">Seleccionar XLSX</h2>
                        <p class="mt-1 text-xs leading-5 text-slate-500">El archivo se valida antes de escribir datos y permanece en almacenamiento privado.</p>
                    </div>
                    <AppBadge tone="info">XLSX · {{ config.max_mb }} MB máx.</AppBadge>
                </div>

                <FileDropzone v-model="file" :max-mb="config.max_mb" :error="error" @change="selectFile" />

                <div class="mt-5 flex flex-wrap items-center justify-between gap-3">
                    <p class="text-xs text-slate-500">Se verifican extensión, contenido, encabezados y hash SHA-256.</p>
                    <AppButton :loading="validating" :disabled="!file || Boolean(error)" @click="validateFile">
                        Validar y previsualizar
                    </AppButton>
                </div>
            </div>

            <div v-if="preview" class="rounded-2xl border border-slate-200 bg-white p-5 shadow-card md:p-6">
                <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
                    <div>
                        <h2 class="text-base font-bold text-[#172033]">Vista previa</h2>
                        <p class="mt-1 text-xs text-slate-500">Primeras {{ preview.preview?.length ?? 0 }} filas detectadas.</p>
                    </div>
                    <AppBadge v-if="preview.archivo_duplicado" tone="warning">Archivo ya procesado</AppBadge>
                    <AppBadge v-else-if="canImport" tone="success">Encabezados válidos</AppBadge>
                </div>

                <div v-if="preview.archivo_duplicado" class="mb-4 rounded-xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-800">
                    El hash coincide con la importación #{{ preview.archivo_duplicado.id }}. Si confirmas, no se procesará nuevamente.
                </div>

                <AppTable :columns="previewColumns" :rows="preview.preview ?? []" row-key="__row">
                    <template #empty>No hay filas de datos en la primera hoja.</template>
                </AppTable>

                <div class="mt-5 flex justify-end">
                    <AppButton :disabled="!canImport" @click="confirmOpen = true">Confirmar importación</AppButton>
                </div>
            </div>
        </section>

        <aside class="space-y-5">
            <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-card">
                <h3 class="text-sm font-bold text-[#172033]">Columnas obligatorias</h3>
                <ul class="mt-4 space-y-2">
                    <li v-for="column in requiredColumns" :key="column" class="flex items-center gap-2 text-xs text-slate-600">
                        <span class="flex h-5 w-5 items-center justify-center rounded-full bg-escall-50 text-[10px] font-bold text-escall-600">✓</span>
                        {{ column }}
                    </li>
                </ul>
            </section>

            <section v-if="lastImport" class="rounded-2xl border border-slate-200 bg-white p-5 shadow-card">
                <div class="flex items-center justify-between gap-2">
                    <h3 class="text-sm font-bold text-[#172033]">Última importación</h3>
                    <AppBadge :tone="statusTone(lastImport.estado)">{{ lastImport.estado }}</AppBadge>
                </div>
                <p class="mt-3 truncate text-xs font-semibold text-slate-700">{{ lastImport.nombre_original }}</p>
                <p class="mt-1 text-[11px] text-slate-500">{{ new Date(lastImport.created_at).toLocaleString('es-PE') }}</p>
                <ImportSummary class="mt-4" :summary="lastImport" />
                <div class="mt-4 flex flex-wrap gap-2">
                    <AppButton :href="`/expertis/importaciones/${lastImport.id}`" variant="secondary" size="sm">Ver detalle</AppButton>
                    <a
                        v-if="lastImport.filas_error > 0"
                        :href="`/expertis/importaciones/${lastImport.id}/errores`"
                        class="rounded-lg px-3 py-1.5 text-xs font-semibold text-[#F04438] hover:bg-red-50"
                    >Descargar errores</a>
                </div>
            </section>
        </aside>

        <ConfirmDialog
            :open="confirmOpen"
            title="Confirmar importación"
            :message="`Se procesará el archivo de ${title}. La operación aplica deduplicación y no reemplaza tablas legacy.`"
            confirm-text="Importar ahora"
            :loading="processing"
            @close="confirmOpen = false"
            @confirm="importFile"
        />
        <LoadingOverlay :show="processing" :message="`Procesando ${title} Expertis…`" />
    </div>
</template>
