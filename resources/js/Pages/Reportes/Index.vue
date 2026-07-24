<script setup>
import { Head } from '@inertiajs/vue3';
import { ArrowRightIcon, BuildingLibraryIcon, ChartBarIcon, PresentationChartLineIcon, Squares2X2Icon } from '@heroicons/vue/24/outline';
import PageHeader from '../../Components/PageHeader.vue';
import AppLayout from '../../Layouts/AppLayout.vue';

const reports = [
    { title: 'Impulse', description: 'Gestiones de cartera propia por rango y equipo.', href: '/reportes/impulse', icon: PresentationChartLineIcon },
    { title: 'KP Invest', description: 'Gestiones tipificadas y promesas de KP Invest.', href: '/reportes/kp-invest', icon: ChartBarIcon },
    { title: 'Carteras', description: 'Entregables de DATA, TEC Center y asignación.', href: '/reportes/carteras', icon: BuildingLibraryIcon },
    { title: 'Expertis', description: 'Indicadores y exportaciones de gestiones y pagos Expertis.', href: '/expertis/reportes', icon: Squares2X2Icon },
];
</script>

<template>
    <Head title="Reportes" />
    <AppLayout title="Reportes" subtitle="Centro unificado de exportaciones e indicadores." :breadcrumbs="['Analítica', 'Reportes']">
        <PageHeader title="Selecciona un reporte" description="Los filtros usados en pantalla se reutilizan en cada exportación." />
        <div class="grid gap-5 md:grid-cols-2 xl:grid-cols-4">
            <a v-for="report in reports" :key="report.title" :href="report.href" class="group rounded-2xl border border-slate-200 bg-white p-6 shadow-sm transition hover:-translate-y-1 hover:border-blue-200 hover:shadow-card">
                <component :is="report.icon" class="h-8 w-8 text-[#155EEF]" />
                <h2 class="mt-5 font-bold">{{ report.title }}</h2>
                <p class="mt-2 min-h-16 text-sm leading-6 text-slate-500">{{ report.description }}</p>
                <span class="mt-4 flex items-center gap-2 text-sm font-semibold text-[#073DC7]">Abrir reporte <ArrowRightIcon class="h-4 w-4 transition group-hover:translate-x-1" /></span>
            </a>
        </div>
    </AppLayout>
</template>
