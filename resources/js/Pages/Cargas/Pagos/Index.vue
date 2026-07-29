<script setup>
import { ref } from 'vue';
import axios from 'axios';
import { Head, useForm } from '@inertiajs/vue3';
import { BanknotesIcon, MagnifyingGlassIcon } from '@heroicons/vue/24/outline';
import Alert from '../../../Components/Alert.vue';
import AppButton from '../../../Components/AppButton.vue';
import AppInput from '../../../Components/AppInput.vue';
import AppSelect from '../../../Components/AppSelect.vue';
import CurrencyInput from '../../../Components/CurrencyInput.vue';
import PageHeader from '../../../Components/PageHeader.vue';
import { useDebouncedSearch } from '../../../Composables/useDebouncedSearch';
import AppLayout from '../../../Layouts/AppLayout.vue';

const props = defineProps({ defaults: { type: Object, required: true } });
const lookupState = ref('idle');
const lookupMessage = ref('');
const form = useForm({
    codigo: '',
    asesor: '',
    fecha: props.defaults.fecha,
    monto: '',
    operacion: '',
    dni: '',
    nombre: '',
    cartera: '',
    entidad: '',
    cosecha: '',
    departamento: '',
    rango: '',
    capital: '',
    producto: '',
});

async function lookup() {
    if (!form.codigo.trim()) {
        lookupState.value = 'idle';
        return;
    }
    lookupState.value = 'loading';
    lookupMessage.value = '';
    try {
        const { data } = await axios.get('/cargas/pagos/lookup', { params: { codigo: form.codigo } });
        Object.assign(form, data.data);
        lookupState.value = 'found';
    } catch (error) {
        lookupState.value = 'missing';
        lookupMessage.value = error.response?.data?.message ?? 'No se pudo realizar la búsqueda.';
    }
}

useDebouncedSearch(() => form.codigo, lookup, 400);

function submit() {
    form.post('/cargas/pagos', {
        onSuccess: () => form.reset(),
    });
}
</script>

<template>
    <Head title="Registrar pago" />
    <AppLayout title="Pagos legacy" subtitle="Registro con snapshot de la cuenta." :breadcrumbs="['Operativo', 'Cargas', 'Pagos']">
        <PageHeader title="Registrar pago" description="Busca el código en DATA o en las tablas de asignación disponibles; los datos encontrados se guardan como snapshot." />
        <form class="grid gap-5 xl:grid-cols-[1.1fr_.9fr]" @submit.prevent="submit">
            <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                <h2 class="mb-4 font-bold">Datos del pago</h2>
                <div class="grid gap-4 sm:grid-cols-2">
                    <div class="sm:col-span-2">
                        <AppInput v-model="form.codigo" label="Código" placeholder="Escribe para buscar…" :error="form.errors.codigo" />
                        <p v-if="lookupState === 'loading'" class="mt-1 flex items-center gap-1 text-xs text-[#155EEF]"><MagnifyingGlassIcon class="h-3.5 w-3.5 animate-pulse" />Buscando cuenta…</p>
                    </div>
                    <AppInput v-model="form.asesor" label="Asesor" :error="form.errors.asesor" />
                    <AppInput v-model="form.fecha" type="date" label="Fecha" :error="form.errors.fecha" />
                    <CurrencyInput v-model="form.monto" label="Monto pagado" :error="form.errors.monto" />
                    <AppSelect v-model="form.operacion" label="Operación" :error="form.errors.operacion" :options="['CANCELACION', 'PAGO PARCIAL', 'CUOTA']" placeholder="Selecciona" />
                </div>
                <Alert v-if="lookupState === 'missing'" tone="warning" class="mt-4">{{ lookupMessage }}</Alert>
                <AppButton type="submit" class="mt-5 w-full" :loading="form.processing"><BanknotesIcon class="h-4 w-4" />Guardar pago</AppButton>
            </section>
            <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                <h2 class="mb-4 font-bold">Registro de la cuenta</h2>
                <div class="grid gap-4 sm:grid-cols-2">
                    <AppInput v-model="form.dni" label="DNI" />
                    <AppInput v-model="form.nombre" label="Cliente" />
                    <AppInput v-model="form.cartera" label="Cartera" />
                    <AppInput v-model="form.entidad" label="Entidad" />
                    <AppInput v-model="form.cosecha" label="Cosecha" />
                    <AppInput v-model="form.departamento" label="Departamento" />
                    <AppInput v-model="form.producto" label="Producto" />
                    <AppInput v-model="form.capital" type="number" label="Capital" />
                    <AppInput v-model="form.rango" label="Rango" disabled class="sm:col-span-2" />
                </div>
            </section>
        </form>
    </AppLayout>
</template>
