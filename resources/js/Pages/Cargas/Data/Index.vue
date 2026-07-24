<script setup>
import { Head, useForm } from '@inertiajs/vue3';
import { ArrowDownTrayIcon, CircleStackIcon, DocumentArrowUpIcon } from '@heroicons/vue/24/outline';
import AppButton from '../../../Components/AppButton.vue';
import Alert from '../../../Components/Alert.vue';
import FileDropzone from '../../../Components/FileDropzone.vue';
import PageHeader from '../../../Components/PageHeader.vue';
import AppLayout from '../../../Layouts/AppLayout.vue';

const xlsx = useForm({ archivo: null });
const csv = useForm({ csv: null });

function uploadXlsx() {
    xlsx.post('/cargas/data/upload', { forceFormData: true, onSuccess: () => xlsx.reset() });
}

function uploadCsv() {
    csv.post('/cargas/data/import-csv', { forceFormData: true, onSuccess: () => csv.reset() });
}
</script>

<template>
    <Head title="Carga de cartera" />
    <AppLayout title="Carga de cartera" subtitle="Importación por lotes de la tabla DATA." :breadcrumbs="['Operativo', 'Cargas', 'Cartera']">
        <PageHeader title="Cartera / Data" description="Los encabezados se normalizan y CODIGO + DNI son obligatorios. Los registros se actualizan mediante upsert.">
            <template #actions><AppButton href="/cargas/data/templateCsv" variant="secondary"><ArrowDownTrayIcon class="h-4 w-4" />Descargar plantilla</AppButton></template>
        </PageHeader>
        <Alert tone="info" class="mb-5">XLSX admite hasta 100 MB y CSV hasta 200 MB. Ambos procesos mantienen lectura e inserción por lotes.</Alert>
        <div class="grid gap-5 lg:grid-cols-2">
            <form class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm" @submit.prevent="uploadXlsx">
                <div class="mb-4 flex items-center gap-3"><DocumentArrowUpIcon class="h-6 w-6 text-[#155EEF]" /><div><h2 class="font-bold">Archivo XLSX</h2><p class="text-xs text-slate-500">Libro Excel con la hoja de cartera.</p></div></div>
                <FileDropzone v-model="xlsx.archivo" accept=".xlsx" label="Arrastra o selecciona el XLSX" :max-mb="100" :error="xlsx.errors.archivo" />
                <AppButton type="submit" class="mt-4 w-full" :loading="xlsx.processing" :disabled="!xlsx.archivo"><CircleStackIcon class="h-4 w-4" />Importar XLSX</AppButton>
            </form>
            <form class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm" @submit.prevent="uploadCsv">
                <div class="mb-4 flex items-center gap-3"><DocumentArrowUpIcon class="h-6 w-6 text-[#155EEF]" /><div><h2 class="font-bold">Archivo CSV</h2><p class="text-xs text-slate-500">Recomendado para volúmenes muy grandes.</p></div></div>
                <FileDropzone v-model="csv.csv" accept=".csv,text/csv" label="Arrastra o selecciona el CSV" :max-mb="200" :error="csv.errors.csv" />
                <AppButton type="submit" class="mt-4 w-full" :loading="csv.processing" :disabled="!csv.csv"><CircleStackIcon class="h-4 w-4" />Importar CSV</AppButton>
            </form>
        </div>
    </AppLayout>
</template>
