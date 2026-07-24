<script setup>
import AppLayout from '../../../Layouts/AppLayout.vue';
import ExpertisImportForm from '../../../Components/ExpertisImportForm.vue';

defineProps({
    ultimaImportacion: { type: Object, default: null },
    configuracion: { type: Object, required: true },
});
</script>

<template>
    <AppLayout
        title="Importar gestiones Expertis"
        subtitle="Carga acumulados o archivos diarios sin generar duplicados físicos."
        :breadcrumbs="['Operativo', 'Cargas', 'Expertis · Gestiones']"
    >
        <ExpertisImportForm
            type="gestiones"
            preview-url="/expertis/importaciones/gestiones/preview"
            store-url="/expertis/importaciones/gestiones"
            :required-columns="['DNI', 'Cartera', 'Fecha Llamada', 'Nivel 2']"
            :config="configuracion"
            :last-import="ultimaImportacion"
        />
    </AppLayout>
</template>
