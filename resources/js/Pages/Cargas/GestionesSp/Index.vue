<script setup>
import { reactive } from 'vue';
import { Head, router, useForm } from '@inertiajs/vue3';
import { ArrowDownTrayIcon, CloudArrowDownIcon, EyeIcon } from '@heroicons/vue/24/outline';
import AppButton from '../../../Components/AppButton.vue';
import Alert from '../../../Components/Alert.vue';
import ConfirmDialog from '../../../Components/ConfirmDialog.vue';
import DataTable from '../../../Components/DataTable.vue';
import DateRangeFilter from '../../../Components/DateRangeFilter.vue';
import FilterPanel from '../../../Components/FilterPanel.vue';
import PageHeader from '../../../Components/PageHeader.vue';
import { useConfirm } from '../../../Composables/useConfirm';
import AppLayout from '../../../Layouts/AppLayout.vue';

const props = defineProps({
    filtros: { type: Object, required: true },
    preview: { type: Object, default: null },
});
const filters = reactive({ ...props.filtros });
const importForm = useForm({ fi: filters.fi, ff: filters.ff });
const { confirmation, ask, confirm, cancel } = useConfirm();
const columns = [
    { key: 'fecha_gestion', label: 'Fecha' },
    { key: 'dni', label: 'DNI' },
    { key: 'telefono', label: 'Teléfono' },
    { key: 'status', label: 'Contacto' },
    { key: 'tipificacion', label: 'Tipificación' },
    { key: 'nombre', label: 'Asesor' },
];

function previewRange() {
    router.get('/cargas/sp/preview', filters, { preserveState: true, preserveScroll: true });
}

async function importRange() {
    if (!await ask({
        title: 'Reemplazar rango de gestiones',
        message: `Se eliminarán y volverán a insertar las gestiones locales entre ${filters.fi} y ${filters.ff}. Si el SP falla, la transacción local se revierte.`,
        confirmText: 'Importar rango',
        tone: 'danger',
    })) return;
    importForm.fi = filters.fi;
    importForm.ff = filters.ff;
    importForm.post('/cargas/sp/import');
}
</script>

<template>
    <Head title="Gestiones por SP" />
    <AppLayout title="Gestiones por SP" subtitle="Sincronización transaccional desde sp_gestiones." :breadcrumbs="['Operativo', 'Cargas', 'Gestiones por SP']">
        <PageHeader title="Importar gestiones" description="La vista previa lee el SP en modo unbuffered. La importación reemplaza explícitamente el rango dentro de una transacción.">
            <template #actions><AppButton href="/cargas/sp/templateCsv" variant="secondary"><ArrowDownTrayIcon class="h-4 w-4" />Plantilla</AppButton></template>
        </PageHeader>
        <FilterPanel>
            <DateRangeFilter v-model:from="filters.fi" v-model:to="filters.ff" :errors="importForm.errors" />
            <template #actions>
                <AppButton variant="secondary" @click="previewRange"><EyeIcon class="h-4 w-4" />Vista previa</AppButton>
                <AppButton :loading="importForm.processing" @click="importRange"><CloudArrowDownIcon class="h-4 w-4" />Importar</AppButton>
            </template>
        </FilterPanel>
        <Alert v-if="preview" tone="info" class="mb-4"><strong>{{ preview.total }}</strong> filas leídas. Se muestran como máximo {{ preview.limit }}.</Alert>
        <DataTable v-if="preview" :columns="columns" :rows="preview.rows" row-key="dni" />
        <ConfirmDialog v-bind="confirmation" :loading="importForm.processing" @confirm="confirm" @close="cancel" />
    </AppLayout>
</template>
