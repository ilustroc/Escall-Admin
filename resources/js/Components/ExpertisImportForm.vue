<script setup>
import { computed, ref } from 'vue';
import { router } from '@inertiajs/vue3';
import axios from 'axios';

import AppBadge from './AppBadge.vue';
import AppButton from './AppButton.vue';
import AppTable from './AppTable.vue';
import ConfirmDialog from './ConfirmDialog.vue';
import FileDropzone from './FileDropzone.vue';
import ImportSummary from './ImportSummary.vue';
import LoadingOverlay from './LoadingOverlay.vue';

const props = defineProps({
    type: {
        type: String,
        required: true,
    },

    previewUrl: {
        type: String,
        required: true,
    },

    storeUrl: {
        type: String,
        required: true,
    },

    requiredColumns: {
        type: Array,
        required: true,
    },

    config: {
        type: Object,
        required: true,
    },

    lastImport: {
        type: Object,
        default: null,
    },
});

const file = ref(null);
const preview = ref(null);
const error = ref('');
const validating = ref(false);
const processing = ref(false);
const confirmOpen = ref(false);

const title = computed(() => (
    props.type === 'gestiones'
        ? 'gestiones'
        : 'pagos'
));

const previewColumns = computed(() => (
    preview.value?.columnas_detectadas ?? []
).map((key) => ({
    key,
    label: String(key)
        .replaceAll('_', ' ')
        .replace(/\b\w/g, (letter) => letter.toUpperCase()),
})));

const canImport = computed(() => (
    Boolean(preview.value?.token)
    && !preview.value?.columnas_faltantes?.length
));

function selectFile(selected) {
    file.value = selected;
    preview.value = null;
    error.value = '';

    if (!selected) {
        return;
    }

    if (!selected.name.toLowerCase().endsWith('.xlsx')) {
        error.value = 'Selecciona un archivo con extensión .xlsx.';
        return;
    }

    const maxMb = Number(props.config.max_mb ?? 50);
    const maximumSize = maxMb * 1024 * 1024;

    if (selected.size > maximumSize) {
        error.value = `El archivo supera el límite de ${maxMb} MB.`;
    }
}

async function validateFile() {
    if (!file.value || error.value) {
        return;
    }

    validating.value = true;
    preview.value = null;
    error.value = '';

    const body = new FormData();
    body.append('archivo', file.value);

    try {
        const { data } = await axios.post(
            props.previewUrl,
            body,
            {
                headers: {
                    'Content-Type': 'multipart/form-data',
                },
            },
        );

        preview.value = data;

        if (data.columnas_faltantes?.length) {
            error.value = `Faltan columnas obligatorias: ${data.columnas_faltantes.join(', ')}.`;
        }
    } catch (exception) {
        const validationMessage =
            exception.response?.data?.errors?.archivo?.[0];

        error.value =
            validationMessage
            || exception.response?.data?.message
            || 'No se pudo validar el archivo XLSX.';
    } finally {
        validating.value = false;
    }
}

function importFile() {
    confirmOpen.value = false;

    if (!canImport.value) {
        return;
    }

    router.post(
        props.storeUrl,
        {
            preview_token: preview.value.token,
        },
        {
            preserveScroll: true,

            onStart: () => {
                processing.value = true;
            },

            onFinish: () => {
                processing.value = false;
            },
        },
    );
}

function statusTone(status) {
    const tones = {
        completado: 'success',
        completado_con_errores: 'warning',
        duplicado: 'warning',
        fallido: 'danger',
        procesando: 'info',
        validando: 'info',
        pendiente: 'info',
    };

    return tones[status] ?? 'info';
}

function formatStatus(value) {
    if (!value) {
        return 'Sin estado';
    }

    const normalized = String(value)
        .replaceAll('_', ' ')
        .trim()
        .toLowerCase();

    return normalized.charAt(0).toUpperCase()
        + normalized.slice(1);
}

function formatDateTime(value) {
    if (!value) {
        return 'Sin fecha registrada';
    }

    const date = new Date(value);

    if (Number.isNaN(date.getTime())) {
        return String(value);
    }

    return new Intl.DateTimeFormat('es-PE', {
        day: '2-digit',
        month: '2-digit',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
        second: '2-digit',
        hour12: true,
        timeZone: 'America/Lima',
    }).format(date);
}
</script>

<template>
    <div class="grid min-w-0 gap-6 xl:grid-cols-[minmax(0,1fr)_380px]">
        <!-- Contenido principal -->
        <section class="min-w-0 space-y-5">
            <!-- Selección del archivo -->
            <div
                class="rounded-2xl border border-slate-200 bg-white p-5 shadow-card md:p-6"
            >
                <div
                    class="mb-5 flex flex-wrap items-start justify-between gap-3"
                >
                    <div class="min-w-0">
                        <h2 class="text-base font-bold text-[#172033]">
                            Seleccionar XLSX
                        </h2>

                        <p class="mt-1 text-xs leading-5 text-slate-500">
                            El archivo se valida antes de escribir datos y
                            permanece en almacenamiento privado.
                        </p>
                    </div>

                    <AppBadge
                        class="shrink-0"
                        tone="info"
                    >
                        XLSX · {{ config.max_mb }} MB máx.
                    </AppBadge>
                </div>

                <FileDropzone
                    v-model="file"
                    :max-mb="config.max_mb"
                    :error="error"
                    @change="selectFile"
                />

                <div
                    class="mt-5 flex flex-wrap items-center justify-between gap-3"
                >
                    <p class="text-xs leading-5 text-slate-500">
                        Se verifican extensión, contenido, encabezados y
                        hash SHA-256.
                    </p>

                    <AppButton
                        :loading="validating"
                        :disabled="!file || Boolean(error)"
                        @click="validateFile"
                    >
                        Validar y previsualizar
                    </AppButton>
                </div>
            </div>

            <!-- Vista previa -->
            <div
                v-if="preview"
                class="rounded-2xl border border-slate-200 bg-white p-5 shadow-card md:p-6"
            >
                <div
                    class="mb-4 flex flex-wrap items-center justify-between gap-3"
                >
                    <div class="min-w-0">
                        <h2 class="text-base font-bold text-[#172033]">
                            Vista previa
                        </h2>

                        <p class="mt-1 text-xs text-slate-500">
                            Primeras
                            {{ preview.preview?.length ?? 0 }}
                            filas detectadas.
                        </p>
                    </div>

                    <AppBadge
                        v-if="preview.archivo_duplicado"
                        tone="warning"
                    >
                        Archivo ya procesado
                    </AppBadge>

                    <AppBadge
                        v-else-if="canImport"
                        tone="success"
                    >
                        Encabezados válidos
                    </AppBadge>
                </div>

                <div
                    v-if="preview.archivo_duplicado"
                    class="mb-4 rounded-xl border border-amber-200 bg-amber-50 p-4 text-sm leading-6 text-amber-800"
                >
                    El hash coincide con la importación
                    #{{ preview.archivo_duplicado.id }}.

                    Si confirmas, el archivo no se procesará nuevamente.
                </div>

                <AppTable
                    :columns="previewColumns"
                    :rows="preview.preview ?? []"
                    row-key="__row"
                >
                    <template #empty>
                        No hay filas de datos en la primera hoja.
                    </template>
                </AppTable>

                <div class="mt-5 flex justify-end">
                    <AppButton
                        :disabled="!canImport"
                        @click="confirmOpen = true"
                    >
                        Confirmar importación
                    </AppButton>
                </div>
            </div>
        </section>

        <!-- Panel lateral -->
        <aside class="min-w-0 space-y-5">
            <!-- Columnas obligatorias -->
            <section
                class="rounded-2xl border border-slate-200 bg-white p-5 shadow-card"
            >
                <h3 class="text-sm font-bold text-[#172033]">
                    Columnas obligatorias
                </h3>

                <ul class="mt-4 space-y-2.5">
                    <li
                        v-for="column in requiredColumns"
                        :key="column"
                        class="flex items-center gap-2.5 text-xs text-slate-600"
                    >
                        <span
                            class="flex h-5 w-5 shrink-0 items-center justify-center rounded-full bg-[#EEF4FF] text-[10px] font-bold text-[#073DC7]"
                        >
                            ✓
                        </span>

                        <span class="min-w-0">
                            {{ column }}
                        </span>
                    </li>
                </ul>
            </section>

            <!-- Última importación -->
            <section
                v-if="lastImport"
                class="min-w-0 overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-card"
            >
                <!-- Cabecera -->
                <div class="border-b border-slate-100 p-5">
                    <div
                        class="flex min-w-0 items-start justify-between gap-3"
                    >
                        <div class="min-w-0 flex-1">
                            <h3 class="text-sm font-bold text-[#172033]">
                                Última importación
                            </h3>

                            <p
                                class="mt-3 break-words text-xs font-semibold leading-5 text-slate-700"
                                :title="lastImport.nombre_original"
                            >
                                {{ lastImport.nombre_original }}
                            </p>

                            <p
                                class="mt-1 text-[11px] leading-5 text-slate-500"
                            >
                                {{ formatDateTime(lastImport.created_at) }}
                            </p>
                        </div>

                        <AppBadge
                            class="shrink-0"
                            :tone="statusTone(lastImport.estado)"
                        >
                            {{ formatStatus(lastImport.estado) }}
                        </AppBadge>
                    </div>
                </div>

                <!-- Resumen reutilizable -->
                <div class="p-5">
                    <ImportSummary
                        :summary="lastImport"
                    />

                    <div
                        class="mt-4 flex flex-wrap items-center gap-2"
                    >
                        <AppButton
                            :href="`/expertis/importaciones/${lastImport.id}`"
                            variant="secondary"
                            size="sm"
                        >
                            Ver detalle
                        </AppButton>

                        <a
                            v-if="Number(lastImport.filas_error ?? 0) > 0"
                            :href="`/expertis/importaciones/${lastImport.id}/errores`"
                            class="rounded-lg px-3 py-2 text-xs font-semibold text-[#F04438] transition hover:bg-red-50"
                        >
                            Descargar errores
                        </a>
                    </div>
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

        <LoadingOverlay
            :show="processing"
            :message="`Procesando ${title} Expertis…`"
        />
    </div>
</template>