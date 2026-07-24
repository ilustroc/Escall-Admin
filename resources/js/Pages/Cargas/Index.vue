<script setup>
import { Head } from '@inertiajs/vue3';
import { ArrowRightIcon, BanknotesIcon, CircleStackIcon, CloudArrowDownIcon } from '@heroicons/vue/24/outline';
import PageHeader from '../../Components/PageHeader.vue';
import AppLayout from '../../Layouts/AppLayout.vue';

defineProps({ tab: { type: String, default: 'gestiones' } });

const modules = [
    { title: 'Gestiones por SP', description: 'Previsualiza y sincroniza rangos desde la conexión remota.', href: '/cargas/sp', icon: CloudArrowDownIcon },
    { title: 'Cartera / Data', description: 'Importa archivos XLSX o CSV en lotes y descarga la plantilla.', href: '/cargas/data', icon: CircleStackIcon },
    { title: 'Pagos legacy', description: 'Busca una cuenta, toma el snapshot y registra el pago.', href: '/cargas/pagos', icon: BanknotesIcon },
];
</script>

<template>
    <Head title="Cargas" />
    <AppLayout title="Cargas" subtitle="Procesos de ingreso y sincronización de datos." :breadcrumbs="['Operativo', 'Cargas']">
        <PageHeader title="Selecciona un proceso" description="Cada carga conserva sus validaciones, streaming y reglas actuales." />
        <div class="grid gap-5 md:grid-cols-3">
            <a v-for="item in modules" :key="item.title" :href="item.href" class="group rounded-2xl border border-slate-200 bg-white p-6 shadow-sm transition hover:-translate-y-1 hover:border-blue-200 hover:shadow-card">
                <component :is="item.icon" class="h-8 w-8 text-[#155EEF]" />
                <h2 class="mt-5 font-bold">{{ item.title }}</h2>
                <p class="mt-2 min-h-12 text-sm leading-6 text-slate-500">{{ item.description }}</p>
                <span class="mt-5 flex items-center gap-2 text-sm font-semibold text-[#073DC7]">Abrir <ArrowRightIcon class="h-4 w-4 transition group-hover:translate-x-1" /></span>
            </a>
        </div>
    </AppLayout>
</template>
