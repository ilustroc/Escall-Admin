<script setup>
import { ref } from 'vue';
import { router, useForm } from '@inertiajs/vue3';
import AppBadge from '../../../Components/AppBadge.vue';
import AppButton from '../../../Components/AppButton.vue';
import AppInput from '../../../Components/AppInput.vue';
import AppModal from '../../../Components/AppModal.vue';
import AppPagination from '../../../Components/AppPagination.vue';
import AppTable from '../../../Components/AppTable.vue';
import ConfirmDialog from '../../../Components/ConfirmDialog.vue';
import AppLayout from '../../../Layouts/AppLayout.vue';

defineProps({
    tipificaciones: { type: Object, required: true },
});

const modalOpen = ref(false);
const editingId = ref(null);
const deleting = ref(null);
const form = useForm({
    tipificacion: '',
    gestion: '',
    peso: '',
    observacion: '',
    activo: true,
});

const columns = [
    { key: 'tipificacion', label: 'Tipificación' },
    { key: 'gestion', label: 'Gestión' },
    { key: 'peso', label: 'Peso' },
    { key: 'observacion', label: 'Observación' },
    { key: 'activo', label: 'Estado' },
    { key: 'gestiones_count', label: 'Gestiones' },
    { key: 'acciones', label: '' },
];

function openCreate() {
    editingId.value = null;
    form.reset();
    form.clearErrors();
    form.activo = true;
    modalOpen.value = true;
}

function openEdit(row) {
    editingId.value = row.id;
    form.clearErrors();
    Object.assign(form, {
        tipificacion: row.tipificacion,
        gestion: row.gestion ?? '',
        peso: row.peso,
        observacion: row.observacion ?? '',
        activo: Boolean(row.activo),
    });
    modalOpen.value = true;
}

function submit() {
    const options = {
        preserveScroll: true,
        onSuccess: () => { modalOpen.value = false; },
    };
    if (editingId.value) {
        form.put(`/expertis/tipificaciones/${editingId.value}`, options);
    } else {
        form.post('/expertis/tipificaciones', options);
    }
}

function confirmDelete() {
    if (!deleting.value) return;
    router.delete(`/expertis/tipificaciones/${deleting.value.id}`, {
        preserveScroll: true,
        onFinish: () => { deleting.value = null; },
    });
}
</script>

<template>
    <AppLayout
        title="Tipificaciones Expertis"
        subtitle="El peso determina la gestión única. Los cambios se reflejan inmediatamente en consultas y reportes."
        :breadcrumbs="['Operativo', 'Expertis', 'Tipificaciones']"
    >
        <div class="mb-5 flex justify-end">
            <AppButton @click="openCreate">Nueva tipificación</AppButton>
        </div>

        <AppTable :columns="columns" :rows="tipificaciones.data">
            <template #cell-tipificacion="{ value }"><AppBadge tone="info">{{ value }}</AppBadge></template>
            <template #cell-peso="{ value }"><strong class="text-[#172033]">{{ value }}</strong></template>
            <template #cell-observacion="{ value }"><span class="block max-w-sm truncate whitespace-normal">{{ value ?? '—' }}</span></template>
            <template #cell-activo="{ value }"><AppBadge :tone="value ? 'success' : 'neutral'">{{ value ? 'Activa' : 'Inactiva' }}</AppBadge></template>
            <template #cell-acciones="{ row }">
                <div class="flex gap-1">
                    <AppButton variant="ghost" size="sm" @click="openEdit(row)">Editar</AppButton>
                    <AppButton variant="ghost" size="sm" @click="deleting = row">{{ row.gestiones_count ? 'Desactivar' : 'Eliminar' }}</AppButton>
                </div>
            </template>
        </AppTable>
        <AppPagination v-bind="tipificaciones" />

        <AppModal :open="modalOpen" :title="editingId ? 'Editar tipificación' : 'Nueva tipificación'" max-width="max-w-lg" @close="modalOpen = false">
            <form class="space-y-4" @submit.prevent="submit">
                <div class="grid gap-4 sm:grid-cols-2">
                    <AppInput v-model="form.tipificacion" label="Tipificación" :error="form.errors.tipificacion" />
                    <AppInput v-model="form.gestion" label="Gestión" :error="form.errors.gestion" placeholder="CEF, CNE o NOC" />
                    <AppInput v-model="form.peso" type="number" min="1" max="65535" label="Peso" :error="form.errors.peso" />
                    <label class="flex items-center gap-3 self-end rounded-xl border border-slate-200 px-4 py-3">
                        <input v-model="form.activo" type="checkbox" class="h-4 w-4 rounded border-slate-300 text-escall-600 focus:ring-escall-500">
                        <span class="text-sm font-semibold text-slate-700">Tipificación activa</span>
                    </label>
                </div>
                <label class="block">
                    <span class="mb-1.5 block text-xs font-semibold text-slate-700">Observación</span>
                    <textarea v-model="form.observacion" rows="4" class="w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm focus:border-escall-500 focus:ring-2 focus:ring-escall-100" />
                    <span v-if="form.errors.observacion" class="mt-1 block text-xs text-[#F04438]">{{ form.errors.observacion }}</span>
                </label>
                <div class="flex justify-end gap-2 border-t border-slate-100 pt-4">
                    <AppButton variant="secondary" @click="modalOpen = false">Cancelar</AppButton>
                    <AppButton type="submit" :loading="form.processing">Guardar</AppButton>
                </div>
            </form>
        </AppModal>

        <ConfirmDialog
            :open="Boolean(deleting)"
            :title="deleting?.gestiones_count ? 'Desactivar tipificación' : 'Eliminar tipificación'"
            :message="deleting?.gestiones_count
                ? 'Tiene gestiones relacionadas y se conservará para el historial, pero quedará inactiva.'
                : 'La tipificación no tiene relaciones y puede eliminarse de forma segura.'"
            :confirm-text="deleting?.gestiones_count ? 'Desactivar' : 'Eliminar'"
            tone="danger"
            @close="deleting = null"
            @confirm="confirmDelete"
        />
    </AppLayout>
</template>
