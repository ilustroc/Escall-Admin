<script setup>
import { computed } from 'vue';
import { Head } from '@inertiajs/vue3';
import {
    ArrowUpTrayIcon,
    BanknotesIcon,
    BoltIcon,
    ClipboardDocumentListIcon,
} from '@heroicons/vue/24/outline';
import AppButton from '../../Components/AppButton.vue';
import EmptyState from '../../Components/EmptyState.vue';
import MetricCard from '../../Components/MetricCard.vue';
import PageHeader from '../../Components/PageHeader.vue';
import AppLayout from '../../Layouts/AppLayout.vue';

const props = defineProps({
    legacy: { type: Object, required: true },
    expertis: { type: Object, required: true },
});

const metrics = computed(() => props.expertis.metricas ?? {});
const money = (value) => new Intl.NumberFormat('es-PE', { style: 'currency', currency: 'PEN' }).format(Number(value || 0));
</script>

<template>
    <Head title="Dashboard" />
    <AppLayout title="Dashboard" subtitle="Indicadores operativos de la plataforma unificada." :breadcrumbs="['Principal']">
        <PageHeader title="Resumen del día" description="Legacy y Expertis comparten ahora la misma experiencia administrativa.">
            <template #actions>
                <AppButton href="/cargas"><ArrowUpTrayIcon class="h-4 w-4" />Nueva carga</AppButton>
                <AppButton href="/cargas/pagos" variant="secondary"><BanknotesIcon class="h-4 w-4" />Registrar pago</AppButton>
            </template>
        </PageHeader>

        <section class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
            <MetricCard label="Gestiones legacy hoy" :value="legacy.gestiones_hoy" hint="Registros del día" tone="info"><template #icon><ClipboardDocumentListIcon class="h-4 w-4" /></template></MetricCard>
            <MetricCard label="Gestiones legacy del mes" :value="legacy.gestiones_mes" hint="Acumulado mensual" tone="neutral" />
            <MetricCard label="Pagos legacy hoy" :value="money(legacy.pagos_hoy)" hint="Recaudo diario" tone="success"><template #icon><BanknotesIcon class="h-4 w-4" /></template></MetricCard>
            <MetricCard label="Pagos legacy del mes" :value="money(legacy.pagos_mes)" hint="Recaudo mensual" tone="success" />
        </section>

        <section class="mt-7">
            <div class="mb-4 flex items-center gap-2"><BoltIcon class="h-5 w-5 text-[#155EEF]" /><h2 class="text-lg font-bold">Expertis</h2></div>
            <div v-if="expertis.disponible" class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                <MetricCard label="Gestiones Expertis hoy" :value="metrics.gestiones_hoy || 0" tone="info" />
                <MetricCard label="Gestiones Expertis del mes" :value="metrics.gestiones_mes || 0" />
                <MetricCard label="Pagos Expertis hoy" :value="metrics.pagos_hoy || 0" tone="success" />
                <MetricCard label="Recaudo Expertis del mes" :value="money(metrics.recaudo_mes)" tone="success" />
            </div>
            <EmptyState v-else title="Expertis aún no está disponible" description="Las tablas Expertis se habilitarán cuando se complete su despliegue." />
        </section>

        <section class="mt-7 grid gap-4 md:grid-cols-3">
            <a href="/cargas/sp" class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm transition hover:-translate-y-0.5 hover:border-blue-200 hover:shadow-card"><h3 class="font-bold">Importar gestiones</h3><p class="mt-1 text-sm text-slate-500">Previsualiza y sincroniza un rango desde el SP remoto.</p></a>
            <a href="/listas" class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm transition hover:-translate-y-0.5 hover:border-blue-200 hover:shadow-card"><h3 class="font-bold">Administrar listas</h3><p class="mt-1 text-sm text-slate-500">Filtra, edita, elimina o exporta registros.</p></a>
            <a href="/reportes" class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm transition hover:-translate-y-0.5 hover:border-blue-200 hover:shadow-card"><h3 class="font-bold">Generar reportes</h3><p class="mt-1 text-sm text-slate-500">Impulse, KP Invest, carteras y Expertis.</p></a>
        </section>
    </AppLayout>
</template>
