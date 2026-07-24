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
        title="Importar pagos Expertis"
        subtitle="Valida el archivo, conserva la cuenta visible y descarta pagos idénticos."
        :breadcrumbs="['Operativo', 'Cargas', 'Expertis · Pagos']"
    >
        <ExpertisImportForm
            type="pagos"
            preview-url="/expertis/importaciones/pagos/preview"
            store-url="/expertis/importaciones/pagos"
            :required-columns="['FECHA', 'CUENTA', 'MONTO']"
            :config="configuracion"
            :last-import="ultimaImportacion"
        />
    </AppLayout>
</template>
