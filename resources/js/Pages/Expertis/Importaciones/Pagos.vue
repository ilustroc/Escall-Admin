<script setup>
import { ref } from 'vue';
import { useForm } from '@inertiajs/vue3';
import {
    BanknotesIcon,
    DocumentArrowUpIcon,
    PencilSquareIcon,
} from '@heroicons/vue/24/outline';
import Alert from '../../../Components/Alert.vue';
import AppButton from '../../../Components/AppButton.vue';
import AppInput from '../../../Components/AppInput.vue';
import CurrencyInput from '../../../Components/CurrencyInput.vue';
import ExpertisImportForm from '../../../Components/ExpertisImportForm.vue';
import PageHeader from '../../../Components/PageHeader.vue';
import AppLayout from '../../../Layouts/AppLayout.vue';

const props = defineProps({
    ultimaImportacion: { type: Object, default: null },
    configuracion: { type: Object, required: true },
    modo: { type: String, default: 'archivo' },
    manualDefaults: { type: Object, required: true },
});

const activeMode = ref(props.modo);
const manualForm = useForm({
    fecha: props.manualDefaults.fecha,
    cuenta: '',
    monto: '',
    ejecutivo: props.manualDefaults.ejecutivo,
    tipo_acuerdo: '',
    recaudo: '',
});

function submitManual() {
    manualForm.post('/expertis/importaciones/pagos/manual', {
        preserveScroll: true,
        onSuccess: () => manualForm.reset('cuenta', 'monto', 'tipo_acuerdo', 'recaudo'),
    });
}
</script>

<template>
    <AppLayout
        title="Cargar pagos Expertis"
        subtitle="Importa un archivo completo o registra un pago individual."
        :breadcrumbs="['Operativo', 'Cargas', 'Expertis · Pagos']"
    >
        <PageHeader
            title="Pagos Expertis"
            description="Ambos métodos aplican la misma normalización de cuenta, validación de monto y deduplicación."
        />

        <div class="mb-6 grid gap-3 sm:grid-cols-2">
            <button
                type="button"
                class="flex items-start gap-3 rounded-2xl border p-4 text-left transition"
                :class="activeMode === 'archivo'
                    ? 'border-escall-500 bg-escall-50 ring-2 ring-escall-100'
                    : 'border-slate-200 bg-white hover:border-slate-300'"
                :aria-pressed="activeMode === 'archivo'"
                @click="activeMode = 'archivo'"
            >
                <span class="rounded-xl bg-white p-2 text-escall-600 shadow-sm">
                    <DocumentArrowUpIcon class="h-5 w-5" />
                </span>
                <span>
                    <strong class="block text-sm text-[#172033]">Importar archivo XLSX</strong>
                    <span class="mt-1 block text-xs leading-5 text-slate-500">
                        Para cargas masivas con FECHA, CUENTA y MONTO.
                    </span>
                </span>
            </button>
            <button
                type="button"
                class="flex items-start gap-3 rounded-2xl border p-4 text-left transition"
                :class="activeMode === 'manual'
                    ? 'border-escall-500 bg-escall-50 ring-2 ring-escall-100'
                    : 'border-slate-200 bg-white hover:border-slate-300'"
                :aria-pressed="activeMode === 'manual'"
                @click="activeMode = 'manual'"
            >
                <span class="rounded-xl bg-white p-2 text-escall-600 shadow-sm">
                    <PencilSquareIcon class="h-5 w-5" />
                </span>
                <span>
                    <strong class="block text-sm text-[#172033]">Registrar pago manual</strong>
                    <span class="mt-1 block text-xs leading-5 text-slate-500">
                        Para ingresar un pago individual sin preparar un Excel.
                    </span>
                </span>
            </button>
        </div>

        <ExpertisImportForm
            v-if="activeMode === 'archivo'"
            type="pagos"
            preview-url="/expertis/importaciones/pagos/preview"
            store-url="/expertis/importaciones/pagos"
            template-url="/expertis/importaciones/pagos/plantilla"
            :required-columns="['FECHA', 'CUENTA', 'MONTO']"
            :config="configuracion"
            :last-import="ultimaImportacion"
        />

        <form
            v-else
            class="grid gap-6 xl:grid-cols-[minmax(0,1fr)_360px]"
            @submit.prevent="submitManual"
        >
            <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-card md:p-6">
                <div class="mb-5">
                    <h2 class="text-base font-bold text-[#172033]">Datos del pago</h2>
                    <p class="mt-1 text-xs leading-5 text-slate-500">
                        La cuenta conecta el pago con las gestiones del mismo DNI y cartera.
                    </p>
                </div>

                <div class="grid gap-4 sm:grid-cols-2">
                    <AppInput
                        v-model="manualForm.fecha"
                        type="date"
                        label="Fecha de pago"
                        :error="manualForm.errors.fecha"
                    />
                    <AppInput
                        v-model="manualForm.cuenta"
                        label="Cuenta"
                        placeholder="47752785-QAPAQ"
                        hint="Formato: DNI-CARTERA"
                        :error="manualForm.errors.cuenta"
                    />
                    <CurrencyInput
                        v-model="manualForm.monto"
                        label="Monto pagado"
                        :error="manualForm.errors.monto"
                    />
                    <AppInput
                        v-model="manualForm.ejecutivo"
                        label="Ejecutivo"
                        :error="manualForm.errors.ejecutivo"
                    />
                    <AppInput
                        v-model="manualForm.tipo_acuerdo"
                        label="Tipo de acuerdo"
                        placeholder="Cuota, cancelación…"
                        :error="manualForm.errors.tipo_acuerdo"
                    />
                    <AppInput
                        v-model="manualForm.recaudo"
                        label="Recaudo"
                        placeholder="Banco, aplicativo…"
                        :error="manualForm.errors.recaudo"
                    />
                </div>

                <div class="mt-6 flex justify-end">
                    <AppButton type="submit" :loading="manualForm.processing">
                        <BanknotesIcon class="h-4 w-4" />
                        Guardar pago
                    </AppButton>
                </div>
            </section>

            <aside class="space-y-5">
                <Alert tone="info">
                    Si ya existe un pago con la misma cuenta, fecha, monto, ejecutivo, acuerdo y
                    recaudo, el sistema lo marcará como duplicado sin insertarlo nuevamente.
                </Alert>
                <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-card">
                    <h3 class="text-sm font-bold text-[#172033]">Trazabilidad</h3>
                    <p class="mt-2 text-xs leading-5 text-slate-500">
                        El registro queda asociado al usuario actual y aparece en el historial como
                        “Registro manual”, sin generar un archivo ficticio.
                    </p>
                </section>
            </aside>
        </form>
    </AppLayout>
</template>
