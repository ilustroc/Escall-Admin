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
        subtitle="Importa la asignación mensual y conserva cada periodo por separado."
        :breadcrumbs="['Operativo', 'Cargas', 'Expertis · Asignaciones']"
    >
        <PageHeader
            title="Asignaciones Expertis"
            description="El periodo identifica el mes de asignación. El código se genera con DNI y tipo de cartera únicamente para Expertis."
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
