<script setup>
import { computed, reactive, ref } from 'vue';
import { Head, router, useForm } from '@inertiajs/vue3';
import { ArrowDownTrayIcon, PencilSquareIcon, TrashIcon } from '@heroicons/vue/24/outline';
import AppButton from '../../Components/AppButton.vue';
import AppInput from '../../Components/AppInput.vue';
import AppPagination from '../../Components/AppPagination.vue';
import AppSelect from '../../Components/AppSelect.vue';
import ConfirmDialog from '../../Components/ConfirmDialog.vue';
import CurrencyInput from '../../Components/CurrencyInput.vue';
import DataTable from '../../Components/DataTable.vue';
import DateRangeFilter from '../../Components/DateRangeFilter.vue';
import Drawer from '../../Components/Drawer.vue';
import FilterPanel from '../../Components/FilterPanel.vue';
import PageHeader from '../../Components/PageHeader.vue';
import SearchInput from '../../Components/SearchInput.vue';
import Tabs from '../../Components/Tabs.vue';
import TextArea from '../../Components/TextArea.vue';
import { useConfirm } from '../../Composables/useConfirm';
import { useDebouncedSearch } from '../../Composables/useDebouncedSearch';
import { useFilters } from '../../Composables/useFilters';
import AppLayout from '../../Layouts/AppLayout.vue';

const props = defineProps({
    tab: { type: String, required: true },
    filtros: { type: Object, required: true },
    registros: { type: Object, required: true },
    opciones: { type: Object, required: true },
});
const { filters, apply, clean, reset } = useFilters(props.filtros, '/listas');
const editorOpen = ref(false);
const edit = useForm({});
const { confirmation, ask, confirm, cancel } = useConfirm();
const tabs = [
    { label: 'Pagos', value: 'pagos', href: '/listas?tab=pagos' },
    { label: 'Gestiones', value: 'gestiones', href: '/listas?tab=gestiones' },
    { label: 'Cartera / Data', value: 'data', href: '/listas?tab=data' },
];
const columns = computed(() => ({
    pagos: [
        { key: 'fecha', label: 'Fecha' }, { key: 'codigo', label: 'Código' },
        { key: 'dni', label: 'DNI' }, { key: 'nombre', label: 'Cliente' },
        { key: 'cartera', label: 'Cartera' }, { key: 'monto', label: 'Monto' },
        { key: 'asesor', label: 'Asesor' }, { key: 'operacion', label: 'Operación' },
        { key: 'actions', label: 'Acciones' },
    ],
    gestiones: [
        { key: 'fecha_gestion', label: 'Fecha' }, { key: 'dni', label: 'DNI' },
        { key: 'telefono', label: 'Teléfono' }, { key: 'status', label: 'Contacto' },
        { key: 'tipificacion', label: 'Resultado' }, { key: 'nombre', label: 'Asesor' },
        { key: 'actions', label: 'Acciones' },
    ],
    data: [
        { key: 'codigo', label: 'Código' }, { key: 'dni', label: 'DNI' },
        { key: 'titular', label: 'Titular' }, { key: 'cartera', label: 'Cartera' },
        { key: 'cosecha', label: 'Cosecha' }, { key: 'deuda_capital', label: 'Capital' },
        { key: 'producto', label: 'Producto' }, { key: 'actions', label: 'Acciones' },
    ],
}[props.tab]));
const singular = computed(() => ({ pagos: 'pago', gestiones: 'gestion', data: 'data' }[props.tab]));
const exportUrl = computed(() => `/listas/export?${new URLSearchParams(clean()).toString()}`);
const money = (value) => new Intl.NumberFormat('es-PE', { style: 'currency', currency: 'PEN' }).format(Number(value || 0));

useDebouncedSearch(() => filters.dni, () => apply(), 400);

function openEditor(row) {
    const id = props.tab === 'data' ? row.codigo : row.id;
    edit.defaults({
        id,
        type: singular.value,
        fecha: row.fecha ?? '',
        monto: row.monto ?? '',
        cosecha: row.cosecha ?? '',
        asesor: row.asesor ?? row.nombre ?? '',
        operacion: row.operacion ?? '',
        resultado: row.tipificacion ?? '',
        observacion: row.observacion ?? '',
        fecha_gestion: row.fecha_gestion ?? '',
        cartera: row.cartera ?? '',
        titular: row.titular ?? '',
        deuda_capital: row.deuda_capital ?? '',
    });
    edit.reset();
    editorOpen.value = true;
}

function save() {
    edit.put('/listas/update', { preserveScroll: true, onSuccess: () => { editorOpen.value = false; } });
}

async function remove(row) {
    if (!await ask({ title: 'Eliminar registro', message: 'Esta acción elimina el registro seleccionado y no se puede deshacer.', confirmText: 'Eliminar' })) return;
    router.delete('/listas/delete', {
        data: { id: props.tab === 'data' ? row.codigo : row.id, type: singular.value },
        preserveScroll: true,
    });
}
</script>

<template>
    <Head title="Listas" />
    <AppLayout title="Listas" subtitle="Consulta y mantenimiento de registros legacy." :breadcrumbs="['Operativo', 'Listas']">
        <PageHeader title="Listas operativas" description="Los filtros, la paginación y el ordenamiento se procesan en el servidor.">
            <template #actions><AppButton :href="exportUrl" variant="secondary"><ArrowDownTrayIcon class="h-4 w-4" />Exportar CSV</AppButton></template>
        </PageHeader>
        <Tabs :items="tabs" :active="tab" />
        <FilterPanel>
            <SearchInput v-model="filters.dni" placeholder="Buscar DNI…" />
            <template v-if="tab !== 'data'"><DateRangeFilter v-model:from="filters.fecha_ini" v-model:to="filters.fecha_fin" /></template>
            <AppSelect v-if="tab === 'pagos'" v-model="filters.asesor" label="Asesor" :options="opciones.asesores" />
            <AppSelect v-if="tab === 'pagos' || tab === 'data'" v-model="filters.cosecha" label="Cosecha" :options="opciones.cosechas" />
            <AppSelect v-if="tab === 'data'" v-model="filters.cartera" label="Cartera" :options="opciones.carteras" />
            <AppSelect v-if="tab === 'gestiones'" v-model="filters.resultado" label="Resultado" :options="opciones.resultados" />
            <AppSelect v-model="filters.per_page" label="Filas" :options="[25, 50, 100, 250]" />
            <template #actions>
                <AppButton variant="ghost" @click="reset({ tab, per_page: 25, direction: 'desc' })">Limpiar</AppButton>
                <AppButton @click="apply()">Aplicar filtros</AppButton>
            </template>
        </FilterPanel>
        <DataTable :columns="columns" :rows="registros.data" :row-key="tab === 'data' ? 'codigo' : 'id'">
            <template #cell-monto="{ value }">{{ money(value) }}</template>
            <template #cell-deuda_capital="{ value }">{{ money(value) }}</template>
            <template #cell-actions="{ row }">
                <div class="flex gap-1">
                    <button class="rounded-lg p-2 text-[#155EEF] hover:bg-blue-50" title="Editar" @click="openEditor(row)"><PencilSquareIcon class="h-4 w-4" /></button>
                    <button class="rounded-lg p-2 text-[#F04438] hover:bg-red-50" title="Eliminar" @click="remove(row)"><TrashIcon class="h-4 w-4" /></button>
                </div>
            </template>
        </DataTable>
        <AppPagination v-bind="registros" />

        <Drawer :open="editorOpen" :title="`Editar ${singular}`" @close="editorOpen = false">
            <form class="space-y-4" @submit.prevent="save">
                <template v-if="tab === 'pagos'">
                    <AppInput v-model="edit.fecha" type="date" label="Fecha" :error="edit.errors.fecha" />
                    <CurrencyInput v-model="edit.monto" :error="edit.errors.monto" />
                    <AppInput v-model="edit.cosecha" label="Cosecha" />
                    <AppInput v-model="edit.asesor" label="Asesor" />
                    <AppInput v-model="edit.operacion" label="Operación" />
                </template>
                <template v-else-if="tab === 'gestiones'">
                    <AppInput v-model="edit.fecha_gestion" type="date" label="Fecha de gestión" />
                    <AppInput v-model="edit.asesor" label="Asesor" />
                    <AppInput v-model="edit.resultado" label="Resultado" />
                    <TextArea v-model="edit.observacion" label="Observación" />
                </template>
                <template v-else>
                    <AppInput v-model="edit.titular" label="Titular" />
                    <AppInput v-model="edit.cartera" label="Cartera" />
                    <AppInput v-model="edit.cosecha" label="Cosecha" />
                    <CurrencyInput v-model="edit.deuda_capital" label="Capital" />
                </template>
                <AppButton type="submit" class="w-full" :loading="edit.processing">Guardar cambios</AppButton>
            </form>
        </Drawer>
        <ConfirmDialog v-bind="confirmation" @confirm="confirm" @close="cancel" />
    </AppLayout>
</template>
