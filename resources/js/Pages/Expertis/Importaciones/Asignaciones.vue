<script setup>
import ExpertisImportForm from '../../../Components/ExpertisImportForm.vue';
import PageHeader from '../../../Components/PageHeader.vue';
import AppLayout from '../../../Layouts/AppLayout.vue';

defineProps({
    ultimaImportacion: { type: Object, default: null },
    configuracion: { type: Object, required: true },
});
</script>

<template>
    <AppLayout
        title="Cargar asignaciones Expertis"
        subtitle="Carga el XLSX y deja que la validación continúe en segundo plano."
        :breadcrumbs="['Operativo', 'Cargas', 'Expertis · Asignaciones']"
    >
        <PageHeader
            title="Asignaciones Expertis"
            description="El XLSX se lee una sola vez. Después de validar periodo, empresa y filas, podrás confirmar la importación desde el detalle."
        />

        <ExpertisImportForm
            type="asignaciones"
            preview-url="/expertis/importaciones/asignaciones/preview"
            store-url="/expertis/importaciones/asignaciones"
            template-url="/expertis/importaciones/asignaciones/plantilla"
            :required-columns="[
                'PERIODO',
                'EMPRESA',
                'DNI',
                'TITULAR',
                'TIPO DE CARTERA',
                'DEUDA TOTAL',
                'DEUDA CAPITAL',
            ]"
            :config="configuracion"
            :last-import="ultimaImportacion"
        />
    </AppLayout>
</template>
